<?php

declare (strict_types=1);
namespace Mt_Haml\Target;

use Mt_Haml\Environment;
use Mt_Haml\Node\Node_Abstract;
interface Target_Interface
{
    public function parse(Environment $env, $string, $filename);
    public function compile(Environment $env, Node_Abstract $node);
}