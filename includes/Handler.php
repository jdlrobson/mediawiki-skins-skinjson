<?php

namespace SkinJSON;
use ExtensionRegistry;
use MediaWiki\Rest;

/**
 * Handler class for Core REST API endpoint that handles basic search
 * http://localhost:8888/w/rest.php/v1/skins
 */
class Handler extends Rest\Handler {
	public function needsWriteAccess() {
		return false;
	}

	/**
	 * @return Response
	 * @throws LocalizedHttpException
	 */
	public function execute() {
		$reg = ExtensionRegistry::getInstance();
		$skins = [];
		$installed = $reg->getAllThings();
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
					$skins[ $skinkey ] = $info;
				}
			}
		}

		$response = $this->getResponseFactory()->createJson( [ 'skins' => $skins, 'foo' => 1 ] );
		$response->setHeader( 'Access-Control-Allow-Origin', '*' );
		$response->setHeader( 'Cache-Control', 'no-store, max-age=0' );
		return $response;
	}
}
