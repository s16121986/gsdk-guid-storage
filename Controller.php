<?php
//https://github.com/Rukudzo/laravel-image-renderer
namespace Gsdk\GuidStorage;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Controller extends BaseController
{
	protected $storage;

	public function __construct()
	{
		$this->storage = File::storage();
	}

	public function file(Request $request, $guid, $part = null)
	{
		$filename = $this->getFilePath($guid, $part);
		if (!$filename || !$this->storage->exists($filename))
			return $this->sendMissingFileResponse($request);

		if ($this->notModified($request, $filename, $request->input()))
			return Response::make()->setNotModified();

		$response = Response::make($this->storage->get($filename));
		$response->headers->add($this->responseHeaders($filename, $request->input()));

		return $response;
	}

	public function image(Request $request, $guid, $part = null)
	{
		return $this->file($request, $guid, $part);
	}

	public function uploads(Request $request, $path)
	{
		$storage = Storage::disk('upload');
		$filename = $storage->path($path);
		if (!file_exists($filename) || !is_file($filename))
			return abort(404);

		$response = Response::make(file_get_contents($filename));
		$response->headers->add([
			'Content-Type' => $storage->mimeType($path)
		]);

		return $response;
	}

	protected function getFilePath($guid, $part)
	{
		$file = File::findByGuid($guid);
		if (!$file)
			return null;

		return $file->fullname . ($part ? '_' . $part : '');
	}

	/**
	 * Response for missing files.
	 *
	 * @param Request $request
	 * @param string $filename
	 */
	protected function sendMissingFileResponse(Request $request)
	{
		throw new NotFoundHttpException('File was not found.');
	}

	protected function renderNotFoundImage($destination)
	{
		$response = Response::make(file_get_contents($destination));
		$response->headers->add([
			'Content-Type' => \File::mimeType($destination),
			'Cache-Control' => 'public'
		]);

		return $response;
	}

	protected function notModified(Request $request, string $filename, array $options = [])
	{
		return in_array($this->hash($filename, $options), $request->getETags());
	}

	/**
	 * Get the headers to attach to the response.
	 *
	 * @param string $path
	 * @return array
	 */
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
	protected function getETag(string $filename, array $options = [])
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
	protected function hash(string $filename, array $options = [])
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
