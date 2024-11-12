<?php

namespace Core\Traits;

use Core\Response;
use Core\Database\Model;
use Attla\Support\Invoke;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait HasFactory
{
    /** @var array */
    protected array $data = [];

    /** @var string */
    protected string $namespace = '';
    protected string $feature = '';

    /** @var \Core\Database\Model */
    protected string $model;
    protected ?Model $entity = null;

    /** @inheritdoc */
    public function __construct(
        // Injectable
        protected ?Request $request = null,
        protected ?Authenticatable $auth = null,
        // Extendable
        string|null $model = null,
    ) {
        $this->resolveNamespaces();
        $this->entity($model);
    }

    protected function handleAction(string $action, array $params): Response {
        if (!method_exists($action = $this->action($action), $handle = 'handle'))
            return Response::internalError()->message(sprintf('Method %s::%s does not exist.', $action, $handle));

        return Invoke::call($action, $handle, $params);
    }

    protected function binds(): array {
        return [
            'auth' => $this->auth,
            'request' => $this->request,
            'model' => $this->model,
            'data' => $this->data(),
        ];
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

    protected function resolveNamespaces()
    {
        $this->feature = $this->name();
        $this->namespace = explode('\\', get_called_class(), 2)[0] . '\\';
        $this->model ??= $this->model($this->feature);
    }

    final protected function feature(?string $feat = null): string
    {
        return $this->namespace . 'Features\\' . ($feat ? $this->name($feat) : $this->feature) . '\\';
    }

    final protected function action(?string $action = null): string
    {
        return $this->feature() . 'Actions\\'. Str::studly($action);
    }

    final protected function model(?string $model = null): string
    {
        return $this->namespace . 'Entities\\' . Str::studly(Str::singular(
            $model ? $this->name($model) : $this->feature
        ));
    }

    final protected function name(?string $name = null): string
    {
        if ($name && strpos($name, '\\') === false) {
            return $name;
        }

        return implode('\\', array_slice(explode('\\', $name ?: get_called_class()), 2, 1));
    }

    /** @inheritdoc */
    // final protected function connection(): ConnectionInterface
    // {
    //     return DB::connection($this->connectionName());
    // }

    /** @inheritdoc */
    // protected function connectionName(): string
    // {
    //     return config('database.default');
    // }
}
