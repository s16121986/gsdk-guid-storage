<?php

namespace Gsdk\GuidFilesystem;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class GuidStorageServiceProvider extends BaseServiceProvider {
	public function register() {
		$this->app->singleton('filesystem', function ($app) {
			//$app['config']->get('filesystem');
			//return new DatabaseManager($app, $app['db.factory']);
		});
	}
}