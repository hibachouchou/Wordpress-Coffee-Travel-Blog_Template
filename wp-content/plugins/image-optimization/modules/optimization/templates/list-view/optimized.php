<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use ImageOptimizer\Classes\File_Utils;

?>

<div class="image-optimization-control image-optimization-control--list-view image-optimization-control--optimized"
		data-image-optimization-context="list-view"
		data-image-optimization-status="optimized"
		data-image-optimization-image-id="<?php echo esc_attr( $args['image_id'] ); ?>"
		data-image-optimization-can-be-restored="<?php echo esc_attr( $args['can_be_restored'] ); ?>">
	<p class="image-optimization-control__property">
		<?php esc_html_e( 'Image sizes optimized', 'image-optimizer' ); ?>:
		<span><?php echo esc_html( $args['sizes_optimized_count'] ); ?></span>
	</p>

	<p class="image-optimization-control__property">
		<?php if ( 0 === $args['saved']['absolute'] ) { ?>
			<span>
				<?php esc_html_e( 'Image is fully optimized', 'image-optimizer' ); ?>
			</span>
		<?php } else { ?>
			<?php esc_html_e( 'Overall saving', 'image-optimizer' ); ?>:

			<span>
				<?php
				printf(
					esc_html__( '%1$s%% (%2$s)', 'image-optimizer' ),
					esc_html( $args['saved']['relative'] ),
					esc_html( File_Utils::format_file_size( $args['saved']['absolute'], 1 ) )
				);
				?>
			</span>
		<?php } ?>
	</p>

	<div class="image-optimization-control__buttons-wrapper">
		<?php if ( $args['can_be_restored'] ) { ?>
			<button type="button"
							class="button button-secondary image-optimization-control__button image-optimization-control__button--restore-original">
				<?php esc_html_e( 'Restore original', 'image-optimizer' ); ?>
			</button>
		<?php } ?>

		<button type="button"
						class="button button-secondary image-optimization-control__button image-optimization-control__button--reoptimize">
			<?php esc_html_e( 'Reoptimize', 'image-optimizer' ); ?>
		</button>
	</div>
</div>
