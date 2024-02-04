<?php

namespace ImageOptimizer\Modules\Optimization\Classes;

use ImageOptimizer\Classes\File_Utils;
use ImageOptimizer\Classes\Image\{
	Exceptions\Invalid_Image_Exception,
	Image,
	Image_Backup,
	Image_Meta,
	Image_Status,
	WP_Image_Meta
};
use ImageOptimizer\Classes\Utils;
use ImageOptimizer\Modules\Oauth\Classes\Exceptions\Quota_Exceeded_Error;
use ImageOptimizer\Modules\Oauth\Components\Connect;
use ImageOptimizer\Modules\Optimization\Classes\Exceptions\Image_Optimization_Error;
use ImageOptimizer\Modules\Settings\Classes\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once ABSPATH . 'wp-admin/includes/file.php';
WP_Filesystem();

/**
 * The class is responsible for the optimization logic itself. It gets an image file, runs
 * backup process if needed, sends a file to the service, stores the result and updates image meta.
 *
 * This class is used by manual, bulk and on-upload optimization flows.
 */
class Optimize_Image {
	private const IMAGE_OPTIMIZE_ENDPOINT = 'image/optimize';

	protected ?Image $image;
	protected WP_Image_Meta $wp_meta;
	protected string $initiator;
	protected ?string $bulk_token;
	private string $current_image_path;
	private string $current_image_size;
	private bool $convert_to_webp;

	/**
	 * @throws Image_Optimization_Error|Quota_Exceeded_Error
	 */
	public function optimize(): void {
		$sizes_enabled = Settings::get( Settings::CUSTOM_SIZES_OPTION_NAME );
		$sizes_exist = $this->wp_meta->get_size_keys();

		foreach ( $sizes_exist as $size_exist ) {
			// If some image sizes optimization is disabled in settings, we check if the current one is still enabled
			if (
				'all' !== $sizes_enabled &&
				Image::SIZE_FULL !== $size_exist &&
				! in_array( $size_exist, $sizes_enabled, true )
			) {
				continue;
			}

			$image_meta = new Image_Meta( $this->image->get_id() );

			// If the current size was already optimized -- ignore it.
			if ( in_array( $size_exist, $image_meta->get_optimized_sizes(), true ) ) {
				continue;
			}

			if ( ! file_exists( $this->image->get_file_path( $size_exist ) ) ) {
				throw new Image_Optimization_Error( esc_html__( 'File is missing. Verify the upload', 'image-optimizer' ) );
			}

			$this->current_image_size = $size_exist;
			$this->current_image_path = $this->image->get_file_path( $size_exist );

			$this->optimize_current_size();

			$this->current_image_size = '';
			$this->current_image_path = '';
		}

		$this->mark_as_optimized();
	}

	/**
	 * @throws Image_Optimization_Error|Quota_Exceeded_Error
	 */
	private function optimize_current_size(): void {
		$response = $this->send_file();

		if ( is_wp_error( $response ) ) {
			if ( $response->get_error_message() === 'user reached limit' ) {
				throw new Quota_Exceeded_Error( esc_html__( 'Plan quota reached', 'image-optimizer' ) );
			}

			if ( $response->get_error_message() === 'Image already optimized' ) {
				$original_size = $this->wp_meta->get_file_size( $this->current_image_size );

				$this->update_attachment_meta( $original_size );
				$this->update_attachment_post();

				return;
			}

			throw new Image_Optimization_Error( $response->get_error_message() );
		}

		$optimized_size = (int) $response->optimizedSize; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$optimized_image_binary = base64_decode( $response->image, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		$this->replace_image_file( $optimized_image_binary );
		$this->update_attachment_meta( $optimized_size );

		// This should only be updated after meta
		$this->update_attachment_post();
	}

	private function send_file() {
		$connect_status = Connect::check_connect_status();
		$headers = [
			'access_token' => $connect_status->access_token ?? '',
		];

		if ( $this->bulk_token ) {
			$headers['x-elementor-bulk-token'] = $this->bulk_token;
		}

		$optimization_options = [
			'compression_level' => Settings::get( Settings::COMPRESSION_LEVEL_OPTION_NAME ),
			'convert_to_webp' => $this->convert_to_webp,
			'strip_exif' => Settings::get( Settings::STRIP_EXIF_METADATA_OPTION_NAME ),
		];

		if ( Settings::get( Settings::RESIZE_LARGER_IMAGES_OPTION_NAME ) ) {
			$optimization_options['resize'] = Settings::get( Settings::RESIZE_LARGER_IMAGES_SIZE_OPTION_NAME );
		}

		return Utils::get_api_client()->make_request(
			'POST',
			self::IMAGE_OPTIMIZE_ENDPOINT,
			[
				'initiator' => $this->initiator,
				'image_url' => $this->image->get_url( $this->current_image_size ),
				'attachment_id' => $this->image->get_id(),
				'attachment_parent_id' => Image::SIZE_FULL === $this->current_image_size ? 0 : $this->image->get_id(),
				'image_optimization_settings' => base64_encode( wp_json_encode( $optimization_options ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			],
			$headers,
			$this->current_image_path,
			'image'
		);
	}

	private function replace_image_file( string $file_data ): void {
		global $wp_filesystem;

		$path = $this->current_image_path;

		// If we have backups disabled, we want to make sure we can download and save new file before we
		// remove an existing one.
		$tmp_path = File_Utils::replace_extension( $path, 'tmp' );

		$wp_filesystem->put_contents( $tmp_path, $file_data );

		// The original file doesn't exist after any of these operations.
		if ( Settings::get( Settings::BACKUP_ORIGINAL_IMAGES_OPTION_NAME ) ) {
			Image_Backup::create( $this->image->get_id(), $this->current_image_size, $this->current_image_path );
		} else {
			$wp_filesystem->delete( $path, false, 'f' );
		}

		if ( $this->convert_to_webp ) {
			$path = File_Utils::replace_extension( $tmp_path, 'webp' );
		}

		$wp_filesystem->move( $tmp_path, $path, true );

		if ( Image::SIZE_FULL === $this->current_image_size ) {
			// Drop WP caches
			update_attached_file( $this->image->get_id(), $path );
		}

		// Updating to the correct value
		$this->current_image_path = $path;
	}

	/**
	 * Updates attachment records in the `wp_posts` table.
	 *
	 * @return void
	 */
	private function update_attachment_post() {
		$update_query = [];

		if ( $this->convert_to_webp ) {
			$attachment_object = $this->image->get_attachment_object();

			$update_query['guid'] = File_Utils::replace_extension( $attachment_object->guid, 'webp' );
			$update_query['post_mime_type'] = 'image/webp';
		}

		$update_query['post_modified'] = current_time( 'mysql' );
		$update_query['post_modified_gmt'] = current_time( 'mysql', true );

		$this->image->update_attachment( $update_query );
	}

	/**
	 * Updates attachment records in the `wp_postmeta` table.
	 *
	 * @param int $optimized_size
	 *
	 * @return void
	 */
	private function update_attachment_meta( int $optimized_size ) {
		$meta = new Image_Meta( $this->image->get_id() );

		list($width, $height) = getimagesize( $this->current_image_path );

		$meta
			->set_compression_level( Settings::get( Settings::COMPRESSION_LEVEL_OPTION_NAME ) )
			->add_optimized_size( $this->current_image_size )
			->add_original_data( $this->current_image_size, $this->wp_meta->get_size_data( $this->current_image_size ) );

		$this->wp_meta
			->set_width( $this->current_image_size, $width )
			->set_height( $this->current_image_size, $height )
			->set_file_size( $this->current_image_size, $optimized_size );

		if ( $this->convert_to_webp ) {
			$this->wp_meta
				->set_file_path( $this->current_image_size, $this->current_image_path )
				->set_mime_type( $this->current_image_size, 'image/webp' );
		}

		$meta->save();
		$this->wp_meta->save();
	}

	/**
	 * Changes image status after all image sizes were optimized.
	 *
	 * @return void
	 */
	private function mark_as_optimized() {
		( new Image_Meta( $this->image->get_id() ) )
			->set_status( Image_Status::OPTIMIZED )
			->set_error_type( null )
			->save();
	}

	/**
	 * @throws Invalid_Image_Exception
	 */
	public function __construct( int $image_id, string $initiator, ?string $bulk_token = null ) {
		$this->image = new Image( $image_id );
		$this->wp_meta = new WP_Image_Meta( $image_id, $this->image );
		$this->initiator = $initiator;
		$this->bulk_token = $bulk_token;
		$this->convert_to_webp = Settings::get( Settings::CONVERT_TO_WEBP_OPTION_NAME );
	}
}
