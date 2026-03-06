<?php

declare(strict_types=1);

namespace MtHaml\Target;

use MtHaml\Environment;
use MtHaml\Node\NodeAbstract;

interface TargetInterface
{
    public function parse(Environment $env, $string, $filename);
    public function compile(Environment $env, NodeAbstract $node);
}
