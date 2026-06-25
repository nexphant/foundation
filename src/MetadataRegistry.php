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
 * MetadataRegistry — SSOT for all class-level metadata (model, validation, DB, etc.)
 *
 * Immutable once locked; call lock() after boot to prevent runtime mutation.
 */
class MetadataRegistry
{
    private static self $instance;
    private array $store   = [];
    private bool  $locked  = false;

    private function __construct() {}

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Register metadata for a class under a named group.
     *
     * @throws \LogicException if registry is locked
     */
    public function register(string $group, string $class, array $metadata): void
    {
        if ($this->locked) {
            throw new \LogicException('MetadataRegistry is locked — cannot register after boot.');
        }
        $this->store[$group][$class] = $metadata;
    }

    /**
     * Retrieve metadata for a class in a group.
     */
    public function get(string $group, string $class): ?array
    {
        return $this->store[$group][$class] ?? null;
    }

    /**
     * Get all metadata for a group.
     *
     * @return array<string, array>
     */
    public function group(string $group): array
    {
        return $this->store[$group] ?? [];
    }

    /**
     * Check if metadata exists.
     */
    public function has(string $group, string $class): bool
    {
        return isset($this->store[$group][$class]);
    }

    /**
     * Lock the registry — no further registrations allowed.
     */
    public function lock(): void
    {
        $this->locked = true;
    }

    public function isLocked(): bool { return $this->locked; }
}
