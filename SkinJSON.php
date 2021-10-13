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

	/**
	 * Forwards OutputPageBeforeHTML hook modifications to the template
	 * This makes SkinJSON work with the MobileFrontend ContentProvider proxy.
	 */
	public static function onOutputPageBeforeHTML( $out, &$html ) {
	    global $wgSkinJSONDebug;
	    
	    if ($wgSkinJSONDebug) {
    		$out->addModules( [ 'skins.skinjson.debug' ] );
    		$out->addModuleStyles( [ 'skins.skinjson.debug.styles' ] );
	    }
		if ( self::isSkinJSONMode( $out->getContext()->getRequest() ) ) {
			$out->getSkin()->setTemplateVariable('html-body-content', $html);
		}
	}

	private static function isSkinJSONMode( $request ) {
		$reqSkinKey = $request->getVal( 'useskin' );
		$format = $request->getVal( 'useformat' );
		return $reqSkinKey && $format === 'json';
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
