<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
/**
 * Comment Node
 */
class Comment extends Nest_Abstract
{
    protected $rendered;
    protected $condition;
    /**
     * @param bool   $rendered  Whether the comment is rendered in the
     *                          HTML output (as a HTML comment).
     * @param string $condition IE condition. If not null, the HTML comment
     *                          will be rendered as an IE conditional
     *                          comment.
     */
    public function __construct(array $position, $rendered, $condition = null)
    {
        parent::__construct($position);
        $this->rendered = $rendered;
        $this->condition = $condition;
    }
    public function is_rendered()
    {
        return $this->rendered;
    }
    public function has_condition()
    {
        return null !== $this->condition;
    }
    public function get_condition()
    {
        return $this->condition;
    }
    public function get_node_name()
    {
        return 'comment';
    }
    public function accept(Node_Visitor_Interface $visitor)
    {
        if (false !== $visitor->enter_comment($this)) {
            if (false !== $visitor->enter_comment_content($this)) {
                $this->visit_content($visitor);
            }
            $visitor->leave_comment_content($this);
            if (false !== $visitor->enter_comment_childs($this)) {
                $this->visit_childs($visitor);
            }
            $visitor->leave_comment_childs($this);
        }
        $visitor->leave_comment($this);
    }
    public function allows_nesting_and_content()
    {
        return !$this->rendered;
    }
}