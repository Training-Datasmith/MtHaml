<?php

declare(strict_types=1);

namespace MtHaml\Runtime;

class AttributeList
{
    public $attributes;

    public static function create($attributes)
    {
        $instance = new AttributeList();
        $instance->attributes = $attributes;

        return $instance;
    }
}
