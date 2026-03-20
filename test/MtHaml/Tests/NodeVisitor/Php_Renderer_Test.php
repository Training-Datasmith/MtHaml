<?php

declare(strict_types=1);

namespace MtHaml\Tests\NodeVisitor;

use MtHaml\Environment;
use MtHaml\Node\InterpolatedString;
use MtHaml\Node\Text;
use MtHaml\NodeVisitor\PhpRenderer;

class PhpRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTestTrimInlineCommentsData
     */
    public function testTrimInlineComments($expect, $code)
    {
        $env = new Environment('php');
        $renderer = new PhpRenderer($env);
        $result = $renderer->trimInlineComments($code);
        $this->assertSame($expect, $result);
    }

    public function getTestTrimInlineCommentsData()
    {
        return [
            'no comments' => ['1 + 2', '1 + 2'],
            '# comment' => ['1 + 2', '1 + 2 # comment'],
            '// comment' => ['1 + 2', '1 + 2 // comment'],
            'comment without whitespace' => ['1 + 2', '1 + 2// comment'],

            'double quoted string' => [
                '"foo"', '"foo" # bar',
            ],
            'double quoted string with escapes' => [
                '"f\\\\o\\"o\n"', '"f\\\\o\\"o\n" # bar',
            ],
            'single quoted string' => [
                '\'foo\'', '\'foo\' # bar',
            ],
            'single quoted string with escapes' => [
                '\'f\\\\o\\\'o\'', '\'f\\\\o\\\'o\' # bar',
            ],
            'backticks string' => [
                '`foo`', '`foo` # bar',
            ],
            'backticks string with escapes' => [
                '`f\\\\o\\`o`', '`f\\\\o\\`o` # bar',
            ],
            'double quoted string with #' => [
                '"fo#o"', '"fo#o" # bar',
            ],
            '# in comment' => [
                '"foo"', '"foo" # b # a # r',
            ],
        ];
    }

    /** @dataProvider getPhpOpenTagsAreEscapedData */
    public function testPhpOpenTagsAreEscaped($expect, $node)
    {
        $env = $this->getMockBuilder('MtHaml\Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $r = new PhpRenderer($env);

        $node = $node();
        $node->accept($r);

        $output = $r->getOutput();
        $this->assertSame($expect, $output);
    }

    public function getPhpOpenTagsAreEscapedData()
    {
        $pos = [0, 0];

        return [
            'middle' => [
                'expect' => "foo <?php echo '<?'; ?> bar",
                'node' => function () use ($pos) {
                    return new Text($pos, 'foo <? bar');
                },
            ],
            '? leading in node, not preceeded by <' => [
                'expect' => 'foo ? bar',
                'nodes' => function () use ($pos) {
                    return new InterpolatedString($pos, [
                        new Text($pos, 'foo '),
                        new Text($pos, '? bar'),
                    ]);
                },
            ],
            '? leading in node, preceeded by <' => [
                'expect' => "foo <<?php echo '?'; ?> bar",
                'nodes' => function () use ($pos) {
                    return new InterpolatedString($pos, [
                        new Text($pos, 'foo <'),
                        new Text($pos, '? bar'),
                    ]);
                },
            ],
            '? leading in node, globally leading' => [
                'expect' => "<?php echo '?'; ?> bar",
                'nodes' => function () use ($pos) {
                    return new InterpolatedString($pos, [
                        new Text($pos, '? bar'),
                    ]);
                },
            ],
        ];
    }
}
