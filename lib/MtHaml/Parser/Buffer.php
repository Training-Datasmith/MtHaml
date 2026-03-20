<?php

declare (strict_types=1);
namespace Mt_Haml\Parser;

class Buffer
{
    protected $lines;
    protected $line;
    protected $lineno;
    protected $column;
    public function __construct($string, $lineno = 1)
    {
        $this->lines = preg_split('~\r\n|\n|\r~', $string);
        $this->lineno = $lineno - 1;
    }
    public function next_line()
    {
        $this->line = array_shift($this->lines);
        if (null !== $this->line) {
            ++$this->lineno;
            $this->column = 1;
            return true;
        }
        return false;
    }
    public function peek_line()
    {
        if (isset($this->lines[0])) {
            return $this->lines[0];
        }
    }
    public function has_next_line()
    {
        return isset($this->lines[0]);
    }
    public function merge_next_line()
    {
        if (!isset($this->lines[0])) {
            return;
        }
        ++$this->lineno;
        $this->line .= "\n" . array_shift($this->lines);
    }
    public function replace_line($string)
    {
        $this->line = $string;
    }
    public function is_eol()
    {
        return $this->line === '';
    }
    public function peek_char()
    {
        if (isset($this->line[0])) {
            return $this->line[0];
        }
    }
    public function eat_char()
    {
        if (isset($this->line[0])) {
            $char = $this->line[0];
            $this->line = (string) substr($this->line, 1);
            ++$this->column;
            return $char;
        }
    }
    public function eat_chars($n)
    {
        $chars = (string) substr($this->line, 0, $n);
        $this->line = (string) substr($this->line, $n);
        $this->column += strlen($chars);
        return $chars;
    }
    public function match($pattern, &$match = null, $eat = true)
    {
        if ($count = preg_match($pattern, $this->line, $match, PREG_OFFSET_CAPTURE)) {
            $pos = [];
            foreach ($match as $key => &$capture) {
                $pos[$key] = ['lineno' => $this->lineno, 'column' => $capture[1]];
                $capture = $capture[0];
            }
            unset($capture);
            // ref
            $match['pos'] = $pos;
            if ($eat) {
                $this->eat($match[0]);
            }
        }
        return $count > 0;
    }
    public function eat($string)
    {
        $this->line = (string) substr($this->line, strlen($string));
        $this->column += strlen($string);
    }
    public function skip_ws()
    {
        $this->match('~[ \t]+~A');
    }
    public function get_column()
    {
        return $this->column;
    }
    public function get_lineno()
    {
        return $this->lineno;
    }
    public function get_position()
    {
        return ['lineno' => $this->lineno, 'column' => $this->column];
    }
    public function get_line()
    {
        return $this->line;
    }
}