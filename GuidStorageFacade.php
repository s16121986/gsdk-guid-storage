<?php

namespace Gsdk\GuidStorage\Facade;

use Illuminate\Support\Facades\Facade;

class GuidStorageFacade extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() {
		return 'guidStorage';
	}
}