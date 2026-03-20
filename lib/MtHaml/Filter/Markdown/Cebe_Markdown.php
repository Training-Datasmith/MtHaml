<?php

declare (strict_types=1);
namespace Mt_Haml\Filter\Markdown;

use cebe\markdown\Parser;
use Mt_Haml\Filter\Markdown;
class Cebe_Markdown extends Markdown
{
    private $markdown;
    public function __construct(Parser $markdown, $force_optimization = false)
    {
        parent::__construct($force_optimization);
        $this->markdown = $markdown;
    }
    public function filter($content, array $context, $options)
    {
        return $this->markdown->parse($content);
    }
}