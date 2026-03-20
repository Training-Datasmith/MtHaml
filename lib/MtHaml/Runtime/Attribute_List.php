<?php

declare (strict_types=1);
namespace Mt_Haml\Runtime;

class Attribute_List
{
    public $attributes;
    public static function create($attributes)
    {
        $instance = new Attribute_List();
        $instance->attributes = $attributes;
        return $instance;
    }
}