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
 * MetadataBuilder — builds and registers metadata for a class by reading
 * PHP 8 Attributes via AttributeReader and storing into MetadataRegistry.
 */
class MetadataBuilder
{
    public function __construct(
        private readonly AttributeReader  $reader,
        private readonly MetadataRegistry $registry,
        private readonly MetadataCache    $cache,
    ) {}

    /**
     * Build and register model metadata from a class.
     *
     * Reads #[Table], #[Column], #[Index] attributes (if present).
     */
    public function build(string $class, string $group = 'model'): array
    {
        $cacheKey = $group . ':' . $class;

        if ($cached = $this->cache->get($cacheKey)) {
            if (!$this->registry->has($group, $class)) {
                $this->registry->register($group, $class, $cached);
            }
            return $cached;
        }

        $ref      = new \ReflectionClass($class);
        $meta     = ['class' => $class, 'group' => $group, 'attributes' => []];

        // Class-level attributes
        foreach ($ref->getAttributes() as $attr) {
            $meta['attributes']['class'][] = [
                'name'      => $attr->getName(),
                'arguments' => $attr->getArguments(),
            ];
        }

        // Property-level attributes
        foreach ($ref->getProperties() as $prop) {
            $propAttrs = [];
            foreach ($prop->getAttributes() as $attr) {
                $propAttrs[] = [
                    'name'      => $attr->getName(),
                    'arguments' => $attr->getArguments(),
                ];
            }
            if (!empty($propAttrs)) {
                $meta['attributes']['properties'][$prop->getName()] = $propAttrs;
            }
        }

        // Method-level attributes
        foreach ($ref->getMethods() as $method) {
            $methodAttrs = [];
            foreach ($method->getAttributes() as $attr) {
                $methodAttrs[] = [
                    'name'      => $attr->getName(),
                    'arguments' => $attr->getArguments(),
                ];
            }
            if (!empty($methodAttrs)) {
                $meta['attributes']['methods'][$method->getName()] = $methodAttrs;
            }
        }

        $this->cache->put($cacheKey, $meta);
        $this->registry->register($group, $class, $meta);

        return $meta;
    }

    /**
     * Build metadata for all classes in a scanned result.
     *
     * @param array<class-string, \ReflectionClass> $classes
     * @return array<string, array>
     */
    public function buildAll(array $classes, string $group = 'model'): array
    {
        $result = [];
        foreach ($classes as $class => $_) {
            $result[$class] = $this->build($class, $group);
        }
        return $result;
    }
}
