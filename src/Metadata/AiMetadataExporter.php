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
 * AiMetadataExporter — exports all registered metadata as a structured JSON
 * suitable for AI tooling, CRUD generators, and form generators.
 */
class AiMetadataExporter
{
    public function __construct(private readonly MetadataRegistry $registry) {}

    /**
     * Export all metadata groups to a single JSON document.
     */
    public function export(): array
    {
        return [
            'models'     => $this->registry->group('model'),
            'validation' => $this->registry->group('validation'),
            'forms'      => $this->registry->group('form'),
            'database'   => $this->registry->group('database'),
            'openapi'    => $this->registry->group('openapi'),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->export(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function writeFile(string $path): void
    {
        file_put_contents($path, $this->toJson(), LOCK_EX);
    }
}
