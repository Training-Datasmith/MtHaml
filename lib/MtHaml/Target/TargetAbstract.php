<?php

declare (strict_types=1);
namespace Mt_Haml\Target;

use Mt_Haml\Environment;
use Mt_Haml\Node\Node_Abstract;
use Mt_Haml\Parser;
abstract class Target_Abstract implements Target_Interface
{
    protected $options = [];
    protected $parser_factory;
    protected $renderer_factory;
    public function __construct(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }
    public function get_default_parser_factory()
    {
        return function (Environment $env, array $options) {
            return new Parser();
        };
    }
    public function get_parser_factory()
    {
        if (null === $this->parser_factory) {
            $this->parser_factory = $this->get_default_parser_factory();
        }
        return $this->parser_factory;
    }
    public function set_parser_factory($factory)
    {
        $this->parser_factory = $factory;
    }
    public function create_parser(Environment $env, array $options)
    {
        return call_user_func($this->get_parser_factory(), $env, $options);
    }
    abstract public function get_default_renderer_factory();
    public function get_renderer_factory()
    {
        if (null === $this->renderer_factory) {
            $this->renderer_factory = $this->get_default_renderer_factory();
        }
        return $this->renderer_factory;
    }
    public function set_renderer_factory($factory)
    {
        $this->renderer_factory = $factory;
    }
    public function create_renderer(Environment $env, array $options)
    {
        return call_user_func($this->get_renderer_factory(), $env, $options);
    }
    public function parse(Environment $env, $string, $filename)
    {
        $parser = $this->create_parser($env, $this->options);
        return $parser->parse($string, $filename);
    }
    public function compile(Environment $env, Node_Abstract $node)
    {
        $renderer = $this->create_renderer($env, []);
        $node->accept($renderer);
        return $renderer->get_output();
    }
    public function get_option($name)
    {
        return $this->options[$name];
    }
}