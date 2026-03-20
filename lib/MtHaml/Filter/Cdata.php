<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Mt_Haml\Node\Filter;
use Mt_Haml\Node_Visitor\Renderer_Abstract as Renderer;
class Cdata extends Plain
{
    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        $renderer->write('<![CDATA[')->indent();
        $this->render_filter($renderer, $node);
        $renderer->undent()->write(']]>');
    }
}