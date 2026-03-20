<?php

declare (strict_types=1);
namespace Mt_Haml\Indentation;

class Undefined implements Indentation_Interface
{
    public function new_level($indent)
    {
        if (0 === strlen($indent)) {
            return $this;
        }
        return Indentation::one_level($indent);
    }
    public function get_char()
    {
        return null;
    }
    public function get_width()
    {
        return null;
    }
    public function get_level()
    {
        return 0;
    }
    public function get_string($level_offset = 0, $fallback = null)
    {
        $char = substr($fallback, 0, 1);
        if (' ' === $char || "\t" === $char) {
            return $char;
        }
        return '';
    }
}