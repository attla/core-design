<?php

namespace Core;

use Attla\Support\Traits\{ HasArrayOffsets, HasMagicAttributes };
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
     * The resource instance.
     *
     * @var mixed
     */
    public $resource = [];

    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Indicate if the response is data only.
     *
     * @var bool
     */
    public $dataOnly = false;
    public $requestTime = 0;

    /** JsonResource constructor override */
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
     * @param  int  $code
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
     * @param  int  $code
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
     * @param  array  $error
     * @return self
     */
    public function addError(array $error = [])
    {
        if (!empty($error))
            $this->errors[] = $error;

        return $this;
    }

    /**
     * Add error
     *
     * @param  array  $error
     * @return self
     */
    public function error(array $error = [])
    {
        return $this->addError($error);
    }

    /**
     * Replace error bag
     *
     * @param  array  $error
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
     * @param  string  $message
     * @param  array  $replace
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
     * @param  mixed  $data
     * @return self
     */
    public function data($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set a header on the Response.
     *
     * @param  string  $key
     * @param  array|string  $values
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
     * @param  array  $headers
     * @return self
     */
    public function headers(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
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
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
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
            is_array($resource = $this->resource ?? []) ? $resource : $resource->toArray()
        );
        return !empty($response) ? $response : $this->data;
    }

    public static function fromStatusCode($code = 200, $data = null) {
        return new static($code, $data);
    }

    public function time() {
        if (defined('LARAVEL_START')) {
            $this->requestTime = round((microtime(true) - LARAVEL_START) * 1000) . ' ms';
        }

        return $this;
    }

    public function dataOnly() {
        $this->dataOnly = true;
        return $this;
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
