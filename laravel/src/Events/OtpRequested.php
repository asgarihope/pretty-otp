<?php

namespace PrettyOtp\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OtpRequested {

	use Dispatchable, SerializesModels;

	public $mobile;
	public $key;
	public $segment;

	public function __construct(string $mobile, string $key, string $segment) {
		$this->mobile  = $mobile;
		$this->key     = $key;
		$this->segment = $segment;
	}
}
