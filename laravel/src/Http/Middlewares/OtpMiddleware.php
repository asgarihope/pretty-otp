<?php

namespace PrettyOtp\Laravel\Http\Middlewares;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Response;
use PrettyOtp\Laravel\Events\OtpRequested;
use PrettyOtp\Laravel\Services\OtpService;

class OtpMiddleware
{
    protected $otpService;
    /**
     * @var mixed
     */
    private $segment;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function handle($request, Closure $next, $key, $segment, ?int $lifetimeInHours = null)
    {
        if (!$segment) {
            return $this->responseWithMetadata(['error' => trans('otp.segment_required')], 400);
        }

        $this->segment = $segment;
        $mobile = $request->input($key);

        if (!$mobile) {
            return $this->responseWithMetadata(
                ['error' => trans('otp.key_required', ['key' => $key])],
                400
            );
        }

        if ($this->otpService->hasAccess($segment, $mobile)) {
            return $next($request);
        }

        $otp = $request->input('otp');
        $lastOtpTime = $this->otpService->getLastOtpTime($segment, $mobile);
        $timeRemaining = max(0, $lastOtpTime ? now()->diffInSeconds($lastOtpTime->copy()->addSeconds(config('otp.otp_retry_time', 1) * 60), false) : 0);
        $remainingAttempts = max(0, Config::get('otp.otp_attempts') - $this->otpService->getAttempt($segment, $mobile));

        if (!$otp && $remainingAttempts > 0) {
            if ($timeRemaining > 0) {
                return $this->responseWithMetadata(
                    ['error' => trans('otp.otp_retry_time', ['seconds' => $timeRemaining])],
                    429
                );
            }
            $this->otpService->dropOtp($segment, $mobile);
            Event::dispatch(new OtpRequested($mobile, $key, $segment));
            $this->otpService->storeOtpTime($segment, $mobile);

            return $this->responseWithMetadata(
                ['message' => trans('otp.otp_sent', ['mobile' => $mobile])],
                200
            );
        }

        if ($remainingAttempts === 0) {
            if ($timeRemaining === 0) {
                $this->otpService->dropOtp($segment, $mobile);
                return Response::json(['error' => trans('otp.get_new_otp')], 400);
            }
            return $this->responseWithMetadata(
                ['error' => trans('otp.max_attempts_reached')],
                429
            );
        }

        if (!$this->otpService->exist($mobile, $segment)) {
            $this->otpService->dropOtp($segment, $mobile);
            return Response::json(['error' => trans('otp.otp_expired')], 400);
        }

        if (!$this->otpService->validateOtp($mobile, $otp, $segment)) {
            $this->otpService->incrementAttempts($segment, $mobile);
            return $this->responseWithMetadata(
                ['error' => trans('otp.invalid_otp')],
                400
            );
        }

        $this->otpService->grantAccess($segment, $mobile, $lifetimeInHours);

        return $next($request);
    }

    private function responseWithMetadata($response, $status = 200)
    {
        $mobile = request()->input('mobile');
        $segment = $this->segment;

        $lastOtpTime = $this->otpService->getLastOtpTime($segment, $mobile);
        $timeRemaining = max(0, $lastOtpTime ? now()->diffInSeconds($lastOtpTime->copy()->addSeconds(config('otp.otp_retry_time', 60)), false) : 0);
        $remainingAttempts = max(0, Config::get('otp.otp_attempts') - $this->otpService->getAttempt($segment, $mobile));

        return Response::json(array_merge(
            is_array($response) ? $response : ['message' => trans($response)],
            ['time_remain' => $timeRemaining, 'remain_attempt' => $remainingAttempts]
        ), $status);
    }

}
