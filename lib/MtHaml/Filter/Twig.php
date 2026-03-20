<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Mt_Haml\Node\Filter;
use Mt_Haml\Node_Visitor\Renderer_Abstract as Renderer;
use Mt_Haml\Node_Visitor\Twig_Renderer;
class Twig extends Abstract_Filter
{
    private $twig;
    public function __construct(\Twig_Environment $twig = null)
    {
        $this->twig = $twig;
        if (null !== $twig && !function_exists('twig_template_from_string')) {
            $twig->add_extension(new \Twig_extension_string_Loader());
        }
    }
    public function is_optimizable(Renderer $renderer, Filter $node, $options)
    {
        if (!$renderer instanceof Twig_Renderer) {
            return false;
        }
        return parent::is_optimizable($renderer, $node, $options);
    }
    public function optimize(Renderer $renderer, Filter $node, $options)
    {
        foreach ($node->get_childs() as $line) {
            $content = '';
            foreach ($line->get_content()->get_childs() as $child) {
                $content .= $child->get_content();
            }
            $renderer->write($content);
        }
    }
    public function filter($content, array $context, $options)
    {
        $template = twig_template_from_string($this->twig, $content);
        return $template->render($context);
    }
}