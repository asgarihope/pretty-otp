# PrettyOtp Laravel Package

PrettyOtp is a Laravel package designed to simplify the implementation of OTP (One-Time Password) mechanisms for authentication and other secure actions.

## Installation

Install the package via Composer:

```bash
composer require pretty-otp/laravel
```

## Configuration

Publish the package configuration file:

```bash
php artisan vendor:publish --tag=otp-config
```

This will create a configuration file at `config/otp.php`:

```php
return [
    'otp_expiry'     => 5,   // OTP expiry time in minutes
    'otp_attempts'   => 5,   // Max OTP attempts
    'otp_length'     => 6,   // OTP length
    'otp_retry_time' => 2,   // Retry time in minutes
];
```

## Middleware

The package provides an `OtpMiddleware` to secure routes. You can use it as follows:

### Example Usage

In your `routes/web.php` or `routes/api.php`, apply the middleware:

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['otp:key,segment,3'])->group(function () {
    Route::post('/secure-action', [SecureController::class, 'handle']);
});
```

### Middleware Parameters
- `key`: The request parameter containing the mobile number.
- `segment`: A string to identify the OTP usage context.
- `lifetimeInHours`: (Optional) The duration (in hours) for which access is granted after a successful OTP validation.

## Events and Listeners

The package dispatches an `OtpRequested` event whenever an OTP is requested. You can listen to this event and define your custom notification logic.

### OtpRequested Event

#### Event Properties:
- `$mobile`: The mobile number for which the OTP is requested.
- `$key`: The request parameter key used for the mobile number.
- `$segment`: The OTP usage context.

#### Example Listener

```php
use PrettyOtp\Laravel\Events\OtpRequested;

class CustomOtpListener
{
    public function handle(OtpRequested $event)
    {
        $otp = $this->generateOtp($event->mobile, $event->segment);
        $message = "Your OTP for {$event->segment} is: {$otp}";

        // Send the OTP via SMS or another notification channel
        $this->sendNotification($event->mobile, $message);
    }

    private function generateOtp($mobile, $segment)
    {
        // Custom OTP generation logic
    }

    private function sendNotification($mobile, $message)
    {
        // Custom notification logic
    }
}
```

#### Updated Listener Example

The `SendOtpListener` class now includes the `$otp` property and passes it to the `sendNotification` method:

```php
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
```

## Service Provider

The `PrettyOtpServiceProvider` automatically registers the middleware and event listeners. It also publishes the configuration and translation files.

## Translation

Publish the translations for customization:

```bash
php artisan vendor:publish --tag=otp-translations
```

Translations are located under `resources/lang/vendor/pretty-otp/`.

## Example Flow

1. **Request OTP**:
   When the middleware detects a request for an OTP, it dispatches the `OtpRequested` event. This event triggers the configured listener to send the OTP to the user.

2. **Validate OTP**:
   Subsequent requests to routes protected by the middleware will require a valid OTP. The middleware handles validation and grants access if the OTP is correct.

3. **Access Grant**:
   Upon successful validation, the middleware grants access to the user for the specified lifetime (`lifetimeInHours`, default is null).

### Cache Storage
The package uses Laravel's cache system to store OTPs, attempts, and access grants. Configure your preferred cache driver in `config/cache.php`.


## Contribution

Contributions are welcome! Feel free to submit a pull request or report issues in the repository.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
