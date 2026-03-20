<?php

declare (strict_types=1);
namespace Mt_Haml;

use Mt_Haml\Exception\Syntax_Error_Exception;
use Mt_Haml\Indentation\Indentation_Exception;
use Mt_Haml\Node\Comment;
use Mt_Haml\Node\Doctype;
use Mt_Haml\Node\Filter;
use Mt_Haml\Node\Insert;
use Mt_Haml\Node\Interpolated_String;
use Mt_Haml\Node\Node_Abstract;
use Mt_Haml\Node\Object_Ref_Class;
use Mt_Haml\Node\Object_Ref_Id;
use Mt_Haml\Node\Run;
use Mt_Haml\Node\Statement;
use Mt_Haml\Node\Tag;
use Mt_Haml\Node\Tag_Attribute;
use Mt_Haml\Node\Tag_Attribute_Interpolation;
use Mt_Haml\Node\Tag_Attribute_List;
use Mt_Haml\Node\Text;
use Mt_Haml\Parser\Buffer;
/**
 * MtHaml Parser
 */
class Parser
{
    protected $filename;
    protected $column;
    protected $lineno;
    /**
     * @var \MtHaml\Indentation\IndentationInterface
     */
    private $prev_indent;
    /**
     * @var \MtHaml\Indentation\IndentationInterface
     */
    private $indent;
    /**
     * @var \MtHaml\TreeBuilder
     */
    private $tree_builder;
    public function __construct()
    {
        $this->tree_builder = new Tree_Builder();
        $this->indent = new Indentation\Undefined();
        $this->prev_indent = $this->indent;
    }
    /**
     * Updates the indentation state
     *
     * @param string $indent The indentation characters of the current line
     */
    private function update_indent(Buffer $buf, $indent)
    {
        $this->prev_indent = $this->indent;
        try {
            $this->indent = $this->indent->new_level($indent);
        } catch (Indentation_Exception $e) {
            throw $this->syntax_error($buf, $e->get_message());
        }
        if (!$this->tree_builder->has_statements() && 0 < $this->indent->get_level()) {
            throw $this->syntax_error($buf, 'Indenting at the beginning of the document is illegal');
        }
    }
    /**
     * Processes a statement
     *
     * Inserts a new $node in the tree
     *
     * @param NodeAbstract $node Node to insert in the tree
     */
    public function process_statement(Buffer $buf, Node_Abstract $node)
    {
        $level = $this->indent->get_level() - $this->prev_indent->get_level();
        try {
            $this->tree_builder->add_child($level, $node);
        } catch (Tree_Builder_Exception $e) {
            throw $this->syntax_error($buf, $e->get_message());
        }
    }
    /**
     * Parses a HAML document
     *
     * @param string $string    A HAML document
     * @param string $fileaname Filename to report in error messages
     * @param string $lineno    Line number of the first line of $string in
     *                          $filename (for error messages)
     */
    public function parse($string, $filename, $lineno = 1)
    {
        $this->filename = $filename;
        $buf = new Buffer($string, $lineno);
        while ($buf->next_line()) {
            $this->handle_multiline($buf);
            $this->parse_line($buf);
        }
        return $this->tree_builder->get_root();
    }
    /**
     * Handles HAML multiline syntax
     *
     * Any line terminated by ` |` is concatenated with the following lines
     * also terminated by ` |`. Empty or whitespace-only lines are ignored. The
     * current line is replaced by the resulting line in $buf.
     */
    public function handle_multiline(Buffer $buf)
    {
        $line = $buf->get_line();
        if (!$this->is_multiline($line)) {
            return;
        }
        $line = substr(rtrim($line), 0, -1);
        while ($next = $buf->peek_line()) {
            if (trim($next) == '') {
                $buf->next_line();
                continue;
            }
            if (!$this->is_multiline($next)) {
                break;
            }
            $line .= substr(trim($next), 0, -1);
            $buf->next_line();
        }
        $buf->replace_line($line);
    }
    public function is_multiline($string)
    {
        return ' |' === substr(rtrim($string), -2);
    }
    /**
     * Parses a HAML line
     */
    protected function parse_line(Buffer $buf)
    {
        if ('' === trim($buf->get_line())) {
            return;
        }
        $buf->match('/[ \t]*/A', $match);
        $indent = $match[0];
        $this->update_indent($buf, $indent);
        if (null === $node = $this->parse_statement($buf)) {
            throw $this->syntax_error_expected($buf, 'statement');
        }
        $this->process_statement($buf, $node);
    }
    protected function parse_statement(Buffer $buf)
    {
        if (null !== $node = $this->parse_tag($buf)) {
            return $node;
        }
        if (null !== $node = $this->parse_filter($buf)) {
            return $node;
        }
        if (null !== $comment = $this->parse_comment($buf)) {
            return $comment;
        }
        if (null !== $run = $this->parse_run($buf)) {
            return $run;
        }
        if (null !== $doctype = $this->parse_doctype($buf)) {
            return $doctype;
        }
        if (null !== $node = $this->parse_nestable_statement($buf)) {
            return new Statement($node->get_position(), $node);
        }
    }
    protected function parse_doctype(Buffer $buf)
    {
        $doctype_regex = '/
            !!!                         # start of doctype decl
            (?:
                \s(?P<type>[^\s]+)      # optional doctype id
                (?:\s(?P<options>.*))?  # doctype options (e.g. charset, for
                                        # xml decls)
            )?$/Ax';
        if ($buf->match($doctype_regex, $match)) {
            $type = empty($match['type']) ? null : $match['type'];
            $options = empty($match['options']) ? null : $match['options'];
            return new Doctype($match['pos'][0], $type, $options);
        }
    }
    protected function parse_comment(Buffer $buf)
    {
        if ($buf->match('!(-#|/)\s*!A', $match)) {
            $pos = $match['pos'][0];
            $rendered = '/' === $match[1];
            $condition = null;
            if ($rendered) {
                // IE conditional comments
                // example: [if IE lte 8]
                //
                // matches nested [...]
                if ($buf->match('!(\[ ( [^\[\]]+ | (?1) )+  \])$!Ax', $match)) {
                    $condition = $match[0];
                }
            }
            $node = new Comment($pos, $rendered, $condition);
            if ('' !== $line = trim($buf->get_line())) {
                $content = new Text($buf->get_position(), $line);
                $node->set_content($content);
            }
            if (!$rendered) {
                while (null !== $next = $buf->peek_line()) {
                    $indent = '';
                    if ('' !== trim($next)) {
                        $indent = $this->indent->get_string(1, $next);
                        if ('' === $indent) {
                            break;
                        }
                        if (strpos($next, $indent) !== 0) {
                            break;
                        }
                    }
                    $buf->next_line();
                    if ('' !== trim($next)) {
                        $buf->eat_chars(strlen($indent));
                        $str = new Text($buf->get_position(), $buf->get_line());
                        $node->add_child(new Statement($str->get_position(), $str));
                    }
                }
            }
            return $node;
        }
    }
    protected function get_multiline_code(Buffer $buf)
    {
        $code = $buf->get_line();
        while (preg_match('/,\s*$/', $code)) {
            $buf->next_line();
            $line = trim($buf->get_line());
            if ('' !== $line) {
                $code .= ' ' . $line;
            }
        }
        return $code;
    }
    protected function parse_run(Buffer $buf)
    {
        if ($buf->match('/-(?!#)/A', $match)) {
            $buf->skip_ws();
            $code = $this->get_multiline_code($buf);
            return new Run($match['pos'][0], $code);
        }
    }
    protected function parse_tag(Buffer $buf)
    {
        $tag_regex = '/
            %(?P<tag_name>[\w:-]+)  # explicit tag name ( %tagname )
            | (?=[.#][\w-])         # implicit div followed by class or id
                                    # ( .class or #id )
            /xA';
        if ($buf->match($tag_regex, $match)) {
            $tag_name = empty($match['tag_name']) ? 'div' : $match['tag_name'];
            $attributes = $this->parse_tag_attributes($buf);
            $flags = $this->parse_tag_flags($buf);
            $node = new Tag($match['pos'][0], $tag_name, $attributes, $flags);
            $buf->skip_ws();
            if (null !== $nested = $this->parse_nestable_statement($buf)) {
                if ($flags & Tag::FLAG_SELF_CLOSE) {
                    $msg = 'Illegal nesting: nesting within a self-closing tag is illegal';
                    throw $this->syntax_error($buf, $msg);
                }
                $node->set_content($nested);
            }
            return $node;
        }
    }
    protected function parse_tag_flags(Buffer $buf)
    {
        $flags = 0;
        while (null !== $char = $buf->peek_char()) {
            switch ($char) {
                case '<':
                    $flags |= Tag::FLAG_REMOVE_INNER_WHITESPACES;
                    $buf->eat_char();
                    break;
                case '>':
                    $flags |= Tag::FLAG_REMOVE_OUTER_WHITESPACES;
                    $buf->eat_char();
                    break;
                case '/':
                    $flags |= Tag::FLAG_SELF_CLOSE;
                    $buf->eat_char();
                    break;
                default:
                    break 2;
            }
        }
        return $flags;
    }
    protected function parse_tag_attributes(Buffer $buf)
    {
        $attrs = [];
        // short notation for classes and ids
        while ($buf->match('/(?P<type>[#.])(?P<name>[\w-]+)/A', $match)) {
            if ($match['type'] == '#') {
                $name = 'id';
            } else {
                $name = 'class';
            }
            $name = new Text($match['pos'][0], $name);
            $value = new Text($match['pos'][1], $match['name']);
            $attr = new Tag_Attribute($match['pos'][0], $name, $value);
            $attrs[] = $attr;
        }
        $has_ruby_attrs = false;
        $has_html_attrs = false;
        $has_object_ref = false;
        // accept ruby-attrs, html-attrs, and object-ref in any order,
        // but only one of each
        while (true) {
            switch ($buf->peek_char()) {
                case '{':
                    if ($has_ruby_attrs) {
                        break 2;
                    }
                    $has_ruby_attrs = true;
                    $new_attrs = $this->parse_tag_attributes_ruby($buf);
                    $attrs = array_merge($attrs, $new_attrs);
                    break;
                case '(':
                    if ($has_html_attrs) {
                        break 2;
                    }
                    $has_html_attrs = true;
                    $new_attrs = $this->parse_tag_attributes_html($buf);
                    $attrs = array_merge($attrs, $new_attrs);
                    break;
                case '[':
                    if ($has_object_ref) {
                        break 2;
                    }
                    $has_object_ref = true;
                    $new_attrs = $this->parse_tag_attributes_object($buf);
                    $attrs = array_merge($attrs, $new_attrs);
                    break;
                default:
                    break 2;
            }
        }
        return $attrs;
    }
    protected function parse_tag_attributes_ruby(Buffer $buf)
    {
        $attrs = [];
        if ($buf->match('/\{\s*/')) {
            do {
                $attrs[] = $this->parse_tag_attribute_ruby($buf);
                $buf->skip_ws();
                if ($buf->match('/}/A')) {
                    break;
                }
                $buf->skip_ws();
                if (!$buf->match('/,\s*/A')) {
                    throw $this->syntax_error_expected($buf, "',' or '}'");
                }
                // allow line break after comma
                if ($buf->is_eol()) {
                    $buf->next_line();
                    $buf->skip_ws();
                }
            } while (true);
        }
        return $attrs;
    }
    protected function parse_tag_attribute_ruby(Buffer $buf)
    {
        if ($expr = $this->parse_interpolation($buf)) {
            return new Tag_Attribute_Interpolation($expr->get_position(), $expr);
        }
        list($name, $ruby19) = $this->parse_tag_attribute_name_ruby($buf);
        $buf->skip_ws();
        if (!$ruby19 && !$buf->match('/=>\s*/A')) {
            return new Tag_Attribute_List($name->get_position(), $name);
        }
        $value = $this->parse_tag_attribute_value_ruby($buf);
        return new Tag_Attribute($name->get_position(), $name, $value);
    }
    protected function parse_tag_attribute_name_ruby(Buffer $buf)
    {
        try {
            if ($name = $this->parse_tag_attribute_name_ruby19($buf)) {
                return [$name, true];
            }
            return [$this->parse_attr_expression($buf, '=,'), false];
        } catch (Syntax_Error_Exception $e) {
            // Allow line break after comma
            if ($buf->match('/,\s*$/', $match, false) && $buf->has_next_line()) {
                $buf->merge_next_line();
                return $this->parse_tag_attribute_name_ruby($buf);
            }
            throw $e;
        }
    }
    protected function parse_tag_attribute_name_ruby19(Buffer $buf)
    {
        if ($buf->match('/(\w+):/A', $match)) {
            return new Text($match['pos'][0], $match[1]);
        }
    }
    protected function parse_tag_attribute_value_ruby(Buffer $buf)
    {
        try {
            return $this->parse_attr_expression($buf, ',');
        } catch (Syntax_Error_Exception $e) {
            // Allow line break after comma
            if ($buf->match('/,\s*$/', $match, false) && $buf->has_next_line()) {
                $buf->merge_next_line();
                return $this->parse_tag_attribute_value_ruby($buf);
            }
            throw $e;
        }
    }
    protected function parse_tag_attributes_html(Buffer $buf)
    {
        if (!$buf->match('/\(\s*/A')) {
            return null;
        }
        $attrs = [];
        do {
            $attrs[] = $this->parse_tag_attribute_html($buf);
            if ($buf->match('/\s*\)/A')) {
                break;
            }
            if (!$buf->match('/\s+/A')) {
                if (!$buf->is_eol()) {
                    throw $this->syntax_error_expected($buf, "' ', ')' or end of line");
                }
            }
            // allow line break
            if ($buf->is_eol()) {
                $buf->next_line();
                $buf->skip_ws();
            }
        } while (true);
        return $attrs;
    }
    private function parse_tag_attribute_html(Buffer $buf)
    {
        if ($expr = $this->parse_interpolation($buf)) {
            return new Tag_Attribute_Interpolation($expr->get_position(), $expr);
        }
        if ($buf->match('/[@\.\w+:-]+/A', $match)) {
            $name = new Text($match['pos'][0], $match[0]);
            if (!$buf->match('/\s*=\s*/A')) {
                $value = null;
            } else {
                $value = $this->parse_attr_expression($buf, ' ');
            }
            return new Tag_Attribute($name->get_position(), $name, $value);
        }
        throw $this->syntax_error_expected($buf, 'html attribute name or #{interpolation}');
    }
    protected function parse_tag_attributes_object(Buffer $buf)
    {
        $nodes = [];
        $attrs = [];
        if (!$buf->match('/\[\s*/A', $match)) {
            return $attrs;
        }
        $pos = $match['pos'][0];
        do {
            if ($buf->match('/\s*\]\s*/A')) {
                break;
            }
            list($expr, $pos) = $this->parse_expression($buf, ',\]');
            $nodes[] = new Insert($pos, $expr);
            if ($buf->match('/\s*\]\s*/A')) {
                break;
            } elseif (!$buf->match('/\s*,\s*/A')) {
                throw $this->syntax_error_expected($buf, "',' or ']'");
            }
        } while (true);
        list($object, $prefix) = array_pad($nodes, 2, null);
        if (!$object) {
            return $attrs;
        }
        $class = new Object_Ref_Class($pos, $object, $prefix);
        $id = new Object_Ref_Id($pos, $object, $prefix);
        $name = new Text($pos, 'class');
        $attrs[] = new Tag_Attribute($pos, $name, $class);
        $name = new Text($pos, 'id');
        $attrs[] = new Tag_Attribute($pos, $name, $id);
        return $attrs;
    }
    protected function parse_attr_expression(Buffer $buf, $delims)
    {
        $sub = clone $buf;
        list($expr, $pos) = $this->parse_expression($buf, $delims);
        // hack to return a parsed string or symbol instead of an expression
        // if the whole expression can be parsed as string or symbol.
        if (preg_match('/"/A', $expr)) {
            try {
                $string = $this->parse_interpolated_string($sub);
                if ($sub->get_column() >= $buf->get_column()) {
                    $buf->eat_chars($sub->get_column() - $buf->get_column());
                    return $string;
                }
            } catch (Syntax_Error_Exception $e) {
            }
        } elseif (preg_match('/:/A', $expr)) {
            try {
                $sym = $this->parse_symbol($sub);
                if ($sub->get_column() >= $buf->get_column()) {
                    $buf->eat_chars($sub->get_column() - $buf->get_column());
                    return $sym;
                }
            } catch (Syntax_Error_Exception $e) {
            }
        }
        return new Insert($pos, $expr);
    }
    protected function parse_expression(Buffer $buf, $delims)
    {
        // matches everything until a delimiter is found
        // delimiters are allowed inside quoted strings,
        // {}, and () (recursive)
        $re = "/(?P<expr>(?:\n\n                # anything except \", ', (), {}, []\n                (?:[^(){}\\[\\]\"\\'\\\\{$delims}]+(?=(?P>expr)))\n                |(?:[^(){}\\[\\]\"\\'\\\\ {$delims}]+)\n\n                # double quoted string\n                | \"(?: [^\"\\\\]+ | \\\\[\\#\"\\\\] )*\"\n\n                # single quoted string\n                | '(?: [^'\\\\]+ | \\\\[\\#'\\\\] )*'\n\n                # { ... } pair\n                | \\{ (?: (?P>expr) | [ {$delims}] )* \\}\n\n                # ( ... ) pair\n                | \\( (?: (?P>expr) | [ {$delims}] )* \\)\n\n                # [ ... ] pair\n                | \\[ (?: (?P>expr) | [ {$delims}] )* \\]\n            )+)/xA";
        if ($buf->match($re, $match)) {
            return [$match[0], $match['pos'][0]];
        }
        throw $this->syntax_error_expected($buf, 'target language expression');
    }
    protected function parse_symbol(Buffer $buf)
    {
        if (!$buf->match('/:(\w+)/A', $match)) {
            throw $this->syntax_error_expected($buf, 'symbol');
        }
        return new Text($match['pos'][0], $match[1]);
    }
    protected function parse_interpolated_string(Buffer $buf, $quoted = true)
    {
        if ($quoted && !$buf->match('/"/A', $match)) {
            throw $this->syntax_error_expected($buf, 'double quoted string');
        }
        $node = new Interpolated_String($buf->get_position());
        if ($quoted) {
            $string_regex = '/(
                    [^\#"\\\\]+           # anything without hash or " or \
                    |\\\\(?:["\\\\]|\#\{) # or escaped quote slash or hash followed by {
                    |\#(?!\{)             # or hash, but not followed by {
                )+/Ax';
        } else {
            $string_regex = '/(
                    [^\#\\\\]+          # anything without hash or \
                    |\\\\(?:\#\{|[^\#]) # or escaped hash followed by { or anything without hash
                    |\#(?!\{)           # or hash, but not followed by {
                )+/Ax';
        }
        do {
            if ($buf->match($string_regex, $match)) {
                $text = $match[0];
                if ($quoted) {
                    // strip slashes
                    $text = preg_replace('/\\\\(["\\\\])/', '\1', $match[0]);
                }
                // strip back slash before hash followed by {
                $text = preg_replace('/\\\\\\#\{/', '#{', $text);
                $text = new Text($match['pos'][0], $text);
                $node->add_child($text);
            } elseif ($expr = $this->parse_interpolation($buf)) {
                $node->add_child($expr);
            } elseif ($quoted && $buf->match('/"/A')) {
                break;
            } elseif (!$quoted && $buf->match('/$/A')) {
                break;
            } else {
                throw $this->syntax_error_expected($buf, 'string or #{...}');
            }
        } while (true);
        // ensure that the InterpolatedString has at least one child
        if (0 === count($node->get_childs())) {
            $text = new Text($buf->get_position(), '');
            $node->add_child($text);
        }
        return $node;
    }
    protected function parse_interpolation(Buffer $buf)
    {
        // This matches an interpolation:
        // #{ expr... }
        $expr_regex = '/
            \#\{(?P<insert>(?P<expr>
                # do not allow {}"\' in expr
                [^\{\}"\']+
                # allow balanced {}
                | \{ (?P>expr)* \}
                # allow balanced \'
                | \'([^\'\\\\]+|\\\\[\'\\\\])*\'
                # allow balanced "
                | "([^"\\\\]+|\\\\["\\\\])*"
            )+)\}
            /AxU';
        if ($buf->match($expr_regex, $match)) {
            return new Insert($match['pos']['insert'], $match['insert']);
        }
    }
    protected function parse_nestable_statement(Buffer $buf)
    {
        if ($insert = $this->parse_insert($buf)) {
            return $insert;
        }
        if (null !== $comment = $this->parse_comment($buf)) {
            return $comment;
        }
        if ('\\' === $buf->peek_char()) {
            $buf->eat_char();
        }
        if (strlen(trim($buf->get_line())) > 0) {
            return $this->parse_interpolated_string($buf, false);
        }
    }
    protected function parse_insert(Buffer $buf)
    {
        if ($buf->match('/([&!]?)(==?|~)\s*/A', $match)) {
            if ($match[2] == '==') {
                $node = $this->parse_interpolated_string($buf, false);
            } else {
                $code = $this->get_multiline_code($buf);
                $node = new Insert($match['pos'][0], $code);
            }
            if ($match[1] == '&') {
                $node->get_escaping()->set_enabled(true);
            } elseif ($match[1] == '!') {
                $node->get_escaping()->set_enabled(false);
            }
            $buf->skip_ws();
            return $node;
        }
    }
    protected function parse_filter(Buffer $buf)
    {
        if (!$buf->match('/:(.*)/A', $match)) {
            return null;
        }
        $node = new Filter($match['pos'][0], $match[1]);
        while (null !== $next = $buf->peek_line()) {
            $indent = '';
            if ('' !== trim($next)) {
                $indent = $this->indent->get_string(1, $next);
                if ('' === $indent) {
                    break;
                }
                if (strpos($next, $indent) !== 0) {
                    break;
                }
            }
            $buf->next_line();
            $buf->eat_chars(strlen($indent));
            $str = $this->parse_interpolated_string($buf, false);
            $node->add_child(new Statement($str->get_position(), $str));
        }
        return $node;
    }
    protected function syntax_error_expected(Buffer $buf, $expected)
    {
        $unexpected = $buf->peek_char();
        if ($unexpected) {
            $unexpected = "'{$unexpected}'";
        } else {
            $unexpected = 'end of line';
        }
        $msg = sprintf('Unexpected %s, expected %s', $unexpected, $expected);
        return $this->syntax_error($buf, $msg);
    }
    protected function syntax_error(Buffer $buf, $msg)
    {
        $this->column = $buf->get_column();
        $this->lineno = $buf->get_lineno();
        $msg = sprintf('%s in %s on line %d, column %d', $msg, $this->filename, $this->lineno, $this->column);
        return new Syntax_Error_Exception($msg);
    }
    public function get_column()
    {
        return $this->column;
    }
    public function get_lineno()
    {
        return $this->lineno;
    }
    public function get_filename()
    {
        return $this->filename;
    }
}