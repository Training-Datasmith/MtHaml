<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Mt_Haml\Node\Filter;
use Mt_Haml\Node_Visitor\Renderer_Abstract as Renderer;
class Plain extends Abstract_Filter
{
    public function is_optimizable(Renderer $renderer, Filter $node, $options)
    {
        return true;
    }
    public function optimize(Renderer $renderer, Filter $filter, $options)
    {
        $this->render_filter($renderer, $filter);
    }
    public function filter($content, array $context, $options)
    {
        throw new \RuntimeException('Filter is optimizable and does not run in runtime');
    }
}