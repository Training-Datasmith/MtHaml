<?php

declare (strict_types=1);
namespace Mt_Haml;

use Mt_Haml\Runtime\Attribute_Interpolation;
use Mt_Haml\Runtime\Attribute_List;
class Runtime
{
    /**
     * Renders a list of attributes
     *
     * Handles the following special cases:
     * - attribute named 'data' with iterable value it rendered as multiple
     *   html5 data- attributes
     * - attribute with boolean value is rendered as boolean attribute. In html5
     *   format, only the attribute name is rendered, like 'checked'. In
     *   other formats, it is rendered with the attribute name as value, like
     *   'checked="checked"'.
     * - attribute with null or false value isn't rendered
     * - multiple id attributes and id attributes with iterable values are
     *   rendered concatenated with underscores
     * - multiple class attributes and class attributes with iterable values are
     *   rendered concatenated with spaces
     *
     * @param array  $list    A list of attributes (items are array($name, $value))
     * @param string $format  Output format (e.g. html5)
     * @param string $charset Output charset
     */
    public static function render_attributes($list, $format, $charset)
    {
        $attributes = [];
        self::merge_attributes($attributes, $list, $format);
        $result = null;
        foreach ($attributes as $name => $value) {
            if (null !== $result) {
                $result .= ' ';
            }
            if ($value instanceof Attribute_Interpolation) {
                $result .= htmlspecialchars((string) $value->value, ENT_QUOTES, $charset);
            } elseif (true === $value) {
                $result .= htmlspecialchars($name, ENT_QUOTES, $charset);
            } else {
                $result .= htmlspecialchars($name, ENT_QUOTES, $charset) . '="' . htmlspecialchars((string) $value, ENT_QUOTES, $charset) . '"';
            }
        }
        return $result;
    }
    private static function merge_attributes(array &$dest, $list, $format)
    {
        foreach ($list as $item) {
            if ($item instanceof Attribute_Interpolation) {
                $dest[] = $item;
                continue;
            }
            if ($item instanceof Attribute_List) {
                $pairs = [];
                foreach ($item->attributes as $name => $value) {
                    $pairs[] = [$name, $value];
                }
                self::merge_attributes($dest, $pairs, $format);
                continue;
            }
            list($name, $value) = $item;
            if ('data' === $name) {
                self::render_data_attributes($dest, $value);
            } elseif ('id' === $name) {
                $value = self::render_joined_value($value, '_');
                if (null !== $value) {
                    if (isset($dest['id'])) {
                        $dest['id'] .= '_' . $value;
                    } else {
                        $dest['id'] = $value;
                    }
                }
            } elseif ('class' === $name) {
                $value = self::render_joined_value($value, ' ');
                if (null !== $value) {
                    if (isset($dest['class'])) {
                        $dest['class'] .= ' ' . $value;
                    } else {
                        $dest['class'] = $value;
                    }
                }
            } elseif (true === $value) {
                if ('html5' === $format) {
                    $dest[$name] = true;
                } else {
                    $dest[$name] = $name;
                }
            } elseif (false === $value || null === $value) {
                // do not output
            } else {
                if (isset($dest[$name])) {
                    // so that next assignment puts the attribute
                    // at the end for the array
                    unset($dest[$name]);
                }
                $dest[$name] = $value;
            }
        }
    }
    private static function render_data_attributes(array &$dest, $value, string $prefix = 'data')
    {
        if (\is_array($value) || $value instanceof \Traversable) {
            foreach ($value as $subname => $subvalue) {
                self::render_data_attributes($dest, $subvalue, $prefix . '-' . $subname);
            }
        } else if (!isset($dest[$prefix])) {
            $dest[$prefix] = $value;
        }
    }
    private static function render_joined_value($values, string $separator)
    {
        $result = null;
        if (\is_array($values) || $values instanceof \Traversable) {
            foreach ($values as $value) {
                if (\is_array($value) || $value instanceof \Traversable) {
                    $value = self::render_joined_value($value, $separator);
                }
                if (null !== $value && false !== $value) {
                    if (null !== $result) {
                        $result .= $separator;
                    }
                    $result .= $value;
                }
            }
        } else if (null !== $values && false !== $values) {
            $result = $values;
        }
        return $result;
    }
    public static function render_object_ref_class($object, $prefix = null)
    {
        if (!$object) {
            return;
        }
        $class = self::get_object_ref_class_string($object);
        if (false !== $prefix && null !== $prefix) {
            return $prefix . '_' . $class;
        }
        return $class;
    }
    public static function render_object_ref_id($object, $prefix = null)
    {
        if (!$object) {
            return;
        }
        $id = null;
        if (\is_callable([$object, 'getId'])) {
            $id = $object->get_id();
        } elseif (\is_callable([$object, 'id'])) {
            $id = $object->id();
        }
        if (false === $id || null === $id) {
            $id = 'new';
        }
        $id = self::get_object_ref_class_string($object) . '_' . $id;
        if (false !== $prefix && null !== $prefix) {
            return $prefix . '_' . $id;
        }
        return $id;
    }
    public static function get_object_ref_class_string($object)
    {
        $class = self::get_object_ref_name($object);
        if (false !== $pos = \strrpos($class, '\\')) {
            $class = \substr($class, $pos + 1);
        }
        return \strtolower(\preg_replace('#(?<=[a-z])[A-Z]+#', '_$0', $class));
    }
    public static function get_object_ref_name($object)
    {
        return \is_callable([$object, 'hamlObjectRef']) ? $object->haml_object_ref() : \get_class($object);
    }
    public static function filter(Environment $mthaml, $filter, array $context, $content)
    {
        return $mthaml->get_filter($filter)->filter($content, $context, $mthaml->get_options());
    }
}