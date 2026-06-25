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

/**
 * GraphQlSchemaGenerator — generates a GraphQL schema SDL string from model metadata.
 */
class GraphQlSchemaGenerator
{
    private static array $typeMap = [
        'int'     => 'Int',
        'integer' => 'Int',
        'float'   => 'Float',
        'double'  => 'Float',
        'bool'    => 'Boolean',
        'boolean' => 'Boolean',
        'string'  => 'String',
        'array'   => 'String', // JSON as String
        'mixed'   => 'String',
    ];

    public function __construct(private readonly MetadataRegistry $registry) {}

    /**
     * Generate SDL for all registered models.
     */
    public function generate(): string
    {
        $types = [];
        foreach ($this->registry->group('model') as $class => $meta) {
            $types[] = $this->modelToType($meta);
        }
        return implode("\n\n", $types);
    }

    private function modelToType(array $meta): string
    {
        $name   = class_exists($meta['class'])
            ? (new \ReflectionClass($meta['class']))->getShortName()
            : $meta['class'];
        $fields = [];

        foreach ($meta['properties'] ?? [] as $prop => $info) {
            $gql      = self::$typeMap[$info['type']] ?? 'String';
            $nullable = $info['nullable'] ? '' : '!';
            $fields[] = "  {$prop}: {$gql}{$nullable}";
        }

        return "type {$name} {\n" . implode("\n", $fields) . "\n}";
    }

    public function writeFile(string $path): void
    {
        file_put_contents($path, $this->generate(), LOCK_EX);
    }
}
