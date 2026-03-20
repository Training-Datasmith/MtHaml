<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Mt_Haml\Node\Filter;
use Mt_Haml\Node_Visitor\Php_Renderer;
use Mt_Haml\Node_Visitor\Renderer_Abstract as Renderer;
class Php extends Abstract_Filter
{
    public function is_optimizable(Renderer $renderer, Filter $node, $options)
    {
        if (!$renderer instanceof Php_Renderer) {
            return false;
        }
        return parent::is_optimizable($renderer, $node, $options);
    }
    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        $renderer->write('<?php')->indent();
        $this->render_filter($renderer, $node);
        $renderer->undent()->write('?>');
    }
    public function filter($content, array $context, $options)
    {
        $__content__ = '?><?php ' . $content;
        unset($options, $content);
        extract($context, EXTR_SKIP);
        ob_start();
        eval($__content__);
        return ob_get_clean();
    }
}