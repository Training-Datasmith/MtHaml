<?php

declare(strict_types=1);

namespace MtHaml\Filter;

use MtHaml\Node\Filter;
use MtHaml\Node\InterpolatedString;
use MtHaml\Node\Text;
use MtHaml\NodeVisitor\RendererAbstract as Renderer;

abstract class OptimizableFilter extends AbstractFilter
{
    private $forceOptimization;

    public function __construct($forceOptimization = false)
    {
        $this->forceOptimization = $forceOptimization;
    }

    public function isOptimizable(Renderer $renderer, Filter $node, $options)
    {
        if ($this->forceOptimization) {
            return true;
        }

        return parent::isOptimizable($renderer, $node, $options);
    }

    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        $inserts = [];
        $content = '';
        foreach ($node->getChilds() as $child) {
            foreach ($child->getContent()->getChilds() as $item) {
                if ($item instanceof Text) {
                    $content .= $item->getContent();
                } else {
                    $hash = md5(mt_rand());
                    $inserts[$hash] = $item;
                    $content .= $hash;
                }
            }
            $content .= "\n";
        }

        $string = new InterpolatedString([]);
        $result = $this->filter($content, [], []);
        foreach ($inserts as $hash => $insert) {
            $parts = explode($hash, $result, 2);
            $string->addChild(new Text([], $parts[0]));
            $string->addChild($insert);
            $result = $parts[1];
        }
        $string->addChild(new Text([], $result));
        $string->accept($renderer);
    }

    abstract public function filter($content, array $context, $options);
}
