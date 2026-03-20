<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

interface Nest_Interface
{
    public function add_child(Node_Abstract $child);
    public function has_content();
    public function allows_nesting_and_content();
}