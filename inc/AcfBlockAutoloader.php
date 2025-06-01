<?php
namespace OmgAcfBlockAutoloader;

use DirectoryIterator;
use Exception;
use OmgCore\Fs;

defined( 'ABSPATH' ) || exit;

class AcfBlockAutoloader {
	use Helper\DashToCamelcase;

	protected string $key;
	protected Fs $fs;
	protected string $acf_block_dir;

	public function __construct( string $key, Fs $fs, string $acf_block_dir = 'acf-block' ) {
		$this->key           = $key;
		$this->fs            = $fs;
		$this->acf_block_dir = $acf_block_dir;
	}

	/**
	 * @throws Exception
	 */
	public function register_block_type( string $post_type, string $title, string $field_namespace ): self {
		if (
			! function_exists( 'acf_register_block_type' ) ||
			! function_exists( 'register_block_type' )
		) {
			return $this;
		}

		$this->register_block_category( $post_type, $title );
		$this->register_blocks( $post_type, $field_namespace );

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
				$path = "$this->acf_block_dir/$post_type";
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

	protected function register_block_fields( string $slug, string $field_namespace ): void {
		$classname = $field_namespace . '\\' . $this->dash_to_camelcase( $slug, true );

		if ( ! class_exists( $classname ) ) {
			return;
		}

		new $classname();
	}
}
