<?php

namespace Gsdk\GuidStorage;

use Illuminate\Support\Facades\Facade;

class GuidStorageFacade extends Facade
{
	protected static function getFacadeAccessor()
	{
		return 'guidStorage';
	}
}
