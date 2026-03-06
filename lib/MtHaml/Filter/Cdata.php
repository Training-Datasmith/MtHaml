<?php

declare(strict_types=1);

namespace MtHaml\Filter;

use MtHaml\Node\Filter;
use MtHaml\NodeVisitor\RendererAbstract as Renderer;

class Cdata extends Plain
{
    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        $renderer->write('<![CDATA[')->indent();
        $this->renderFilter($renderer, $node);
        $renderer->undent()->write(']]>');
    }
}
