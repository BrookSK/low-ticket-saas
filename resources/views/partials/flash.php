<?php
use App\Core\Session;
$types = ['success' => 'alert-success', 'error' => 'alert-error', 'warning' => 'alert-warning', 'info' => 'alert-info'];
foreach ($types as $key => $class):
    if (Session::hasFlash($key)):
?>
    <div class="alert <?= $class ?>" data-dismiss><?= e(Session::getFlash($key)) ?></div>
<?php
    endif;
endforeach;

$errors = Session::getFlash('errors', []);
if (!empty($errors) && is_array($errors)):
?>
    <div class="alert alert-error" data-dismiss>
        <?php foreach ($errors as $err): ?>
            <div><?= e($err) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
