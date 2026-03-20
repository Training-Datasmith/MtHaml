<?php

declare (strict_types=1);
namespace Mt_Haml;

class Escaping
{
    protected $enabled;
    protected $once;
    public function set_enabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }
    public function is_enabled()
    {
        return $this->enabled;
    }
    public function set_once($once)
    {
        $this->once = $once;
        return $this;
    }
    public function is_once()
    {
        return $this->once;
    }
}