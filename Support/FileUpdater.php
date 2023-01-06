<?php

namespace Gsdk\GuidStorage\Support;

use Gsdk\GuidStorage\File;

class FileUpdater {

	private $content = null;

	private array $changedAttributes = [];

	public function __construct(private readonly File $file) {
	}

	public function content(string $content): static {
		$this->content = $content;
		return $this;
	}

	public function attributes(array $attributes): static {
		$this->changedAttributes = $attributes;
		return $this;
	}

	public function save() {
		$createdFlag = !$this->file->exists();

		if ($createdFlag) {
			$umask = umask(0);
			$this->checkFileDirectory($this->file->dirname(), 0770);
		}

		$this->file->put($this->content);

		if ($createdFlag) {
			$this->file->chmod(0660);
			umask($umask);
		}
	}

	private function checkFileDirectory($path, $mode): void {
		if (is_dir($path))
			return;

		try {
			mkdir($path, $mode, true);
		} catch (\Exception $e) {
			throw new \Exception('Cant create folder "' . $path . '"', 0, $e);
		}
	}
}