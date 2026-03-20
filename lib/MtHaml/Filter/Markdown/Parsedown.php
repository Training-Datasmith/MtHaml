<?php

declare (strict_types=1);
namespace Mt_Haml\Filter\Markdown;

use Mt_Haml\Filter\Markdown;
class Parsedown extends Markdown
{
    private $markdown;
    public function __construct(\Parsedown $markdown, $force_optimization = false)
    {
        parent::__construct($force_optimization);
        $this->markdown = $markdown;
    }
    public function filter($content, array $context, $options)
    {
        return $this->markdown->text($content);
    }
}