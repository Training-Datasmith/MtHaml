<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
/**
 * Run Node
 *
 * Represents code to execute. If there is children, the node should be
 * rendered as a block (the renderer should emit a properly closed block).
 */
class Run extends Nest_Abstract
{
    private $midblock;
    public function __construct(array $position, $content)
    {
        parent::__construct($position);
        $this->set_content($content);
    }
    public function allows_nesting_and_content()
    {
        return true;
    }
    public function get_node_name()
    {
        return 'exec';
    }
    public function set_midblock(Run $midblock = null)
    {
        $this->midblock = $midblock;
    }
    public function get_midblock()
    {
        return $this->midblock;
    }
    public function has_midblock()
    {
        return null !== $this->midblock;
    }
    public function is_block()
    {
        if ($this->has_childs()) {
            return true;
        }
        return (bool) $this->has_midblock();
    }
    public function accept(Node_Visitor_Interface $visitor)
    {
        if (false !== $visitor->enter_run($this)) {
            if (false !== $visitor->enter_run_childs($this)) {
                $this->visit_childs($visitor);
            }
            $visitor->leave_run_childs($this);
            if (false !== $visitor->enter_run_midblock($this)) {
                if (null !== $block = $this->get_midblock()) {
                    $block->accept($visitor);
                }
            }
            $visitor->leave_run_midblock($this);
        }
        $visitor->leave_run($this);
    }
}