<?php

namespace ImageOptimizer\Modules\Oauth\Rest;

use ImageOptimizer\Classes\Utils;
use ImageOptimizer\Modules\Oauth\Classes\Route_Base;
use ImageOptimizer\Modules\Oauth\Components\Connect;
use WP_REST_Request;

class GetSubscriptions extends Route_Base {
	const NONCE_NAME = 'image-optimizer-get-subscription';
	protected string $path = 'get-subscriptions';

	public function get_name(): string {
		return 'get_subscriptions';
	}

	public function get_methods(): array {
		return [ 'POST' ];
	}

	public function POST( WP_REST_Request $request ) {
		$this->verify_nonce_and_capability(
			$request->get_param( self::NONCE_NAME ),
			self::NONCE_NAME
		);

		if ( ! Connect::is_connected() ) {
			return $this->respond_error_json( [
				'message' => esc_html__( 'Please connect first', 'image-optimizer' ),
				'code' => 'forbidden',
			] );
		}

		$response = Utils::get_api_client()->make_request(
			'POST',
			'activation/get-subscriptions'
		);

		if ( is_wp_error( $response ) ) {
			return $this->respond_error_json( [
				'message' => $response->get_error_message(),
				'code' => 'internal_server_error',
			] );
		}

		if ( ! isset( $response->subscriptions ) ) {
			return $this->respond_error_json( [
				'message' => esc_html__( 'Invalid response from server', 'image-optimizer' ),
				'code' => 'internal_server_error',
			] );
		}

		return $this->respond_success_json( $response->subscriptions );
	}
}
