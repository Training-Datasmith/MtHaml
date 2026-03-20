<?php

declare (strict_types=1);
namespace Mt_Haml\Support\Twig;

use Mt_Haml\Environment;
/**
 * Example integration of MtHaml with Twig, by proxying the Lexer
 *
 * This lexer will parse Twig templates as HAML if their filename end with
 * `.haml`, or if the code starts with `{% haml %}`.
 *
 * Alternatively, use MtHaml\Support\Twig\Loader.
 *
 * <code>
 * $lexer = new \MtHaml\Support\Twig\Lexer($mthaml);
 * $lexer->setLexer($twig->getLexer());
 * $twig->setLexer($lexer);
 * </code>
 */
class Lexer implements \Twig_lexer_Interface
{
    protected $env;
    protected $lexer;
    public function __construct(Environment $env)
    {
        $this->env = $env;
    }
    public function set_lexer(\Twig_lexer_Interface $lexer)
    {
        $this->lexer = $lexer;
    }
    public function tokenize($code, $filename = null)
    {
        if (preg_match('#^\s*{%\s*haml\s*%}#', $code, $match)) {
            $padding = str_repeat(' ', strlen($match[0]));
            $code = $padding . substr($code, strlen($match[0]));
            $code = $this->env->compile_string($code, $filename);
        } elseif (null !== $filename && 'haml' === pathinfo($filename, PATHINFO_EXTENSION)) {
            $code = $this->env->compile_string($code, $filename);
        }
        return $this->lexer->tokenize($code, $filename);
    }
}