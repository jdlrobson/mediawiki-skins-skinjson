<?php

namespace SkinJSON;
use SkinException;
use ExtensionRegistry;
use MediaWiki\Rest;
use MediaWiki\MediaWikiServices;

/**
 * Handler class for Core REST API endpoint that handles basic search
 * http://localhost:8888/w/rest.php/v1/skins
 */
class Handler extends Rest\Handler {
	public function needsWriteAccess() {
		return false;
	}

	protected function getLastModified() {
		return date( 'Y-m-d', strtotime('-1 day') );
	}

	private function getTags( $factory, $skinkey ) {
		$tags = [];
		try {
			$skin = $factory->makeSkin( $skinkey );
			$options = $skin->getOptions() ?? [];
			ob_start();
			$html = $skin->generateHTML();
			$html .= ob_get_contents();
			ob_end_clean();
			if (
				is_a( $skin, 'SkinMustache' ) ||
				is_subclass_of( $skin, 'SkinMustache' )
			) {
				$tags[] = 'mustache';
			} elseif (
				is_a( $skin, 'SkinTemplate' ) ||
				is_subclass_of( $skin, 'SkinTemplate' )
			) {
				$tags[] = 'php';
				$bodyOnly = $options['bodyOnly'] ?? false;
				if ( !$bodyOnly ) {
					$tags[] = 'php-legacy';
				}
			}
			$responsive = $options['responsive'] ?? false;
			if ( $responsive ) {
				$tags[] = 'responsive';
			}
		} catch ( SkinException $e ) {
			$tags[] = 'load-error';
		}
		if ( strpos( $html, '<b>Deprecated</b>' ) !== false ) {
			$tags[] = 'deprecation-warnings';
		}
		return $tags;
	}

	/**
	 * @return Response
	 * @throws LocalizedHttpException
	 */
	public function execute() {
		$reg = ExtensionRegistry::getInstance();
		$skins = [];
		$installed = $reg->getAllThings();
		$factory = MediaWikiServices::getInstance()->getSkinFactory();
		foreach( $installed as $key => $info ) {
			if ( is_string( $info['author'] ) ) {
				$info['author'] = [ $info['author'] ];
			}
			if ( $info['type'] === 'skin' ) {
				// work out skin
				$skinInfo = json_decode( file_get_contents( $info['path'] ), true );
				$validSkins = $skinInfo['ValidSkinNames'];
				unset( $info['name'] );
				foreach( $validSkins as $skinkey => $validSkinInfo ) {
					$skinJSON = json_decode( file_get_contents( $info['path'] ), true );
					$compatibility = $skinJSON['requires']['MediaWiki'] ?? null;
					$info['compatible'] = $compatibility;
					$info['hooks'] = array_keys( $skinJSON['Hooks'] ?? [] );
					$info['tag'] = $this->getTags( $factory, $skinkey );
					$skins[ $skinkey ] = $info;
				}
			}
		}

		$response = $this->getResponseFactory()->createJson( [ 'skins' => $skins ] );
		$response->setHeader( 'Access-Control-Allow-Origin', '*' );
		$response->setHeader( 'Cache-Control', 'no-store, max-age=0' );
		return $response;
	}
}
