<?php
class SkinJSON extends SkinMustache {
	public function generateHTML() {
		$this->setupTemplateContext();
		$out = $this->getOutput();
		$data = $this->getTemplateData();
		return json_encode( $data );
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
