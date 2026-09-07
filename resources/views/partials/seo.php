<?php
/**
 * Meta tags de SEO. Recebe: $title, $metaDescription (opcionais).
 */
$appName = setting('app.name', 'LowTicket SaaS');
$metaTitle = $title ?? $appName;
$desc = $metaDescription ?? 'Crie orcamentos profissionais, descubra quanto cobrar e organize suas financas.';
$currentUrl = url(ltrim(app(\App\Core\Request::class)->path(), '/'));
$ogImage = setting('app.logo', '');
?>
<title><?= e($metaTitle) ?></title>
<meta name="description" content="<?= e($desc) ?>">
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
