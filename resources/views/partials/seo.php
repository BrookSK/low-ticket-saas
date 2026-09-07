<?php
/**
 * Meta tags de SEO. Recebe: $title, $metaDescription (opcionais).
 */
$appName = setting('app.name', 'Meu Orçamento');
$metaTitle = $title ?? $appName;
$desc = $metaDescription ?? 'Crie orcamentos profissionais, descubra quanto cobrar e organize suas financas. Feito para quem presta servico.';
$currentUrl = url(ltrim(app(\App\Core\Request::class)->path(), '/'));
$ogImage = setting('app.logo', '') ?: url('favicon.svg');
?>
<title><?= e($metaTitle) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<link rel="icon" type="image/svg+xml" href="<?= url('favicon.svg') ?>">
<link rel="apple-touch-icon" href="<?= url('favicon.svg') ?>">
<meta name="theme-color" content="#6366f1">
<link rel="canonical" href="<?= e($currentUrl) ?>">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($metaTitle) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($currentUrl) ?>">
<?php if ($ogImage): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($metaTitle) ?>">
<meta name="twitter:description" content="<?= e($desc) ?>">
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => $appName,
    'applicationCategory' => 'BusinessApplication',
    'operatingSystem' => 'Web',
    'description' => $desc,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
