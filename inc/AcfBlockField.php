<?php
namespace OmgAcfHelper;

use OmgCore\OmgFeature;

defined( 'ABSPATH' ) || exit;

abstract class AcfBlockField extends OmgFeature {
	public function __construct() {
		parent::__construct();
		add_action( 'acf/init', $this->register() );
	}

	abstract protected function register(): callable;
}
