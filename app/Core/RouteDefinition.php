<?php

namespace App\Core;

/**
 * Interface fluente para configurar uma rota (name, middleware).
 */
class RouteDefinition
{
    protected Router $router;
    protected int $index;

    public function __construct(Router $router, int $index)
    {
        $this->router = $router;
        $this->index = $index;
    }

    public function name(string $name): self
    {
        $this->router->setName($this->index, $name);
        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        $this->router->addMiddleware($this->index, (array) $middleware);
        return $this;
    }
}
