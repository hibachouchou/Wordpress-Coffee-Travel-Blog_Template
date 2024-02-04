<?php

namespace ImageOptimizer\Classes\Async_Operation\Exceptions;

use Exception;

class Async_Operation_Exception extends Exception {
	protected $message = 'Async operation library is not loaded';
}
