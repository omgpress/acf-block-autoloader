<?php
namespace OmgAcfBlockAutoloader;

use OmgCore\OmgFeature;

defined( 'ABSPATH' ) || exit;

abstract class AcfBlockField extends OmgFeature {
	public function __construct( array $config = array() ) {
		parent::__construct( $config );
		add_action( 'acf/init', $this->register() );
	}

	abstract protected function register(): callable;
}
