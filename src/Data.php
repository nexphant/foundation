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

use Nexphant\Validation\Rule;
use Nexphant\Validation\Validator;
use Nexphant\Validation\ValidationException;

/**
 * Data — base class for typed DTOs with built-in validation, casting, and serialization.
 *
 * Supports: nested DTOs, collections, default values, optional values,
 * enum support, auto casting, JSON/array serialization.
 */
abstract class Data
{
    /**
     * Create a validated DTO from raw data.
     */
    public static function from(array $data): static
    {
        $instance = new static();
        $instance->fill($data);
        return $instance;
    }

    /**
     * Create a collection of DTOs from an array of rows.
     *
     * @return static[]
     */
    public static function collection(array $rows): array
    {
        return array_map(fn($row) => static::from($row), $rows);
    }

    /**
     * Override to define validation rules.
     *
     * @return array<string, Rule|string[]>
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Override to define casts.
     *
     * @return array<string, string|class-string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Convert to array, including nested DTOs and collections.
     */
    public function toArray(): array
    {
        $result = [];
        $ref    = new \ReflectionClass($this);

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $value = $prop->getValue($this);
            $result[$prop->getName()] = $this->serializeValue($value);
        }

        return $result;
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | $flags);
    }

    // -------------------------------------------------------------------------

    private function fill(array $data): void
    {
        // Validate first if rules defined
        $rules = $this->rules();
        if (!empty($rules)) {
            $v = new Validator($data, $rules);
            if ($v->fails()) {
                throw new ValidationException($v->errors());
            }
        }

        $ref   = new \ReflectionClass($this);
        $casts = $this->casts();

        foreach ($ref->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $name = $prop->getName();
            $type = $prop->getType();

            $value = array_key_exists($name, $data)
                ? $data[$name]
                : ($prop->hasDefaultValue() ? $prop->getDefaultValue() : null);

            if ($value !== null) {
                $value = $this->castValue($name, $value, $type, $casts[$name] ?? null);
            }

            $prop->setValue($this, $value);
        }
    }

    private function castValue(string $name, mixed $value, ?\ReflectionType $type, ?string $cast): mixed
    {
        // Explicit cast override
        if ($cast !== null) {
            if (class_exists($cast) && is_subclass_of($cast, self::class)) {
                return $cast::from(is_array($value) ? $value : []);
            }
            if (enum_exists($cast)) {
                return $cast::from($value);
            }
            return match ($cast) {
                'int', 'integer' => (int) $value,
                'float'          => (float) $value,
                'bool', 'boolean'=> (bool) $value,
                'string'         => (string) $value,
                'array'          => (array) $value,
                'json'           => is_string($value) ? json_decode($value, true) : $value,
                default          => $value,
            };
        }

        // Auto-cast from type hint
        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();
            if (class_exists($typeName) && is_subclass_of($typeName, self::class)) {
                return $typeName::from(is_array($value) ? $value : []);
            }
            if (enum_exists($typeName) && is_string($value)) {
                return $typeName::from($value);
            }
            return match ($typeName) {
                'int'    => (int) $value,
                'float'  => (float) $value,
                'bool'   => (bool) $value,
                'string' => (string) $value,
                'array'  => (array) $value,
                default  => $value,
            };
        }

        return $value;
    }

    private function serializeValue(mixed $value): mixed
    {
        if ($value instanceof self) return $value->toArray();
        if (is_array($value)) {
            return array_map(fn($v) => $v instanceof self ? $v->toArray() : $v, $value);
        }
        if ($value instanceof \BackedEnum) return $value->value;
        if ($value instanceof \UnitEnum)  return $value->name;
        return $value;
    }
}
