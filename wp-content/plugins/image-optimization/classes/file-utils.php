<?php

namespace ImageOptimizer\Classes;

class File_Utils {
	public static function get_extension( string $path ): string {
		return pathinfo( $path, PATHINFO_EXTENSION );
	}

	public static function get_basename( string $path ): string {
		return pathinfo( $path, PATHINFO_BASENAME );
	}

	public static function replace_extension( string $path, string $new_extension, bool $unique_filename = false ): string {
		$path = pathinfo( $path );
		$basename = sprintf( '%s.%s', $path['filename'], $new_extension );

		if ( $unique_filename ) {
			$basename = wp_unique_filename( $path['dirname'], $basename );
		}

		return sprintf( '%s/%s', $path['dirname'], $basename );
	}

	public static function get_unique_path( string $path ): string {
		$path = pathinfo( $path );
		$basename = sprintf( '%s.%s', $path['filename'], $path['extension'] );

		return sprintf( '%s/%s', $path['dirname'], wp_unique_filename( $path['dirname'], $basename ) );
	}

	public static function get_relative_upload_path( string $path ): string {
		return _wp_relative_upload_path( $path );
	}

	public static function get_url_from_path( string $full_path ): string {
		$upload_info = wp_upload_dir();
		$url_base = $upload_info['baseurl'];

		$parts = preg_split(
			'/\/wp-content\/uploads/',
			$full_path
		);

		return $url_base . $parts[1];
	}

	public static function format_file_size( int $file_size_in_bytes, $decimals = 2 ): string {
		$sizes = [
			__( '%s Bytes', 'image-optimizer' ),
			__( '%s Kb', 'image-optimizer' ),
			__( '%s Mb', 'image-optimizer' ),
			__( '%s Gb', 'image-optimizer' ),
		];

		if ( ! $file_size_in_bytes ) {
			return sprintf( $sizes[0], 0 );
		}

		$current_scale = floor( log( $file_size_in_bytes ) / log( 1024 ) );
		$formatted_value = number_format( $file_size_in_bytes / pow( 1024, $current_scale ), $decimals );

		return sprintf( $sizes[ $current_scale ], $formatted_value );
	}
}
