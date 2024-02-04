<?php

namespace ImageOptimizer\Modules\Oauth\Classes\Exceptions;

use Exception;

class Quota_Exceeded_Error extends Exception {
	protected $message = 'Quota exceeded';
}
