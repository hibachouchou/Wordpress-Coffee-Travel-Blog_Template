<?php


namespace ImageOptimizer\Classes\Image;

use ImageOptimizer\Classes\Basic_Enum;

final class Image_Optimization_Error_Type extends Basic_Enum {
	public const QUOTA_EXCEEDED = 'quota-exceeded';
	public const GENERIC = 'generic';
}
