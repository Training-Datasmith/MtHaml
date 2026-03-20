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
class Environment
{
    protected $options = ['format' => 'html5', 'enable_escaper' => true, 'escape_html' => true, 'escape_attrs' => true, 'cdata' => true, 'autoclose' => ['meta', 'img', 'link', 'br', 'hr', 'input', 'area', 'param', 'col', 'base'], 'charset' => 'UTF-8', 'enable_dynamic_attrs' => true];
    protected $filters = ['css' => 'MtHaml\Filter\Css', 'cdata' => 'MtHaml\Filter\Cdata', 'escaped' => 'MtHaml\Filter\Escaped', 'javascript' => 'MtHaml\Filter\Javascript', 'php' => 'MtHaml\Filter\Php', 'plain' => 'MtHaml\Filter\Plain', 'preserve' => 'MtHaml\Filter\Preserve', 'twig' => 'MtHaml\Filter\Twig'];
    protected $target;
    public function __construct($target, array $options = [], $filters = [])
    {
        $this->target = $target;
        $this->options = $options + $this->options;
        $this->filters = $filters + $this->filters;
    }
    public function compile_string($string, $filename)
    {
        $target = $this->get_target();
        $node = $target->parse($this, $string, $filename);
        foreach ($this->get_visitors() as $visitor) {
            $node->accept($visitor);
        }
        return $target->compile($this, $node, $filename);
    }
    public function get_options()
    {
        return $this->options;
    }
    public function get_option($name)
    {
        return $this->options[$name];
    }
    /**
     * Returns a filter
     *
     * @param $name A name of filter
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     *
     * @return FilterInterface
     */
    public function get_filter($name)
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
    public function add_filter($name, $filter)
    {
        if (!is_string($filter) && !(is_object($filter) && $filter instanceof Filter_Interface)) {
            throw new \InvalidArgumentException('Filter should be a class name or an instance of FilterInterface');
        }
        $this->filters[$name] = $filter;
        return $this;
    }
    public function get_target()
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
                    throw new Exception(sprintf('Unknown target language: %s', $target));
            }
            $this->target = $target;
        }
        return $target;
    }
    public function get_visitors()
    {
        $visitors = [];
        $visitors[] = $this->get_autoclosevisitor();
        $visitors[] = $this->get_midblock_visitor();
        $visitors[] = $this->get_merge_attrs_visitor();
        if ($this->get_option('enable_escaper')) {
            $visitors[] = $this->get_escaping_visitor();
        }
        return $visitors;
    }
    public function get_escaping_visitor()
    {
        $html = Escaping_Visitor::ESCAPE_TRUE;
        if (!$this->get_option('escape_html')) {
            $html = Escaping_Visitor::ESCAPE_FALSE;
        }
        $attrs = Escaping_Visitor::ESCAPE_TRUE;
        if ('once' === $this->get_option('escape_attrs')) {
            $attrs = Escaping_Visitor::ESCAPE_ONCE;
        } elseif (!$this->get_option('escape_attrs')) {
            $attrs = Escaping_Visitor::ESCAPE_FALSE;
        }
        return new Escaping_Visitor($html, $attrs);
    }
    public function get_autoclose_visitor()
    {
        return new Autoclose($this->get_option('autoclose'));
    }
    public function get_midblock_visitor()
    {
        return new Midblock($this->get_target()->get_option('midblock_regex'));
    }
    public function get_merge_attrs_visitor()
    {
        return new Merge_Attrs();
    }
}