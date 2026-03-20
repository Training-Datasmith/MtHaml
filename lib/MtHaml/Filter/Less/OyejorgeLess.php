<?php

declare (strict_types=1);
namespace Mt_Haml\Filter\Less;

use Mt_Haml\Filter\Less;
class Oyejorge_Less extends Less
{
    private $less;
    public function __construct(\Less_Parser $less)
    {
        $this->less = $less;
    }
    protected function get_css($content, array $context, $options)
    {
        $this->less->Reset(\Less_Parser::$options);
        $this->less->parse($content);
        return $this->less->get_css();
    }
}