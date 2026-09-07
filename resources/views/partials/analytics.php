<?php
/**
 * Snippets de analytics/marketing (GA4, Google Ads, GTM, Meta Pixel).
 * Renderiza apenas as integracoes ATIVADAS no painel. IDs nunca hardcoded:
 * vem sempre das settings gerenciadas pelo Super Admin.
 */
$gaEnabled = (bool) setting('google.analytics_enabled', false);
$gaId = (string) setting('google.analytics_id', '');
$gtmEnabled = (bool) setting('google.gtm_enabled', false);
$gtmId = (string) setting('google.tag_manager_id', '');
$adsEnabled = (bool) setting('google.ads_enabled', false);
$adsId = (string) setting('google.ads_conversion_id', '');
$metaEnabled = (bool) setting('meta.pixel_enabled', false);
$metaId = (string) setting('meta.pixel_id', '');
?>
<?php if ($gtmEnabled && $gtmId !== ''): ?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= e($gtmId) ?>');</script>
<?php endif; ?>

<?php if (($gaEnabled && $gaId !== '') || ($adsEnabled && $adsId !== '')): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaEnabled && $gaId ? $gaId : $adsId) ?>"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    <?php if ($gaEnabled && $gaId !== ''): ?>gtag('config', '<?= e($gaId) ?>');<?php endif; ?>
    <?php if ($adsEnabled && $adsId !== ''): ?>gtag('config', '<?= e($adsId) ?>');<?php endif; ?>
</script>
<?php endif; ?>

<?php if ($metaEnabled && $metaId !== ''): ?>
<script>
    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?= e($metaId) ?>'); fbq('track', 'PageView');
</script>
<?php endif; ?>
