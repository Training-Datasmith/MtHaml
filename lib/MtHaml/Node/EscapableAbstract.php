<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Escaping;
abstract class Escapable_Abstract extends Node_Abstract
{
    private $escaping;
    public function get_escaping()
    {
        if (null === $this->escaping) {
            $this->escaping = new Escaping();
        }
        return $this->escaping;
    }
}