<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Coffee_Script\Compiler;
use Mt_Haml\Node\Filter;
use Mt_Haml\Node_Visitor\Renderer_Abstract as Renderer;
class Coffee_Script extends Abstract_Filter
{
    private $coffeescript;
    private $options;
    public function __construct(Compiler $coffeescript, array $options = [])
    {
        $this->coffeescript = $coffeescript;
        $this->options = $options;
    }
    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        $renderer->write($this->filter($this->get_content($node), [], $options));
    }
    public function filter($content, array $context, $options)
    {
        if (isset($options['cdata']) && $options['cdata'] === true) {
            return "<script type=\"text/javascript\">\n//<![CDATA[\n" . $this->coffeescript->compile($content, $this->options) . "\n//]]>\n</script>";
        }
        return "<script type=\"text/javascript\">\n" . $this->coffeescript->compile($content, $this->options) . "\n</script>";
    }
}