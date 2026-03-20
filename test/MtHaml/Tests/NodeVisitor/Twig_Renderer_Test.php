<?php

declare(strict_types=1);

namespace MtHaml\Tests\NodeVisitor;

use MtHaml\Node\InterpolatedString;
use MtHaml\Node\Text;
use MtHaml\NodeVisitor\TwigRenderer;

class TwigRendererTest extends \PHPUnit_Framework_TestCase
{
    /** @dataProvider getCurlyPercentAndCurlyCurclyAreEscapedData */
    public function testCurlyPercentAndCurlyCurclyAreEscaped($expect, $node)
    {
        $env = $this->getMockBuilder('MtHaml\Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $r = new TwigRenderer($env);

        $node = $node();
        $node->accept($r);

        $output = $r->getOutput();
        $this->assertSame($expect, $output);
    }

    public function getCurlyPercentAndCurlyCurclyAreEscapedData()
    {
        $pos = [0, 0];

        return [
            'middle' => [
                'expect' => "foo {{ '{{' }} bar {{ '{%' }} baz",
                'node' => function () use ($pos) {
                    return new Text($pos, 'foo {{ bar {% baz');
                },
            ],
            'leading in node, not preceeded by {' => [
                'expect' => 'foo % bar { baz',
                'nodes' => function () use ($pos) {
                    return new InterpolatedString($pos, [
                        new Text($pos, 'foo '),
                        new Text($pos, '% bar '),
                        new Text($pos, '{ baz'),
                    ]);
                },
            ],
            'leading in node, preceeded by {' => [
                'expect' => "foo {{{ '%' }} bar {{{ '{' }} baz",
                'nodes' => function () use ($pos) {
                    return new InterpolatedString($pos, [
                        new Text($pos, 'foo {'),
                        new Text($pos, '% bar {'),
                        new Text($pos, '{ baz'),
                    ]);
                },
            ],
            'leading in node, globally leading' => [
                'expect' => "{{ '%' }} bar",
                'nodes' => function () use ($pos) {
                    return new InterpolatedString($pos, [
                        new Text($pos, '% bar'),
                    ]);
                },
            ],
        ];
    }
}
