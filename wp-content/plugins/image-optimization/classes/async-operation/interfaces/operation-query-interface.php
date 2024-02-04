<?php

namespace ImageOptimizer\Classes\Async_Operation\Interfaces;

interface Operation_Query_Interface {
	public function get_query(): array;
	public function get_return_type(): string;
}
