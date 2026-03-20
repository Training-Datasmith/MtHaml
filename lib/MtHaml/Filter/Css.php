<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Mt_Haml\Node\Filter;
use Mt_Haml\Node_Visitor\Renderer_Abstract as Renderer;
class Css extends Plain
{
    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        $renderer->write('<style type="text/css">');
        if ($options['cdata'] === true) {
            $renderer->write('/*<![CDATA[*/');
        }
        $renderer->indent();
        $this->render_filter($renderer, $node);
        $renderer->undent();
        if ($options['cdata'] === true) {
            $renderer->write('/*]]>*/');
        }
        $renderer->write('</style>');
    }
}