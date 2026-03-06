<?php

namespace MtHaml\Target;

use MtHaml\NodeVisitor\PhpRenderer;
use MtHaml\Environment;

class Php extends TargetAbstract
{
    public function __construct(array $options = [])
    {
        parent::__construct($options + [
            'midblock_regex' => '~else\b|else\s*if\b|catch\b~A',
        ]);
    }

    public function getDefaultRendererFactory()
    {
        return function (Environment $env, array $options) {
            return new PhpRenderer($env);
        };
    }
}
