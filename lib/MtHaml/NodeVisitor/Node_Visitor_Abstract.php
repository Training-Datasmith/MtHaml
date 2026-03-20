<?php

declare (strict_types=1);
namespace Mt_Haml\Node_Visitor;

use Mt_Haml\Node\Comment;
use Mt_Haml\Node\Doctype;
use Mt_Haml\Node\Filter;
use Mt_Haml\Node\Insert;
use Mt_Haml\Node\Interpolated_String;
use Mt_Haml\Node\Node_Abstract;
use Mt_Haml\Node\Object_Ref_Class;
use Mt_Haml\Node\Object_Ref_Id;
use Mt_Haml\Node\Root;
use Mt_Haml\Node\Run;
use Mt_Haml\Node\Statement;
use Mt_Haml\Node\Tag;
use Mt_Haml\Node\Tag_Attribute;
use Mt_Haml\Node\Tag_Attribute_Interpolation;
use Mt_Haml\Node\Tag_Attribute_List;
use Mt_Haml\Node\Text;
/**
 * Abstract node visitor
 *
 * All traversing logic is outside of the visitors.
 */
abstract class Node_Visitor_Abstract implements Node_Visitor_Interface
{
    public function enter_comment(Comment $node)
    {
    }
    public function enter_comment_content(Comment $node)
    {
    }
    public function leave_comment_content(comment $node)
    {
    }
    public function enter_comment_childs(Comment $node)
    {
    }
    public function leave_comment_childs(Comment $node)
    {
    }
    public function leave_comment(Comment $node)
    {
    }
    public function enter_doctype(Doctype $node)
    {
    }
    public function leave_doctype(Doctype $node)
    {
    }
    public function enter_insert(Insert $node)
    {
    }
    public function leave_insert(Insert $node)
    {
    }
    public function enter_interpolated_string(Interpolated_String $node)
    {
    }
    public function enter_interpolated_string_childs(Interpolated_String $node)
    {
    }
    public function leave_interpolated_string_childs(Interpolated_String $node)
    {
    }
    public function leave_interpolated_string(Interpolated_String $node)
    {
    }
    public function enter_root(Root $node)
    {
    }
    public function enter_root_content(Root $node)
    {
    }
    public function leave_root_content(Root $node)
    {
    }
    public function enter_root_childs(Root $node)
    {
    }
    public function leave_root_childs(Root $node)
    {
    }
    public function leave_root(Root $node)
    {
    }
    public function enter_run(Run $node)
    {
    }
    public function enter_run_childs(Run $node)
    {
    }
    public function leave_run_childs(Run $node)
    {
    }
    public function enter_run_midblock(Run $node)
    {
    }
    public function leave_run_midblock(Run $node)
    {
    }
    public function leave_run(Run $node)
    {
    }
    public function enter_statement(Statement $node)
    {
    }
    public function enter_statement_content(Statement $node)
    {
    }
    public function leave_statement_content(Statement $node)
    {
    }
    public function leave_statement(Statement $node)
    {
    }
    public function enter_tag(Tag $node)
    {
    }
    public function enter_tag_attributes(Tag $node)
    {
    }
    public function leave_tag_attributes(Tag $node)
    {
    }
    public function enter_tag_content(Tag $node)
    {
    }
    public function leave_tag_content(Tag $node)
    {
    }
    public function enter_tag_childs(Tag $node)
    {
    }
    public function leave_tag_childs(Tag $node)
    {
    }
    public function leave_tag(Tag $node)
    {
    }
    public function enter_tag_attribute(Tag_Attribute $node)
    {
    }
    public function enter_tag_attribute_name(Tag_Attribute $node)
    {
    }
    public function leave_tag_attribute_name(Tag_Attribute $node)
    {
    }
    public function enter_tag_attribute_value(Tag_Attribute $node)
    {
    }
    public function leave_tag_attribute_value(Tag_Attribute $node)
    {
    }
    public function enter_tag_attribute_interpolation(Tag_Attribute_Interpolation $node)
    {
    }
    public function leave_tag_attribute_interpolation(Tag_Attribute_Interpolation $node)
    {
    }
    public function enter_tag_attribute_list(Tag_Attribute_List $node)
    {
    }
    public function leave_tag_attribute_list(Tag_Attribute_List $node)
    {
    }
    public function leave_tag_attribute(Tag_Attribute $node)
    {
    }
    public function enter_object_ref_class(Object_Ref_Class $node)
    {
    }
    public function leave_object_ref_class(Object_Ref_Class $node)
    {
    }
    public function enter_object_ref_id(Object_Ref_Id $node)
    {
    }
    public function leave_object_ref_id(Object_Ref_Id $node)
    {
    }
    public function enter_object_ref_object(Node_Abstract $node)
    {
    }
    public function leave_object_ref_object(Node_Abstract $node)
    {
    }
    public function enter_object_ref_prefix(Node_Abstract $node)
    {
    }
    public function leave_object_ref_prefix(Node_Abstract $node)
    {
    }
    public function enter_text(Text $node)
    {
    }
    public function leave_text(Text $node)
    {
    }
    public function enter_filter(Filter $node)
    {
    }
    public function enter_filter_childs(Filter $node)
    {
    }
    public function leave_filter_childs(Filter $node)
    {
    }
    public function leave_filter(Filter $node)
    {
    }
}