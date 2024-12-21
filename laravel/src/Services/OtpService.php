<?php

namespace PrettyOtp\Laravel\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class OtpService {

	public function generateOtp(string $mobile, string $segment): string {
		$otp    = rand(100000, 999999);
		$key    = $this->getOtpKey($mobile, $segment);
		$expiry = Config::get('otp.expire', 5);

		Cache::put($key, $otp, now()->addMinutes($expiry));

		$this->storeOtpTime($segment, $mobile);

		return $otp;
	}

	public function exist(string $mobile, string $segment):bool {
		$key    = $this->getOtpKey($mobile, $segment);
		return !!Cache::get($key);
	}

	public function validateOtp(string $mobile, string $otp, string $segment): bool {
		$key       = $this->getOtpKey($mobile, $segment);
		$cachedOtp = (string)Cache::get($key);
		if ($cachedOtp && $cachedOtp === $otp) {
			Cache::forget($key);

			return true;
		}

		return false;
	}

	public function incrementAttempts(string $segment, string $mobile): void {
		$attemptKey = $this->getAttemptKey($segment, $mobile);
		$expiry     = Config::get('otp.expire', 5);

		$attempts = Cache::get($attemptKey, 0);
		$attempts++;

		Cache::put($attemptKey, $attempts, now()->addMinutes($expiry));
	}

	public function hasTooManyAttempts(string $segment, string $mobile): bool {
		$attempts    = $this->getAttempt($segment, $mobile);
		$maxAttempts = Config::get('otp.max_attempts', 5);

		return $attempts > $maxAttempts;
	}

	public function getAttempt(string $segment, string $mobile): int {
		$attemptKey = $this->getAttemptKey($segment, $mobile);

		return Cache::get($attemptKey, 0);
	}

	public function hasAccess(string $segment, string $mobile): bool {
		$accessKey = $this->getAccessKey($segment, $mobile);

		return Cache::has($accessKey);
	}

	public function grantAccess(string $segment, string $mobile,? int $lifetimeInHours=null): void {
		$accessKey = $this->getAccessKey($segment, $mobile);
		Cache::put($accessKey, true, $lifetimeInHours??now()->addHours($lifetimeInHours));
	}

	public function getLastOtp(string $segment, string $mobile) {
		$otpKey = $this->getOtpKey($segment, $mobile);

		return Cache::get($otpKey);
	}
	public function getLastOtpTime(string $segment, string $mobile) {
		$otpKey = $this->getOtpKey($segment, $mobile);

		return Cache::get("{$otpKey}_sent_at");
	}

	public function storeOtpTime(string $segment, string $mobile) {
		$otpKey = $this->getOtpKey($segment, $mobile);
		Cache::put("{$otpKey}_sent_at", Carbon::now(), Carbon::now()->addMinutes(Config::get('otp.otp_expiry', 5)));
	}

	public function dropOtp(string $segment, string $mobile) {
		$otpKey = $this->getOtpKey($segment, $mobile);
		Cache::forget($otpKey);
		Cache::forget("{$otpKey}_sent_at");
		Cache::forget($this->getAttemptKey($segment, $mobile));
		Cache::forget($this->getAccessKey($segment, $mobile));
	}

	protected function getOtpKey(string $mobile, string $segment): string {
		return "otp_{$segment}_{$mobile}";
	}

	protected function getAttemptKey(string $segment, string $mobile): string {
		return "attempts_{$segment}_{$mobile}";
	}

	protected function getAccessKey(string $segment, string $mobile): string {
		return "access_{$segment}_{$mobile}";
	}
}
