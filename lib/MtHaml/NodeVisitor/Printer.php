<?php

declare (strict_types=1);
namespace Mt_Haml\Node_Visitor;

use Mt_Haml\Node\Comment;
use Mt_Haml\Node\Doctype;
use Mt_Haml\Node\Filter;
use Mt_Haml\Node\Insert;
use Mt_Haml\Node\Interpolated_String;
use Mt_Haml\Node\Object_Ref_Class;
use Mt_Haml\Node\Object_Ref_Id;
use Mt_Haml\Node\Root;
use Mt_Haml\Node\Run;
use Mt_Haml\Node\Statement;
use Mt_Haml\Node\Tag;
use Mt_Haml\Node\Tag_Attribute;
use Mt_Haml\Node\Text;
class Printer extends Node_Visitor_Abstract
{
    protected $indent = 0;
    protected $output = '';
    public function get_output()
    {
        return $this->output;
    }
    protected function indent()
    {
        $this->indent += 1;
        return $this;
    }
    protected function undent()
    {
        $this->indent -= 1;
        return $this;
    }
    protected function write($string, $indent = true, $break = true)
    {
        if ($indent) {
            $this->write_indentation();
        }
        $this->raw($string);
        if ($break) {
            $this->output .= "\n";
        }
        return $this;
    }
    protected function raw(string $string)
    {
        $this->output .= $string;
        return $this;
    }
    protected function write_indentation()
    {
        $this->output .= str_repeat(' ', $this->indent * 2);
        return $this;
    }
    public function enter_root(Root $node)
    {
        $this->write('root(')->indent();
    }
    public function leave_root(Root $node)
    {
        $this->undent()->write(')');
    }
    public function enter_tag(Tag $node)
    {
        $name = $node->get_tag_name();
        $flags = $node->get_flags();
        if ($flags & Tag::FLAG_REMOVE_INNER_WHITESPACES) {
            $name .= '<';
        }
        if ($flags & Tag::FLAG_REMOVE_OUTER_WHITESPACES) {
            $name .= '>';
        }
        if ($flags & Tag::FLAG_SELF_CLOSE) {
            $name .= '/';
        }
        $this->write('tag(' . $name, true, false)->indent();
        if ($node->has_content()) {
            $this->raw(' ');
            $node->get_content()->accept($this);
        }
        if ($node->has_attributes() || $node->has_childs()) {
            $this->raw("\n");
        }
    }
    public function enter_tag_content(Tag $node)
    {
        return false;
    }
    public function leave_tag(Tag $node)
    {
        $this->undent()->write(')', $node->has_attributes() || $node->has_childs());
    }
    public function enter_tag_attribute(Tag_Attribute $node)
    {
        $this->write('attr(', true, false);
    }
    public function leave_tag_attribute(Tag_Attribute $node)
    {
        $this->write(')', false, true);
    }
    public function enter_statement(Statement $node)
    {
        $this->write('', true, false);
    }
    public function leave_statement(Statement $node)
    {
        $this->write('', false, true);
    }
    public function enter_text(Text $node)
    {
        $content = $node->get_content();
        $escaping = $node->get_escaping();
        if (true === $escaping->is_enabled()) {
            $flag = '&';
            if ($node->get_escaping()->is_once()) {
                $flag .= '!';
            }
            $content = $flag . $content;
        } elseif (false === $escaping->is_enabled()) {
            $content = '!' . $content;
        }
        $this->raw('text(' . $content . ')');
    }
    public function enter_insert(Insert $node)
    {
        $content = $node->get_content();
        $escaping = $node->get_escaping();
        if (true === $escaping->is_enabled()) {
            $flag = '&';
            if ($node->get_escaping()->is_once()) {
                $flag .= '!';
            }
            $content = $flag . $content;
        } elseif (false === $escaping->is_enabled()) {
            $content = '!' . $content;
        }
        $this->raw('insert(' . $content . ')');
    }
    public function enter_run(Run $node)
    {
        $this->write('run(' . $node->get_content(), true, $node->has_childs())->indent();
    }
    public function enter_run_midblock(Run $node)
    {
        if ($node->has_midblock()) {
            $this->write('midblock(')->indent();
        }
    }
    public function leave_run_midblock(Run $node)
    {
        if ($node->has_midblock()) {
            $this->undent()->write(')');
        }
    }
    public function leave_run(Run $node)
    {
        $this->undent()->write(')', $node->has_childs());
    }
    public function enter_interpolated_string(Interpolated_String $node)
    {
        $this->raw('interpolated(');
    }
    public function leave_interpolated_string(Interpolated_String $node)
    {
        $this->raw(')');
    }
    public function enter_comment(Comment $node)
    {
        $this->write('comment(' . $node->get_condition(), true, false)->indent();
    }
    public function enter_comment_childs(Comment $node)
    {
        if ($node->has_childs()) {
            $this->raw("\n");
        }
    }
    public function leave_comment(Comment $node)
    {
        $this->undent()->write(')', $node->has_childs());
    }
    public function enter_doctype(Doctype $doctype)
    {
        $str = 'doctype(';
        $str .= $doctype->get_doctype_id() ?: 'default';
        if ($options = $doctype->get_options()) {
            $str .= ', ' . $options;
        }
        $str .= ')';
        $this->write($str);
    }
    public function enter_filter(Filter $node)
    {
        $this->write('filter(' . $node->get_filter())->indent();
    }
    public function leave_filter(Filter $node)
    {
        $this->undent()->write(')');
    }
    public function enter_object_ref_class(Object_Ref_Class $node)
    {
        $this->raw('object_ref_class(');
    }
    public function leave_object_ref_class(Object_Ref_Class $node)
    {
        $this->raw(')');
    }
    public function enter_object_ref_id(Object_Ref_Id $node)
    {
        $this->raw('object_ref_id(');
    }
    public function leave_object_ref_id(Object_Ref_Id $node)
    {
        $this->raw(')');
    }
}