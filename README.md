# Pretty OTP

**Pretty OTP** is a Laravel package for handling OTP (One-Time Password) generation, validation, and access control with robust features like middleware integration, configurable settings, and event-driven notification support.

## Installation

### 1. Install via Composer
```bash
composer require asgarihope/pretty-otp
```

### 2. Publish Configuration
Publish the configuration file to customize settings for your application:
```bash
php artisan vendor:publish --tag=otp-config
```

### 3. Configure OTP Settings
The `otp.php` configuration file includes:

```php
return [
    'otp_expiry'     => 5,  // OTP expiry time in minutes
    'otp_attempts'   => 5,  // Max OTP attempts
    'otp_length'     => 6,  // OTP length
    'otp_retry_time' => 2,  // Retry time in minutes
];
```

## Usage

### 1. Middleware Integration
Add the `OtpMiddleware` to your route or controller to protect endpoints:

```php
Route::post('/protected-route', function () {
    return 'Access granted';
})->middleware('otp:mobile,segment');
```

The middleware parameters:
- **key**: The request input key for the mobile number (e.g., `mobile`).
- **segment**: A unique identifier for the OTP usage context (e.g., `login`).
- **lifetimeInHours** *(optional)*: Duration in hours for which the user has access after OTP validation.

### 2. Event and Listener
**Event**: `PrettyOtp\Laravel\Events\OtpRequested`

This event is fired when an OTP is requested. You can customize the listener to handle OTP notifications:

```php
namespace App\Listeners;

use PrettyOtp\Laravel\Events\OtpRequested;
use PrettyOtp\Laravel\Listeners\SendOtpListener;

class CustomSendOtpListener extends SendOtpListener
{
    public function sendNotification(string $key, string $inputValue, string $message)
    {
        // Implement SMS or email notification logic here
        \Log::info("OTP Notification: ", compact('key', 'inputValue', 'message'));
    }
}
```

Register your custom listener in the `EventServiceProvider`:

```php
protected $listen = [
    OtpRequested::class => [
        CustomSendOtpListener::class,
    ],
];
```

### 3. Services
Use the `PrettyOtp\Laravel\Services\OtpService` to manage OTPs programmatically:

```php
$otpService = app(\PrettyOtp\Laravel\Services\OtpService::class);

// Generate OTP
$otp = $otpService->generateOtp('1234567890', 'login');

// Validate OTP
$isValid = $otpService->validateOtp('1234567890', '123456', 'login');

// Grant access after OTP validation
$otpService->grantAccess('login', '1234567890', 2); // 2 hours access
```

## Configuration
### Events
Customize OTP notifications by listening to the `OtpRequested` event and implementing your notification logic.

### Middleware
The middleware enforces OTP validation and rate-limiting, ensuring secure access control.

### Cache Storage
The package uses Laravel's cache system to store OTPs, attempts, and access grants. Configure your preferred cache driver in `config/cache.php`.

## Contributing
Pull requests and issues are welcome. For significant changes, please open an issue to discuss your ideas beforehand.

## License
This package is open-source and available under the [MIT License](LICENSE).
