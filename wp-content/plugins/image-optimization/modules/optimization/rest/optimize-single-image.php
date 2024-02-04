<?php

namespace ImageOptimizer\Modules\Optimization\Rest;

use ImageOptimizer\Classes\Image\Image;
use ImageOptimizer\Modules\Oauth\Components\Connect;
use ImageOptimizer\Modules\Optimization\Classes\{
	Route_Base,
	Single_Optimization,
	Validate_Image,
};
use Throwable;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Optimize_Single_Image extends Route_Base {
	const NONCE_NAME = 'image-optimizer-optimize-image';
	const IMAGE_ID_PARAM = 'imageId';

	protected string $path = 'image';

	public function get_name(): string {
		return 'optimize-single-image';
	}

	public function get_methods(): array {
		return [ 'POST' ];
	}

	public function POST( WP_REST_Request $request ) {
		$this->verify_nonce_and_capability(
			$request->get_param( self::NONCE_NAME ),
			self::NONCE_NAME
		);

		if ( ! Connect::is_activated() ) {
			return $this->respond_error_json([
				'message' => esc_html__( 'Invalid activation', 'image-optimizer' ),
				'code' => 'unauthorized',
			]);
		}

		$is_reoptimize = (bool) $request->get_param( 'reoptimize' );
		$image_id = (int) $request->get_param( self::IMAGE_ID_PARAM );

		if ( empty( $image_id ) ) {
			return $this->respond_error_json([
				'message' => esc_html__( 'Invalid image id', 'image-optimizer' ),
				'code' => 'internal_server_error',
			]);
		}

		try {
			Validate_Image::is_valid( $image_id );

			Single_Optimization::schedule_single_optimization( $image_id, $is_reoptimize );

			return $this->respond_success_json();
		} catch ( Throwable $t ) {
			return $this->respond_error_json([
				'message' => $t->getMessage(),
				'code' => 'internal_server_error',
			]);
		}
	}
}
