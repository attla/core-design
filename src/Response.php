<?php

namespace Core;

use Attla\Support\Arr as AttlaArr;
use Attla\Support\Traits\{ HasArrayOffsets, HasMagicAttributes };
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class Response extends JsonResource
{
    use HasMagicAttributes;
    use HasArrayOffsets;

    /**
     * Response http code
     *
     * @var int
     */
    public int $code = 200;

    /**
     * Response message
     *
     * @var string
     */
    public string $message;

    /**
     * Error code
     *
     * @var int|string|null
     */
    public $errorCode;

    /**
     * Response errors
     *
     * @var Error[]
     */
    public array $errors = [];

    /**
     * Response headers
     *
     * @var array<string, string|string[]>
     */
    public array $headers = [];

    /**
     * Response data
     *
     * @var mixed
     */
    public $data;

    /**
     * The resource instance
     *
     * @var mixed
     */
    public $resource = [];

    /**
     * The "data" wrapper that should be applied
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Indicate if the response is data only
     *
     * @var bool
     */
    public $dataOnly = false;

    /**
     * Store the request time
     *
     * @var bool
     */
    public $requestTime = 0;

    private function __construct(
        $code = null,
        $data = []
    ) {
        $this->code($code)
            ->data($data);
    }

    /**
     * Set the message
     *
     * @param int $code
     * @return self
     */
    public function code($code)
    {
        $this->code = $code;
        $this->message = HttpResponse::$statusTexts[$code] ?? 'Unknown status';
        // $this->code = empty($message = HttpResponse::$statusTexts[$code] ?? null) ? 500 : $code;
        // $this->message = $message ?: 'Unknown status';

        return $this;
    }

    /**
     * Set the error code
     *
     * @param int $code
     * @return self
     */
    public function errorCode($code = null)
    {
        $this->errorCode = $code;
        return $this;
    }

    /**
     * Add error
     *
     * @param array $error
     * @return self
     */
    public function addError($error, $key = null)
    {
        $key ??= 'general';
        if (!empty($error)) {
            isset($this->errors[$key])
                ? $this->errors[$key][] = $error
                : $this->errors[$key] = Arr::wrap($error);
        }

        return $this;
    }

    /**
     * Add error
     *
     * @param array $error
     * @return self
     */
    public function error($error, $key = null)
    {
        return $this->addError($error, $key);
    }

    /**
     * Replace error bag
     *
     * @param array $error
     * @return self
     */
    public function errors(array $errors)
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Set the message
     *
     * @param string $message
     * @param array $replace
     * @return self
     */
    public function message($message = null, $replace = [])
    {
        if (is_string($message))
            $this->message = __(is_null($message) ? 'Unknown' : $message, $replace);

        return $this;
    }

    /**
     * Set response data
     *
     * @param mixed $data
     * @return self
     */
    public function data($data)
    {
        if (is_object($data)) {
            $data = AttlaArr::toArray($data);
        }

        $this->data = $data;
        return $this;
    }

    /**
     * Set a header on the Response
     *
     * @param string $key
     * @param array|string $values
     * @return self
     */
    public function header($key, $values)
    {
        $this->headers[$key] = $values;
        return $this;
    }

    /**
     * Add header to response
     *
     * @param array $headers
     * @return self
     */
    public function headers(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Customize the outgoing response for the resource
     *
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Http\Response $response
     * @return void
     */
    public function withResponse($request, $response)
    {
        $response->setStatusCode(
            $this->code,
            !empty($this->message) && empty(HttpResponse::$statusTexts[$this->code]) ? $this->message : null
        );

        if (!empty($this->headers)) {
            foreach ($this->headers as $key => $values)
                $response->header($key, $values);
        }
    }

    /**
     * Transform the resource into an array
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray(Request $request)
    {
        if ($this->dataOnly && !empty($this->data)) {
            return $this->data;
        }

        $response = [];

        foreach ([
            'code', 'message',
            'errorCode', 'errors',
            'requestTime',
            'data'
        ] as $attr) {
            if (!empty($val = $this->$attr))
                $response[$attr] = $val;
        }

        $response = array_merge(
            $response,
            AttlaArr::toArray($this->resource ?? []),
        );

        return $this->resumeResponse($response);
    }

    /**
     * Resume response value
     *
     * @param array $response
     * @return array
     */
    public function resumeResponse($response)
    {
        if (Arr::has($response, 'data.data')) {
            $response = $this->wrapPagination($response);
        }

        return $this->wrapData($response);
    }

    /**
     * Dynamic resume a array by aliases
     *
     * @param array $array
     * @param string $key
     * @param string|null $prefix
     * @param array $aliases
     * @return array
     */
    public function wrapIt($array, $key, $prefix = null, ...$aliases)
    {
        $value = null;
        $aliases = Arr::flatten($aliases);
        array_unshift($aliases, $key);

        if (!empty($prefix)) {
            $aliases = Arr::map($aliases, fn($alias) => $prefix.$alias);
        }

        foreach ($aliases as $alias) {
            if (!empty($val = Arr::get($array, $alias))) {
                $value = $val;
            }
        }

        if (!empty($value) || !isset($array[$key])) {
            $array[$key] = $value;
        }

        return $array;
    }

    /**
     * Resume response data
     *
     * @param array $response
     * @return array
     */
    public function wrapData($response)
    {
        return $this->wrapIt($response, 'data', 'data.', 'result', 'response');
    }

    /**
     * Resume pagination data
     *
     * @param array $response
     * @return array
     */
    public function wrapPagination($response)
    {
        $prefix = 'data.';
        $response = $this->wrapIt($response, 'next_page', $prefix, 'next_page_url');
        // $response = $this->wrapIt($response, 'prev_page', $prefix, 'next_page_url');
        return $this->wrapIt($response, 'page_size', $prefix, 'size', 'per_page', 'pageSize');
    }

    /**
     * Set request time
     *
     * @return $this
     */
    public function time() {
        if (defined('LARAVEL_START')) {
            $this->requestTime = round((microtime(true) - LARAVEL_START) * 1000) . ' ms';
        }

        return $this;
    }

    /**
     * Set response to return only the resource data
     *
     * @return $this
     */
    public function dataOnly() {
        $this->dataOnly = true;
        return $this;
    }

    /**
     * Create a response from status code
     *
     * @param int $code
     * @param mixed $data
     * @return static
     */
    public static function fromStatusCode($code = 200, $data = null) {
        return new static($code, $data);
    }

    public static function body($data = null) {
        return static::ok($data)->dataOnly();
    }

    public static function ok($data = null) {
        return new static(200, $data);
    }

    public static function created($data = null) {
        return new static(201, $data);
    }

    public static function accepted($data = null) {
        return new static(202, $data);
    }

    public static function deleted($data = null) {
        return new static(204, $data);
    }

    public static function badRequest($data = null) {
        return new static(400, $data);
    }

    public static function unauthorized($data = null) {
        return new static(401, $data);
    }

    public static function forbidden($data = null) {
        return new static(403, $data);
    }

    public static function notFound($data = null) {
        return new static(404, $data);
    }

    public static function conflict($data = null) {
        return new static(409, $data);
    }

    public static function unsupportedMediaType($data = null) {
        return new static(415, $data);
    }

    public static function unprocessableEntity($data = null) {
        return new static(422, $data);
    }

    public static function internalError($data = null) {
        return new static(500, $data);
    }

    /**
     * Get an data value
     *
     * @param string $name
     * @param mixed $default
     * @return mixed|null
     */
    public function get(string $name, $default = null)
    {
        return $this->resource[$name] ?? $default;
    }

    /**
     * Check if an data is set
     *
     * @param string $name
     * @return bool
     */
    public function isset(string $name): bool
    {
        return key_exists($name, $this->resource);
    }

    /**
     * Set an data value
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set(string $name, $value = null): void
    {
        $this->resource[$name] = $value;
    }

    /**
     * Unset an data
     *
     * @param string $name
     * @return void
     */
    protected function remove(string $name): void
    {
        if ($this->isset($name)) {
            unset($this->resource[$name]);
        }
    }

    /**
     * Dynamically retrieve or set the value
     *
     * @param string $name
     * @param array $args
     * @return mixed|$this
     */
    public function __call($name, $args)
    {
        if ($this->isset($name) && empty($args)) {
            return $this->get($name);
        }

        $this->set($name, ...$args);
        return $this;
    }
}
