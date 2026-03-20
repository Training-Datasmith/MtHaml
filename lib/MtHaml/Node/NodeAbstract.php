<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
abstract class Node_Abstract
{
    private $position;
    private $parent;
    private $next_sibling;
    private $previous_sibling;
    public function __construct(array $position)
    {
        $this->position = $position;
    }
    public function get_position()
    {
        return $this->position;
    }
    public function get_lineno()
    {
        return $this->position['lineno'];
    }
    public function get_column()
    {
        return $this->position['column'];
    }
    protected function set_parent(Node_Abstract $parent = null)
    {
        $this->parent = $parent;
    }
    public function has_parent()
    {
        return null !== $this->parent;
    }
    public function get_parent()
    {
        return $this->parent;
    }
    abstract public function get_node_name();
    abstract public function accept(Node_Visitor_Interface $visitor);
    protected function set_next_sibling(Node_Abstract $node = null)
    {
        $this->next_sibling = $node;
    }
    public function get_next_sibling()
    {
        return $this->next_sibling;
    }
    protected function set_previous_sibling(Node_Abstract $node = null)
    {
        $this->previous_sibling = $node;
    }
    public function get_previous_sibling()
    {
        return $this->previous_sibling;
    }
    public function is_const()
    {
        return false;
    }
}