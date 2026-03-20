<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Mt_Haml\Node\Filter;
use Mt_Haml\Node_Visitor\Renderer_Abstract;
interface Filter_Interface
{
    public function is_optimizable(Renderer_Abstract $renderer, Filter $node, $options);
    public function optimize(Renderer_Abstract $renderer, Filter $node, $options);
    public function filter($content, array $context, $options);
}