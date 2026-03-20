<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Gregwar\RST\Parser;
class Re_St extends Optimizable_Filter
{
    private $parser;
    public function __construct(Parser $parser, $force_optimization = false)
    {
        parent::__construct($force_optimization);
        $this->parser = $parser;
    }
    public function filter($content, array $context, $options)
    {
        return $this->parser->parse($content);
    }
}