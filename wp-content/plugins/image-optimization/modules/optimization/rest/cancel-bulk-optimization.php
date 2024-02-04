<?php

namespace ImageOptimizer\Modules\Optimization\Rest;

use ImageOptimizer\Modules\Optimization\Classes\{
	Bulk_Optimization,
	Route_Base,
};
use ImageOptimizer\Modules\Oauth\Components\Connect;
use Throwable;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Cancel_Bulk_Optimization extends Route_Base {
	const NONCE_NAME = 'image-optimizer-cancel-bulk-optimization';

	protected string $path = 'bulk/cancel';

	public function get_name(): string {
		return 'cancel-bulk-optimization';
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

		try {
			$is_in_progress = Bulk_Optimization::is_optimization_in_progress();

			if ( ! $is_in_progress ) {
				return $this->respond_error_json([
					'message' => esc_html__( 'Bulk optimization is not in progress', 'image-optimizer' ),
					'code' => 'forbidden',
				]);
			}

			Bulk_Optimization::cancel_bulk_optimization();

			return $this->respond_success_json();
		} catch ( Throwable $t ) {
			return $this->respond_error_json([
				'message' => $t->getMessage(),
				'code' => 'internal_server_error',
			]);
		}
	}
}
