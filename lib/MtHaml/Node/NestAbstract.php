<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Exception;
use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
abstract class Nest_Abstract extends Node_Abstract implements Nest_Interface
{
    private $content;
    private $childs = [];
    public function add_child(Node_Abstract $node)
    {
        if (!$this->allows_nesting_and_content() && $this->has_content()) {
            throw new Exception('A node cannot have both content and nested nodes');
        }
        if (null !== $parent = $node->get_parent()) {
            $parent->remove_child($node);
        }
        $prev = end($this->childs) ?: null;
        $this->childs[] = $node;
        $node->set_parent($this);
        if ($prev) {
            $prev->set_next_sibling($node);
        }
        $node->set_previous_sibling($prev);
        $node->set_next_sibling();
    }
    public function remove_child(Node_Abstract $node)
    {
        if (false === $key = array_search($node, $this->childs, true)) {
            return;
        }
        unset($this->childs[$key]);
        $prev = $node->get_previous_sibling();
        $next = $node->get_next_sibling();
        if ($prev) {
            $prev->set_next_sibling($next);
        }
        if ($next) {
            $next->set_previous_sibling($prev);
        }
        $node->set_parent();
        $node->set_previous_sibling();
        $node->set_next_sibling();
    }
    public function has_childs()
    {
        return 0 < count($this->childs);
    }
    public function get_childs()
    {
        return $this->childs;
    }
    public function get_first_child()
    {
        if (false !== $child = reset($this->childs)) {
            return $child;
        }
    }
    public function get_last_child()
    {
        if (false !== $child = end($this->childs)) {
            return $child;
        }
    }
    public function set_content($content)
    {
        if (!$this->allows_nesting_and_content() && $this->has_childs()) {
            throw new Exception('A node cannot have both content and nested nodes');
        }
        $this->content = $content;
    }
    public function has_content()
    {
        return null !== $this->content;
    }
    public function get_content()
    {
        return $this->content;
    }
    public function allows_nesting_and_content()
    {
        return false;
    }
    public function visit_content(Node_Visitor_Interface $visitor)
    {
        if ($this->has_content()) {
            $this->get_content()->accept($visitor);
        }
    }
    public function visit_childs(Node_Visitor_Interface $visitor)
    {
        foreach ($this->get_childs() as $child) {
            $child->accept($visitor);
        }
    }
}