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
 * FormMetadata — registers form field definitions for form generation.
 */
class FormMetadata
{
    public function __construct(
        private readonly MetadataRegistry $registry,
    ) {}

    /**
     * @param array<string, array{type: string, label?: string, required?: bool, ...}> $fields
     */
    public function register(string $class, array $fields): void
    {
        $this->registry->register('form', $class, ['class' => $class, 'fields' => $fields]);
    }

    public function get(string $class): ?array
    {
        return $this->registry->get('form', $class);
    }

    public function all(): array
    {
        return $this->registry->group('form');
    }
}
