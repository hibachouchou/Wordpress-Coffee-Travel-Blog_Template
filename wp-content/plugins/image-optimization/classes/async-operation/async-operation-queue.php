<?php

namespace ImageOptimizer\Classes\Async_Operation;

use ImageOptimizer\Classes\Basic_Enum;

final class Async_Operation_Queue extends Basic_Enum {
	public const OPTIMIZE = 'image-optimizer/optimize';
	public const BACKUP = 'image-optimizer/backup';
	public const RESTORE = 'image-optimizer/restore';
}
