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

use Nexphant\Foundation\Container;
use Nexphant\Validation\Validator;
use Nexphant\Validation\ValidationException;

/**
 * ControllerDispatcher — resolves controller methods via DI and auto-validates
 * request data when the method typehints a Data subclass or has a rules() method.
 */
class ControllerDispatcher
{
    public function __construct(private readonly Container $container) {}

    /**
     * Dispatch a controller action with auto DI, auto casting, and auto validation.
     */
    public function dispatch(callable|array|string $action, array $requestData = [], array $routeParams = []): mixed
    {
        if (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action, 2);
            $controller = $this->container->make($class);
            return $this->callMethod($controller, $method, $requestData, $routeParams);
        }

        if (is_array($action) && count($action) === 2) {
            [$class, $method] = $action;
            $controller = is_string($class) ? $this->container->make($class) : $class;
            return $this->callMethod($controller, $method, $requestData, $routeParams);
        }

        return $this->container->call($action, array_merge($requestData, $routeParams));
    }

    // -------------------------------------------------------------------------

    private function callMethod(object $controller, string $method, array $data, array $params): mixed
    {
        $ref    = new \ReflectionMethod($controller, $method);
        $args   = [];

        foreach ($ref->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();

                // Auto-inject Data subclasses with validation
                if (class_exists($typeName) && is_subclass_of($typeName, Data::class)) {
                    $args[] = $typeName::from($data);
                    continue;
                }

                // Resolve from container
                try {
                    $args[] = $this->container->make($typeName);
                    continue;
                } catch (\RuntimeException) {}
            }

            // Route param or request data
            if (array_key_exists($name, $params)) {
                $args[] = $params[$name];
            } elseif (array_key_exists($name, $data)) {
                $args[] = $data[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        return $ref->invokeArgs($controller, $args);
    }
}
