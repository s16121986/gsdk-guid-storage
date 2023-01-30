<?php

namespace Gsdk\GuidStorage;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class GuidStorageServiceProvider extends BaseServiceProvider
{
	public function register()
	{
		$this->app->singleton('guidStorage', function ($app) {
			return new FileManager($this->getConfig());
		});
	}

	protected function getConfig()
	{
		return config('filesystems.disks.files');
	}
}