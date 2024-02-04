<?php

namespace ImageOptimizer\Classes\Image;

use ImageOptimizer\Classes\Async_Operation\Exceptions\Async_Operation_Exception;
use ImageOptimizer\Classes\File_Utils;
use ImageOptimizer\Classes\Image\Exceptions\{
	Image_Restoring_Exception,
	Invalid_Image_Exception,
};
use Throwable;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once ABSPATH . 'wp-admin/includes/file.php';
WP_Filesystem();

class Image_Restore {
	public static function restore_many( array $image_ids, bool $keep_image_meta = false ): void {
		foreach ( $image_ids as $image_id ) {
			try {
				self::restore( $image_id, $keep_image_meta );
			} catch ( Throwable $t ) {
				( new Image_Meta( $image_id ) )
				->set_status( Image_Status::RESTORING_FAILED )
				->save();

				continue;
			}
		}
	}

	/**
	 * @throws Invalid_Image_Exception
	 * @throws Image_Restoring_Exception
	 * @throws Async_Operation_Exception
	 */
	public static function restore( int $image_id, bool $keep_image_meta = false ): void {
		global $wp_filesystem;

		$image = new Image( $image_id );

		if ( ! $image->can_be_restored() ) {
			throw new Image_Restoring_Exception( "Image $image_id cannot be restored" );
		}

		$meta = new Image_Meta( $image_id );
		$wp_meta = new WP_Image_Meta( $image_id );

		foreach ( $meta->get_optimized_sizes() as $image_size ) {
			$backup_path = $meta->get_image_backup_path( $image_size );
			$current_path = $image->get_file_path( $image_size );

			if ( $backup_path && $current_path ) {
				$original_path = self::get_path_from_backup_path( $backup_path );

				$wp_filesystem->move( $backup_path, $original_path, true );

				if ( $original_path !== $current_path ) {
					$wp_filesystem->delete( $current_path, false, 'f' );
				}

				$wp_meta
					->set_file_path( $image_size, $original_path )
					->set_mime_type( $image_size, $meta->get_original_mime_type( $image_size ) )
					->set_width( $image_size, $meta->get_original_width( $image_size ) )
					->set_height( $image_size, $meta->get_original_height( $image_size ) )
					->set_file_size( $image_size, $meta->get_original_file_size( $image_size ) );

				if ( Image::SIZE_FULL === $image_size ) {
					self::update_image_post(
						$image,
						$original_path,
						$meta->get_original_mime_type( Image::SIZE_FULL )
					);
				}
			}
		}

		$wp_meta->save();

		if ( $keep_image_meta ) {
			$meta
				->clear_optimized_sizes()
				->clear_backups()
				->clear_original_data()
				->save();

			return;
		}

		$meta->delete();
	}

	private static function get_path_from_backup_path( string $backup_path ): string {
		$extension = File_Utils::get_extension( $backup_path );
		return str_replace( ".backup.$extension", ".$extension", $backup_path );
	}

	private static function update_image_post( Image $image, string $image_path, string $mime_type ): void {
		$post_update_query = [
			'post_modified' => current_time( 'mysql' ),
			'post_modified_gmt' => current_time( 'mysql', true ),
			'post_mime_type' => $mime_type,
			'guid' => File_Utils::get_url_from_path( $image_path ),
		];

		$image->update_attachment( $post_update_query );

		update_attached_file( $image->get_id(), $image_path );
	}
}
