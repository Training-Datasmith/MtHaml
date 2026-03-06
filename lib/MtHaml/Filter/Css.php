<?php

declare(strict_types=1);

namespace MtHaml\Filter;

use MtHaml\Node\Filter;
use MtHaml\NodeVisitor\RendererAbstract as Renderer;

class Css extends Plain
{
    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        $renderer->write('<style type="text/css">');
        if ($options['cdata'] === true) {
            $renderer->write('/*<![CDATA[*/');
        }

        $renderer->indent();
        $this->renderFilter($renderer, $node);
        $renderer->undent();

        if ($options['cdata'] === true) {
            $renderer->write('/*]]>*/');
        }
        $renderer->write('</style>');
    }
}
