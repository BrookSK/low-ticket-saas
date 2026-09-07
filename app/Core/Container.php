<?php

namespace App\Core;

use Closure;
use ReflectionClass;
use RuntimeException;

/**
 * Container de injecao de dependencia simples com auto-resolucao.
 */
class Container
{
    /** @var array<string, Closure> */
    protected array $bindings = [];

    /** @var array<string, object> */
    protected array $instances = [];

    /**
     * Registra um binding (factory).
     */
    public function bind(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Registra um singleton (resolvido uma unica vez).
     */
    public function singleton(string $abstract, Closure $factory): void
    {
        $this->bindings[$abstract] = function () use ($abstract, $factory) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $factory($this);
            }
            return $this->instances[$abstract];
        };
    }

    /**
     * Registra uma instancia ja construida.
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
        $this->bindings[$abstract] = fn() => $instance;
    }

    /**
     * Resolve uma abstracao para uma instancia concreta.
     */
    public function make(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        return $this->build($abstract);
    }

    /**
     * Constroi a classe resolvendo dependencias do construtor via reflection.
     */
    public function build(string $concrete)
    {
        if (!class_exists($concrete)) {
            throw new RuntimeException("Classe nao encontrada: {$concrete}");
        }

        $reflector = new ReflectionClass($concrete);
        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type !== null && !$type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());
            } elseif ($param->isDefaultValueAvailable()) {
                $dependencies[] = $param->getDefaultValue();
            } else {
                throw new RuntimeException(
                    "Nao foi possivel resolver a dependencia \${$param->getName()} de {$concrete}"
                );
            }
        }

        $instance = $reflector->newInstanceArgs($dependencies);

        // Cacheia servicos por padrao para evitar reconstrucao.
        $this->instances[$concrete] = $instance;

        return $instance;
    }
}
