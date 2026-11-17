<?php

declare(strict_types=1);

/*
 * Formulario compartido de crear y editar equipo.
 * Variables: $equipment (valores), $categoryOptions, $errors, $formAction, $submitLabel
 * y, al editar, $equipmentId y $version.
 */
?>
<form method="post" action="<?= e($formAction) ?>" class="form">
    <?= csrf_field() ?>
    <?php if (isset($equipmentId)): ?>
        <input type="hidden" name="id" value="<?= e($equipmentId) ?>">
        <input type="hidden" name="version" value="<?= e($version) ?>">
    <?php endif; ?>

    <label for="code">Código de inventario *</label>
    <input type="text" id="code" name="code" maxlength="30" required value="<?= e($equipment['code']) ?>">
    <?= field_error($errors, 'code') ?>

    <label for="name">Nombre *</label>
    <input type="text" id="name" name="name" maxlength="100" required value="<?= e($equipment['name']) ?>">
    <?= field_error($errors, 'name') ?>

    <label for="category_id">Categoría *</label>
    <select id="category_id" name="category_id" required>
        <option value="">Seleccione…</option>
        <?php foreach ($categoryOptions as $option): ?>
            <option value="<?= e($option['id']) ?>" <?= (string) $option['id'] === $equipment['category_id'] ? 'selected' : '' ?>>
                <?= e($option['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?= field_error($errors, 'category_id') ?>

    <label for="status">Estado *</label>
    <select id="status" name="status" required>
        <?php foreach (EQUIPMENT_STATUSES as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= $value === $equipment['status'] ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <?= field_error($errors, 'status') ?>

    <label for="description">Descripción</label>
    <textarea id="description" name="description" maxlength="500" rows="3"><?= e($equipment['description']) ?></textarea>
    <?= field_error($errors, 'description') ?>

    <button type="submit"><?= e($submitLabel) ?></button>
    <a href="<?= e(url('pages/equipment/index.php')) ?>">Cancelar</a>
</form>
