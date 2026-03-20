<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
class Filter extends Node_Abstract
{
    private $childs = [];
    private $filter;
    public function __construct(array $position, $filter)
    {
        parent::__construct($position);
        $this->filter = $filter;
    }
    public function get_filter()
    {
        return trim($this->filter);
    }
    public function add_child(Node_Abstract $node)
    {
        $this->childs[] = $node;
    }
    public function get_childs()
    {
        return $this->childs;
    }
    public function get_node_name()
    {
        return 'filter';
    }
    public function accept(Node_Visitor_Interface $visitor)
    {
        if (false !== $visitor->enter_filter($this)) {
            if (false !== $visitor->enter_filter_childs($this)) {
                foreach ($this->get_childs() as $child) {
                    $child->accept($visitor);
                }
            }
            $visitor->leave_filter_childs($this);
        }
        $visitor->leave_filter($this);
    }
}