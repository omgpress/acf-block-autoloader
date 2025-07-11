<?php
namespace OmgAcfBlockAutoloader;

use DirectoryIterator;
use Exception;
use OmgCore\OmgFeature;
use OmgCore\Fs;
use OmgCore\Helper\DashToCamelcase;

defined( 'ABSPATH' ) || exit;

class AcfBlockAutoloader extends OmgFeature {
	use DashToCamelcase;

	protected string $key;
	protected Fs $fs;
	protected string $template_dir;
	protected string $field_namespace;
	protected array $block_fields = array();

	protected array $config_props = array(
		'template_dir'    => 'acf-block',
		'field_namespace' => 'AcfBlock',
	);

	public function __construct( string $key, Fs $fs, $config = array() ) {
		parent::__construct( $config );

		$this->key = $key;
		$this->fs  = $fs;
	}

	/**
	 * @throws Exception
	 */
	public function register_block_type( string $post_type, string $title, string $this_namespace ): self {
		if (
			! function_exists( 'acf_register_block_type' ) ||
			! function_exists( 'register_block_type' )
		) {
			return $this;
		}

		$this->register_block_category( $post_type, $title );
		$this->register_blocks( $post_type, $this_namespace );

		return $this;
	}

	protected function register_block_category( string $post_type, string $title ): void {
		add_filter(
			'block_categories_all',
			function ( array $categories ) use ( $post_type, $title ): array {
				return array_merge(
					array(
						array(
							'slug'  => $this->key . "_$post_type",
							'title' => $title,
						),
					),
					$categories
				);
			}
		);
	}

	/**
	 * @throws Exception
	 */
	protected function register_blocks( string $post_type, string $field_namespace ): void {
		add_action(
			'acf/init',
			function () use ( $post_type, $field_namespace ): void {
				$path = "$this->template_dir/$post_type";
				$dir  = $this->fs->get_path( $path );

				if ( ! file_exists( $dir ) ) {
					throw new Exception( esc_html( "The \"$dir\" directory does not exist" ) );
				}

				$dir_iterator = new DirectoryIterator( $dir );

				foreach ( $dir_iterator as $file ) {
					if ( $file->isDot() ) {
						continue;
					}

					$slug         = str_replace( '.php', '', $file->getFilename() );
					$file_headers = get_file_data(
						"$dir/$slug.php",
						array(
							'name'        => 'Block Name',
							'description' => 'Block Description',
							'icon'        => 'Block Icon',
							'keywords'    => 'Block Keywords',
						)
					);

					if ( empty( $file_headers['name'] ) ) {
						throw new Exception( esc_html( "The \"$slug.php\" file does not have a block name" ) );
					}

					$file_headers['description'] = $file_headers['description'] ?? '';
					$file_headers['icon']        = $file_headers['icon'] ?? '';
					$file_headers['keywords']    = $file_headers['icon'] ?? '';

					$this->register_block_fields( $slug, $field_namespace );
					acf_register_block_type(
						array(
							'name'            => $slug,
							'title'           => __( $file_headers['name'], 'starter-theme' ), // phpcs:ignore
							'description'     => __( $file_headers['description'], 'starter-theme' ), // phpcs:ignore
							'category'        => $this->key . "_$post_type",
							'icon'            => $file_headers['icon'],
							'keywords'        => explode( ', ', $file_headers['keywords'] ),
							'post_types'      => array( $post_type ),
							'mode'            => 'edit',
							'supports'        => array(
								'mode'  => false,
								'align' => false,
							),
							'render_callback' => function ( array &$args ) use ( $path, $slug ) { // phpcs:ignore
								require_once $this->fs->get_path( "$path/$slug.php" );
							},
						)
					);
				}
			}
		);
	}

	/**
	 * @throws Exception
	 */
	protected function register_block_fields( string $slug, string $this_namespace ): void {
		$classname = $this_namespace . '\\' . $this->field_namespace . '\\' . $this->dash_to_camelcase( $slug, true );

		if ( ! class_exists( $classname ) ) {
			throw new Exception( esc_html( "The \"$classname\" block fields class does not exist" ) );
		}

		if ( ! is_subclass_of( $classname, 'OmgAcfBlockAutoloader\AcfBlockField' ) ) {
			throw new Exception( esc_html( "The \"$classname\" class must extend OmgAcfBlockAutoloader\AcfBlockField" ) );
		}

		$this->block_fields[] = new $classname();
	}
}
