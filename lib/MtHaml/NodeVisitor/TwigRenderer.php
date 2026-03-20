<?php

declare (strict_types=1);
namespace Mt_Haml\Node_Visitor;

use Mt_Haml\Node\Filter;
use Mt_Haml\Node\Insert;
use Mt_Haml\Node\Interpolated_String;
use Mt_Haml\Node\Node_Abstract;
use Mt_Haml\Node\Object_Ref_Class;
use Mt_Haml\Node\Object_Ref_Id;
use Mt_Haml\Node\Run;
use Mt_Haml\Node\Tag;
use Mt_Haml\Node\Tag_Attribute_Interpolation;
use Mt_Haml\Node\Tag_Attribute_List;
class Twig_Renderer extends Renderer_Abstract
{
    protected function escape_language($string, $context)
    {
        // If there is a '%' or '{' at the begining of the string, it could
        // become '{%' or '{{' when concatenated with previous output. So we
        // need to escape '{' and '%' when appearing at the begining of the
        // string, unless we know that previous output doesn't end with '{'.
        $re = '~(^[{%][{%]?|\{[{%])~';
        // when context is empty, consider that we don't know what's before
        if (0 < strlen($context)) {
            $len = strlen($context);
            $char = $context[$len - 1];
            if ('{' !== $char) {
                $re = '~(\{[{%])~';
            }
        }
        return preg_replace($re, "{{ '\\1' }}", $string);
    }
    protected function string_literal($string)
    {
        return var_export((string) $string, true);
    }
    public function enter_interpolated_string(Interpolated_String $node)
    {
        if (!$this->is_echo_mode() && 1 < count($node->get_childs())) {
            $this->raw('(');
        }
    }
    public function between_interpolated_string_childs(Interpolated_String $node)
    {
        if (!$this->is_echo_mode()) {
            $this->raw(' ~ ');
        }
    }
    public function leave_interpolated_string(Interpolated_String $node)
    {
        if (!$this->is_echo_mode() && 1 < count($node->get_childs())) {
            $this->raw(')');
        }
    }
    public function enter_insert(Insert $node)
    {
        if ($this->is_echo_mode()) {
            $escaping = $node->get_escaping()->is_enabled();
            if (true === $escaping) {
                $fmt = '{{ (%s)|escape }}';
            } elseif (false === $escaping) {
                $fmt = '{{ (%s)|raw }}';
            } else {
                $fmt = '{{ %s }}';
            }
            $this->add_debug_infos($node);
            $this->raw(sprintf($fmt, $node->get_content()));
        } else {
            $content = $node->get_content();
            if (!preg_match('~^[a-zA-Z0-9_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$~', $content)) {
                $this->raw('(' . $node->get_content() . ')');
            } else {
                $this->raw($node->get_content());
            }
        }
    }
    public function enter_topblock(Run $node)
    {
        $this->render_block_top($node);
    }
    public function enter_midblock(Run $node)
    {
        $this->render_block_top($node);
    }
    public function leave_top_block(Run $node)
    {
        if ($node->is_block()) {
            if (preg_match('~^(?:-\s*)?(\w+)~', $node->get_content(), $match)) {
                $this->write($this->render_tag('end' . $match[1]));
            }
        }
    }
    protected function render_block_top(Run $node)
    {
        $this->add_debug_infos($node);
        $this->write($this->render_tag($node->get_content()));
    }
    public function enter_object_ref_class(Object_Ref_Class $node)
    {
        if ($this->is_echo_mode()) {
            $this->raw('{{ ');
        }
        $this->raw('mthaml_object_ref_class(');
        $this->push_echo_mode(false);
    }
    public function leave_object_ref_class(Object_Ref_Class $node)
    {
        $this->raw(')');
        $this->pop_echo_mode();
        if ($this->is_echo_mode()) {
            $this->raw(' }}');
        }
    }
    public function enter_object_ref_id(Object_Ref_Id $node)
    {
        if ($this->is_echo_mode()) {
            $this->raw('{{ ');
        }
        $this->raw('mthaml_object_ref_id(');
        $this->push_echo_mode(false);
    }
    public function leave_object_ref_id(Object_Ref_Id $node)
    {
        $this->raw(')');
        $this->pop_echo_mode();
        if ($this->is_echo_mode()) {
            $this->raw(' }}');
        }
    }
    public function enter_object_ref_prefix(Node_Abstract $node)
    {
        $this->raw(', ');
    }
    public function enter_filter(Filter $node)
    {
        $filter = $this->env->get_filter($node->get_filter());
        if (!$filter->is_optimizable($this, $node, $this->env->get_options())) {
            $this->write('{% filter mthaml_' . $node->get_filter() . ' %}', true, false);
            $this->saved_indent[] = $this->indent;
            $this->indent = 0;
        }
    }
    public function leave_filter(Filter $node)
    {
        $filter = $this->env->get_filter($node->get_filter());
        if (!$filter->is_optimizable($this, $node, $this->env->get_options())) {
            $this->write('{% endfilter %}');
            $this->indent = $this->pop_saved_indent();
        }
    }
    protected function render_tag($content)
    {
        $prefix = ' ';
        $suffix = ' ';
        if (preg_match('/^-/', $content)) {
            $prefix = '';
        }
        if (preg_match('/-$/', $content)) {
            $suffix = '';
        }
        return sprintf('{%%%s%s%s%%}', $prefix, $content, $suffix);
    }
    protected function write_debug_infos($lineno)
    {
        $infos = sprintf('{%% line %d %%}', $lineno);
        $this->raw($infos);
    }
    protected function render_dynamic_attributes(Tag $tag)
    {
        $this->raw(' ');
        foreach ($tag->get_attributes() as $attr) {
            $this->add_debug_infos($attr);
            break;
        }
        $this->raw('{{ mthaml_attributes([');
        $this->set_echo_mode(false);
        foreach (array_values($tag->get_attributes()) as $i => $attr) {
            if (0 !== $i) {
                $this->raw(', ');
            }
            if ($attr instanceof Tag_Attribute_Interpolation) {
                $this->raw('mthaml_attribute_interpolation(');
                $attr->get_value()->accept($this);
                $this->raw(')');
            } elseif ($attr instanceof Tag_Attribute_List) {
                $this->raw('mthaml_attribute_list(');
                $attr->get_value()->accept($this);
                $this->raw(')');
            } else {
                $this->raw('[');
                $attr->get_name()->accept($this);
                $this->raw(', ');
                if ($attr->get_value()) {
                    $attr->get_value()->accept($this);
                } else {
                    $this->raw('true');
                }
                $this->raw(']');
            }
        }
        $this->raw(']');
        $this->set_echo_mode(true);
        $this->raw(', ');
        $this->raw($this->string_literal($this->env->get_option('format')));
        $this->raw(', ');
        $this->raw($this->string_literal($this->charset));
        $this->raw(')|raw }}');
    }
}