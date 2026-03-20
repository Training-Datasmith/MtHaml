<?php

declare(strict_types=1);

namespace MtHaml\Tests;

use MtHaml\Indentation\Undefined;

class IndentationTest extends \PHPUnit_Framework_TestCase
{
    /** @dataProvider getTransitionData */
    public function testTransition($expectChar, $expectWidth, $expectLevel, $expectString, array $transitions)
    {
        $i = new Undefined();
        foreach ($transitions as $transition) {
            $i = $i->newLevel($transition);
        }
        $this->assertSame($expectChar, $i->getChar());
        $this->assertSame($expectWidth, $i->getWidth());
        $this->assertSame($expectLevel, $i->getLevel());
        $this->assertSame($expectString, $i->getString());
    }

    public function getTransitionData()
    {
        return [
            'none' => [
                'char' => null,
                'width' => null,
                'level' => 0,
                'string' => '',
                [''],
            ],
            'one' => [
                'char' => ' ',
                'width' => 2,
                'level' => 1,
                'string' => '  ',
                ['  '],
            ],
            'two' => [
                'char' => ' ',
                'width' => 2,
                'level' => 2,
                'string' => '    ',
                ['  ', '    '],
            ],
            'two, 3 spaces' => [
                'char' => ' ',
                'width' => 3,
                'level' => 2,
                'string' => '      ',
                ['   ', '      '],
            ],
            'two, 4 spaces' => [
                'char' => ' ',
                'width' => 4,
                'level' => 2,
                'string' => '        ',
                ['    ', '        '],
            ],
            'two, tabs' => [
                'char' => "\t",
                'width' => 1,
                'level' => 2,
                'string' => "\t\t",
                ["\t", "\t\t"],
            ],
            'same level' => [
                'char' => ' ',
                'width' => 2,
                'level' => 2,
                'string' => '    ',
                ['  ', '    ', '    '],
            ],
            'undent' => [
                'char' => ' ',
                'width' => 2,
                'level' => 1,
                'string' => '  ',
                ['  ', '    ', '  '],
            ],
            'undent many' => [
                'char' => ' ',
                'width' => 2,
                'level' => 1,
                'string' => '  ',
                ['  ', '    ', '      ', '  '],
            ],
            'undent to zero' => [
                'char' => ' ',
                'width' => 2,
                'level' => 0,
                'string' => '',
                ['  ', '    ', '      ', ''],
            ],
        ];
    }

    /**
     * @expectedException MtHaml\Indentation\IndentationException
     * @expectedExceptionMessage Indentation can use only tabs or spaces
     */
    public function testOnlySpacesAndTabsAreAllowed()
    {
        $i = new Undefined();
        $i->newLevel('_');
    }

    /**
     * @expectedException MtHaml\Indentation\IndentationException
     * @expectedExceptionMessage Indentation can't use both tabs and spaces
     */
    public function testCanNotMixTabsAndSpaces()
    {
        $i = new Undefined();
        $i->newLevel(" \t");
    }

    /**
     * @expectedException MtHaml\Indentation\IndentationException
     * @expectedExceptionMessage The line was indented more than one level deeper than the previous line
     */
    public function testCanOnlyIndentOneLevelAtOnce()
    {
        $i = new Undefined();
        $i = $i->newLevel(' ');
        $i = $i->newLevel('    ');
    }

    /**
     * @expectedException MtHaml\Indentation\IndentationException
     * @expectedExceptionMessage Inconsistent indentation: 3 is not a multiple of 2
     */
    public function testWidthMustBeConsistent()
    {
        $i = new Undefined();
        $i = $i->newLevel('  ');
        $i = $i->newLevel('   ');
    }
}
