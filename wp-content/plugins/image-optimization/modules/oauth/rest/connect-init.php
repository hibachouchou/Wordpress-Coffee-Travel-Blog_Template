<?php

namespace ImageOptimizer\Modules\Oauth\Rest;

use ImageOptimizer\Modules\Oauth\Classes\{
	Route_Base,
	Data
};
use ImageOptimizer\Modules\Oauth\Components\Connect;
use WP_REST_Request;

class ConnectInit extends Route_Base {
	const NONCE_NAME = 'image-optimizer-connect';
	protected string $path = 'init';

	public function get_name(): string {
		return 'connect-init';
	}

	public function get_methods(): array {
		return [ 'GET' ];
	}

	public function get( WP_REST_Request $request ) {
		$this->verify_nonce_and_capability(
			$request->get_param( self::NONCE_NAME ),
			self::NONCE_NAME
		);

		if ( Connect::is_connected() ) {
			return $this->respond_error_json( [
				'message' => esc_html__( 'You are already connected', 'image-optimizer' ),
				'code' => 'forbidden',
			] );
		}

	    $response = wp_remote_request(
		    Connect::API_URL . '/library/get_client_id',
		    [
			    'method' => 'POST',
			    'body' => [
				    'local_id' => (int) get_current_user_id(),
				    'site_key' => Data::get_site_key(),
				    'app' => 'library',
				    'home_url' => trailingslashit( home_url() ),
				    'source' => 'image-optimizer'
			    ]
		    ]
	    );

		if ( is_wp_error( $response ) ) {
			return $this->respond_error_json( [
				'message' => $response->get_error_message(),
				'code' => 'internal_server_error',
			] );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! isset( $data->client_id ) || ! isset( $data->auth_secret ) ) {
			return $this->respond_error_json( [
				'message' => esc_html__( 'Invalid response from server' ),
				'code' => 'internal_server_error',
			] );
		}

		Data::set_client_data( $data->client_id, $data->auth_secret );

		return $this->respond_success_json(add_query_arg( [
			'utm_source'      => 'image-optimizer-panel',
			'utm_campaign'    => 'image-optimizer',
			'utm_medium'      => 'wp-dash',
			'source'          => 'generic',
			'action'          => 'authorize',
			'response_type'   => 'code',
			'client_id'       => $data->client_id,
			'auth_secret'     => $data->auth_secret,
			'state'           => Data::get_connect_state( true ),
			'redirect_uri'    => rawurlencode( add_query_arg( [
				'page'   => 'elementor-connect',
				'app'    => 'library',
				'action' => 'get_token',
				'nonce'  => wp_create_nonce( 'nonce_action' . 'get_token' ),
			], admin_url( 'admin.php' ) ) ),
			'may_share_data'  => 0,
			'reconnect_nonce' => wp_create_nonce( 'nonce_action' . 'reconnect' ),
		], self::SITE_URL . 'library' ));
	}
}
