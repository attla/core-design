<?php

namespace Core\Traits;

trait HasBuildEvents
{
    /** @return self */
    public function __construct(array $attributes = []) {
        $this->fireModelEvent('building');
        parent::__construct($attributes);
        $this->fireModelEvent('builded');
    }

    /**
     * Register a "building" event
     *
     * @param \Illuminate\Events\QueuedClosure|callable|array|class-string $callback
     * @return void
     */
    public static function building($callback)
    {
        static::registerModelEvent('building', $callback);
    }

    /**
     * Register a "builded" event
     *
     * @param \Illuminate\Events\QueuedClosure|callable|array|class-string $callback
     * @return void
     */
    public static function builded($callback)
    {
        static::registerModelEvent('builded', $callback);
    }
}
