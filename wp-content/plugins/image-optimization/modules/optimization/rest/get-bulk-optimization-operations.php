<?php

namespace ImageOptimizer\Modules\Optimization\Rest;

use ImageOptimizer\Modules\Optimization\Classes\{
	Bulk_Optimization,
	Route_Base,
};

use Throwable;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Get_Bulk_Optimization_Operations extends Route_Base {
	protected string $path = 'bulk/operations';

	public function get_name(): string {
		return 'bulk-optimization-operations';
	}

	public function get_methods(): array {
		return [ 'GET' ];
	}

	public function GET() {
		try {
			$operations = Bulk_Optimization::get_processed_images();

			return $this->respond_success_json([
				'operations' => $operations,
			]);
		} catch ( Throwable $t ) {
			return $this->respond_error_json([
				'message' => $t->getMessage(),
				'code' => 'internal_server_error',
			]);
		}
	}
}
