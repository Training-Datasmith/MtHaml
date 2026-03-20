<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Mt_Haml\Node\Filter;
use Mt_Haml\Node_Visitor\Renderer_Abstract as Renderer;
class Preserve extends Plain
{
    public function optimize(Renderer $renderer, Filter $filter, $options)
    {
        $renderer->push_saved_indent($renderer->get_indent());
        $renderer->set_indent(0);
        $this->render_filter($renderer, $filter);
        $renderer->set_indent($renderer->pop_saved_indent());
    }
}