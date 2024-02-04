<?php

namespace ImageOptimizer\Classes\Async_Operation;

use ImageOptimizer\Classes\Basic_Enum;

final class Async_Operation_Hook extends Basic_Enum {
	public const OPTIMIZE_SINGLE = 'image-optimizer/optimize/single';
	public const OPTIMIZE_ON_UPLOAD = 'image-optimizer/optimize/upload';
	public const OPTIMIZE_BULK = 'image-optimizer/optimize/bulk';
	public const REOPTIMIZE_SINGLE = 'image-optimizer/reoptimize/single';
	public const REOPTIMIZE_BULK = 'image-optimizer/reoptimize/bulk';
	public const REMOVE_MANY_BACKUPS = 'image-optimizer/backup/remove-many';
	public const RESTORE_SINGLE_IMAGE = 'image-optimizer/restore/single';
	public const RESTORE_MANY_IMAGES = 'image-optimizer/restore/restore-many';
}
