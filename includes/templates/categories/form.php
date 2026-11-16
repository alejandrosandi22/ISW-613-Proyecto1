<?php

declare(strict_types=1);

/*
 * Formulario compartido de crear y editar categoría.
 * Variables: $category (valores), $errors, $formAction, $submitLabel y opcional $categoryId.
 */
?>
<form method="post" action="<?= e($formAction) ?>" class="form">
    <?= csrf_field() ?>
    <?php if (isset($categoryId)): ?>
        <input type="hidden" name="id" value="<?= e($categoryId) ?>">
    <?php endif; ?>

    <label for="name">Nombre *</label>
    <input type="text" id="name" name="name" maxlength="60" required value="<?= e($category['name']) ?>">
    <?= field_error($errors, 'name') ?>

    <label for="description">Descripción</label>
    <input type="text" id="description" name="description" maxlength="255" value="<?= e($category['description']) ?>">
    <?= field_error($errors, 'description') ?>

    <label for="max_loan_days">Días máximos de préstamo *</label>
    <input type="number" id="max_loan_days" name="max_loan_days" min="1" max="60" required
           value="<?= e($category['max_loan_days']) ?>">
    <?= field_error($errors, 'max_loan_days') ?>

    <button type="submit"><?= e($submitLabel) ?></button>
    <a href="<?= e(url('pages/categories/index.php')) ?>">Cancelar</a>
</form>
