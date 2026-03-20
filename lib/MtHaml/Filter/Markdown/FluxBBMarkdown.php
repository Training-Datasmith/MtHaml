<?php

declare (strict_types=1);
namespace Mt_Haml\Filter\Markdown;

use Flux_Bb\Common_Mark\Document_Parser;
use Flux_Bb\Common_Mark\Renderer;
use Mt_Haml\Filter\Markdown;
class Flux_Bb_Markdown extends Markdown
{
    private $parser;
    private $renderer;
    public function __construct(Document_Parser $parser, Renderer $renderer, $force_optimization = false)
    {
        parent::__construct($force_optimization);
        $this->parser = $parser;
        $this->renderer = $renderer;
    }
    public function filter($content, array $context, $options)
    {
        return $this->renderer->render($this->parser->convert($content));
    }
}