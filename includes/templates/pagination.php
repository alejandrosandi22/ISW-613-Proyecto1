<?php

declare(strict_types=1);

/*
 * Navegación entre páginas de un listado.
 * Variables: $pagination (de paginate()), $listPath y $listParams (filtros actuales).
 */
?>
<nav class="pagination">
    <?php if ($pagination['page'] > 1): ?>
        <a href="<?= e(list_url($listPath, $listParams + ['page' => $pagination['page'] - 1])) ?>">« Anterior</a>
    <?php endif; ?>

    <span>Página <?= e($pagination['page']) ?> de <?= e($pagination['pages']) ?>
        (<?= e($pagination['total']) ?> registros)</span>

    <?php if ($pagination['page'] < $pagination['pages']): ?>
        <a href="<?= e(list_url($listPath, $listParams + ['page' => $pagination['page'] + 1])) ?>">Siguiente »</a>
    <?php endif; ?>
</nav>
