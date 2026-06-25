<?php

/**
 * This file is part of the nexphant Framework.
 *
 * (c) nexphant <https://github.com/nexphant>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Nexphant\Foundation\Metadata;

use Nexphant\Foundation\MetadataRegistry;
use Nexphant\Foundation\AttributeReader;

/**
 * ModelMetadata — reads model class attributes and registers metadata.
 */
class ModelMetadata
{
    public function __construct(
        private readonly MetadataRegistry $registry,
    ) {}

    public function register(string $modelClass): array
    {
        $ref  = new \ReflectionClass($modelClass);
        $meta = [
            'class'      => $modelClass,
            'table'      => method_exists($modelClass, 'getTable') ? $modelClass::getTable() : '',
            'primaryKey' => defined("{$modelClass}::PRIMARY_KEY") ? $modelClass::PRIMARY_KEY : 'id',
            'properties' => [],
        ];

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $type = $prop->getType();
            $meta['properties'][$prop->getName()] = [
                'type'     => $type instanceof \ReflectionNamedType ? $type->getName() : 'mixed',
                'nullable' => $type?->allowsNull() ?? true,
            ];
        }

        $this->registry->register('model', $modelClass, $meta);
        return $meta;
    }
}
