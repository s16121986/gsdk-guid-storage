<?php
//https://github.com/Rukudzo/laravel-image-renderer
namespace Gsdk\GuidStorage\Http;

use Gsdk\GuidFiles\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Controller extends BaseController {

	protected function notModified(Request $request, File $file, array $options = []) {
		return in_array($this->hash($file, $options), $request->getETags());
	}

	/**
	 * Get the headers to attach to the response.
	 *
	 * @param string $path
	 * @return array
	 */
	protected function responseHeaders(File $file, array $options = []) {
		$cacheControl =
			(config('renderer.cache.public') ? 'public' : 'private') .
			',max-age=' . config('renderer.cache.duration');

		return [
			'Content-Type' => $file->mimeType(),
			'Cache-Control' => $cacheControl,
			'ETag' => $this->getETag($file, $options),
		];
	}

	/**
	 * Get the E-Tag from the last modified date of the file.
	 *
	 * @param string $path
	 * @return string
	 */
	protected function getETag(File $file, array $options = []) {
		return $this->hash($file, $options);
	}

	/**
	 * Get an MD5 hash of the files last modification time and the query string.
	 *
	 * @param string $path
	 * @param array $options
	 * @return string
	 */
	protected function hash(File $file, array $options = []) {
		$query = http_build_query($options);

		return md5($file->lastModified() . '?' . $query);
	}

	/**
	 * Response for missing files.
	 *
	 * @param Request $request
	 * @param string $filename
	 */
	protected function sendMissingFileResponse(Request $request, ?File $file) {
		$message = 'File ' . ($file ? $file->name() : '') . ' was not found.';

		throw new NotFoundHttpException($message);
	}

	/**
	 * Get a rendered image response.
	 *
	 * @param string $path
	 * @param array $options
	 * @return \Illuminate\Http\Response
	 */
	protected function render(File $file, array $options = []) {
		//$make = ImageRenderer::render($path, $options);

		$response = Response::make($file->get());
		$response->headers->add($this->responseHeaders($file, $options));

		return $response;
	}

	protected function renderNotFoundImage($destination) {
		$response = Response::make(file_get_contents($destination));
		$response->headers->add([
			'Content-Type' => \File::mimeType($destination),
			'Cache-Control' => 'public'
		]);

		return $response;
	}

	/**
	 * Store a new user.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\Response
	 */
	public function file(Request $request, $guid, $part = null) {
		$file = File::findByGuid($guid);
		if (!$file)
			return $this->sendMissingFileResponse($request, null);

		if (!$file->exists($guid, $part))
			return $this->sendMissingFileResponse($request, $file);

		if ($this->notModified($request, $file, $request->input()))
			return Response::make()->setNotModified();

		$response = Response::make($file->get());
		$response->headers->add($this->responseHeaders($file, $request->input()));

		return $response;
	}

	public function image(Request $request, $guid, $part = null) {
		return $this->file($request, $guid, $part);
	}

	public function uploads(Request $request, $path) {
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

}
