<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
class Tag extends Nest_Abstract
{
    public const FLAG_REMOVE_INNER_WHITESPACES = 1;
    public const FLAG_REMOVE_OUTER_WHITESPACES = 2;
    public const FLAG_SELF_CLOSE = 4;
    protected $tag_name;
    protected $attributes;
    protected $flags;
    public function __construct(array $position, $tag_name, array $attributes, $flags = 0)
    {
        parent::__construct($position);
        $this->tag_name = $tag_name;
        $this->attributes = $attributes;
        $this->flags = $flags;
    }
    public function get_tag_name()
    {
        return $this->tag_name;
    }
    public function add_attribute(Tag_Attribute $attribute)
    {
        $this->attributes[] = $attribute;
    }
    public function has_attributes()
    {
        return 0 < count($this->attributes);
    }
    public function get_attributes()
    {
        return $this->attributes;
    }
    public function remove_attribute(Tag_Attribute $attribute)
    {
        if (null !== $key = array_search($attribute, $this->attributes, true)) {
            unset($this->attributes[$key]);
        }
    }
    public function set_flag($flag)
    {
        $this->flags |= $flag;
    }
    public function get_flags()
    {
        return $this->flags;
    }
    public function get_node_name()
    {
        return 'tag';
    }
    public function accept(Node_Visitor_Interface $visitor)
    {
        if (false !== $visitor->enter_tag($this)) {
            if (false !== $visitor->enter_tag_attributes($this)) {
                foreach ($this->get_attributes() as $attribute) {
                    $attribute->accept($visitor);
                }
            }
            $visitor->leave_tag_attributes($this);
            if (false !== $visitor->enter_tag_content($this)) {
                $this->visit_content($visitor);
            }
            $visitor->leave_tag_content($this);
            if (false !== $visitor->enter_tag_childs($this)) {
                $this->visit_childs($visitor);
            }
            $visitor->leave_tag_childs($this);
        }
        $visitor->leave_tag($this);
    }
}