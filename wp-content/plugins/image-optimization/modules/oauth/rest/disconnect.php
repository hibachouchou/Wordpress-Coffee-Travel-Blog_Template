<?php

namespace ImageOptimizer\Modules\Oauth\Rest;

use ImageOptimizer\Modules\Oauth\Classes\Route_Base;
use ImageOptimizer\Modules\Oauth\Components\Connect;
use WP_REST_Request;

class Disconnect extends Route_Base {
	const NONCE_NAME = 'image-optimizer-disconnect';

	protected string $path = 'disconnect';

	public function get_name(): string {
		return 'disconnect';
	}

	public function get_methods(): array {
		return [ 'POST' ];
	}

	public function POST( WP_REST_Request $request ) {
		$this->verify_nonce_and_capability(
			$request->get_param( self::NONCE_NAME ),
			self::NONCE_NAME
		);

		Connect::disconnect();

		return $this->respond_success_json();
	}
}
