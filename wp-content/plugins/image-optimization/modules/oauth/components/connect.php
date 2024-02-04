<?php

namespace ImageOptimizer\Modules\Oauth\Components;

use ImageOptimizer\Classes\{
	Logger,
	Utils
};

use ImageOptimizer\Modules\Oauth\Classes\Data;
use ImageOptimizer\Modules\Settings\Module as Settings_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Connect
 */
class Connect {
	const API_URL = 'https://my.elementor.com/api/connect/v1';

	/**
	 * is_connected
	 * @return bool
	 */
	public static function is_connected(): bool {
		return ! empty( Data::get_connect_data()['access_token'] );
	}

	/**
	 * is_activated
	 * @return bool
	 */
	public static function is_activated(): bool {
		return ! empty( Data::get_activation_state() );
	}

	/**
	 * maybe_handle_admin_connect_page
	 * @return bool
	 */
	public static function maybe_handle_admin_connect_page(): bool {
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'nonce_actionget_token' ) ) {
			return false;
		}

		$args = [
			'page' => 'elementor-connect',
			'app' => 'library',
			'action' => 'get_token',
			'state' => Data::get_connect_state(),
		];

		foreach ( $args as $key => $value ) {
			if ( ! isset( $_GET[ $key ] ) || $_GET[ $key ] !== $value ) {
				return false;
			}
		}

		if ( ! isset( $_GET['nonce'] ) || ! isset( $_GET['code'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * handle_elementor_connect_admin
	 */
	public function handle_elementor_connect_admin(): void {
		// validate args
		if ( ! self::maybe_handle_admin_connect_page() ) {
			return;
		}

		// validate nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'nonce_actionget_token' ) ) {
			wp_die( 'Nonce verification failed', 'image-optimizer' );
		}

		$token_response = wp_remote_request( self::API_URL . '/get_token', [
			'method' => 'POST',
			'body' => [
				'app' => 'library',
				'grant_type' => 'authorization_code',
				'client_id' => Data::get_client_id(),
				'code' => sanitize_text_field( $_GET['code'] ),
			],
		] );

		if ( is_wp_error( $token_response ) ) {
			wp_die( $token_response->get_error_message(), 'image-optimizer' );
		}

		$data = json_decode( wp_remote_retrieve_body( $token_response ), true );
		Data::set_connect_data( $data );

		do_action( Checkpoint::ON_CONNECT );

		// cleanup
		Data::delete_connect_state();

		wp_redirect( add_query_arg( [
			'page' => Settings_Module::SETTING_BASE_SLUG,
			'connected' => 'true',
		], admin_url( 'admin.php' ) ) );
		die();
	}

	/**
	 * disconnect
	 */
	public static function disconnect() {
		$response = wp_remote_request( self::API_URL . '/disconnect', [
			'method' => 'POST',
			'body' => [
				'app' => 'library',
				'home_url' => trailingslashit( home_url() ),
				'client_id' => Data::get_client_id(),
				'access_token' => Data::get_access_token(),
			],
		] );

		if ( is_wp_error( $response ) ) {
			wp_die( $response->get_error_message(), 'image-optimizer' );
		}

		Data::reset();
		do_action( Checkpoint::ON_DISCONNECT );
	}

	public static function check_connect_status() {
		if ( ! self::is_connected() ) {
			Logger::log( Logger::LEVEL_INFO, 'Status check error. Reason: User is not connected' );

			return null;
		}

		$response = Utils::get_api_client()->make_request(
			'POST',
			'status/check'
		);

		if ( is_wp_error( $response ) ) {
			Logger::log(
				Logger::LEVEL_ERROR,
				'Status check error. Reason: ' . $response->get_error_message()
			);

			return null;
		}

		if ( ! isset( $response->status ) ) {
			Logger::log( Logger::LEVEL_ERROR, 'Invalid response from server' );

			return null;
		}

		return $response;
	}

	public static function get_connect_route_url( $endpoint ): string {
		return rest_url( 'image-optimizer/v1/connect/' . $endpoint );
	}

	public function __construct() {
		// handle connect if elementor is active
		add_action( 'load-elementor_page_elementor-connect', [ $this, 'handle_elementor_connect_admin' ], 9 );
		// handle connect if elementor is not active
		add_action( '_admin_menu', [ $this, 'handle_elementor_connect_admin' ] );
	}
}
