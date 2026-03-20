<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
class Tag_Attribute extends Node_Abstract
{
    protected $name;
    protected $value;
    public function __construct(array $position, Node_Abstract $name = null, Node_Abstract $value = null)
    {
        parent::__construct($position);
        $this->name = $name;
        $this->value = $value;
    }
    public function set_name(Node_Abstract $name)
    {
        $this->name = $name;
    }
    public function get_name()
    {
        return $this->name;
    }
    public function set_value(Node_Abstract $value)
    {
        $this->value = $value;
    }
    public function get_value()
    {
        return $this->value;
    }
    public function get_node_name()
    {
        return 'attribute';
    }
    public function accept(Node_Visitor_Interface $visitor)
    {
        if (false !== $visitor->enter_tag_attribute($this)) {
            if (false !== $visitor->enter_tag_attribute_name($this)) {
                $this->get_name()->accept($visitor);
            }
            $visitor->leave_tag_attribute_name($this);
            if ($this->get_value()) {
                if (false !== $visitor->enter_tag_attribute_value($this)) {
                    $this->get_value()->accept($visitor);
                }
                $visitor->leave_tag_attribute_value($this);
            }
        }
        $visitor->leave_tag_attribute($this);
    }
}