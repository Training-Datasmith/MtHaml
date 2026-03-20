<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
class Text extends Escapable_Abstract
{
    private $content;
    public function __construct(array $position, $content)
    {
        parent::__construct($position);
        $this->content = $content;
    }
    public function get_content()
    {
        return $this->content;
    }
    public function get_node_name()
    {
        return 'text';
    }
    public function accept(Node_Visitor_Interface $visitor)
    {
        $visitor->enter_text($this);
        $visitor->leave_text($this);
    }
    public function is_const()
    {
        return true;
    }
}