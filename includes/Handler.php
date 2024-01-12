<?php

namespace SkinJSON;
use SkinException;
use ExtensionRegistry;
use MediaWiki\Rest;
use MediaWiki\MediaWikiServices;
use SkinJSON;
use Title;
use Wikimedia\ParamValidator\ParamValidator;

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

	public function getParamSettings() {
		return [
			'name' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			'title' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false
			],
			'experimental' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_REQUIRED => false,
				Handler::PARAM_SOURCE => 'query',
			]
		];
	}

	private function getMeta( $factory, $skinkey ) {
		$tags = [];
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$ignore = in_array( $skinkey, $config->get( 'SkinJSONDisabledSkins' ) );

		if ( !$ignore ) {
			try {
				$skin = $factory->makeSkin( $skinkey );
				$options = $skin->getOptions() ?? [];
			} catch ( SkinException $e ) {
				$tags[] = 'load-error';
				$options = [];
			}
		} else {
			$tags[] = 'load-error';
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
		$args = $this->getValidatedParams();
		$meta = $args['experimental'] ?
			SkinJSON::getRenderSkinMeta( $skin ) : [ 'tag' => [] ];
		$meta['tag'] = array_merge( $tags,  $meta['tag'] );
		return $meta;
	}

	private function generateSkinJSON( $name, $title ) {
		$services = MediaWikiServices::getInstance();
		$factory = $services->getSkinFactory();

		$skin = $factory->makeSkin( $name );
		$ctx = $skin->getContext();
		$out = $skin->getOutput();
		if ( !$title ) {
			$title = 'Main_Page';
		}
		$t = Title::newFromText( $title );
		$out->setTitle( $t );
		$ctx->setOutput( $out );
		$skin->setContext( $ctx );
		return $skin->getTemplateData();
	}

	/**
	 * @return Response
	 * @throws LocalizedHttpException
	 */
	public function execute() {
		$request = $this->getRequest();
		$name = $request->getPathParam( 'name' );
		$json = $name ? $this->generateSkinJSON( $name, $request->getPathParam( 'title' ) ) : $this->getResponseJSON();
		$response = $this->getResponseFactory()->createJson(
			$json
		);
		$response->setStatus( 200 );
		$response->setHeader( 'Access-Control-Allow-Origin', '*' );
		$response->setHeader( 'Cache-Control', 'no-store, max-age=0' );
		$response->setHeader( 'Content-Type', 'application/json' );
		return $response;
	}

	private function makeSkinEntry() {
		return [
			'path' => null,
			'type' => 'skin',
			'author' => [],
			'descriptionmsg' => '',
			'hooks' => [],
			'tag' => [],
			'license-name' => null,
			'namemsg' => null,
			'url' => null,
			'compatibility' => null,
		];
	}

	private function getResponseJSON() {
		$cacheFilePath = dirname( __FILE__ ) . '/cache-skins-json-response.txt';
		$cached = file_get_contents($cacheFilePath);
		$config = MediaWikiServices::getInstance()->getMainConfig();

		if ( $cached ) {
			$cached = json_decode( $cached, true );
			if ( $cached['timestamp'] === date( 'Y-m-d' ) ) {
				return $cached;
			}
		}

		// otherwise generate...
		$services = MediaWikiServices::getInstance();
		$skins = $this->getSkinsJSON( $services );
		$args = $this->getValidatedParams();
		foreach ( $config->get( 'SkinJSONEnabledSkins' ) as $key => $entry ) {
			$skins[$key] = $this->makeSkinEntry();
		}
		$json = [ 'skins' => $skins, 'timestamp' => date( 'Y-m-d' ) ];
		$encoded = json_encode( $json );
		// cache to file when running experimental
		if ( $args['experimental'] ) {
			file_put_contents( $cacheFilePath, $encoded );
		}
		return $json;
	}

	private function getSkinsJSON( $services ) {
		$reg = ExtensionRegistry::getInstance();
		$installed = $reg->getAllThings();
		$skins = [];
		$factory = $services->getSkinFactory();
		$wanCache = $services->getMainWANObjectCache();
		$key = $wanCache->makeKey( 'skinjson-rest-handler-json' );
		$result = $wanCache->get( $key );
		if ( $result ) {
			return json_decode( $result, true );
		}

		$highest = 0;
		foreach( $installed as $key => $info ) {
			if ( is_string( $info['author'] ) ) {
				$info['author'] = [ $info['author'] ];
			}
			$path = implode( '/', array_slice( explode( '/', $info['path'] ), 0, -1 ) );
			$branches = shell_exec('cd ' . $path . ' && git branch -r');
			$mwVersions = array_map( static function ( $branch ) {
					return trim( $branch );
				},
				array_values(
					array_filter( explode( "\n", $branches ), static function ( $branch ) {
						return strpos( $branch, 'origin/REL' ) !== false;
					} )
				)
			);

			if ( $info['type'] === 'skin' ) {
				// work out skin
				$skinInfo = json_decode( file_get_contents( $info['path'] ), true );
				$validSkins = $skinInfo['ValidSkinNames'];
				unset( $info['name'] );
				foreach( $validSkins as $skinkey => $validSkinInfo ) {
					$json = json_decode( file_get_contents( $info['path'] ), true );
					$compatibility = $json['requires']['MediaWiki'] ?? null;
					$info['compatibility'] = $compatibility;
					$info['hooks'] = array_keys( $json['Hooks'] ?? [] );
					$info['versions'] = $mwVersions;
					$info += $this->getMeta( $factory, $skinkey );
					if ( isset( $info['time'] ) && $info['time'] > $highest ) {
						$highest = $info['time'];
					}
					$skins[ $skinkey ] = $info;
				}
			}
		}
		$a = $highest / 3;
		$b = $a + $a;
		$c = $highest;
		foreach( $skins as $key => $data ) {
			if ( isset( $data['time'] ) ) {
				if ( $data['time'] < $a ) {
					$skins[$key]['perf'] = 'A';
				} elseif ( $data['time'] < $b ) {
					$skins[$key]['perf'] = 'B';
				} else {
					$skins[$key]['perf'] = 'C';
				}
			}
		}
		$wanCache->set( $key, json_encode( $skins ), 60 * 10 );
		return $skins;
	}
}
