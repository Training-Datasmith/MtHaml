<?php

declare (strict_types=1);
namespace Mt_Haml\Node_Visitor;

use Mt_Haml\Node\Run;
class Midblock extends Node_Visitor_Abstract
{
    protected $midblock_regex;
    public function __construct($midblock_regex)
    {
        $this->midblock_regex = $midblock_regex;
    }
    public function enter_run(Run $node)
    {
        do {
            if (null === $prev = $node->get_previous_sibling()) {
                break;
            }
            if (!$prev instanceof Run) {
                break;
            }
            if (!preg_match($this->midblock_regex, $node->get_content())) {
                break;
            }
            $node->get_parent()->remove_child($node);
            while (null !== $prev->get_midblock()) {
                $prev = $prev->get_midblock();
            }
            $prev->set_midblock($node);
        } while (false);
    }
}