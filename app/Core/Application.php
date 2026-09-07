<?php

namespace App\Core;

use App\Exceptions\HttpException;
use App\Services\LogService;
use Throwable;

/**
 * Nucleo da aplicacao. Faz o bootstrap dos servicos essenciais,
 * registra os bindings do container e despacha a requisicao.
 */
class Application extends Container
{
    protected static ?Application $instance = null;
    protected string $basePath;
    protected Router $router;
    protected bool $booted = false;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        static::$instance = $this;
        $this->instance(Application::class, $this);
    }

    public static function getInstance(): Application
    {
        return static::$instance;
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    /**
     * Registra os servicos fundamentais no container.
     */
    public function bootstrap(): void
    {
        if ($this->booted) {
            return;
        }

        // Configuracoes de bootstrap (arquivos /config).
        Config::load($this->basePath('config'));

        // Sessao segura.
        Session::start(config('session', []));

        // Banco de dados (singleton).
        $this->singleton(Database::class, function () {
            return new Database(config('database'));
        });

        // Criptografia para valores sensiveis das settings.
        $this->singleton(Encrypter::class, function () {
            return new Encrypter(config('app.key', 'change-me-in-config'));
        });

        // View engine.
        $this->singleton(View::class, function () {
            $view = new View($this->basePath('resources/views'));
            $view->share('app', $this);
            return $view;
        });

        // Roteador.
        $this->router = new Router($this);
        $this->instance(Router::class, $this->router);

        $this->loadRoutes();

        // Registra listeners dos eventos comerciais (e-mails transacionais, etc).
        \App\Services\EventListeners::register($this->make(\App\Services\EventService::class));

        $this->booted = true;
    }

    protected function loadRoutes(): void
    {
        $router = $this->router;
        require $this->basePath('routes/web.php');
        require $this->basePath('routes/admin.php');
        require $this->basePath('routes/api.php');
    }

    public function router(): Router
    {
        return $this->router;
    }

    /**
     * Processa a requisicao e envia a resposta.
     */
    public function run(): void
    {
        $request = new Request();
        $this->instance(Request::class, $request);

        try {
            $response = $this->router->dispatch($request);
        } catch (Throwable $e) {
            $response = $this->handleException($e, $request);
        }

        $response->send();
    }

    protected function handleException(Throwable $e, Request $request): Response
    {
        $status = $e instanceof HttpException ? $e->getStatusCode() : 500;

        // Registra erros de servidor.
        if ($status >= 500) {
            try {
                $this->make(LogService::class)->error('exception', $e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            } catch (Throwable $ignore) {
                error_log($e->getMessage());
            }
        }

        if ($request->wantsJson()) {
            return Response::json([
                'error' => true,
                'message' => $status < 500 ? $e->getMessage() : 'Erro interno do servidor.',
            ], $status);
        }

        // Tenta renderizar view de erro; senao, resposta simples.
        try {
            $debug = (bool) config('app.debug', false);
            $content = $this->make(View::class)->render('errors.error', [
                'status' => $status,
                'message' => $e->getMessage(),
                'debug' => $debug,
                'exception' => $e,
            ])->getContent();
            return new Response($content, $status);
        } catch (Throwable $ignore) {
            return new Response('Erro ' . $status, $status);
        }
    }
}
