<?php

namespace PrettyOtp\Laravel\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use PrettyOtp\Laravel\Events\OtpRequested;
use PrettyOtp\Laravel\Http\Middlewares\OtpMiddleware;
use PrettyOtp\Laravel\Listeners\SendOtpListener;

class PrettyOtpServiceProvider extends ServiceProvider
{
	/**
	 * Register any package services.
	 *
	 * @return void
	 */
	public function register()
	{
		// Merge the package configuration with the app's published config
		$this->mergeConfigFrom(__DIR__.'/../Config/otp.php', 'otp');
	}

	/**
	 * Boot the package services.
	 *
	 * @return void
	 */
	public function boot()
	{
		// Register middleware alias
		$this->app['router']->aliasMiddleware('otp', OtpMiddleware::class);

		// Register event-listener
		Event::listen(OtpRequested::class, SendOtpListener::class);

		// Publish configuration and translations if running in console
		if ($this->app->runningInConsole()) {
			$this->publishes([
				__DIR__.'/../Config/otp.php' => config_path('otp.php'),
			], 'otp-config');

			$this->publishes([
				__DIR__.'/../Resources/lang' => resource_path('lang'),
			], 'otp-translations');
		}

		// Register translations
		$this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'pretty-otp');
	}
}
