<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Mt_Haml\Node\Escapable_Abstract;
use Mt_Haml\Node\Filter;
use Mt_Haml\Node_Visitor\Renderer_Abstract as Renderer;
class Escaped extends Plain
{
    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        foreach ($node->get_childs() as $child) {
            foreach ($child->get_content()->get_childs() as $item) {
                if ($item instanceof Escapable_Abstract) {
                    $item->get_escaping()->set_enabled(true);
                }
            }
            $child->accept($renderer);
        }
    }
}