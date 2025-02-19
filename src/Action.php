<?php

namespace Core;

use Core\Database\Model;
use Core\Traits\HasContext;
use Attla\Support\Invoke;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;

abstract class Action
{
    use HasContext;

    protected $rules = [];
    // TODO: request middlewares
        // https://stackoverflow.com/questions/78265691/laravel-11-middleware-authentication-with-controllers-method
    // TODO: request transforms

    /** @var array */
    protected array $data = [];

    /** @var \Core\Database\Model */
    protected ?Model $entity = null;

    /** @inheritdoc */
    public function __construct(
        // Injectable
        protected ?Request $request = null,
        protected ?Authenticatable $auth = null
    ) {
        $this->context();
        $this->entity();
        $this->validate();
    }

    /**
     * Validate the action.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\UnauthorizedException
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validate()
    {
        if (!$this->passesAuthorization()) {
            throw new UnauthorizedException;
        }

        if (!empty($this->rules)) {
            $this->request->validate($this->rules);
        }
    }

    /**
     * Retrieve request data
     *
     * @param ?array $data = []
     * @return array
     */
    protected function data(?array $data = []): array
    {
        if ($data) {
            return $data;
        }

        if (empty($this->request)) {
            return [];
        }

        if (empty($this->request->route())) {
            return $this->request->all();
        }

        return array_merge($this->request->route()->parameters(), $this->request->all());
    }

    /**
     * Build the entity action
     *
     * @param null|string|Model $model = []
     * @return void
     */
    protected function entity(string|Model|null $model = null) {
        !$model && isset($this->model) && ($model = $this->model);

        if ($model && (class_exists($model) || Invoke::isAlias($model))) {
            $this->entity = Invoke::new($model);
            $this->model = $model instanceof Model ? get_class($model) : $model;
        }
    }

    /**
     * Determine if the request passes the authorization check.
     *
     * @return bool
     */
    protected function passesAuthorization()
    {
        if (method_exists($this, 'authorize')) {
            return $this->authorize();
        }

        return true;
    }
}
