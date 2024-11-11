<?php

namespace Core\Helpers;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;

class Invoke
{
    /**
     * Identify the given invokable method and inject its dependencies.
     *
     * @param  string|array  $invokable
     * @param  array<string, mixed>  $params
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public static function resolve(string|array $invokable, ...$params)
    {
        if (is_array($invokable)) {
            $invokable[] = $params;
            return static::call(...$invokable);
        }

        if (strpos($invokable, '::') !== false) {
            return call_user_func_array($invokable, $params);
        }

        $delimiter = strpos($invokable, '->') !== false ? '->' : (strpos($invokable, '@') !== false ? '@' : null);
        if ($delimiter) {
            list($object, $method) = explode($delimiter, $invokable);
            return static::call($object, $method, $params);
        }

        return call_user_func_array($invokable, $params);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $class
     * @param  array  $params
     * @param  ContainerContract|null  $app
     * @return mixed
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function new(
        string $class,
        array $params = [],
        ?ContainerContract $app = null
    ) {
        if (empty($app)) {
            return app($class, $params);
        }

        return $app->make($class, $params);
    }

    /**
     * Call the given Closure / class@method and inject its dependencies.
     *
     * @param  callable|string  $instance
     * @param  array<string, mixed>  $params
     * @param  array  $params
     * @param  ContainerContract|null  $app
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public static function call(
        $instance,
        string $method,
        array $params = [],
        ?ContainerContract $app = null
    ) {
        if (is_string($instance)) {
            $instance = static::new($instance);
        }

        $instance = [$instance, $method];
        if (empty($app)) {
            return Container::getInstance()->call($instance, $params);
        }

        return $app->call($instance, $params);
    }
}
