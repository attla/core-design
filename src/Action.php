<?php

namespace Core;

use Core\Database\Model;
use Core\Traits\HasContext;
use Attla\Support\Invoke;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

abstract class Action
{
    use HasContext;

    // TODO: request validations
    // TODO: request middlewares
    // TODO: request transforms

    /** @var array */
    protected array $data = [];

    /** @var \Core\Database\Model */
    protected ?Model $entity = null;

    /** @inheritdoc */
    public function __construct(
        // Injectable
        protected ?Request $request = null,
        protected ?Authenticatable $auth = null,
        // Extendable
        string|null $model = null,
    ) {
        $this->context();
        $this->entity($model);
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

    protected function entity(string|Model|null $model = null) {
        !$model && isset($this->model) && ($model = $this->model);

        if ($model && (class_exists($model) || Invoke::isAlias($model))) {
            $this->entity = Invoke::new($model);
            $this->model = $model instanceof Model ? get_class($model) : $model;
        }
    }
}
