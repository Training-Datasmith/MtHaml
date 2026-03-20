<?php

declare (strict_types=1);
namespace Mt_Haml\Filter\Less;

use Mt_Haml\Filter\Less;
class Leafo_Less extends Less
{
    private $less;
    public function __construct(\lessc $less)
    {
        $this->less = $less;
    }
    public function get_css($content, array $context, $options)
    {
        return $this->less->compile($content);
    }
}