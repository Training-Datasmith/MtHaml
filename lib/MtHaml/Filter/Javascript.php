<?php

declare(strict_types=1);

namespace MtHaml\Filter;

use MtHaml\Node\Filter;
use MtHaml\NodeVisitor\RendererAbstract as Renderer;

class Javascript extends Plain
{
    public function optimize(Renderer $renderer, Filter $filter, $options)
    {
        $renderer->write('<script type="text/javascript">');
        if ($options['cdata'] === true) {
            $renderer->write('//<![CDATA[');
        }

        $renderer->indent();
        $this->renderFilter($renderer, $filter);
        $renderer->undent();

        if ($options['cdata'] === true) {
            $renderer->write('//]]>');
        }
        $renderer->write('</script>');
    }
}
