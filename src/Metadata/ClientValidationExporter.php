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
use Nexphant\Validation\Rule;

/**
 * ClientValidationExporter — exports server-side Rule metadata as JSON
 * for client-side validation (live validation from Rule metadata).
 */
class ClientValidationExporter
{
    public function __construct(private readonly MetadataRegistry $registry) {}

    /**
     * Export all registered validation rules as a JSON string
     * suitable for consumption by a client-side validator.
     */
    public function toJson(): string
    {
        $all    = $this->registry->group('validation');
        $export = [];

        foreach ($all as $class => $entry) {
            $export[$class] = $entry['rules'] ?? [];
        }

        return json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Write the exported JSON to a public asset path.
     */
    public function writeFile(string $path): void
    {
        file_put_contents($path, $this->toJson(), LOCK_EX);
    }
}
