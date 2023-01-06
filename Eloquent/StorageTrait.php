<?php

namespace Gsdk\GuidStorage\Support;

trait StorageTrait {

	public function upload(UploadedFile $uploadFile) {
		Facade::saveFileFromUpload($this, $uploadFile);
	}

	public function exists(int $part = null): bool {
		return $this->guid && Facade::exists($this->guid, $part);
	}

	public function content(int $part = null) {
		return $this->exists($part) ? Facade::get($this->guid, $part) : null;
	}

	public function unlink() {
		return $this->exists() && Facade::delete($this->guid);
	}

}