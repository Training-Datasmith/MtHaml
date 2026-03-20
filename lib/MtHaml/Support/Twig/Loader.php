<?php

declare (strict_types=1);
namespace Mt_Haml\Support\Twig;

use Mt_Haml\Environment;
/**
 * Example integration of MtHaml with Twig, by proxying the Loader
 *
 * This loader will parse Twig templates as HAML if their filename end with
 * `.haml`, or if the code starts with `{% haml %}`.
 *
 * Alternatively, use MtHaml\Support\Twig\Lexer.
 *
 * <code>
 * $origLoader = $twig->getLoader();
 * $twig->setLoader($mthaml, new \MtHaml\Support\Twig\Loader($origLoader));
 * </code>
 */
class Loader implements \Twig_loader_Interface, \Twig_exists_Loader_Interface, \Twig_source_Context_Loader_Interface
{
    protected $env;
    protected $loader;
    public function __construct(Environment $env, \Twig_loader_Interface $loader)
    {
        $this->env = $env;
        $this->loader = $loader;
    }
    /**
     * Deprecated in Twig 1.27
     * Removed in Twig 2.x
     * {@inheritdoc}
     */
    public function get_source($name)
    {
        $source = $this->loader->get_source($name);
        if ('haml' === pathinfo($name, PATHINFO_EXTENSION)) {
            $source = $this->env->compile_string($source, $name);
        } elseif (preg_match('#^\s*{%\s*haml\s*%}#', $source, $match)) {
            $padding = str_repeat(' ', strlen($match[0]));
            $source = $padding . substr($source, strlen($match[0]));
            $source = $this->env->compile_string($source, $name);
        }
        return $source;
    }
    /**
     * Support Twig 2.x
     * {@inheritdoc}
     */
    public function get_source_context($name)
    {
        $context = $this->loader->get_source_context($name);
        $source = $context->get_code();
        if ('haml' === pathinfo($name, PATHINFO_EXTENSION)) {
            $source = $this->env->compile_string($source, $name);
        } elseif (preg_match('#^\s*{%\s*haml\s*%}#', $source, $match)) {
            $padding = str_repeat(' ', strlen($match[0]));
            $source = $padding . substr($source, strlen($match[0]));
            $source = $this->env->compile_string($source, $name);
        }
        return new \Twig\Source($source, $context->get_name(), $context->get_path());
    }
    /**
     * {@inheritdoc}
     */
    public function get_cache_key($name)
    {
        return $this->loader->get_cache_key($name);
    }
    /**
     * {@inheritdoc}
     */
    public function is_fresh($name, $time)
    {
        return $this->loader->is_fresh($name, $time);
    }
    /**
     * {@inheritdoc}
     */
    public function exists($name)
    {
        if ($this->loader instanceof \Twig_exists_Loader_Interface) {
            return $this->loader->exists($name);
        }
        try {
            $this->loader->get_source($name);
            return true;
        } catch (\Twig_Error_Loader $e) {
            return false;
        }
    }
}