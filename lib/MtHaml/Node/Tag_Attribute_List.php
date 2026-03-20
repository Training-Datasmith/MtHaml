<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
class Tag_Attribute_List extends Tag_Attribute
{
    public function __construct(array $position, Node_Abstract $value = null)
    {
        parent::__construct($position, null, $value);
    }
    public function accept(Node_Visitor_Interface $visitor)
    {
        if (false !== $visitor->enter_tag_attribute($this)) {
            if (false !== $visitor->enter_tag_attribute_list($this)) {
                $this->get_value()->accept($visitor);
            }
            $visitor->leave_tag_attribute_list($this);
        }
        $visitor->leave_tag_attribute($this);
    }
}