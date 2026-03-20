<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Mt_Haml\Node\Filter;
use Mt_Haml\Node_Visitor\Renderer_Abstract as Renderer;
abstract class Less extends Abstract_Filter
{
    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        $renderer->write($this->filter($this->get_content($node), [], $options));
    }
    public function filter($content, array $context, $options)
    {
        $css = $this->get_css($content, $context, $options);
        if (isset($options['cdata']) && $options['cdata'] === true) {
            return "<style type=\"text/css\">\n/*<![CDATA[*/\n" . $css . "\n/*]]>*/\n</style>";
        }
        return "<style type=\"text/css\">\n" . $css . "\n</style>";
    }
    abstract protected function get_css($content, array $context, $options);
}