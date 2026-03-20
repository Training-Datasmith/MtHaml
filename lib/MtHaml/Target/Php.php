<?php

declare (strict_types=1);
namespace Mt_Haml\Target;

use Mt_Haml\Environment;
use Mt_Haml\Node_Visitor\Php_Renderer;
class Php extends Target_Abstract
{
    public function __construct(array $options = [])
    {
        parent::__construct($options + ['midblock_regex' => '~else\b|else\s*if\b|catch\b~A']);
    }
    public function get_default_renderer_factory()
    {
        return function (Environment $env, array $options) {
            return new Php_Renderer($env);
        };
    }
}