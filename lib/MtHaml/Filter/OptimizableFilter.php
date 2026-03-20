<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Mt_Haml\Node\Filter;
use Mt_Haml\Node\Interpolated_String;
use Mt_Haml\Node\Text;
use Mt_Haml\Node_Visitor\Renderer_Abstract as Renderer;
abstract class Optimizable_Filter extends Abstract_Filter
{
    private $force_optimization;
    public function __construct($force_optimization = false)
    {
        $this->force_optimization = $force_optimization;
    }
    public function is_optimizable(Renderer $renderer, Filter $node, $options)
    {
        if ($this->force_optimization) {
            return true;
        }
        return parent::is_optimizable($renderer, $node, $options);
    }
    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        $inserts = [];
        $content = '';
        foreach ($node->get_childs() as $child) {
            foreach ($child->get_content()->get_childs() as $item) {
                if ($item instanceof Text) {
                    $content .= $item->get_content();
                } else {
                    $hash = bin2hex(random_bytes(8));
                    $inserts[$hash] = $item;
                    $content .= $hash;
                }
            }
            $content .= "\n";
        }
        $string = new Interpolated_String([]);
        $result = $this->filter($content, [], []);
        foreach ($inserts as $hash => $insert) {
            $parts = explode($hash, $result, 2);
            $string->add_child(new Text([], $parts[0]));
            $string->add_child($insert);
            $result = $parts[1];
        }
        $string->add_child(new Text([], $result));
        $string->accept($renderer);
    }
    abstract public function filter($content, array $context, $options);
}