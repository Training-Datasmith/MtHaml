<?php

declare (strict_types=1);
namespace Mt_Haml\Runtime;

class Attribute_Interpolation
{
    public $value;
    public static function create($value)
    {
        $instance = new Attribute_Interpolation();
        $instance->value = $value;
        return $instance;
    }
}