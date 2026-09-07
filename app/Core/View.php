<?php

namespace App\Core;

use RuntimeException;

/**
 * Motor de views simples baseado em PHP puro, com layouts e sections.
 */
class View
{
    protected string $viewPath;
    /** @var array<string, string> */
    protected array $sections = [];
    protected array $sectionStack = [];
    protected ?string $layout = null;
    /** @var array<string, mixed> */
    protected array $shared = [];

    public function __construct(string $viewPath)
    {
        $this->viewPath = rtrim($viewPath, '/\\');
    }

    public function share(string $key, $value): void
    {
        $this->shared[$key] = $value;
    }

    public function render(string $template, array $data = []): Response
    {
        $content = $this->renderTemplate($template, $data);

        // Se um layout foi definido dentro do template, renderiza-o.
        while ($this->layout !== null) {
            $layout = $this->layout;
            $this->layout = null;
            // Se a view nao definiu explicitamente a secao 'content' via start/stop,
            // usa o conteudo "solto" renderizado como content. Caso contrario,
            // preserva a secao ja capturada (nao sobrescreve com o buffer vazio).
            if (!array_key_exists('content', $this->sections) || trim($this->sections['content']) === '') {
                if (trim($content) !== '') {
                    $this->sections['content'] = $content;
                }
            }
            $content = $this->renderTemplate($layout, $data);
        }

        return new Response($content);
    }

    protected function renderTemplate(string $template, array $data): string
    {
        $file = $this->viewPath . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $template) . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View nao encontrada: {$template} ({$file})");
        }

        $data = array_merge($this->shared, $data);
        extract($data, EXTR_SKIP);

        ob_start();
        include $file;
        return ob_get_clean() ?: '';
    }

    // ---- API usada dentro das views ----

    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    public function start(string $section): void
    {
        $this->sectionStack[] = $section;
        ob_start();
    }

    public function stop(): void
    {
        $section = array_pop($this->sectionStack);
        $this->sections[$section] = ob_get_clean() ?: '';
    }

    public function section(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function partial(string $template, array $data = []): void
    {
        echo $this->renderTemplate($template, $data);
    }
}
