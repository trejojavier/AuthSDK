<?php

namespace AuthSDK\SDK;

class ExceptionApiCall extends \Exception {
	public function __construct($message) {
		parent::__construct($message, 9002, null);
	}

}