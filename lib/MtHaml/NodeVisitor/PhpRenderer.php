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
class Php_Renderer extends Renderer_Abstract
{
    protected function escape_language($string, $context)
    {
        // If there is a '?' at the begining of the string, it could become
        // a '<?' when concatenated with previous output. So we need to escape
        // '?' when appearing at the begining of the string, unless we know
        // that previous output doesn't end with '<'.
        $re = '~(^\?|<\?)~';
        // when context is empty, consider that we don't know what's before
        if (0 < strlen($context)) {
            $len = strlen($context);
            $char = $context[$len - 1];
            if ('<' !== $char) {
                $re = '~(<\?)~';
            }
        }
        return preg_replace($re, "<?php echo '\\1'; ?>", $string);
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
            $this->raw(' . ');
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
        $content = $node->get_content();
        $content = $this->trim_inline_comments($content);
        if ($this->is_echo_mode()) {
            $fmt = '<?php echo %s; ?>';
            if ($node->get_escaping()->is_enabled()) {
                if ($node->get_escaping()->is_once()) {
                    $fmt = "<?php echo htmlspecialchars(%s,ENT_QUOTES,'%s',false); ?>";
                } else {
                    $fmt = "<?php echo htmlspecialchars(%s,ENT_QUOTES,'%s'); ?>";
                }
            }
            $this->add_debug_infos($node);
            $this->raw(sprintf($fmt, $content, $this->charset));
        } else {
            $content = $node->get_content();
            if (!preg_match('~^\$?[a-zA-Z0-9_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$~', $content)) {
                $this->raw('(' . $content . ')');
            } else {
                $this->raw($content);
            }
        }
    }
    public function enter_top_block(Run $node)
    {
        $this->add_debug_infos($node);
        $content = $this->trim_inline_comments($node->get_content());
        if (!$node->is_block()) {
            if (preg_match('~[:;]\s*$~', $content)) {
                $this->write(sprintf('<?php %s ?>', $content));
            } else {
                $this->write(sprintf('<?php %s; ?>', $content));
            }
        } else {
            $this->write(sprintf('<?php %s { ?>', $content));
        }
    }
    public function enter_mid_block(Run $node)
    {
        $this->add_debug_infos($node);
        $content = $this->trim_inline_comments($node->get_content());
        $this->write(sprintf('<?php } %s { ?>', $content));
    }
    public function leave_top_block(Run $node)
    {
        if ($node->is_block()) {
            $this->write('<?php } ?>');
        }
    }
    public function enter_object_ref_class(Object_Ref_Class $node)
    {
        if ($this->is_echo_mode()) {
            $this->raw('<?php echo ');
        }
        $this->raw('MtHaml\Runtime::renderObjectRefClass(');
        $this->push_echo_mode(false);
    }
    public function leave_object_ref_class(Object_Ref_Class $node)
    {
        $this->raw(')');
        $this->pop_echo_mode();
        if ($this->is_echo_mode()) {
            $this->raw('; ?>');
        }
    }
    public function enter_object_ref_id(Object_Ref_Id $node)
    {
        if ($this->is_echo_mode()) {
            $this->raw('<?php echo ');
        }
        $this->raw('MtHaml\Runtime::renderObjectRefId(');
        $this->push_echo_mode(false);
    }
    public function leave_object_ref_id(Object_Ref_Id $node)
    {
        $this->raw(')');
        $this->pop_echo_mode();
        if ($this->is_echo_mode()) {
            $this->raw('; ?>');
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
            $this->push_echo_mode(false);
            $this->write('<?php echo MtHaml\Runtime::filter(' . $this->env->get_option('mthaml_variable') . ', ' . var_export($node->get_filter(), true) . ', get_defined_vars(),');
            $this->indent();
            $first = true;
            foreach ($node->get_childs() as $statement) {
                if ($first) {
                    $first = false;
                } else {
                    $this->raw(" .\n");
                }
                $this->write_indentation();
                $statement->get_content()->accept($this);
                $this->raw('. "\n"');
            }
            $this->raw("\n");
            return false;
        }
    }
    public function leave_filter(Filter $node)
    {
        $filter = $this->env->get_filter($node->get_filter());
        if (!$filter->is_optimizable($this, $node, $this->env->get_options())) {
            $this->undent();
            $this->write(') ?>');
            $this->pop_echo_mode();
        }
    }
    protected function write_debug_infos($lineno)
    {
    }
    protected function render_dynamic_attributes(Tag $tag)
    {
        $n = 0;
        $this->raw(' <?php echo MtHaml\Runtime::renderAttributes(array(');
        $this->set_echo_mode(false);
        foreach ($tag->get_attributes() as $attr) {
            if (0 !== $n) {
                $this->raw(', ');
            }
            if ($attr instanceof Tag_Attribute_Interpolation) {
                $this->raw('MtHaml\Runtime\AttributeInterpolation::create(');
                $attr->get_value()->accept($this);
                $this->raw(')');
            } elseif ($attr instanceof Tag_Attribute_List) {
                $this->raw('MtHaml\Runtime\AttributeList::create(');
                $attr->get_value()->accept($this);
                $this->raw(')');
            } else {
                $this->raw('array(');
                $attr->get_name()->accept($this);
                $this->raw(', ');
                if ($attr->get_value()) {
                    $attr->get_value()->accept($this);
                } else {
                    $this->raw('TRUE');
                }
                $this->raw(')');
            }
            ++$n;
        }
        $this->raw(')');
        $this->set_echo_mode(true);
        $this->raw(', ');
        $this->raw($this->string_literal($this->env->get_option('format')));
        $this->raw(', ');
        $this->raw($this->string_literal($this->charset));
        $this->raw('); ?>');
    }
    public function trim_inline_comments($code)
    {
        // Removes inlines comments ('//' and '#'), while ignoring '//' and '#'
        // embedded in quoted strings.
        $re = "!\n            (?P<code>\n                (?P<expr>(?:\n                    # anything except \", ', `\n                    [^\"'`]\n\n                    # double quoted string\n                    | \"(?: [^\"\\\\]+ | \\\\. )*\"\n\n                    # single quoted string\n                    | '(?: [^'\\\\]+ | \\\\. )*'\n\n                    # backticks string\n                    | `(?: [^`\\\\]+ | \\\\. )*`\n                )+?)\n            )\n            (?P<comment>\\s*(?://|\\#).*)?\n        \$!xA";
        return preg_replace($re, '$1', $code);
    }
}