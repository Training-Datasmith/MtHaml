<?php

declare (strict_types=1);
namespace Mt_Haml;

use Mt_Haml\Node\Nest_Interface;
use Mt_Haml\Node\Node_Abstract;
use Mt_Haml\Node\Root;
use Mt_Haml\Node\Statement;
use Mt_Haml\Node\Tag;
class Tree_Builder
{
    /**
     * @var array<\MtHaml\Node\NodeAbstract>
     */
    private $parent_stack;
    /**
     * @var \MtHaml\Node\NodeAbstract
     */
    private $parent;
    /**
     * @var \MtHaml\Node\NodeAbstract|null
     */
    private $prev;
    public function __construct()
    {
        $this->parent_stack = [];
        $this->parent = new Root();
    }
    public function add_child($level, Node_Abstract $node)
    {
        $this->update_stack($level);
        if (!$this->parent instanceof Nest_Interface) {
            $parent = $this->parent;
            if ($parent instanceof Statement) {
                $parent = $parent->get_content();
            }
            $msg = sprintf('Illegal nesting: nesting within %s is illegal', $parent->get_node_name());
            throw new Tree_Builder_Exception($msg);
        }
        if ($this->parent->has_content() && !$this->parent->allows_nesting_and_content()) {
            if ($this->parent instanceof Tag) {
                $msg = sprintf('Illegal nesting: content can\'t be both given on the same line as %%%s and nested within it', $this->parent->get_tag_name());
            } else {
                $msg = sprintf('Illegal nesting: nesting within a tag that already has content is illegal');
            }
            throw new Tree_Builder_Exception($msg);
        }
        if ($this->parent instanceof Tag && $this->parent->get_flags() & Tag::FLAG_SELF_CLOSE) {
            $msg = 'Illegal nesting: nesting within a self-closing tag is illegal';
            throw new Tree_Builder_Exception($msg);
        }
        $this->parent->add_child($node);
        $this->prev = $node;
    }
    public function get_root()
    {
        if (count($this->parent_stack) > 0) {
            return $this->parent_stack[0];
        }
        return $this->parent;
    }
    public function has_statements()
    {
        if (!$this->parent instanceof Root) {
            return true;
        }
        return $this->parent->has_childs();
    }
    private function update_stack($level)
    {
        // open node
        if ($level > 0) {
            $this->parent_stack[] = $this->parent;
            $this->parent = $this->prev;
            // close node(s)
        } elseif ($level < 0) {
            for ($i = $level; $i < 0; ++$i) {
                $this->parent = array_pop($this->parent_stack);
            }
        }
    }
}