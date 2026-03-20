<?php

declare (strict_types=1);
namespace Mt_Haml\Indentation;

class Indentation implements Indentation_Interface
{
    private $char;
    private $width;
    private $level;
    public static function one_level($indent)
    {
        $char = self::get_indent_char($indent);
        $width = strlen($indent);
        return new self($char, $width, $indent);
    }
    public function new_level($indent)
    {
        if (0 === strlen($indent)) {
            return new self($this->char, $this->width, $indent);
        }
        $char = self::get_indent_char($indent);
        $this->check_same_char($char);
        $new = new self($this->char, $this->width, $indent);
        if ($new->level > $this->level + 1) {
            throw new Indentation_Exception('The line was indented more than one level deeper than the previous line');
        }
        return $new;
    }
    public function get_char()
    {
        return $this->char;
    }
    public function get_width()
    {
        return $this->width;
    }
    public function get_level()
    {
        return $this->level;
    }
    public function get_string($level_offset = 0, $fallback = null)
    {
        $length = $this->width * ($this->level + $level_offset);
        return str_repeat($this->char, $length);
    }
    private static function get_indent_char($indent)
    {
        $char = count_chars($indent, 3);
        if (1 !== strlen($char)) {
            throw new Indentation_Exception("Indentation can't use both tabs and spaces");
        }
        if (' ' !== $char && "\t" !== $char) {
            throw new Indentation_Exception('Indentation can use only tabs or spaces');
        }
        return $char;
    }
    private function __construct($char, $width, $indent)
    {
        $this->char = $char;
        $this->width = $width;
        if (0 !== strlen($indent) % $width) {
            $msg = sprintf('Inconsistent indentation: %d is not a multiple of %d', strlen($indent), $this->width);
            throw new Indentation_Exception($msg);
        }
        $this->level = strlen($indent) / $width;
    }
    private function check_same_char($char)
    {
        if ($char !== $this->char) {
            $expected = $this->char === ' ' ? 'spaces' : 'tabs';
            $actual = $char === ' ' ? 'spaces' : 'tabs';
            $msg = sprintf('Inconsistent indentation: %s were used for indentation, but the rest of the document was indented using %s', $actual, $expected);
            throw new Indentation_Exception($msg);
        }
    }
}