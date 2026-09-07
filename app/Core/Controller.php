<?php

namespace App\Core;

use App\Exceptions\HttpException;
use App\Services\AuthService;

/**
 * Controller base com helpers de view, redirecionamento e validacao.
 */
abstract class Controller
{
    protected function view(string $template, array $data = []): Response
    {
        return app(View::class)->render($template, $data);
    }

    protected function redirect(string $path, int $status = 302): Response
    {
        $location = preg_match('#^https?://#', $path) ? $path : url($path);
        return Response::redirect($location, $status);
    }

    protected function back(): Response
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
        return Response::redirect($referer);
    }

    protected function json($data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function withFlash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    protected function withErrors(array $errors, array $input = []): void
    {
        Session::flash('errors', $errors);
        Session::flashInput($input);
    }

    /**
     * Valida dados com um conjunto de regras simples.
     * Retorna array de erros (vazio = sem erros).
     */
    protected function validate(array $data, array $rules): array
    {
        return (new Validator($data, $rules))->validate();
    }

    protected function auth(): AuthService
    {
        return app(AuthService::class);
    }

    protected function user(): ?array
    {
        return $this->auth()->user();
    }

    protected function abort(int $status, string $message = ''): void
    {
        throw new HttpException($status, $message);
    }
}
