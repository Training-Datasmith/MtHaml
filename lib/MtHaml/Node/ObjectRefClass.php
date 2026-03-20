<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
class Object_Ref_Class extends Node_Abstract
{
    protected $object;
    protected $prefix;
    public function __construct(array $position, Node_Abstract $object, Node_Abstract $prefix = null)
    {
        parent::__construct($position);
        $this->object = $object;
        $this->prefix = $prefix;
    }
    public function get_node_name()
    {
        return 'object_ref_class';
    }
    public function accept(Node_Visitor_Interface $visitor)
    {
        if (false !== $visitor->enter_object_ref_class($this)) {
            if (false !== $visitor->enter_object_ref_object($this)) {
                $this->object->accept($visitor);
            }
            $visitor->leave_object_ref_object($this);
            if ($this->prefix) {
                if (false !== $visitor->enter_object_ref_prefix($this)) {
                    $this->prefix->accept($visitor);
                }
                $visitor->leave_object_ref_prefix($this);
            }
        }
        $visitor->leave_object_ref_class($this);
    }
}