<?php

declare (strict_types=1);
namespace Mt_Haml\Node_Visitor;

use Mt_Haml\Node\Insert;
use Mt_Haml\Node\Interpolated_String;
use Mt_Haml\Node\Tag;
use Mt_Haml\Node\Tag_Attribute;
use Mt_Haml\Node\Text;
class Merge_Attrs extends Node_Visitor_Abstract
{
    protected $attrs;
    protected $tag;
    public function enter_tag_attributes(Tag $node)
    {
        $this->attrs = [];
        $this->tag = $node;
        // Do not attempt to merge attributes if any attribute
        // name cannot be guessed at compile time.
        //
        // Else it may change the output (e.g. the unknown attribute
        // name could be 'class')
        foreach ($node->get_attributes() as $attr) {
            if (null === $this->get_string($attr->get_name())) {
                return false;
            }
        }
    }
    public function enter_tag_attribute(Tag_Attribute $node)
    {
        if (null !== $name = $this->get_string($node->get_name())) {
            if (isset($this->attrs[$name])) {
                if ('class' === $name) {
                    $orig = $this->attrs[$name]->get_value();
                    $new = $this->merge_classes($orig, $node->get_value());
                    // Don't merge it if the value isn't const since it could
                    // be an array; which needs special handling at runtime.
                    // Also unset $this->attrs[$name] so that following
                    // class arguments do not get merged into this one.
                    if (!$new || !$new->is_const()) {
                        unset($this->attrs[$name]);
                        return;
                    }
                    $this->attrs[$name]->set_value($new);
                    $this->tag->remove_attribute($node);
                } else {
                    $this->tag->remove_attribute($this->attrs[$name]);
                }
            } else {
                $this->attrs[$name] = $node;
            }
        }
    }
    protected function get_string($node)
    {
        if ($node instanceof Text) {
            return $node->get_content();
        }
        if ($node instanceof Interpolated_String) {
            $ret = '';
            foreach ($node->get_childs() as $child) {
                if (null !== $string = $this->get_string($child)) {
                    $ret .= $string;
                } else {
                    return null;
                }
            }
            return $ret;
        }
    }
    protected function merge_classes($a, $b)
    {
        $new = new Interpolated_String($a->get_position());
        if (false === $this->merge_into($new, $a)) {
            return;
        }
        $new->add_child(new Text($b->get_position(), ' '));
        if (false === $this->merge_into($new, $b)) {
            return;
        }
        return $new;
    }
    protected function merge_into(Interpolated_String $dest, $src)
    {
        if ($src instanceof Interpolated_String) {
            foreach ($src->get_childs() as $child) {
                $dest->add_child($child);
            }
        } elseif ($src instanceof Text || $src instanceof Insert) {
            $dest->add_child($src);
        } else {
            return false;
        }
    }
}