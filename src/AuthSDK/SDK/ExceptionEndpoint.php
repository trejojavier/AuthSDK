<?php

namespace AuthSDK\SDK;

class ExceptionEndpoint extends \Exception {
	public function __construct($message) {
		parent::__construct($message, 9001);		
	}

}