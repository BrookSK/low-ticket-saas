<?php
/** @var int $status */
/** @var string $message */
/** @var bool $debug */
/** @var \Throwable $exception */
$titles = [
    404 => 'Pagina nao encontrada',
    403 => 'Acesso negado',
    405 => 'Metodo nao permitido',
    500 => 'Erro interno',
];
$title = $titles[$status] ?? 'Ops!';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $status ?> - <?= e($title) ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <style>
        body{display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;
            font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;background:#0f172a;color:#e2e8f0}
        .err{max-width:560px;padding:2.5rem;text-align:center}
        .err .code{font-size:5rem;font-weight:800;background:linear-gradient(135deg,#6366f1,#8b5cf6);
            -webkit-background-clip:text;background-clip:text;color:transparent;line-height:1}
        .err h1{font-size:1.5rem;margin:.5rem 0}
        .err p{color:#94a3b8}
        .err a{display:inline-block;margin-top:1.5rem;padding:.75rem 1.5rem;background:#6366f1;color:#fff;
            border-radius:.6rem;text-decoration:none;font-weight:600}
        pre{text-align:left;background:#1e293b;padding:1rem;border-radius:.5rem;overflow:auto;font-size:.8rem;color:#f87171}
    </style>
</head>
<body>
    <div class="err">
        <div class="code"><?= $status ?></div>
        <h1><?= e($title) ?></h1>
        <p><?= e($debug && $message ? $message : 'Algo deu errado. Tente novamente em instantes.') ?></p>
        <?php if ($debug && isset($exception) && $status >= 500): ?>
            <pre><?= e($exception->getFile() . ':' . $exception->getLine() . "\n\n" . $exception->getTraceAsString()) ?></pre>
        <?php endif; ?>
        <a href="<?= url('/') ?>">Voltar ao inicio</a>
    </div>
</body>
</html>
