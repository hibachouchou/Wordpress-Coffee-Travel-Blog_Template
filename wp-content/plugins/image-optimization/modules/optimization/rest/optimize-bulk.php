<?php

namespace ImageOptimizer\Modules\Optimization\Rest;

use ImageOptimizer\Classes\Async_Operation\Exceptions\Async_Operation_Exception;
use ImageOptimizer\Modules\Optimization\Classes\{
	Bulk_Optimization,
	Route_Base,
};
use ImageOptimizer\Classes\Image\Exceptions\Invalid_Image_Exception;
use ImageOptimizer\Modules\Oauth\Classes\Exceptions\Quota_Exceeded_Error;
use ImageOptimizer\Modules\Oauth\Components\Connect;
use Throwable;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Optimize_Bulk extends Route_Base {
	const NONCE_NAME = 'image-optimizer-optimize-bulk';

	protected string $path = 'bulk';

	public function get_name(): string {
		return 'optimize-bulk';
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

		try {
			if ( $is_reoptimize ) {
				return $this->handle_bulk_reoptimization();
			} else {
				return $this->handle_bulk_optimization();
			}
		} catch ( Throwable $t ) {
			return $this->respond_error_json([
				'message' => $t->getMessage(),
				'code' => 'internal_server_error',
			]);
		}
	}

	/**
	 * @return \WP_Error|\WP_REST_Response
	 * @throws Async_Operation_Exception|Invalid_Image_Exception|Quota_Exceeded_Error
	 */
	private function handle_bulk_optimization() {
		$is_in_progress = Bulk_Optimization::is_optimization_in_progress();

		if ( $is_in_progress ) {
			return $this->respond_error_json( [
				'message' => esc_html__( 'Bulk optimization is already in progress', 'image-optimizer' ),
				'code'    => 'forbidden',
			] );
		}

		Bulk_Optimization::find_images_and_schedule_optimization();

		return $this->respond_success_json();
	}

	/**
	 * @throws Async_Operation_Exception|Quota_Exceeded_Error
	 */
	private function handle_bulk_reoptimization() {
		$is_in_progress = Bulk_Optimization::is_reoptimization_in_progress();

		if ( $is_in_progress ) {
			return $this->respond_error_json( [
				'message' => esc_html__( 'Bulk re-optimization is already in progress', 'image-optimizer' ),
				'code'    => 'forbidden',
			] );
		}

		Bulk_Optimization::find_optimized_images_and_schedule_reoptimization();

		return $this->respond_success_json();
	}
}
