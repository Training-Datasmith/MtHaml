<?php

declare(strict_types=1);

namespace MtHaml\Node;

use MtHaml\Escaping;

abstract class EscapableAbstract extends NodeAbstract
{
    private $escaping;

    public function getEscaping()
    {
        if (null === $this->escaping) {
            $this->escaping = new Escaping();
        }

        return $this->escaping;
    }
}
