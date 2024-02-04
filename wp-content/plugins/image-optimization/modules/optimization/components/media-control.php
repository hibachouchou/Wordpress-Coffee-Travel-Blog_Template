<?php

namespace ImageOptimizer\Modules\Optimization\Components;

use ImageOptimizer\Classes\Image\{
	Exceptions\Invalid_Image_Exception,
	Image,
	Image_Meta,
	Image_Optimization_Error_Type,
	Image_Status
};
use ImageOptimizer\Modules\Oauth\Components\{
	Connect,
	Exceptions\Auth_Exception,
};
use ImageOptimizer\Modules\Optimization\{
	Classes\Exceptions\Image_Validation_Error,
	Classes\Validate_Image,
	Module,
};
use ImageOptimizer\Modules\Oauth\Classes\Data;
use ImageOptimizer\Modules\Settings\Classes\Settings;
use ImageOptimizer\Modules\Stats\Classes\Optimization_Stats;
use Throwable;

/**
 * The class is responsible for rendering optimization control in all views.
 */
class Media_Control {
	const COLUMN_ID = 'image_optimization';
	const META_BOX_ID = 'image_optimization_meta_box';
	const DETAILS_MODAL_FIELD_ID = 'image_optimization_modal';

	/**
	 * Adds a new optimization column to the library list view.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function add_optimization_column( array $columns ): array {
		if ( empty( $columns ) ) {
			return $columns;
		}

		$columns[ self::COLUMN_ID ] = esc_html__( 'Image optimization', 'image-optimizer' );

		return $columns;
	}

	/**
	 * Renders optimization controls in the library list view.
	 *
	 * @param string $column_name
	 * @param int $attachment_id
	 * @return void
	 */
	public function render_optimization_column( string $column_name, int $attachment_id ) {
		if ( self::COLUMN_ID !== $column_name ) {
			return;
		}

		$this->render_optimization_control( 'list-view', $attachment_id );
	}

	/**
	 * Adds optimization control as media meta box.
	 *
	 * @return void
	 */
	public function add_optimization_meta_box() {
		add_meta_box(
			self::META_BOX_ID,
			__( 'Image optimization', 'image-optimizer' ),
			function( $post ) {
				$this->render_optimization_control( 'meta-box', $post->ID );
			},
			'attachment',
			'side',
		);
	}

	/**
	 * Adds optimization control to media modals.
	 *
	 * @param array $form_fields
	 * @param \WP_Post $post
	 *
	 * @return array
	 */
	public function add_media_modal_details( array $form_fields, \WP_Post $post ): array {
		$form_fields[ self::DETAILS_MODAL_FIELD_ID ] = [
			'label' => '',
			'input' => 'hidden',
			'value' => $this->get_optimization_control_html( 'details-view', $post->ID ),
			'required' => false,
		];

		return $form_fields;
	}

	/**
	 * Returns optimization control as an HTML string.
	 *
	 * @param string $context Control placement context. One of ['list-view', 'meta-box', 'details-view'].
	 * @param int $image_id Attachment id.
	 * @return false|string
	 */
	public function get_optimization_control_html( string $context, int $image_id ) {
		ob_start();

		$this->render_optimization_control( $context, $image_id );

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Renders the optimization control for the current image in the current context.
	 *
	 * @param string $context Control placement context. One of ['list-view', 'meta-box', 'details-view'].
	 * @param int $image_id Attachment id.
	 * @return void Renders the control.
	 */
	public function render_optimization_control( string $context, int $image_id ) {
		$global_context = [
			'image_id' => $image_id,
			'can_be_restored' => false,
		];

		try {
			if ( ! Connect::is_connected() || ! Connect::is_activated() ) {
				throw new Auth_Exception( 'You have to activate your license to use Image Optimizer' );
			}

			Validate_Image::is_valid( $image_id );

			$image = new Image( $image_id );
			$meta = new Image_Meta( $image->get_id() );

			$global_context['can_be_restored'] = Image_Status::OPTIMIZED === $meta->get_status()
					? $image->can_be_restored()
					: Settings::get( Settings::BACKUP_ORIGINAL_IMAGES_OPTION_NAME );

			switch ( $meta->get_status() ) {
				case Image_Status::OPTIMIZATION_IN_PROGRESS:
					Module::load_template( $context, 'loading', array_merge(
						$global_context, [
							'action' => 'optimize',
						]
					) );

					break;

				case Image_Status::REOPTIMIZING_IN_PROGRESS:
					Module::load_template( $context, 'loading', array_merge(
						$global_context, [
							'action' => 'reoptimize',
						]
					) );

					break;

				case Image_Status::RESTORING_IN_PROGRESS:
					Module::load_template( $context, 'loading', array_merge(
						$global_context, [
							'action' => 'restore',
						]
					) );

					break;

				case Image_Status::OPTIMIZED:
					$stats = Optimization_Stats::get_image_stats( $image_id );
					$saved = [
						'relative' => round( $stats['current_image_size'] / $stats['initial_image_size'] * 100 ),
						'absolute' => $stats['initial_image_size'] - $stats['current_image_size'],
					];

					Module::load_template( $context, 'optimized', array_merge(
						$global_context, [
							'sizes_optimized_count' => $stats['optimized_image_count'],
							'saved' => $saved,
						]
					) );

					break;

				case Image_Status::OPTIMIZATION_FAILED:
					$error_type = $meta->get_error_type() ?? Image_Optimization_Error_Type::GENERIC;
					$error_message = Image_Optimization_Error_Type::QUOTA_EXCEEDED === $error_type
						? esc_html__( 'Plan quota reached', 'image-optimizer' )
						: esc_html__( 'Optimization error', 'image-optimizer' );
					$images_left = Data::images_left();

					Module::load_template( $context, 'error', array_merge(
						$global_context, [
							'message' => $error_message,
							'optimization_error_type' => $error_type,
							'images_left' => $images_left,
							'allow_retry' => true,
							'action' => 'optimize',
						]
					) );

					break;

				case Image_Status::REOPTIMIZING_FAILED:
					$error_type = $meta->get_error_type() ?? Image_Optimization_Error_Type::GENERIC;
					$error_message = Image_Optimization_Error_Type::QUOTA_EXCEEDED === $error_type
						? esc_html__( 'Plan quota reached', 'image-optimizer' )
						: esc_html__( 'Image reoptimizing failed', 'image-optimizer' );
					$images_left = Data::images_left();

					Module::load_template( $context, 'error', array_merge(
						$global_context, [
							'message' => $error_message,
							'optimization_error_type' => $error_type,
							'images_left' => $images_left,
							'allow_retry' => true,
							'action' => 'reoptimize',
						]
					) );

					break;

				case Image_Status::RESTORING_FAILED:
					Module::load_template( $context, 'error', array_merge(
						$global_context, [
							'message' => esc_html__( 'Image restoring error', 'image-optimizer' ),
							'allow_retry' => true,
							'action' => 'restore',
						]
					) );

					break;

				default:
					Module::load_template( $context, 'not-optimized', $global_context );
			}
		} catch ( Invalid_Image_Exception | Image_Validation_Error $iie ) {
			Module::load_template( $context, 'error', array_merge(
				$global_context, [
					'action' => 'error',
					'message' => $iie->getMessage(),
					'allow_retry' => false,
				]
			) );
		} catch ( Auth_Exception $ae ) {
			Module::load_template( $context, 'error', array_merge(
				$global_context, [
					'action' => 'error',
					'message' => esc_html__( 'N/A', 'image-optimizer' ),
					'allow_retry' => false,
				]
			) );
		} catch ( Throwable $t ) {
			Module::load_template( $context, 'error', array_merge(
				$global_context, [
					'action' => 'error',
					'message' => esc_html__( 'Internal server error', 'image-optimizer' ),
					'allow_retry' => false,
				]
			) );
		}
	}

	public function __construct() {
		add_filter( 'manage_upload_columns', [ $this, 'add_optimization_column' ] );
		add_action( 'manage_media_custom_column', [ $this, 'render_optimization_column' ], 10, 2 );

		add_action( 'add_meta_boxes_attachment', [ $this, 'add_optimization_meta_box' ] );
		add_filter( 'attachment_fields_to_edit', [ $this, 'add_media_modal_details' ], 10, 2 );
	}
}
