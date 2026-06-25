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
 * DatabaseMetadata — registers DB schema info per model.
 */
class DatabaseMetadata
{
    public function __construct(
        private readonly MetadataRegistry $registry,
    ) {}

    public function register(string $class, array $schema): void
    {
        $this->registry->register('database', $class, array_merge(['class' => $class], $schema));
    }

    public function get(string $class): ?array
    {
        return $this->registry->get('database', $class);
    }

    public function all(): array
    {
        return $this->registry->group('database');
    }
}
