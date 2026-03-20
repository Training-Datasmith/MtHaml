<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Mt_Haml\Node\Filter;
use Mt_Haml\Node_Visitor\Renderer_Abstract as Renderer;
class Scss extends Abstract_Filter
{
    private $scss;
    public function __construct($scss)
    {
        if (!is_object($scss) || !is_a($scss, 'Leafo\ScssPhp\Compiler') && !is_a($scss, 'scssc')) {
            throw new \InvalidArgumentException(sprintf('Argument 1 passed to %s::__construct() must be an instance of %s or %s, %s given', __CLASS__, 'Leafo\ScssPhp\Compiler', 'scssc', is_object($scss) ? 'instance of ' . get_class($scss) : gettype($scss)));
        }
        $this->scss = $scss;
    }
    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        $renderer->write($this->filter($this->get_content($node), [], $options));
    }
    public function filter($content, array $context, $options)
    {
        if (isset($options['cdata']) && $options['cdata'] === true) {
            return "<style type=\"text/css\">\n/*<![CDATA[*/\n" . $this->scss->compile($content) . "\n/*]]>*/\n</style>";
        }
        return "<style type=\"text/css\">\n" . $this->scss->compile($content) . "\n</style>";
    }
}