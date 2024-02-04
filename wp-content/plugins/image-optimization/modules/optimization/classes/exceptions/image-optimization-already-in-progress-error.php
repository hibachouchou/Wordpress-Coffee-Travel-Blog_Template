<?php

namespace ImageOptimizer\Modules\Optimization\Classes\Exceptions;

use Exception;

class Image_Optimization_Already_In_Progress_Error extends Exception {
	protected $message = 'Image optimization already in progress error';
}
