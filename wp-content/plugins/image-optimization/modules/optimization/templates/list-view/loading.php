<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="image-optimization-control image-optimization-control--list-view image-optimization-control--loading"
		data-image-optimization-context="list-view"
		data-image-optimization-status="loading"
		data-image-optimization-action="<?php echo esc_attr( $args['action'] ); ?>"
		data-image-optimization-image-id="<?php echo esc_attr( $args['image_id'] ); ?>"
		data-image-optimization-can-be-restored="<?php echo esc_attr( $args['can_be_restored'] ); ?>">
	<button class="button button-secondary image-optimization-control__button image-optimization-control__button--optimize"
					disabled="">
		<span class="spinner is-active"></span>

		<?php
		switch ( $args['action'] ) {
			case 'restore':
				esc_html_e( 'Restoring…', 'image-optimizer' );
				break;

			case 'optimize':
				esc_html_e( 'Optimizing…', 'image-optimizer' );
				break;

			case 'reoptimize':
				esc_html_e( 'Reoptimizing…', 'image-optimizer' );
				break;

			default:
				esc_html_e( 'Loading…', 'image-optimizer' );
		}
		?>
	</button>
</div>
