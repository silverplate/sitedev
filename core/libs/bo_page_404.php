<?php

class BoPage404 extends BoPage {
	public function __construct() {
		parent::__construct(false);
		$this->SetTemplate(TEMPLATES . 'bo_404.xsl');
	}

	public function Output() {
		header('HTTP/1.0 404 Not Found');
		parent::Output();
	}
}

?>
