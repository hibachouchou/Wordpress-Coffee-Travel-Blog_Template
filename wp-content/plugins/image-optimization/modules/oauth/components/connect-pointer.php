<?php

namespace ImageOptimizer\Modules\Oauth\Components;

use ImageOptimizer\Modules\Core\Components\Pointers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Connect_Pointer {
	const CURRENT_POINTER_SLUG = 'image-optimizer-auth-connect';

	public function admin_print_script() {
		if ( Connect::is_connected() ) {
			return;
		}

		wp_enqueue_script( 'wp-pointer' );
		wp_enqueue_style( 'wp-pointer' );

		$pointer_content = '<h3>' . esc_html__( 'Start by connecting your license', 'image-optimizer' ) . '</h3>';
		$pointer_content .= '<p>' . esc_html__( 'You’re one click away from improving your site’s performance dramatically!', 'image-optimizer' ) . '</p>';
		?>
		<script>
			jQuery( document ).ready( function( $ ) {
				console.log( $( '.image-optimizer-stats-connect-button' ) );

				const intervalId = setInterval( () => {
					if ( ! $( '.image-optimizer-stats-connect-button' ).length ) {
						return;
					}

					clearInterval(intervalId);

					$( '.image-optimizer-stats-connect-button' ).first().pointer( {
						content: '<?php echo $pointer_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>',
						pointerClass: 'image-optimizer-auth-connect-pointer',
						position: {
							edge: 'top',
							align: 'right'
						},
					} ).pointer( 'open' );
				}, 100 );
			} );
		</script>

		<style>
			.image-optimizer-auth-connect-pointer .wp-pointer-arrow {
				top: 4px;
				left: 78%;
			}

			.image-optimizer-auth-connect-pointer .wp-pointer-arrow-inner {
				top: 10px;
			}
		</style>
		<?php
	}

	public function __construct() {
		add_action( 'in_admin_header', [ $this, 'admin_print_script' ] );
	}
}
