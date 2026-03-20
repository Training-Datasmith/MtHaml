<?php

declare(strict_types=1);

namespace MtHaml\Tests\Node;

use MtHaml\Node\Doctype;

class DoctypeTest extends \PHPUnit_Framework_TestCase
{
    /** @dataProvider getGetDoctypeReturnsDefaultOneWhenInvalidData */
    public function testGetDoctypeReturnsDefaultOneWhenInvalid($format, $doctype)
    {
        $node = new Doctype(['lineno' => 0, 'column' => 0], 'invalid', null);

        $result = @$node->getDoctype($format);
        $this->assertSame($doctype, $result);
    }

    public function getGetDoctypeReturnsDefaultOneWhenInvalidData()
    {
        return [
            ['html5', '<!DOCTYPE html>'],
            ['xhtml', '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'],
        ];
    }

    /** @dataProvider getGetDoctypeTriggersWarningWhenInvalidData */
    public function testGetDoctypeTriggersWarningWhenInvalid($format, $msg)
    {
        $node = new Doctype(['lineno' => 0, 'column' => 0], 'invalid', null);

        $e = null;

        try {
            $node->getDoctype($format);
        } catch (\Exception $e) {
        }

        $this->assertNotNull($e);
        $this->assertInstanceOf('PHPUnit_Framework_Error_Warning', $e);
        $this->assertSame($msg, $e->getMessage());

    }

    public function getGetDoctypeTriggersWarningWhenInvalidData()
    {
        return [
            ['html5', "No such doctype '!!! invalid' for the format 'html5'. Available doctypes for the current format are: '!!!', '!!! 5'"],
            ['xhtml', "No such doctype '!!! invalid' for the format 'xhtml'. Available doctypes for the current format are: '!!!', '!!! strict', '!!! frameset', '!!! 5', '!!! 1.1', '!!! basic', '!!! mobile', '!!! rdfa'"],
        ];
    }
}
