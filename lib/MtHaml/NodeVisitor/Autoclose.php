<?php

declare (strict_types=1);
namespace Mt_Haml\Node_Visitor;

use Mt_Haml\Node\Tag;
class Autoclose extends Node_Visitor_Abstract
{
    protected $autoclose_tags;
    public function __construct(array $autoclose_tags)
    {
        $this->autoclose_tags = $autoclose_tags;
    }
    public function enter_tag(Tag $tag)
    {
        if ($tag->has_childs() || $tag->has_content()) {
            return;
        }
        if (in_array($tag->get_tag_name(), $this->autoclose_tags)) {
            $tag->set_flag(Tag::FLAG_SELF_CLOSE);
        }
    }
}