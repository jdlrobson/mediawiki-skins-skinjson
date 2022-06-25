<?php

use MediaWiki\MediaWikiServices;

class SkinJSON extends SkinMustache {
	private $loadedTemplateData;
	public function generateHTML() {
		$this->setupTemplateContext();
		$data = $this->getTemplateData();
		return json_encode( $data, JSON_PRETTY_PRINT );
	}

	public function setTemplateVariable( $key, $value ) {
		$this->loadedTemplateData[$key] = $value;
	}

	/**
	 * Returns template data for the skin from cached data or from core.
	 */
	function getTemplateData() {
		if ($this->loadedTemplateData) {
			return $this->loadedTemplateData;
		} else {
			return parent::getTemplateData();
		}
	}

	/**
	 * Loads template data from another skin into SkinJSON so SkinJSON functions as a proxy.
	 */
	function loadTemplateData( $data ) {
		$this->loadedTemplateData = $data;
	}

	function getUser() {
		$testUserName = $this->getConfig()->get( 'SkinJSONTestUser' );
		if ( $this->getRequest()->getBool('testuser') && $testUserName) {
			$testUser = User::newFromName( $testUserName );
			$testUser->load();
			return $testUser;
		}
		return parent::getUser();
	}

	public static function onSiteNoticeAfter( &$siteNotice, $skin ) {
		$empty = strlen( $siteNotice ) === 0;
		$config = $skin->getConfig();
		if ( $config->get( 'SkinJSONValidate' ) ) {
			$siteNotice .= self::hookTestElement( 'SiteNoticeAfter', $config, false );
			return $empty;
		}
	}

	public static function onRegistration() {
		global $wgRestAPIAdditionalRouteFiles;
		global $IP;
		$wgRestAPIAdditionalRouteFiles[] = wfRelativePath(
			__DIR__ . '/restRoutes.json', $IP
		);
	}

	public static function onOutputPageBodyAttributes( $out, $sk, &$bodyAttrs ): void {
		if ( !$out->getConfig()->get( 'SkinJSONExtensionHints' ) ) {
			$bodyAttrs['class'] .= ' skin-json-hints-hide';
		}
	}

	/**
	 * Forwards OutputPageBeforeHTML hook modifications to the template
	 * This makes SkinJSON work with the MobileFrontend ContentProvider proxy.
	 */
	public static function onOutputPageBeforeHTML( $out, &$html ) {
		$config = $out->getConfig();
		if ( $config->get( 'SkinJSONDebug' ) ) {
			$out->addModules( [ 'skins.skinjson.debug' ] );
			$out->addModuleStyles( [ 'skins.skinjson.debug.styles' ] );
		}
		if ( $config->get( 'SkinJSONValidate' ) ) {
			$out->addSubtitle(
				self::hookTestElement( 'OutputPageBeforeHTML', $config, false, 'Call addSubtitle on OutputPage' )
			);
			$out->addJsConfigVars( [
				'wgSkinJSONValidate' => [
					'wgLogos' => ResourceLoaderSkinModule::getAvailableLogos(
						$config
					),
				],
			] );
			$out->addHTML(
				implode( '', [
					'<style type="text/css">',
					'.skin-json-hints-hide .skin-json-validation-element { display: none !important; }',
					'.skin-json-validation-element { display: none; }',
					'</style>'
				] )
			);
			$out->addModules( [ 'skins.skinjson.validate' ] );
			$out->addModuleStyles( [ 'skins.skinjson.debug.styles' ] );
		}
		if ( self::isSkinJSONMode( $out->getContext()->getRequest() ) ) {
			$out->getSkin()->setTemplateVariable('html-body-content', $html);
		}
	}

	private static function hookTestData( string $hook, Config $config, $inline = true, $note = '' ) {
		$hookUrl = '//www.mediawiki.org/wiki/Manual:Hooks/' . $hook;
		if ( $config->get( 'SkinJSONValidate' ) ) {
			$link = Html::element( 'a', [
				'href' => $hookUrl,
				'title' => $note
			], $hook );
			return [
				'title' => $note,
				'class' => [
					'skin-json-hook-validation-element',
					'skin-json-hook-validation-element-' . $hook,
					'skin-json-validation-element',
					$inline ? 'skin-json-validation-element-inline' : 'skin-json-validation-element-block'
				],
				'html' => $link,
				'data-hook' => $hook,
			];
		} else {
			return [];
		}
	}

	private static function hookTestElement( string $hook, Config $config, $inline = true, $note = '' ) {
		if ( $config->get( 'SkinJSONValidate' ) ) {
			$data = self::hookTestData( $hook, $config, $inline, $note );
			$link = $data['html'];
			unset( $data['html'] );
			return Html::rawElement( 'div', $data, $link );
		} else {
			return '';
		}
	}

	public static function onSkinAfterPortlet( $skin, $name, &$html ) {
		$html .= self::hookTestElement(
			'SkinAfterPortlet',
			$skin->getConfig(),
			// only these ones are inline
			in_array( $name, [ 'navigation', 'namespaces', 'views' ] ),
			'($name === "' . $name . '")'
		);
	}

	public static function onSkinAfterContent( &$html, Skin $skin ) {
		$html .= self::hookTestElement( 'SkinAfterContent', $skin->getConfig(), false );
	}

	public static function onSkinAddFooterLinks( Skin $skin, string $key, array &$footerlinks  ) {
		if ( $key === 'places' ) {
			$footerlinks['test'] = self::hookTestElement( 'SkinAddFooterLinks', $skin->getConfig(), true, '($key === places)' );
		}
	}

	public static function onSkinTemplateNavigationUniversal( $skin, &$links ) {
		$links['user-menu']['skin-json-hook-validation-user-menu'] = [
			'class' => [
				'skin-json-validation-element',
				'skin-json-validation-element-SkinTemplateNavigationUniversal'
			],
		];
	}

	public static function onSidebarBeforeOutput( Skin $skin, &$sidebar ) {
		$sidebar['navigation']['skin-json-hook-validation-sidebar-item'] = self::hookTestData(
			'SidebarBeforeOutput',
			$skin->getConfig(),
			true,
			'(appending to $sidebar["navigation"])'
		);
	}

	private static function isSkinJSONMode( $request ) {
		$reqSkinKey = $request->getVal( 'useskin' );
		$format = $request->getVal( 'useformat' );
		return $reqSkinKey && $format === 'json';
	}

	/**
	 * Attempt a basic render of the skin and collect some meta
	 * data based on what happens.
	 *
	 * @return array of meta data reflecting the render state
	 */
	public static function getRenderSkinMeta( Skin $skin ) {
		$tags = [];
		$profileTime = -1;
		$deprecationWarnings = 0;
		ob_start();
		try {
			error_reporting( -1 );
			ini_set( 'display_errors', -1 );
			$fauxContext = $skin->getContext();
			$out = new OutputPage( new RequestContext() );
			$out->enableOOUI();
			$fauxContext->setOutput( $out );
			$fauxContext->setTitle( Title::newFromText( 'Special:BlankPage' ) );
			$skin->setContext( $fauxContext );

			$then = microtime( true );
			$html = $skin->generateHTML();
			$warnings = ob_get_contents();
			$now = microtime( true );
			$profileTime = $now - $then;
			$deprecationWarnings = substr_count( $warnings, '<b>Deprecated</b>' );
			if ( $deprecationWarnings > 0 ) {
				$tags[] = 'deprecation-warnings';
			} else {
				$tags[] = 'compatible-master';
			}

			error_reporting( 0 );
			ini_set( 'display_errors', 0);
		} catch ( Throwable $e ) {
			$tags[] = 'render-error';
		} finally {
			error_reporting( 0 );
			ini_set( 'display_errors', 0 );
		}
		ob_end_clean();

		return [
			'tag' => $tags,
			'time' => $profileTime,
			'warnings' => $deprecationWarnings,
		];
	}

	public static function onRequestContextCreateSkin( $context, &$skin ) {
		$request = $context->getRequest();
		if ( self::isSkinJSONMode( $request ) ) {
			$reqSkinKey = $request->getVal( 'useskin' );
			$services = MediaWikiServices::getInstance();
			$factory = MediaWikiServices::getInstance()->getSkinFactory();
			$reqSkin = $factory->makeSkin( $reqSkinKey );
			$skin = $factory->makeSkin( Skin::normalizeKey( 'skinjson' ) );
			// Stop the hook from running again.
			$context->setSkin( $skin );

			if ( method_exists( $reqSkin, 'getTemplateData' ) ) {
				//$data = $reqSkin->getTemplateData();
				// Wasteful, but makes sure skin gets initialized. setupTemplateContext is protected.
				$html = $reqSkin->generateHTML();
				$data = $reqSkin->getTemplateData();
				$skin->loadTemplateData( $data );
			} else {
				$skin->loadTemplateData( [
					'error' => 'Skin ' . $reqSkinKey . ' does not use SkinMustache and is not supported by SkinJSON',
				] );
			}
		}
		return false;
	}

	function outputPage() {
		$out = $this->getOutput();
		$this->initPage( $out );
		$out->addJsConfigVars( $this->getJsConfigVars() );
		$response = $this->getRequest()->response();
		$response->header( 'Content-Type: application/json' );
		$response->header( 'Cache-Control: no-cache' );
		$response->header( 'Access-Control-Allow-Methods: GET' );
		$response->header( 'Access-Control-Allow-Origin: *' );
		// result may be an error
		echo $this->generateHTML();
	}
}
