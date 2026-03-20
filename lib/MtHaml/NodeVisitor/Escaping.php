<?php

declare (strict_types=1);
namespace Mt_Haml\Node_Visitor;

use Mt_Haml\Node\Insert;
use Mt_Haml\Node\Interpolated_String;
use Mt_Haml\Node\Node_Abstract;
use Mt_Haml\Node\Tag_Attribute;
use Mt_Haml\Node\Text;
class Escaping extends Node_Visitor_Abstract
{
    /** do not auto-escape */
    public const ESCAPE_FALSE = 1;
    /** do auto-escape */
    public const ESCAPE_TRUE = 2;
    /** do auto-escape, but do not double-escape entities (haml compat) */
    public const ESCAPE_ONCE = 3;
    protected $escape_html;
    protected $escape_attrs;
    protected $in_attr = 0;
    protected $in_interpolated_string = 0;
    public function __construct($escape_html = self::ESCAPE_TRUE, $escape_attrs = self::ESCAPE_TRUE)
    {
        $this->escape_html = $escape_html;
        $this->escape_attrs = $escape_attrs;
    }
    protected function escape(Node_Abstract $node)
    {
        $enabled = $node->get_escaping()->is_enabled();
        if ($enabled !== null) {
            return;
        }
        if ($node instanceof Text) {
            // interpolated strings that are not in attribute name/value
            // are plain HTML and should not be escaped
            if ($this->in_interpolated_string && !$this->in_attr) {
                $node->get_escaping()->set_enabled(false);
                return;
            }
        }
        // everything we don't explicitly not escape is escaped
        if ($this->in_attr) {
            $this->set_escape($node, $this->escape_attrs);
        } else {
            $this->set_escape($node, $this->escape_html);
        }
    }
    protected function set_escape(Node_Abstract $node, $mode)
    {
        switch ($mode) {
            case self::ESCAPE_FALSE:
                $node->get_escaping()->set_enabled(false);
                break;
            case self::ESCAPE_ONCE:
                $node->get_escaping()->set_enabled(true)->set_once(true);
                break;
            case self::ESCAPE_TRUE:
                $node->get_escaping()->set_enabled(true);
                break;
        }
    }
    public function enter_tag_attribute(Tag_Attribute $node)
    {
        ++$this->in_attr;
    }
    public function leave_tag_attribute(Tag_Attribute $node)
    {
        --$this->in_attr;
    }
    public function enter_interpolated_string(Interpolated_String $node)
    {
        ++$this->in_interpolated_string;
    }
    public function leave_interpolated_string(Interpolated_String $node)
    {
        --$this->in_interpolated_string;
    }
    public function enter_text(Text $node)
    {
        $this->escape($node);
    }
    public function enter_insert(Insert $node)
    {
        $this->escape($node);
    }
}