<?php

declare (strict_types=1);
namespace Mt_Haml\Target;

use Mt_Haml\Environment;
use Mt_Haml\Node_Visitor\Twig_Renderer;
class Twig extends Target_Abstract
{
    public function __construct(array $options = [])
    {
        parent::__construct($options + ['midblock_regex' => '/(?:-\s*)?(?:else\b|elseif\b)/A']);
    }
    public function get_default_renderer_factory()
    {
        return function (Environment $env, array $options) {
            return new Twig_Renderer($env);
        };
    }
}