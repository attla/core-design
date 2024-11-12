<?php

namespace Core\Traits;

use Illuminate\Support\Str;

trait HasContext
{
    /** @var string */
    protected string $namespace = '';
    protected string $feature = '';
    protected string $model = '';

    protected function context()
    {
        $this->feature = $this->name();
        $this->namespace = explode('\\', get_called_class(), 2)[0] . '\\';
        $this->model = $this->model ?: $this->model($this->feature);
    }

    protected function feature(?string $feat = null): string
    {
        return $this->namespace . 'Features\\' . ($feat ? $this->name($feat) : $this->feature) . '\\';
    }

    protected function action(?string $action = null): string
    {
        return $this->feature() . 'Actions\\'. Str::studly($action);
    }

    protected function model(?string $model = null): string
    {
        return $this->namespace . 'Entities\\' . Str::studly(Str::singular(
            $model ? $this->name($model) : $this->feature
        ));
    }

    protected function name(?string $name = null): string
    {
        if ($name && strpos($name, '\\') === false) {
            return $name;
        }

        return implode('\\', array_slice(explode('\\', $name ?: get_called_class()), 2, 1));
    }

    /** @inheritdoc */
    // protected function connection(): ConnectionInterface
    // {
    //     return DB::connection($this->connectionName());
    // }

    /** @inheritdoc */
    // protected function connectionName(): string
    // {
    //     return config('database.default');
    // }
}
