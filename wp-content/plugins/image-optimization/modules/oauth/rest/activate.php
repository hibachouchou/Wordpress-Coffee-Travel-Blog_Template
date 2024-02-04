<?php

namespace ImageOptimizer\Modules\Oauth\Rest;

use ImageOptimizer\Classes\Utils;
use ImageOptimizer\Modules\Oauth\Classes\{
	Data,
	Route_Base
};
use ImageOptimizer\Modules\Oauth\Components\{
	Checkpoint,
	Connect
};
use WP_REST_Request;

class Activate extends Route_Base {
	const NONCE_NAME = 'image-optimizer-activate-subscription';
	protected string $path = 'activate';

	public function get_name(): string {
		return 'activate';
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

		if ( Connect::is_activated() ) {
			return $this->respond_error_json( [
				'message' => esc_html__( 'Already activated', 'image-optimizer' ),
				'code' => 'bad_request',
			] );
		}

		if ( ! $request->get_param( 'license_key' ) ) {
			return $this->respond_error_json( [
				'message' => esc_html__( 'Missing license key', 'image-optimizer' ),
				'code' => 'bad_request',
			] );
		}

		$key = $request->get_param( 'license_key' );

		$response = Utils::get_api_client()->make_request(
			'POST',
			'activation/activate',
			[],
			[ 'key' => $key ]
		);

		if ( is_wp_error( $response ) ) {
			return $this->respond_error_json( [
				'message' => $response->get_error_message(),
				'code' => 'internal_server_error',
			] );
		}

		if ( ! isset( $response->id ) ) {
			return $this->respond_error_json( [
				'message' => esc_html__( 'Invalid response from server', 'image-optimizer' ),
				'code' => 'internal_server_error',
			] );
		}

		Data::set_activation_state( $key );

		do_action( Checkpoint::ON_ACTIVATE, $key );
	    return rest_ensure_response( [ 'success' => true ] );
    }
}
