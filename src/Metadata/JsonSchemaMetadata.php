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

/**
 * JsonSchemaMetadata — generates JSON Schema from model/DTO property metadata.
 */
class JsonSchemaMetadata
{
    private static array $typeMap = [
        'string'   => 'string',
        'int'      => 'integer',
        'integer'  => 'integer',
        'float'    => 'number',
        'double'   => 'number',
        'bool'     => 'boolean',
        'boolean'  => 'boolean',
        'array'    => 'array',
        'object'   => 'object',
        'mixed'    => ['string', 'integer', 'number', 'boolean', 'array', 'object', 'null'],
    ];

    /**
     * Generate a JSON Schema from a class (model or DTO).
     */
    public function fromClass(string $class): array
    {
        $ref        = new \ReflectionClass($class);
        $properties = [];
        $required   = [];

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $type     = $prop->getType();
            $name     = $prop->getName();
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : 'mixed';
            $nullable = $type?->allowsNull() ?? true;

            $schema = ['type' => self::$typeMap[$typeName] ?? 'string'];
            if ($nullable) {
                $schema = ['anyOf' => [$schema, ['type' => 'null']]];
            }

            $properties[$name] = $schema;

            if (!$nullable && $prop->hasDefaultValue() === false) {
                $required[] = $name;
            }
        }

        $schema = [
            '$schema'    => 'https://json-schema.org/draft/2020-12/schema',
            'title'      => (new \ReflectionClass($class))->getShortName(),
            'type'       => 'object',
            'properties' => $properties,
        ];

        if (!empty($required)) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * Export schema as JSON string.
     */
    public function toJson(string $class): string
    {
        return json_encode($this->fromClass($class), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Write schema to a file.
     */
    public function writeFile(string $class, string $path): void
    {
        file_put_contents($path, $this->toJson($class), LOCK_EX);
    }
}
