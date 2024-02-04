<?php

namespace ImageOptimizer\Modules\Backups\Components;

use ImageOptimizer\Classes\Async_Operation\Async_Operation_Hook;
use ImageOptimizer\Classes\Image\{
	Image_Meta,
	Image_Restore,
	Image_Status,
};
use Throwable;

class Restore_Images {
	/** @async */
	public function restore_image( int $image_id ) {
		try {
			Image_Restore::restore( $image_id );
		} catch ( Throwable $t ) {
			( new Image_Meta( $image_id ) )
				->set_status( Image_Status::RESTORING_FAILED )
				->save();

			throw $t;
		}
	}

	/** @async */
	public function restore_many_images( array $attachment_ids ) {
		Image_Restore::restore_many( $attachment_ids );
	}

	public function __construct() {
		add_action( Async_Operation_Hook::RESTORE_SINGLE_IMAGE, [ $this, 'restore_image' ] );
		add_action( Async_Operation_Hook::RESTORE_MANY_IMAGES, [ $this, 'restore_many_images' ] );
	}
}
