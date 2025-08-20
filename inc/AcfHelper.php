<?php

namespace OmgAcfHelper;

use OmgCore\Dependency;
use OmgCore\OmgFeature;

defined( 'ABSPATH' ) || exit;

class AcfHelper extends OmgFeature {
	public function __construct( Dependency $dependency ) {
		parent::__construct();

		$dependency->require_plugin(
			'acf_pro',
			'Advanced Custom Fields Pro',
			'advanced-custom-fields-pro/acf.php'
		);
	}
}
