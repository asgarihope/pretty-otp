<?php

namespace PrettyOtp\Laravel\Listeners;

use PrettyOtp\Laravel\Events\OtpRequested;
use PrettyOtp\Laravel\Services\OtpService;

abstract class SendOtpListener {

	protected $otpService;
	/**
	 * @var string
	 */
	protected $otp;

	public function __construct(OtpService $otpService) {
		$this->otpService = $otpService;
	}

	public function handle(OtpRequested $event) {
		$this->otp = $this->otpService->generateOtp($event->mobile, $event->segment);

		$message = "Your OTP for {$event->segment} is: {$this->otp}";

        // Replace this with your SMS/notification logic
        $this->sendNotification($event->key, $event->mobile, $message);
    }

    abstract function sendNotification(string $key, string $inputValue, string $message);
}
