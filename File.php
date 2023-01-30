<?php

namespace Gsdk\GuidStorage;

class File
{
	public function __construct(
		public readonly string $guid,
		public readonly int $parentId
	) {
	}
}
