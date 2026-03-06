<?php

declare(strict_types=1);

namespace MtHaml\Filter;

use MtHaml\Node\EscapableAbstract;
use MtHaml\Node\Filter;
use MtHaml\NodeVisitor\RendererAbstract as Renderer;

class Escaped extends Plain
{
    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        foreach ($node->getChilds() as $child) {
            foreach ($child->getContent()->getChilds() as $item) {
                if ($item instanceof EscapableAbstract) {
                    $item->getEscaping()->setEnabled(true);
                }
            }
            $child->accept($renderer);
        }
    }
}
