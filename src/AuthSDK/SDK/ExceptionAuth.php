<?php

namespace AuthSDK\SDK;

class ExceptionAuth extends \Exception {
	public function __construct() {
		parent::__construct("The SDK settings was not found", 9000, null);
	}

}