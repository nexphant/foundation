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
 * ValidationMetadata — registers validation rules as metadata.
 */
class ValidationMetadata
{
    public function __construct(
        private readonly MetadataRegistry $registry,
    ) {}

    /**
     * Register validation rules for a class.
     *
     * @param array<string, Rule|string[]> $rules
     */
    public function register(string $class, array $rules): void
    {
        $serialized = [];
        foreach ($rules as $field => $rule) {
            $serialized[$field] = $rule instanceof Rule
                ? $rule->toArray()
                : (array) $rule;
        }
        $this->registry->register('validation', $class, ['class' => $class, 'rules' => $serialized]);
    }

    public function get(string $class): ?array
    {
        return $this->registry->get('validation', $class);
    }
}
