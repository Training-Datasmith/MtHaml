<?php

declare(strict_types=1);

namespace MtHaml\Tests {

    use MtHaml\Runtime;
    use MtHaml\Runtime\AttributeList;

    require_once __DIR__ . '/TestCase.php';

    class RuntimeTest extends TestCase
    {
        /**
         * @dataProvider getTestRenderAttributesData
         */
        public function testRenderAttributes($expect, $list, $format = 'html5', $charset = 'utf-8')
        {
            $result = Runtime::renderAttributes($list, $format, $charset);
            $this->assertSame($expect, $result);
        }

        public function getTestRenderAttributesData()
        {
            return [
                'simple' => [
                    'foo="bar" bar="baz"',
                    [
                        ['foo', 'bar'],
                        ['bar', 'baz'],
                    ],
                ],
                'duplicate attribute' => [
                    'bar="baz" foo="qux"',
                    [
                        ['foo', 'bar'],
                        ['bar', 'baz'],
                        ['foo', 'qux'],
                    ],
                ],
                'data attribute' => [
                    'foo="bar" data-a="A" data-b="B"',
                    [
                        ['foo', 'bar'],
                        ['data', ['a' => 'A', 'b' => 'B']],
                    ],
                ],
                'previous data attribute overridden by specific data- attribute' => [
                    'foo="bar" data-b="B" data-a="A2"',
                    [
                        ['foo', 'bar'],
                        ['data', ['a' => 'A', 'b' => 'B']],
                        ['data-a', 'A2'],
                    ],
                ],
                'previous data- attribute not overridden by data attribute list' => [
                    'foo="bar" data-a="A2" data-b="B"',
                    [
                        ['foo', 'bar'],
                        ['data-a', 'A2'],
                        ['data', ['a' => 'A', 'b' => 'B']],
                    ],
                ],
                'deeply nested data attribute' => [
                    'foo="bar" data-a="A" data-b="B" data-c-d-e-f="F" data-c-g="G"',
                    [
                        ['foo', 'bar'],
                        ['data', [
                            'a' => 'A',
                            'b' => 'B',
                            'c' => [
                                'd' => [
                                    'e' => [
                                        'f' => 'F',
                                    ],
                                ],
                                'g' => 'G',
                            ],
                        ]],
                    ],
                ],
                'single id attribute' => [
                    'id="a"',
                    [
                        ['id', 'a'],
                    ],
                ],
                'multiple id attributes are joined with _' => [
                    'id="a_b"',
                    [
                        ['id', 'a'],
                        ['id', 'b'],
                    ],
                ],
                'multiple id attributes skip nulls and falses' => [
                    'id="a_b"',
                    [
                        ['id', 'a'],
                        ['id', null],
                        ['id', false],
                        ['id', 'b'],
                    ],
                ],
                'id attributes recurse' => [
                    'id="a_b_c_d_e_f"',
                    [
                        ['id', 'a'],
                        ['id', ['b', null, 'c']],
                        ['id', ['d', ['e', 'f']]],
                    ],
                ],
                'single class attribute' => [
                    'class="a"',
                    [
                        ['class', 'a'],
                    ],
                ],
                'multiple class attributes are joined with _' => [
                    'class="a b"',
                    [
                        ['class', 'a'],
                        ['class', 'b'],
                    ],
                ],
                'multiple class attributes skip nulls and falses' => [
                    'class="a b"',
                    [
                        ['class', 'a'],
                        ['class', null],
                        ['class', false],
                        ['class', 'b'],
                    ],
                ],
                'class attributes recurse' => [
                    'class="a b c d e f"',
                    [
                        ['class', 'a'],
                        ['class', ['b', null, 'c']],
                        ['class', ['d', ['e', 'f']]],
                    ],
                ],
                'boolean attributes are rendered without value in html5 format' => [
                    'foo',
                    [
                        ['foo', true],
                    ],
                ],
                'boolean attributes are rendered with value in xhtml format' => [
                    'foo="foo"',
                    [
                        ['foo', true],
                    ],
                    'xhtml',
                ],
                'false and null attributes are not rendered' => [
                    null,
                    [
                        ['foo', null],
                        ['bar', false],
                    ],
                ],
                'everything is escaped' => [
                    'foo&gt;="bar&gt;" data-foo&gt;="bar&gt;" data-bar&gt;="bar&gt;" id="bar&gt;" class="bar&gt;"',
                    [
                        ['foo>', 'bar>'],
                        ['data', ['foo>' => 'bar>']],
                        ['data-bar>', 'bar>'],
                        ['id', ['bar>']],
                        ['class', ['bar>']],
                    ],
                ],
                'attribute list' => [
                    'foo="bar" bar="baz" baz="qux" all="ok"',
                    [
                        ['foo', 'bar'],
                        AttributeList::create([
                            'bar' => 'baz',
                            'baz' => 'qux',
                        ]),
                        ['all', 'ok'],
                    ],
                ],
                'attribute list are properly merged' => [
                    'class="foo bar" id="x_43" all="ok"',
                    [
                        ['class', 'foo'],
                        ['id', 'x'],
                        AttributeList::create([
                            'class' => 'bar',
                            'id' => '43',
                        ]),
                        ['all', 'ok'],
                    ],
                ],
            ];
        }

        /**
         * @dataProvider getObjectRefClassStringData
         */
        public function testGetObjectRefClassStringData($expect, $class)
        {
            $result = Runtime::getObjectRefClassString(new $class());
            $this->assertSame($expect, $result);
        }

        public function getObjectRefClassStringData()
        {
            return [
                'simple' => ['foo_bar', 'FooBar'],
                'underscores in name' => ['foo_bar', 'Foo_Bar'],
                'multiple upper case' => ['foo_bbar', 'FooBBar'],
                'namespace' => ['baz_qux', 'Foo\Bar\BazQux'],
            ];
        }

        public function testRenderObjectRefClass()
        {
            $object = new \stdClass();
            $result = Runtime::renderObjectRefClass($object);
            $this->assertSame('std_class', $result);

            $object = new \stdClass();
            $result = Runtime::renderObjectRefClass($object, 'pref<');
            $this->assertSame('pref<_std_class', $result);
        }

        public function testRenderObjectRefId()
        {
            $object = new ObjectRefWithGetIdAndId();
            $result = Runtime::renderObjectRefId($object);
            $this->assertSame('object_ref_with_get_id_and_id_>get_id', $result);

            $object = new ObjectRefWithGetIdAndId();
            $result = Runtime::renderObjectRefId($object, 'pref<');
            $this->assertSame('pref<_object_ref_with_get_id_and_id_>get_id', $result);

            $object = new ObjectRefWithId();
            $result = Runtime::renderObjectRefId($object);
            $this->assertSame('object_ref_with_id_>id', $result);

            $object = new ObjectRefWithGetIdAndId();
            $object->getId = null;
            $result = Runtime::renderObjectRefId($object);
            $this->assertSame('object_ref_with_get_id_and_id_new', $result);
        }

        public function testRenderObjectRefWithRefMethod()
        {
            $object = new ObjectRefWithRefAndId();
            $result = Runtime::getObjectRefName($object);
            $this->assertSame('customRef', $result);

            $result = Runtime::renderObjectRefId($object);
            $this->assertSame('custom_ref_>id', $result);
        }

        /** @dataProvider getRuntimeTests */
        public function testRuntime($file)
        {
            $parts = $this->parseTestFile($file);

            file_put_contents($file . '.haml', $parts['HAML']);
            file_put_contents($file . '.php', $parts['FILE']);
            file_put_contents($file . '.exp', $parts['EXPECT']);

            try {
                ob_start();
                require $file . '.php';
                $out = ob_get_clean();
            } catch (\Exception $e) {
                $this->assertException($parts, $e);
                $this->cleanup($file);

                return;
            }
            $this->assertException($parts);

            file_put_contents($file . '.out', $out);

            $this->assertSame($parts['EXPECT'], $out);

            $this->cleanup($file);
        }

        protected function cleanup($file)
        {
            if (file_exists($file . '.out')) {
                unlink($file . '.out');
            }
            unlink($file . '.haml');
            unlink($file . '.php');
            unlink($file . '.exp');
        }

        public function getRuntimeTests()
        {
            if (false !== $tests = getenv('ENV_TESTS')) {
                $files = explode(' ', $tests);
            } else {
                $files = glob(__DIR__ . '/fixtures/runtime/*.test');
            }

            return array_map(function ($file) {
                return [$file];
            }, $files);
        }
    }

    class ObjectRefWithGetIdAndId
    {
        public $getId = '>get_id';
        public $id = '>id';

        public function getId()
        {
            return $this->getId;
        }

        public function id()
        {
            return $this->id;
        }
    }

    class ObjectRefWithId
    {
        public function id()
        {
            return '>id';
        }
    }

    class ObjectRefWithRefAndId extends ObjectRefWithId
    {
        public function hamlObjectRef()
        {
            return 'customRef';
        }
    }

    class ObjectRefWithoutId
    {
        protected function id()
        {
            return '>id';
        }
    }
}

namespace {
    class Foo_Bar
    {
    }
    class FooBar
    {
    }
    class FooBBar
    {
    }
}

namespace Foo\Bar {
    class BazQux
    {
    }
}
