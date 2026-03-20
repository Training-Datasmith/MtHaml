<?php

declare (strict_types=1);
namespace Mt_Haml\Node;

use Mt_Haml\Node_Visitor\Node_Visitor_Interface;
/**
 * Doctype Node
 */
class Doctype extends Node_Abstract
{
    protected $doctype_id;
    protected $options;
    protected $doctypes = ['xhtml' => ['' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">', 'strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">', 'frameset' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">', '5' => '<!DOCTYPE html>', '1.1' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">', 'basic' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">', 'mobile' => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">', 'rdfa' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">'], 'html4' => ['' => '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">', 'strict' => '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">', 'frameset' => '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">'], 'html5' => ['' => '<!DOCTYPE html>', '5' => '<!DOCTYPE html>']];
    public function __construct(array $position, $doctype_id, $options)
    {
        parent::__construct($position);
        $this->doctype_id = $doctype_id;
        $this->options = $options;
    }
    public function get_doctype($format)
    {
        $lcid = strtolower($this->doctype_id);
        if ($lcid === 'xml') {
            if ('xhtml' !== $format) {
                return '';
            }
            if (!empty($this->options)) {
                return sprintf("<?xml version='1.0' encoding='%s' ?>", htmlspecialchars($this->options, ENT_QUOTES, 'UTF-8'));
            }
            return "<?xml version='1.0' encoding='utf-8' ?>";
        }
        if (empty($lcid)) {
            return $this->doctypes[$format][''];
        }
        if (isset($this->doctypes[$format][$lcid])) {
            return $this->doctypes[$format][$lcid];
        }
        $doctypes = [];
        foreach ($this->doctypes[$format] as $key => $doctype) {
            $doctypes[] = "'" . trim('!!! ' . $key) . "'";
        }
        trigger_error(sprintf("No such doctype '!!! %s' for the format '%s'. Available doctypes for the current format are: %s", $this->doctype_id, $format, implode(', ', $doctypes)), E_USER_WARNING);
        return $this->doctypes[$format][''];
    }
    public function get_doctype_id()
    {
        return $this->doctype_id;
    }
    public function get_options()
    {
        return $this->options;
    }
    public function get_node_name()
    {
        return 'doctype';
    }
    public function accept(Node_Visitor_Interface $visitor)
    {
        $visitor->enter_doctype($this);
        $visitor->leave_doctype($this);
    }
}