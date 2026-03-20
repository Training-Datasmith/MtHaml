<?php

declare (strict_types=1);
namespace Mt_Haml;

use Mt_Haml\Filter\Filter_Interface;
use Mt_Haml\Node_Visitor\Autoclose;
use Mt_Haml\Node_Visitor\Escaping as EscapingVisitor;
use Mt_Haml\Node_Visitor\Merge_Attrs;
use Mt_Haml\Node_Visitor\Midblock;
use Mt_Haml\Target\Php;
use Mt_Haml\Target\Twig;
/**
 * Top-level configuration and compilation entry point for MtHaml.
 *
 * Create an Environment once per application (or once per target type),
 * then call compile_string() for each HAML source you need to compile.
 */
class Environment
{
    /**
     * Default compilation options.
     *
     * @var array<string, mixed>
     */
    protected array $options = [
        'format'               => 'html5',
        'enable_escaper'       => true,
        'escape_html'          => true,
        'escape_attrs'         => true,
        'cdata'                => true,
        'autoclose'            => ['meta', 'img', 'link', 'br', 'hr', 'input', 'area', 'param', 'col', 'base'],
        'charset'              => 'UTF-8',
        'enable_dynamic_attrs' => true,
    ];

    /**
     * Registered filter map: name => class-name string or FilterInterface instance.
     *
     * @var array<string, string|Filter_Interface>
     */
    protected array $filters = [
        'css'        => 'MtHaml\Filter\Css',
        'cdata'      => 'MtHaml\Filter\Cdata',
        'escaped'    => 'MtHaml\Filter\Escaped',
        'javascript' => 'MtHaml\Filter\Javascript',
        'php'        => 'MtHaml\Filter\Php',
        'plain'      => 'MtHaml\Filter\Plain',
        'preserve'   => 'MtHaml\Filter\Preserve',
        'twig'       => 'MtHaml\Filter\Twig',
    ];

    /** @var string|\Mt_Haml\Target\Target_Interface The output target ('php', 'twig', or a Target instance) */
    protected $target;

    /**
     * Creates a new HAML compilation environment.
     *
     * @param string|\Mt_Haml\Target\Target_Interface $target  Output target: 'php', 'twig', or a Target_Interface instance
     * @param array<string, mixed>                    $options Option overrides merged over the defaults
     * @param array<string, string|Filter_Interface>  $filters Additional or replacement filter definitions
     */
    public function __construct($target, array $options = [], array $filters = [])
    {
        $this->target  = $target;
        $this->options = $options + $this->options;
        $this->filters = $filters + $this->filters;
    }

    /**
     * Compiles a HAML source string to the target language (PHP or Twig).
     *
     * Parses the HAML string into an AST, runs all visitor passes, then
     * delegates to the target to render the AST to a string.
     *
     * @param string $string   HAML source string to compile
     * @param string $filename Filename used in parse error messages (does not need to exist)
     *
     * @return string Compiled PHP or Twig source code
     *
     * @complexity O(n) where n is the length of the HAML source string
     */
    public function compile_string(string $string, string $filename): string
    {
        $target = $this->get_target();
        $node   = $target->parse($this, $string, $filename);
        foreach ($this->get_visitors() as $visitor) {
            $node->accept($visitor);
        }
        return $target->compile($this, $node, $filename);
    }

    /**
     * Returns all resolved compilation options.
     *
     * @return array<string, mixed> Current option values
     */
    public function get_options(): array
    {
        return $this->options;
    }

    /**
     * Returns the value of a single compilation option by name.
     *
     * @param string $name Option name (e.g. 'format', 'charset', 'escape_html')
     *
     * @return mixed The option value, or null if not set
     */
    public function get_option(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    /**
     * Returns a filter instance for the given filter name.
     *
     * If the filter is stored as a class name string it is instantiated on first
     * access and cached for subsequent calls.
     *
     * @param string $name The filter name as used in HAML :filter blocks
     *
     * @return Filter_Interface The resolved filter instance
     *
     * @throws \InvalidArgumentException If the filter name is not registered
     * @throws \RuntimeException         If the filter class name does not exist
     */
    public function get_filter(string $name): Filter_Interface
    {
        if (!isset($this->filters[$name])) {
            throw new \InvalidArgumentException(sprintf('Unknown filter name "%s"', $name));
        }
        $filter = $this->filters[$name];
        if (is_string($filter)) {
            if (!class_exists($filter)) {
                throw new \RuntimeException(sprintf('Class "%s" for filter "%s" does not exists', $filter, $name));
            }
            $filter = new $filter();
            $this->add_filter($name, $filter);
        }
        return $filter;
    }
    /**
     * Registers a filter under the given name, replacing any existing registration.
     *
     * @param string                    $name   The filter name as used in HAML :filter blocks
     * @param string|Filter_Interface   $filter A fully-qualified class name or a Filter_Interface instance
     *
     * @return static Fluent interface
     *
     * @throws \InvalidArgumentException If $filter is neither a class-name string nor a Filter_Interface instance
     */
    public function add_filter(string $name, $filter): static
    {
        if (!is_string($filter) && !(is_object($filter) && $filter instanceof Filter_Interface)) {
            throw new \InvalidArgumentException('Filter should be a class name or an instance of FilterInterface');
        }
        $this->filters[$name] = $filter;
        return $this;
    }

    /**
     * Returns the resolved compilation target instance.
     *
     * If the target was provided as a string ('php' or 'twig'), it is instantiated
     * on first call and cached for subsequent calls.
     *
     * @return \Mt_Haml\Target\Target_Interface The resolved compilation target
     *
     * @throws \Exception If the target string does not match a known target language
     */
    public function get_target(): \Mt_Haml\Target\Target_Interface
    {
        $target = $this->target;
        if (is_string($target)) {
            switch ($target) {
                case 'php':
                    $target = new Php();
                    break;
                case 'twig':
                    $target = new Twig();
                    break;
                default:
                    throw new \Exception(sprintf('Unknown target language: %s', $target));
            }
            $this->target = $target;
        }
        return $target;
    }

    /**
     * Returns the ordered list of node visitors to apply during compilation.
     *
     * Visitors are applied in the following order:
     *   1. Autoclose — inserts self-closing tags for void elements
     *   2. Midblock  — wraps mid-block keywords (else, elsif, …) for the target
     *   3. Merge_Attrs — collapses duplicate attribute keys
     *   4. Escaping (optional) — marks nodes for HTML / attribute escaping
     *
     * @return array<int, \Mt_Haml\Node_Visitor\Node_Visitor_Interface> Ordered visitor instances
     */
    public function get_visitors(): array
    {
        $visitors   = [];
        $visitors[] = $this->get_autoclose_visitor();
        $visitors[] = $this->get_midblock_visitor();
        $visitors[] = $this->get_merge_attrs_visitor();
        if ($this->get_option('enable_escaper')) {
            $visitors[] = $this->get_escaping_visitor();
        }
        return $visitors;
    }
    /**
     * Creates the escaping visitor configured from the current options.
     *
     * Reads 'escape_html' and 'escape_attrs' options to determine the escaping
     * mode for HTML content nodes and attribute values respectively.
     *
     * @return EscapingVisitor Configured escaping visitor instance
     */
    public function get_escaping_visitor(): EscapingVisitor
    {
        $html = EscapingVisitor::ESCAPE_TRUE;
        if (!$this->get_option('escape_html')) {
            $html = EscapingVisitor::ESCAPE_FALSE;
        }
        $attrs = EscapingVisitor::ESCAPE_TRUE;
        if ('once' === $this->get_option('escape_attrs')) {
            $attrs = EscapingVisitor::ESCAPE_ONCE;
        } elseif (!$this->get_option('escape_attrs')) {
            $attrs = EscapingVisitor::ESCAPE_FALSE;
        }
        return new EscapingVisitor($html, $attrs);
    }

    /**
     * Creates the autoclose visitor from the 'autoclose' option.
     *
     * The visitor marks void HTML elements (meta, img, br, etc.) as self-closing
     * so the renderer emits `<br />` instead of `<br></br>`.
     *
     * @return Autoclose Visitor that handles void/self-closing elements
     */
    public function get_autoclose_visitor(): Autoclose
    {
        return new Autoclose($this->get_option('autoclose'));
    }

    /**
     * Creates the midblock visitor for the current target language.
     *
     * The midblock regex is target-specific (e.g. PHP mid-block keywords like
     * else/elseif vs. Twig's else/elseif blocks).
     *
     * @return Midblock Visitor that restructures mid-block control-flow nodes
     */
    public function get_midblock_visitor(): Midblock
    {
        return new Midblock($this->get_target()->get_option('midblock_regex'));
    }

    /**
     * Creates the merge-attributes visitor.
     *
     * Merges duplicate HTML attribute keys within a single tag node into a single
     * attribute node, which is required before the target renders the tag.
     *
     * @return Merge_Attrs Visitor that deduplicates attribute keys
     */
    public function get_merge_attrs_visitor(): Merge_Attrs
    {
        return new Merge_Attrs();
    }
}