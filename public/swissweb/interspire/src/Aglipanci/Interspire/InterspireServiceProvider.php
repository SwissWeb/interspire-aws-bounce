<?php namespace Aglipanci\Interspire;

use Illuminate\Support\ServiceProvider;

class InterspireServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__.'/../../config/config.php' => config_path('interspire.php')
		], 'config');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('Aglipanci\Interspire\Interspire', function ($app) {
			return new Interspire();
		});
	}

}
