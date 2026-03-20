<?php

declare (strict_types=1);
namespace Mt_Haml\Node_Visitor;

use Mt_Haml\Environment;
use Mt_Haml\Node\Comment;
use Mt_Haml\Node\Doctype;
use Mt_Haml\Node\Filter;
use Mt_Haml\Node\Interpolated_String;
use Mt_Haml\Node\Nest_Interface;
use Mt_Haml\Node\Node_Abstract;
use Mt_Haml\Node\Run;
use Mt_Haml\Node\Statement;
use Mt_Haml\Node\Tag;
use Mt_Haml\Node\Tag_Attribute;
use Mt_Haml\Node\Tag_Attribute_Interpolation;
use Mt_Haml\Node\Tag_Attribute_List;
use Mt_Haml\Node\Text;
abstract class Renderer_Abstract extends Node_Visitor_Abstract
{
    protected $indent = 0;
    protected $saved_indent = [];
    protected $output = '';
    protected $lineno = 1;
    protected $line_offset = 0;
    protected $prev_flags = 0;
    protected $env;
    protected $charset = 'UTF-8';
    protected $midblock = [false];
    /**
     * Whether echo mode is enabled
     *
     * In echo mode, nodes such as Text or Insert are directly printed with
     * e.g. <?php echo $var; ?> in the PHP renderer.
     *
     * In non echo mode, nodes are rendered as rvalue (e.g. for assignment or
     * argument passing).
     */
    protected $echo_mode = true;
    protected $echo_mode_stack = [];
    public function __construct(Environment $env)
    {
        $this->env = $env;
        $this->charset = $env->get_option('charset');
    }
    public function get_output()
    {
        return $this->output;
    }
    public function get_indent()
    {
        return $this->indent;
    }
    public function set_indent($indent)
    {
        $this->indent = (int) $indent;
        return $this;
    }
    public function indent()
    {
        $this->indent += 1;
        return $this;
    }
    public function undent()
    {
        $this->indent -= 1;
        return $this;
    }
    public function push_saved_indent($indent)
    {
        $this->saved_indent[] = (int) $indent;
        return $this;
    }
    public function pop_saved_indent()
    {
        return array_pop($this->saved_indent);
    }
    public function write($string, $indent = true, $break = true, $filter = null)
    {
        if ($indent) {
            $this->write_indentation();
        }
        $this->raw($string, $filter);
        $this->lineno += substr_count($string, "\n");
        if ($break) {
            $this->output .= "\n";
            $this->lineno++;
        }
        return $this;
    }
    public function raw($string, $filter = null)
    {
        if (null !== $filter) {
            $string = call_user_func($filter, $string, $this->output);
        }
        $this->output .= $string;
        $this->lineno += substr_count($string, "\n");
        return $this;
    }
    protected function write_indentation()
    {
        $this->output .= str_repeat(' ', $this->indent * 2);
        return $this;
    }
    protected function add_debug_infos(Node_Abstract $node)
    {
        if ($this->lineno != $node->get_lineno() + $this->line_offset) {
            $this->write_debug_infos($node->get_lineno());
            $this->line_offset = $this->lineno - $node->get_lineno();
        }
    }
    abstract protected function write_debug_infos($lineno);
    abstract protected function escape_language($string, $context);
    abstract protected function string_literal($string);
    abstract protected function between_interpolated_string_childs(Interpolated_String $node);
    protected function escape_html($string, $double = true)
    {
        return htmlspecialchars($string, ENT_QUOTES, $this->charset, $double);
    }
    public function enter_tag(Tag $node)
    {
        $indent = $this->should_indent_before_open($node);
        $this->write(sprintf('<%s', $node->get_tag_name()), $indent, false);
    }
    public function enter_tag_attributes(Tag $node)
    {
        $has_dyn_attr = false;
        foreach ($node->get_attributes() as $attr) {
            $name_node = $attr->get_name();
            $value_node = $attr->get_value();
            if ($attr instanceof Tag_Attribute_List) {
                $has_dyn_attr = true;
                break;
            }
            if ($name_node && (!$name_node->is_const() || !$value_node || !$value_node->is_const())) {
                $has_dyn_attr = true;
                break;
            }
        }
        if (!$has_dyn_attr || !$this->env->get_option('enable_dynamic_attrs')) {
            return;
        }
        $this->render_dynamic_attributes($node);
        return false;
    }
    public function leave_tag_attributes(Tag $node)
    {
        $close = $node->get_flags() & Tag::FLAG_SELF_CLOSE;
        if ($close) {
            $break = $this->should_break_after_close($node);
        } else {
            $break = $this->should_break_after_open($node);
        }
        if ($close && 'xhtml' === $this->env->get_option('format')) {
            $str = ' />';
        } else {
            $str = '>';
        }
        $this->write($str, false, $break);
        if (!$close && $break) {
            $this->indent();
        }
    }
    public function enter_tag_attribute_name(Tag_Attribute $node)
    {
        $this->raw(' ');
    }
    public function enter_tag_attribute_value(Tag_Attribute $node)
    {
        $this->raw('="');
    }
    public function leave_tag_attribute_value(Tag_Attribute $node)
    {
        $this->raw('"');
    }
    public function enter_tag_attribute_interpolation(Tag_Attribute_Interpolation $node)
    {
        $this->raw(' ');
    }
    public function leave_tag(Tag $node)
    {
        if ($node->get_flags() & Tag::FLAG_SELF_CLOSE) {
            return;
        }
        $indent = $this->should_indent_before_close($node);
        $break = $this->should_break_after_close($node);
        if ($this->should_break_after_open($node)) {
            $this->undent();
        }
        $this->write(sprintf('</%s>', $node->get_tag_name()), $indent, $break);
    }
    public function enter_statement(Statement $node)
    {
        if ($this->should_indent_before_open($node)) {
            $this->write_indentation();
        }
    }
    public function leave_statement(Statement $node)
    {
        if ($this->should_break_after_close($node)) {
            $this->raw("\n");
        }
    }
    public function enter_text(Text $node)
    {
        $string = $node->get_content();
        if ($this->is_echo_mode()) {
            if ($node->get_escaping()->is_enabled()) {
                $once = $node->get_escaping()->is_once();
                $string = $this->escape_html($string, !$once);
            }
            $this->raw($string, [$this, 'escapeLanguage']);
        } else {
            $string = $this->string_literal($string);
            $this->raw($string);
        }
    }
    public function enter_interpolated_string_childs(Interpolated_String $node)
    {
        $n = 0;
        foreach ($node->get_childs() as $child) {
            if (0 !== $n) {
                $this->between_interpolated_string_childs($node);
            }
            $child->accept($this);
            ++$n;
        }
        return false;
    }
    public function enter_doctype(Doctype $node)
    {
        $doctype = $node->get_doctype($this->env->get_option('format'));
        $this->write($doctype, true, true, [$this, 'escapeLanguage']);
    }
    public function enter_comment(Comment $comment)
    {
        if (!$comment->is_rendered()) {
            return false;
        }
        if ($comment->has_condition()) {
            $open = '<!--' . $comment->get_condition() . '>';
        } else {
            $open = '<!--';
        }
        if ($comment->has_content()) {
            $this->write($open . ' ', $comment->has_parent(), false);
        } elseif ($comment->has_childs()) {
            $this->write($open, true, true)->indent();
        }
    }
    public function leave_comment(Comment $comment)
    {
        if (!$comment->is_rendered()) {
            return false;
        }
        if ($comment->has_condition()) {
            $close = '<![endif]-->';
        } else {
            $close = '-->';
        }
        if ($comment->has_content()) {
            $this->write(' ' . $close, false, $comment->has_parent());
        } elseif ($comment->has_childs()) {
            $this->undent()->write($close, true, true);
        }
    }
    public function enter_filter_childs(Filter $node)
    {
        $filter = $this->env->get_filter($node->get_filter());
        if ($filter->is_optimizable($this, $node, $this->env->get_options())) {
            $filter->optimize($this, $node, $this->env->get_options());
            return false;
        }
    }
    public function enter_run(Run $node)
    {
        $is_mid_block = $this->midblock[0];
        array_unshift($this->midblock, false);
        if (!$is_mid_block) {
            $this->enter_topblock($node);
        } else {
            $this->enter_midblock($node);
        }
    }
    public function enter_run_childs(Run $node)
    {
        $this->indent();
    }
    public function leave_run_childs(Run $node)
    {
        $this->undent();
    }
    public function enter_run_midblock(Run $node)
    {
        array_unshift($this->midblock, true);
    }
    public function leave_run_midblock(Run $node)
    {
        array_shift($this->midblock);
    }
    public function leave_run(Run $node)
    {
        array_shift($this->midblock);
        if (!$this->midblock[0]) {
            $this->leave_topblock($node);
        } else {
            $this->leave_midblock($node);
        }
    }
    public function enter_topblock(Run $node)
    {
    }
    public function leave_topblock(Run $node)
    {
    }
    public function enter_midblock(Run $node)
    {
    }
    public function leave_midblock(Run $node)
    {
    }
    protected function get_parent_if_first_child(Node_Abstract $node)
    {
        if (null !== $node->get_previous_sibling()) {
            return;
        }
        return $this->get_parent_tag($node);
    }
    protected function get_parent_if_last_child(Node_Abstract $node)
    {
        if (null !== $node->get_next_sibling()) {
            return;
        }
        return $this->get_parent_tag($node);
    }
    protected function get_parent_tag(Node_Abstract $node)
    {
        if (null !== $parent = $node->get_parent()) {
            if ($parent instanceof Tag) {
                return $parent;
            }
        }
    }
    protected function get_first_child_if_tag(Node_Abstract $node)
    {
        if (!$node instanceof Nest_Interface) {
            return;
        }
        if (null === $first = $node->get_first_child()) {
            return;
        }
        if (!$first instanceof Tag) {
            return;
        }
        return $first;
    }
    protected function get_last_child_if_tag(Node_Abstract $node)
    {
        if (!$node instanceof Nest_Interface) {
            return;
        }
        if (null === $last = $node->get_last_child()) {
            return;
        }
        if (!$last instanceof Tag) {
            return;
        }
        return $last;
    }
    protected function get_previous_if_tag(Node_Abstract $node)
    {
        if (null === $tag = $node->get_previous_sibling()) {
            return;
        }
        if (!$tag instanceof Tag) {
            return;
        }
        return $tag;
    }
    protected function get_next_if_tag(Node_Abstract $node)
    {
        if (null === $tag = $node->get_next_sibling()) {
            return;
        }
        if (!$tag instanceof Tag) {
            return;
        }
        return $tag;
    }
    protected function should_indent_before_open(Node_Abstract $node)
    {
        if (null !== $parent = $this->get_parent_if_first_child($node)) {
            if ($parent->get_flags() & Tag::FLAG_REMOVE_INNER_WHITESPACES) {
                return false;
            }
        }
        if ($node instanceof Tag) {
            if ($node->get_flags() & Tag::FLAG_REMOVE_OUTER_WHITESPACES) {
                return false;
            }
        }
        if (null !== $prev = $this->get_previous_if_tag($node)) {
            if ($prev->get_flags() & Tag::FLAG_REMOVE_OUTER_WHITESPACES) {
                return false;
            }
        }
        return true;
    }
    protected function should_break_after_open(Node_Abstract $node)
    {
        if ($node instanceof Tag) {
            if ($node->get_flags() & Tag::FLAG_REMOVE_INNER_WHITESPACES) {
                return false;
            }
            if (!$node->has_childs()) {
                return false;
            }
        }
        if (null !== $child = $this->get_first_child_if_tag($node)) {
            if ($child->get_flags() & Tag::FLAG_REMOVE_OUTER_WHITESPACES) {
                return false;
            }
        }
        return true;
    }
    protected function should_indent_before_close(Node_Abstract $node)
    {
        if ($node instanceof Tag) {
            if ($node->get_flags() & Tag::FLAG_REMOVE_INNER_WHITESPACES) {
                return false;
            }
            if (!$node->has_childs()) {
                return false;
            }
        }
        if (null !== $child = $this->get_last_child_if_tag($node)) {
            if ($child->get_flags() & Tag::FLAG_REMOVE_OUTER_WHITESPACES) {
                return false;
            }
        }
        return true;
    }
    protected function should_break_after_close(Node_Abstract $node)
    {
        if (null !== $parent = $this->get_parent_if_last_child($node)) {
            if ($parent->get_flags() & Tag::FLAG_REMOVE_INNER_WHITESPACES) {
                return false;
            }
        }
        if (null !== $next = $this->get_next_if_tag($node)) {
            if ($next->get_flags() & Tag::FLAG_REMOVE_OUTER_WHITESPACES) {
                return false;
            }
        }
        if ($node instanceof Tag) {
            if ($node->get_flags() & Tag::FLAG_REMOVE_OUTER_WHITESPACES) {
                return false;
            }
        }
        return true;
    }
    public function set_echo_mode($enabled)
    {
        $this->echo_mode = $enabled;
    }
    public function is_echo_mode()
    {
        return $this->echo_mode;
    }
    public function push_echo_mode($enabled)
    {
        $this->echo_mode_stack[] = $this->echo_mode;
        $this->set_echo_mode($enabled);
    }
    public function pop_echo_mode()
    {
        $this->echo_mode = array_pop($this->echo_mode_stack);
    }
}