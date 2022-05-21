<?php

namespace SkinJSON;
use SkinException;
use ExtensionRegistry;
use MediaWiki\Rest;
use MediaWiki\MediaWikiServices;
use SkinJSON;

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

	private function getMeta( $factory, $skinkey ) {
		$tags = [];
		try {
			$skin = $factory->makeSkin( $skinkey );
			$options = $skin->getOptions() ?? [];
		} catch ( SkinException $e ) {
			$tags[] = 'load-error';
			$options = [];
		}
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
		$meta = SkinJSON::getRenderSkinMeta( $skin );
		$meta['tag'] = array_merge( $tags,  $meta['tag'] );
		return $meta;
	}

	/**
	 * @return Response
	 * @throws LocalizedHttpException
	 */
	public function execute() {
		$services = MediaWikiServices::getInstance();
		$skins = $this->getSkinsJSON( $services );
		$response = $this->getResponseFactory()->createJson( [ 'skins' => $skins ] );
		$response->setHeader( 'Access-Control-Allow-Origin', '*' );
		$response->setHeader( 'Cache-Control', 'no-store, max-age=0' );
		return $response;
	}

	private function getSkinsJSON( $services ) {
		$reg = ExtensionRegistry::getInstance();
		$installed = $reg->getAllThings();
		$skins = [];
		$services = MediaWikiServices::getInstance();
		$factory = $services->getSkinFactory();
		$wanCache = $services->getMainWANObjectCache();
		$key = $wanCache->makeKey( 'skinjson-rest-handler-json' );
		$result = $wanCache->get( $key );
		if ( $result ) {
			return json_decode( $result, true );
		}

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
					$json = json_decode( file_get_contents( $info['path'] ), true );
					$compatibility = $json['requires']['MediaWiki'] ?? null;
					$info['compatible'] = $compatibility;
					$info['hooks'] = array_keys( $json['Hooks'] ?? [] );
					$info += $this->getMeta( $factory, $skinkey );
					$skins[ $skinkey ] = $info;
				}
			}
		}
		$wanCache->set( $key, json_encode( $skins ), 60 * 10 );
		return $skins;
	}
}
