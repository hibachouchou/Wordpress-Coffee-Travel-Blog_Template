<?php

namespace ImageOptimizer\Modules\Backups\Classes;

use ImageOptimizer\Classes\Async_Operation\{
	Async_Operation,
	Async_Operation_Hook,
	Async_Operation_Queue,
};
use ImageOptimizer\Classes\Image\Image_Query_Builder;

class Remove_All_Backups {
	private const CHUNK_SIZE = 100;

	public static function find_and_schedule_removing(): void {
		$query = ( new Image_Query_Builder() )
			->return_images_only_with_backups()
			->execute();

		$attachment_ids = $query->posts;
		$chunks = array_chunk( $attachment_ids, self::CHUNK_SIZE );

		foreach ( $chunks as $chunk ) {
			Async_Operation::create(
				Async_Operation_Hook::REMOVE_MANY_BACKUPS,
				[ 'attachment_ids' => $chunk ],
				Async_Operation_Queue::BACKUP
			);
		}
	}
}
