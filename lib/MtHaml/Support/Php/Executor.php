<?php

declare (strict_types=1);
namespace Mt_Haml\Support\Php;

use Mt_Haml\Environment;
use Mt_Haml\Exception;
/**
 * Executor is a simple helper that can compile haml files, cache them, and execute them
 */
class Executor
{
    private $environment;
    private $options = [
        // Cache directory to store compiled templates
        'cache' => null,
        // Whether to by-pass cache, useful when debugging
        'debug' => false,
    ];
    public function __construct(Environment $environment, array $options)
    {
        $this->environment = $environment;
        $this->options = $options + $this->options;
        if (!$this->options['cache']) {
            throw new Exception("A 'cache' option must be defined");
        }
    }
    /**
     * Executes and displays the template $file, with variables $variables
     */
    public function display($file, array $variables)
    {
        $fun = $this->compile_file($file);
        $fun($variables);
    }
    /**
     * Executes the template $file with variables $variables, and returns its output
     */
    public function render($file, array $variables)
    {
        $level = ob_get_level();
        ob_start();
        try {
            $this->display($file, $variables);
        } catch (\Exception $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
        return ob_get_clean();
    }
    public function warmup($file)
    {
        $this->compile_file($file);
    }
    private function compile_file($file)
    {
        if (!file_exists($file)) {
            throw new Exception(sprintf('File does not exist: `%s`', $file));
        }
        $hash = hash('sha256', $file);
        $fun_name = '__MtHamlTemplate_' . $hash;
        if (function_exists($fun_name)) {
            return $fun_name;
        }
        $cache_file = $this->options['cache'] . '/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . substr($hash, 4) . '_' . basename($file) . '.php';
        $file_mtime = filemtime($file);
        if ($this->options['debug'] || !file_exists($cache_file) || filemtime($cache_file) !== $file_mtime) {
            $haml_code = file_get_contents($file);
            if (false === $haml_code) {
                throw new Exception(sprintf('Failed reading file: `%s`', $file));
            }
            $compiled_code = $this->environment->compile_string($haml_code, $file);
            $compiled_code = $this->wrap_compiled_code($compiled_code, $fun_name);
            $this->write_cache_file($cache_file, $compiled_code, $file_mtime);
        }
        require_once $cache_file;
        return $fun_name;
    }
    private function wrap_compiled_code($code, $fun_name)
    {
        // The code is wrapped in a function so that it can be parsed
        // once, and executed multiple times. This is faster than repeatedly
        // including the same PHP file.
        return <<<PHP
        <?php
        
        function {$fun_name}(\$__variables)
        {
            extract(\$__variables, EXTR_SKIP);
        ?>{$code}<?php
        }
        PHP;
    }
    private function write_cache_file($cache_file, $contents, $timestamp)
    {
        $dir = dirname($cache_file);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new Exception(sprintf('Failed creating cache directory: `%s`', $dir));
            }
        }
        if (!is_writeable($dir)) {
            throw new Exception(sprintf('Cache directory is not writeable: `%s`', $dir));
        }
        $tmp_file = tempnam($dir, basename($cache_file));
        if (false === file_put_contents($tmp_file, $contents)) {
            throw new Exception(sprintf('Failed writing cache file: `%s`', $tmp_file));
        }
        if (!rename($tmp_file, $cache_file)) {
            @unlink($tmp_file);
            throw new Exception(sprintf('Failed writing cache file: `%s`', $cache_file));
        }
        if (!touch($cache_file, $timestamp)) {
            throw new Exception(sprintf('Failed writing cache file: `%s`', $cache_file));
        }
    }
}