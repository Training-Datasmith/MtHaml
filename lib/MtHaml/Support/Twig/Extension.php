<?php

declare (strict_types=1);
namespace Mt_Haml\Support\Twig;

use Mt_Haml\Environment;
class Extension extends \Twig_Extension
{
    private $mthaml;
    public function __construct(Environment $mthaml = null)
    {
        $this->mthaml = $mthaml;
    }
    public function get_functions()
    {
        return [new \Twig_simple_Function('mthaml_attributes', 'MtHaml\Runtime::renderAttributes'), new \Twig_simple_Function('mthaml_attribute_interpolation', 'MtHaml\Runtime\AttributeInterpolation::create'), new \Twig_simple_Function('mthaml_attribute_list', 'MtHaml\Runtime\AttributeList::create'), new \Twig_simple_Function('mthaml_object_ref_class', 'MtHaml\Runtime::renderObjectRefClass'), new \Twig_simple_Function('mthaml_object_ref_id', 'MtHaml\Runtime::renderObjectRefId')];
    }
    public function get_filters()
    {
        if (null === $this->mthaml) {
            return [];
        }
        return [new \Twig_simple_Filter('mthaml_*', [$this, 'filter'], ['needs_context' => true, 'is_safe' => ['html']])];
    }
    public function filter(array $context, $name, $content)
    {
        return $this->mthaml->get_filter($name)->filter($content, $context, $this->mthaml->get_options());
    }
    public function get_name()
    {
        return 'mthaml';
    }
}