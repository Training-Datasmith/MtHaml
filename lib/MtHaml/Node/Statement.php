<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
class Statement extends Node_Abstract
{
    protected $content;
    public function __construct(array $position, Node_Abstract $content)
    {
        parent::__construct($position);
        $this->content = $content;
    }
    public function get_content()
    {
        return $this->content;
    }
    public function has_content()
    {
        return null !== $this->content;
    }
    public function get_node_name()
    {
        return 'statement';
    }
    public function accept(Node_Visitor_Interface $visitor)
    {
        if (false !== $visitor->enter_statement($this)) {
            if (false !== $visitor->enter_statement_content($this)) {
                if ($this->has_content()) {
                    $this->get_content()->accept($visitor);
                }
            }
            $visitor->leave_statement_content($this);
        }
        $visitor->leave_statement($this);
    }
}