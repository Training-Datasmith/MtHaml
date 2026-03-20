<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Mt_Haml\Node\Filter;
use Mt_Haml\Node\Insert;
use Mt_Haml\Node_Visitor\Renderer_Abstract as Renderer;
abstract class Abstract_Filter implements Filter_Interface
{
    public function is_optimizable(Renderer $renderer, Filter $node, $options)
    {
        foreach ($node->get_childs() as $line) {
            foreach ($line->get_content()->get_childs() as $child) {
                if ($child instanceof Insert) {
                    return false;
                }
            }
        }
        return true;
    }
    protected function render_filter(Renderer $renderer, Filter $node)
    {
        foreach ($node->get_childs() as $child) {
            $child->accept($renderer);
        }
    }
    protected function get_content(Filter $node)
    {
        $content = '';
        foreach ($node->get_childs() as $line) {
            foreach ($line->get_content()->get_childs() as $child) {
                $content .= $child->get_content();
            }
            $content .= "\n";
        }
        return $content;
    }
}