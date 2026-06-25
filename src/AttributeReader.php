<?php

/**
 * This file is part of the nexphant Framework.
 *
 * (c) nexphant <https://github.com/nexphant>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Nexphant\Foundation;

/**
 * Immutable attribute reader — reads PHP 8 Attributes from classes/methods/properties.
 */
class AttributeReader
{
    /**
     * Read all attributes of a given type from a class.
     *
     * @template T of object
     * @param class-string<T> $attributeClass
     * @return T[]
     */
    public static function fromClass(string $class, string $attributeClass): array
    {
        $ref  = new \ReflectionClass($class);
        return self::extractFrom($ref->getAttributes($attributeClass));
    }

    /**
     * Read all attributes of a given type from a method.
     *
     * @template T of object
     * @param class-string<T> $attributeClass
     * @return T[]
     */
    public static function fromMethod(string $class, string $method, string $attributeClass): array
    {
        $ref  = new \ReflectionMethod($class, $method);
        return self::extractFrom($ref->getAttributes($attributeClass));
    }

    /**
     * Read all attributes of a given type from a property.
     *
     * @template T of object
     * @param class-string<T> $attributeClass
     * @return T[]
     */
    public static function fromProperty(string $class, string $property, string $attributeClass): array
    {
        $ref  = new \ReflectionProperty($class, $property);
        return self::extractFrom($ref->getAttributes($attributeClass));
    }

    /**
     * Read all attributes from all properties of a class.
     *
     * @return array<string, object[]>  keyed by property name
     */
    public static function allPropertyAttributes(string $class, string $attributeClass): array
    {
        $ref    = new \ReflectionClass($class);
        $result = [];
        foreach ($ref->getProperties() as $prop) {
            $attrs = self::extractFrom($prop->getAttributes($attributeClass));
            if (!empty($attrs)) {
                $result[$prop->getName()] = $attrs;
            }
        }
        return $result;
    }

    // -------------------------------------------------------------------------

    /** @param \ReflectionAttribute[] $attrs */
    private static function extractFrom(array $attrs): array
    {
        return array_map(fn(\ReflectionAttribute $a) => $a->newInstance(), $attrs);
    }
}
