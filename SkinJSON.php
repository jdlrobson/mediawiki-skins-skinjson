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
		$this->getRequest()->response()->header( 'Content-Type: application/json' );
		// result may be an error
		echo $this->generateHTML();
	}
}
