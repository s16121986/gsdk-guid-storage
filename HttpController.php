<?php
//https://github.com/Rukudzo/laravel-image-renderer
namespace Gsdk\GuidStorage;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HttpController extends BaseController
{
	protected $defaultImages = [
		//'/image' => ''
	];

	public function file(Request $request, $guid, $part = null)
	{
		$filename = GuidStorageFacade::guidPath($guid, $part);
		if (!file_exists($filename))
			return $this->sendNotFound($request);

		if ($this->notModified($request, $filename, $request->input()))
			return Response::make()->setNotModified();

		$response = Response::make(File::get($filename));
		$response->headers->add($this->responseHeaders($filename, $request->input()));

		return $response;
	}

	protected function getGuidData(string $guid, ?int $part)
	{
		$cacheId = $this->getCacheId($guid, $part);
		if ($cacheId && Cache::has($cacheId))
			return Cache::get($cacheId);

		$file = GuidStorageFacade::findByGuid($guid);
		if (!$file)
			return null;

		$data = [
			'name' => $file->name
		];

		if ($cacheId)
			Cache::put($cacheId, $data, 86400);

		return $data;
	}

	protected function getCacheId(string $guid, ?int $part)
	{
		return 'file.' . $guid . ($part ? '_' . $part : '');
	}

	protected function sendNotFound(Request $request)
	{
		$path = $request->getPathInfo();
		foreach ($this->defaultImages as $prefix => $v) {
			if (str_starts_with($path, $prefix))
				return $this->renderNotFoundImage(public_path($v));
		}

		throw new NotFoundHttpException('File was not found.');
	}

	protected function renderNotFoundImage($destination)
	{
		$response = Response::make(file_get_contents($destination));
		$response->headers->add([
			'Content-Type' => Storage::mimeType($destination),
			'Cache-Control' => 'public'
		]);
		return $response;
	}

	protected function notModified(Request $request, string $filename, array $options = []): bool
	{
		return in_array($this->hash($filename, $options), $request->getETags());
	}

	protected function responseHeaders(string $filename, array $options = [])
	{
		$cacheControl =
			(config('renderer.cache.public') ? 'public' : 'private') .
			',max-age=' . config('renderer.cache.duration');

		return [
			'Content-Type' => $this->storage->mimeType($filename),
			'Cache-Control' => $cacheControl,
			'ETag' => $this->getETag($filename, $options),
		];
	}

	/**
	 * Get the E-Tag from the last modified date of the file.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function getETag(string $filename, array $options = []): string
	{
		return $this->hash($filename, $options);
	}

	/**
	 * Get an MD5 hash of the files last modification time and the query string.
	 *
	 * @param string $path
	 * @param array $options
	 * @return string
	 */
	protected function hash(string $filename, array $options = []): string
	{
		$query = http_build_query($options);

		return md5($this->storage->lastModified($filename) . '?' . $query);
	}

	protected function isImageFilename($filename): bool
	{
		$ext = substr($filename, strrpos($filename, '.'));

		return in_array(strtolower($ext), [
			'.jpg',
			'.jpeg',
			'.svg',
			'.png',
			'.gif',
			'.tiff',
		]);
	}
}
