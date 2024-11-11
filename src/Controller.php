<?php

namespace Core;

use Core\Traits\HasFactory;

abstract class Controller extends \Illuminate\Routing\Controller
{
    use HasFactory;

    /** @inheritdoc */
    public function callAction($method, $params)
    {
        return $this->handleAction($method, $params);
    }

    /** @inheritdoc */
    public function __call($method, $params)
    {
        return $this->callAction($method, $params);
    }

    /** @inheritdoc */
    public static function __callStatic($method, $params)
    {
        return app(static::class)->callAction($method, $params);
    }

    /** @return self */
    // public function __construct()
    // {
    //     $this->middleware(fn ($request, $next) => $this->setup($request, $next));
    // }

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
