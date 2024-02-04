<?php
namespace ImageOptimizer\Modules\Core;

use ImageOptimizer\Modules\Oauth\{
	Classes\Data,
	Components\Connect,
	Rest\Activate,
	Rest\ConnectInit,
	Rest\Deactivate,
	Rest\Disconnect,
	Rest\GetSubscriptions,
};
use ImageOptimizer\Modules\Optimization\{
	Rest\Cancel_Bulk_Optimization,
	Rest\Optimize_Bulk,
};
use ImageOptimizer\Modules\Backups\Rest\{
	Restore_All,
	Remove_Backups,
};
use ImageOptimizer\Classes\{
	Module_Base,
	Utils,
};

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Module extends Module_Base {
	public function get_name(): string {
		return 'core';
	}

	public static function component_list() : array {
		return [
			'Pointers',
			'Conflicts',
		];
	}

	private function render_top_bar() {
		?>
		<div id="image-optimizer-top-bar"></div>
		<?php
	}

	private function render_app() {
		?>
		<div class="clear"></div>
		<div id="image-optimizer-app"></div>
		<?php
	}

	public function add_plugin_links( $links, $plugin_file_name ): array {
		if ( false === strpos( $plugin_file_name, '/image-optimization.php' ) ) {
			return (array) $links;
		}

		$custom_links = [
			'settings' => sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'admin.php?page=' . \ImageOptimizer\Modules\Settings\Module::SETTING_BASE_SLUG ),
				esc_html__( 'Settings', 'image-optimizer' )
			),
		];

		if ( ! Connect::is_connected() ) {
			$custom_links['connect'] = sprintf(
				'<a href="%s" style="color: #524CFF; font-weight: 700;">%s</a>',
				admin_url( 'admin.php?page=' . \ImageOptimizer\Modules\Settings\Module::SETTING_BASE_SLUG . '&action=connect' ),
				esc_html__( 'Connect', 'image-optimizer' )
			);
		}

		if ( Connect::is_connected() && ! Connect::is_activated() ) {
			$custom_links['activate'] = sprintf(
				'<a href="%s" style="color: #524CFF; font-weight: 700;">%s</a>',
				admin_url( 'admin.php?page=' . \ImageOptimizer\Modules\Settings\Module::SETTING_BASE_SLUG ),
				esc_html__( 'Activate', 'image-optimizer' )
			);
		}

		if ( Connect::is_connected() && Connect::is_activated() ) {
			$plan_data = Connect::check_connect_status();
			$usage_percentage = 0;

			if ( ! empty( $plan_data ) ) {
				$usage_percentage = $plan_data->used_quota / $plan_data->quota * 100;
			}

			if ( $usage_percentage >= 80 ) {
				$custom_links['upgrade'] = sprintf(
					'<a href="%s" style="color: #524CFF; font-weight: 700;">%s</a>',
					'https://go.elementor.com/io-panel-upgrade/',
					esc_html__( 'Upgrade', 'image-optimizer' )
				);
			}
		}

		return array_merge( $custom_links, $links );
	}

	/**
	 * Enqueue fonts
	 */
	public function enqueue_fonts() {
		$screen = get_current_screen();

		if ( ! $this->should_render() && ( 'attachment' !== $screen->id ) ) {
			return;
		}

		wp_enqueue_style(
			'image-optimizer-admin-fonts',
			'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap',
			[],
			IMAGE_OPTIMIZER_VERSION
		);
	}

	/**
	 * Enqueue styles and scripts
	 */
	private function enqueue_scripts() {
		$asset_file = include IMAGE_OPTIMIZER_ASSETS_PATH . 'build/admin.asset.php';

		foreach ( $asset_file['dependencies'] as $style ) {
			wp_enqueue_style( $style );
		}

		wp_enqueue_script(
			'image-optimizer-admin',
			$this->get_js_assets_url( 'admin' ),
			array_merge( $asset_file['dependencies'], [ 'wp-util' ] ),
			IMAGE_OPTIMIZER_VERSION,
			true
		);

		wp_localize_script(
			'image-optimizer-admin',
			'imageOptimizerAppSettings',
			[
				'siteUrl' => wp_parse_url( get_site_url(), PHP_URL_HOST ),
				'thumbnailSizes' => wp_get_registered_image_subsizes(),
				'isDevelopment' => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
			]
		);

		$connect_data = Data::get_connect_data();

		wp_localize_script(
			'image-optimizer-admin',
			'imageOptimizerUserData',
			[
				'isConnected' => Connect::is_connected(),
				'isActivated' => Connect::is_activated(),
				'planData' => Connect::is_activated() ? Connect::check_connect_status() : null,
				'licenseKey' => Connect::is_activated() ? Data::get_activation_state() : null,
				'imagesLeft' => Connect::is_activated() ? Data::images_left() : null,
				'isOwner' => Connect::is_connected() ? Data::user_is_subscription_owner() : null,
				'subscriptionEmail' => $connect_data['user']['email'] ?? null,

				'wpRestNonce' => wp_create_nonce( 'wp_rest' ),
				'disconnect' => wp_create_nonce( 'wp_rest' ),
				'authInitNonce' => wp_create_nonce( ConnectInit::NONCE_NAME ),
				'authDisconnectNonce' => wp_create_nonce( Disconnect::NONCE_NAME ),
				'authDeactivateNonce' => wp_create_nonce( Deactivate::NONCE_NAME ),
				'authGetSubscriptionsNonce' => wp_create_nonce( GetSubscriptions::NONCE_NAME ),
				'authActivateNonce' => wp_create_nonce( Activate::NONCE_NAME ),
				'removeBackupsNonce' => wp_create_nonce( Remove_Backups::NONCE_NAME ),
				'restoreAllImagesNonce' => wp_create_nonce( Restore_All::NONCE_NAME ),
				'optimizeBulkNonce' => wp_create_nonce( Optimize_Bulk::NONCE_NAME ),
				'cancelBulkOptimizationNonce' => wp_create_nonce( Cancel_Bulk_Optimization::NONCE_NAME ),
			]
		);

		wp_set_script_translations( 'image-optimizer-admin', 'image-optimizer' );
	}

	private function should_render(): bool {
		return ( Utils::is_media_page() || Utils::is_plugin_page() ) && Utils::user_is_admin();
	}

	/**
	 * Module constructor.
	 */
	public function __construct() {
		$this->register_components();

		add_filter( 'plugin_action_links', [ $this, 'add_plugin_links' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_fonts' ] );

		add_action('current_screen', function () {
			if ( ! $this->should_render() ) {
				return;
			}

			if ( Utils::is_media_page() ) {
				add_action('in_admin_header', function () {
					$this->render_top_bar();
				});

				add_action('all_admin_notices', function () {
					$this->render_app();
				});
			}

			if ( Utils::is_plugin_page() ) {
				add_action('in_admin_header', function () {
					$this->render_top_bar();
				});
			}

			add_action('admin_enqueue_scripts', function () {
				$this->enqueue_scripts();
			});
		});
	}
}
