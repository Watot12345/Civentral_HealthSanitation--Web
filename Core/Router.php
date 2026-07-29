<?php
// Core/Router.php

namespace Core;

require_once __DIR__ . '/Response.php';

class Router
{
    private array $routes = [];

    public function get(string $path, array|callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array|callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, array|callable $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function patch(string $path, array|callable $handler): void
    {
        $this->addRoute('PATCH', $path, $handler);
    }

    public function delete(string $path, array|callable $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, array|callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => '/' . trim($path, '/'),
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request, ?string $uri = null): void
    {
        $method = $request->getMethod();
        $path = '/' . trim($uri ?? $request->getUri(), '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches); // Remove full match

                $handler = $route['handler'];

                if (is_callable($handler)) {
                    call_user_func_array($handler, $matches);
                    return;
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$class, $action] = $handler;

                    if (is_string($class)) {
                        $class = '\\' . ltrim($class, '\\');
                        if (class_exists($class)) {
                            $instance = new $class();
                            if (method_exists($instance, $action)) {
                                call_user_func_array([$instance, $action], $matches);
                                return;
                            }
                        }
                    }
                }
            }
        }

        // Endpoint not found
        \Response::error("API endpoint [{$method} {$path}] not found.", 404);
    }
}
