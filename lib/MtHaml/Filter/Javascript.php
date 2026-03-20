<?php

declare (strict_types=1);
namespace Mt_Haml\Filter;

use Mt_Haml\Node\Filter;
use Mt_Haml\Node_Visitor\Renderer_Abstract as Renderer;
class Javascript extends Plain
{
    public function optimize(Renderer $renderer, Filter $filter, $options)
    {
        $renderer->write('<script type="text/javascript">');
        if ($options['cdata'] === true) {
            $renderer->write('//<![CDATA[');
        }
        $renderer->indent();
        $this->render_filter($renderer, $filter);
        $renderer->undent();
        if ($options['cdata'] === true) {
            $renderer->write('//]]>');
        }
        $renderer->write('</script>');
    }
}