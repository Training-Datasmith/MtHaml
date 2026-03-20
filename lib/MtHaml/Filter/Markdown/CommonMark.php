<?php

declare (strict_types=1);
namespace Mt_Haml\Filter\Markdown;

use League\Common_Mark\Converter;
use Mt_Haml\Filter\Markdown;
class Common_Mark extends Markdown
{
    private $converter;
    public function __construct(Converter $converter, $force_optimization = false)
    {
        parent::__construct($force_optimization);
        $this->converter = $converter;
    }
    public function filter($content, array $context, $options)
    {
        return $this->converter->convert_to_html($content);
    }
}