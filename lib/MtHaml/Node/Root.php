<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
class Root extends Nest_Abstract
{
    public function __construct(array $position = null)
    {
        parent::__construct($position ?: ['lineno' => 0, 'column' => 0]);
    }
    public function get_node_name()
    {
        return 'root';
    }
    public function accept(Node_Visitor_Interface $visitor)
    {
        if (false !== $visitor->enter_root($this)) {
            if (false !== $visitor->enter_root_content($this)) {
                $this->visit_content($visitor);
            }
            $visitor->leave_root_content($this);
            if (false !== $visitor->enter_root_childs($this)) {
                $this->visit_childs($visitor);
            }
            $visitor->leave_root_childs($this);
        }
        $visitor->leave_root($this);
    }
}