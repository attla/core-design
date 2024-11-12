<?php

namespace Core;

use Attla\Support\Invoke;
use Core\Traits\HasContext;

abstract class Controller extends \Illuminate\Routing\Controller
{
    use HasContext;

    /**
     * {@inheritdoc}
     *
     * @return self
     * */
    public function __construct(
        // Injectable
        // protected ?Request $request = null,
        // protected ?Authenticatable $auth = null
    ) {
        $this->context();

        // $this->middleware(fn ($request, $next) => $this->setup($request, $next));
    }

    protected function handleAction(string $action, array $params): Response
    {
        if (!method_exists($action = $this->action($action), $handle = 'handle')) {
            return Response::internalError()->message(sprintf(
                    'Method %s::%s does not exist.',
                    $action,
                    $handle
                ));
        }

        return Invoke::call($action, $handle, $params);
    }

    /** @inheritdoc */
    public function callAction($method, $params)
    {
        return $this->handleAction($method, $params);
    }

    /** @inheritdoc */
    public function __call($method, $params)
    {
        return $this->handleAction($method, $params);
    }

    /** @inheritdoc */
    public static function __callStatic($method, $params)
    {
        return Invoke::make(static::class)->handleAction($method, $params);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    // protected function setup(Request $request, \Closure $next)
    // {
    //     $this->request = $request;
    //     $this->auth = $request->user();

    //     $this->init();

    //     return $next($request);
    // }
}
