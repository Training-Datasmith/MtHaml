<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
/**
 * InterpolatedString Node
 *
 * Represents a ruby-like interpolated string. Children are Text and Insert
 * nodes.
 */
class Interpolated_String extends Node_Abstract
{
    protected $childs;
    public function __construct(array $position, array $childs = [])
    {
        parent::__construct($position);
        $this->childs = $childs;
    }
    /**
     * @param Text|Insert $child Child
     */
    public function add_child(Node_Abstract $child)
    {
        if (!$child instanceof Text && !$child instanceof Insert) {
            throw new \InvalidArgumentException(sprintf('Argument 1 passed to %s() must be an instance of MtHaml\Node\Text or MtHaml\Node\Insert, instance of %s given', __METHOD__, get_class($child)));
        }
        $this->childs[] = $child;
    }
    /**
     * @return Text|Insert
     */
    public function get_childs()
    {
        return $this->childs;
    }
    public function get_node_name()
    {
        return 'interpolated string';
    }
    public function accept(Node_Visitor_Interface $visitor)
    {
        if (false !== $visitor->enter_interpolated_string($this)) {
            if (false !== $visitor->enter_interpolated_string_childs($this)) {
                foreach ($this->get_childs() as $child) {
                    $child->accept($visitor);
                }
                $visitor->leave_interpolated_string_childs($this);
            }
            $visitor->leave_interpolated_string($this);
        }
    }
    public function is_const()
    {
        foreach ($this->childs as $child) {
            if (!$child->is_const()) {
                return false;
            }
        }
        return true;
    }
}