<?php

namespace Core\Database;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

abstract class DynamoModel extends \Attla\Dynamodb\Model\Model
{
    protected $primaryKey = 'pk';
    protected $sortKey = 'sk';

    protected $hidden = ['created_at', 'updated_at'];

    public static function getGroup(): string {
        return strtoupper(last(explode(
            '\\',
            get_called_class()
        )));
    }

    use HasFactory;
    protected static $factory = '';

    /** @return self */
    public function __construct(array $attributes = []) {
        if (empty($this->sortKey)) {
            $this->sortKeyDefault = empty($this->table)
                ? static::getGroup()
                : Str::studly(Str::singular($this->table)) . '#';
        }

        $entity = Str::studly(Str::singular(strtolower(static::getGroup())));
        static::$factory = 'Database\\Factories\\' . $entity . 'Factory';

        parent::__construct($attributes);
    }
}
