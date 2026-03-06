<?php

declare(strict_types=1);

namespace MtHaml\Node;

interface NestInterface
{
    public function addChild(NodeAbstract $child);
    public function hasContent();
    public function allowsNestingAndContent();
}
