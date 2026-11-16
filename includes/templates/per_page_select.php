<?php

declare(strict_types=1);

/*
 * Selector de registros por página para los formularios de búsqueda.
 * Variable: $pagination (de paginate()).
 */
?>
<label for="per_page">Mostrar</label>
<select id="per_page" name="per_page">
    <?php foreach (config('pagination.allowed_per_page', []) as $option): ?>
        <option value="<?= e($option) ?>" <?= $option === $pagination['per_page'] ? 'selected' : '' ?>>
            <?= e($option) ?>
        </option>
    <?php endforeach; ?>
</select>
