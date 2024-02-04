<?php


namespace ImageOptimizer\Modules\Optimization\Classes\Exceptions;

use Exception;

class Image_Optimization_Retryable_Error extends Exception {
	protected $message = 'Image optimization error';
}
