<?php

namespace ImageOptimizer\Modules\Stats\Classes;

use ImageOptimizer\Classes\Image\{
	Image,
	Image_Meta,
	Image_Query_Builder,
	WP_Image_Meta,
	Exceptions\Invalid_Image_Exception
};
use ImageOptimizer\Classes\Logger;
use ImageOptimizer\Modules\Settings\Classes\Settings;

class Optimization_Stats {
	const PAGING_SIZE = 25000;

	/**
	 * Returns image stats.
	 * If the library is too big, it queries images in chunks.
	 *
	 * @return array{total_image_count: int, optimized_image_count: int, current_image_size: int, initial_image_size: int}
	 */
	public static function get_image_stats( ?int $image_id = null ): array {
		$output = self::get_image_stats_chunk( 1, $image_id );
		$pages_count = $output['pages'];

		if ( $pages_count > 1 ) {
			// $i initially is 2 bc we already got the first page, so we don't have to query it again
			for ( $i = 2; $i <= $pages_count; $i ++ ) {
				$chunk = self::get_image_stats_chunk( $i );

				foreach ( array_keys( $chunk ) as $key ) {
					if ( isset( $output[ $key ] ) ) {
						$output[ $key ] += $chunk[ $key ];
						continue;
					}

					$output[ $key ] = $chunk[ $key ];
				}
			}
		}

		unset( $output['pages'] );

		return $output;
	}

	/**
	 * @return array{pages: int, total_image_count: int, optimized_image_count: int, current_image_size: int, initial_image_size: int}
	 */
	public static function get_image_stats_chunk( int $paged = 1, ?int $image_id = null ): array {
		$output = [
			'pages' => 1,
			'total_image_count' => 0,
			'optimized_image_count' => 0,
			'current_image_size' => 0,
			'initial_image_size' => 0,
		];

		$query = ( new Image_Query_Builder() )
			->set_paging_size( self::PAGING_SIZE )
			->set_current_page( $paged );

		if ( $image_id ) {
			$query->set_image_ids( [ $image_id ] );
		}

		$query = $query->execute();

		$output['pages'] = $query->max_num_pages;

		foreach ( $query->posts as $attachment_id ) {
			try {
				$wp_meta = new WP_Image_Meta( $attachment_id );
			} catch ( Invalid_Image_Exception $ii ) {
				Logger::log( Logger::LEVEL_ERROR, $ii->getMessage() );

				continue;
			}

			$meta = new Image_Meta( $attachment_id );
			$image_sizes = $wp_meta->get_size_keys();

			$current_sizes = self::filter_only_enabled_sizes( $image_sizes );
			$optimized_sizes = self::filter_only_enabled_sizes( $meta->get_optimized_sizes() );

			$output['total_image_count'] += count( $current_sizes );
			$output['optimized_image_count'] += count( $optimized_sizes );

			foreach ( $image_sizes as $image_size ) {
				$output['current_image_size'] += $wp_meta->get_file_size( $image_size );
				$output['initial_image_size'] += $meta->get_original_file_size( $image_size ) ?? $wp_meta->get_file_size( $image_size );
			}
		}

		return $output;
	}

	private static function filter_only_enabled_sizes( array $size_keys ): array {
		$enabled_sizes = Settings::get( Settings::CUSTOM_SIZES_OPTION_NAME );

		if ( 'all' === $enabled_sizes ) {
			return $size_keys;
		}

		return array_filter($size_keys, function( string $size ) use ( $enabled_sizes ) {
			if ( Image::SIZE_FULL === $size ) {
				return true;
			}

			if ( in_array( $size, $enabled_sizes, true ) ) {
				return true;
			}

			return false;
		});
	}
}
