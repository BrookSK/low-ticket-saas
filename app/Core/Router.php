<?php

namespace App\Core;

use App\Exceptions\HttpException;

/**
 * Roteador com URLs amigaveis, parametros dinamicos, grupos e middleware.
 */
class Router
{
    /** @var array<int, array{method:string,pattern:string,regex:string,params:array,handler:mixed,middleware:array,name:?string}> */
    protected array $routes = [];

    /** @var array<string, string> */
    protected array $named = [];

    /** @var array{prefix:string,middleware:array} */
    protected array $groupStack = ['prefix' => '', 'middleware' => []];

    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $uri, $handler): RouteDefinition
    {
        return $this->addRoute('GET', $uri, $handler);
    }

    public function post(string $uri, $handler): RouteDefinition
    {
        return $this->addRoute('POST', $uri, $handler);
    }

    public function put(string $uri, $handler): RouteDefinition
    {
        return $this->addRoute('PUT', $uri, $handler);
    }

    public function patch(string $uri, $handler): RouteDefinition
    {
        return $this->addRoute('PATCH', $uri, $handler);
    }

    public function delete(string $uri, $handler): RouteDefinition
    {
        return $this->addRoute('DELETE', $uri, $handler);
    }

    /**
     * Define um grupo de rotas com prefixo e/ou middleware comuns.
     */
    public function group(array $attributes, callable $callback): void
    {
        $previous = $this->groupStack;

        $this->groupStack = [
            'prefix' => $previous['prefix'] . ($attributes['prefix'] ?? ''),
            'middleware' => array_merge($previous['middleware'], $attributes['middleware'] ?? []),
        ];

        $callback($this);

        $this->groupStack = $previous;
    }

    protected function addRoute(string $method, string $uri, $handler): RouteDefinition
    {
        $uri = $this->groupStack['prefix'] . $uri;
        $uri = '/' . trim($uri, '/');
        $uri = $uri === '/' ? '/' : rtrim($uri, '/');

        // Converte {param} em grupo de captura.
        $params = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) use (&$params) {
            $params[] = $m[1];
            return '([^/]+)';
        }, $uri);
        $regex = '#^' . $regex . '$#';

        $index = count($this->routes);
        $this->routes[$index] = [
            'method' => $method,
            'pattern' => $uri,
            'regex' => $regex,
            'params' => $params,
            'handler' => $handler,
            'middleware' => $this->groupStack['middleware'],
            'name' => null,
        ];

        return new RouteDefinition($this, $index);
    }

    public function setName(int $index, string $name): void
    {
        $this->routes[$index]['name'] = $name;
        $this->named[$name] = $this->routes[$index]['pattern'];
    }

    public function addMiddleware(int $index, array $middleware): void
    {
        $this->routes[$index]['middleware'] = array_merge($this->routes[$index]['middleware'], $middleware);
    }

    /**
     * Resolve e executa a rota correspondente a requisicao.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();
        $path = $path === '/' ? '/' : rtrim($path, '/');

        $methodMismatch = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $methodMismatch = true;
                continue;
            }

            array_shift($matches);
            $params = [];
            foreach ($route['params'] as $i => $name) {
                $params[$name] = $matches[$i] ?? null;
            }
            $request->setRouteParams($params);

            return $this->runWithMiddleware($route, $request);
        }

        if ($methodMismatch) {
            throw new HttpException(405, 'Metodo nao permitido.');
        }

        throw new HttpException(404, 'Pagina nao encontrada.');
    }

    /**
     * Executa a pilha de middleware e por fim o handler.
     */
    protected function runWithMiddleware(array $route, Request $request): Response
    {
        $middlewareList = $route['middleware'];

        $runner = function (Request $request) use ($route): Response {
            return $this->runHandler($route['handler'], $request);
        };

        foreach (array_reverse($middlewareList) as $middlewareName) {
            $next = $runner;
            $runner = function (Request $request) use ($middlewareName, $next): Response {
                $middleware = $this->resolveMiddleware($middlewareName);
                return $middleware->handle($request, $next);
            };
        }

        return $runner($request);
    }

    protected function resolveMiddleware(string $name)
    {
        // Suporta parametros: 'role:admin'
        $params = [];
        if (str_contains($name, ':')) {
            [$name, $paramStr] = explode(':', $name, 2);
            $params = explode(',', $paramStr);
        }

        $map = config('app.middleware', []);
        $class = $map[$name] ?? $name;

        $instance = $this->container->make($class);
        if (!empty($params) && method_exists($instance, 'setParams')) {
            $instance->setParams($params);
        }
        return $instance;
    }

    protected function runHandler($handler, Request $request): Response
    {
        if (is_callable($handler)) {
            $result = $handler($request);
            return $this->toResponse($result);
        }

        // Formato "Controller@method"
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler);
            // Aceita nome curto ("Site\LandingController") ou FQCN completo.
            if (!str_starts_with($class, 'App\\') && !class_exists($class)) {
                $class = 'App\\Controllers\\' . $class;
            }
            $controller = $this->container->make($class);
            $result = $controller->{$method}($request);
            return $this->toResponse($result);
        }

        // Formato [Class::class, 'method']
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = $this->container->make($class);
            $result = $controller->{$method}($request);
            return $this->toResponse($result);
        }

        throw new HttpException(500, 'Handler de rota invalido.');
    }

    protected function toResponse($result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }
        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }
        return Response::make((string) $result);
    }

    public function route(string $name, array $params = []): string
    {
        $pattern = $this->named[$name] ?? '/';
        foreach ($params as $key => $value) {
            $pattern = str_replace('{' . $key . '}', (string) $value, $pattern);
        }
        return $pattern;
    }
}
