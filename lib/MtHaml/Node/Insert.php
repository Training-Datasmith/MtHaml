<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
/**
 * Insert Node
 *
 * Represents code to execute and whose result is inserted in the document.
 */
class Insert extends Escapable_Abstract
{
    protected $content;
    public function __construct(array $position, $content)
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
        return 'echo';
    }
    public function accept(Node_Visitor_Interface $visitor)
    {
        $visitor->enter_insert($this);
        $visitor->leave_insert($this);
    }
}