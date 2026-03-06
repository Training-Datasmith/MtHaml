<?php

declare(strict_types=1);

namespace MtHaml\Filter;

use MtHaml\Node\Filter;
use MtHaml\NodeVisitor\RendererAbstract as Renderer;

class Preserve extends Plain
{
    public function optimize(Renderer $renderer, Filter $filter, $options)
    {
        $renderer->pushSavedIndent($renderer->getIndent());
        $renderer->setIndent(0);

        $this->renderFilter($renderer, $filter);

        $renderer->setIndent($renderer->popSavedIndent());
    }
}
