<?php

declare(strict_types=1);

/*
 * Formulario compartido de crear y editar usuario.
 * Variables: $formUser (valores), $roles, $errors, $formAction, $submitLabel,
 * $isSelf (el administrador se edita a sí mismo) y, al editar, $userId.
 */
$isEdit = isset($userId);
?>
<form method="post" action="<?= e($formAction) ?>" class="form">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= e($userId) ?>">
    <?php endif; ?>

    <label for="full_name">Nombre completo *</label>
    <input type="text" id="full_name" name="full_name" maxlength="100" required value="<?= e($formUser['full_name']) ?>">
    <?= field_error($errors, 'full_name') ?>

    <label for="email">Correo electrónico *</label>
    <input type="email" id="email" name="email" maxlength="150" required value="<?= e($formUser['email']) ?>">
    <?= field_error($errors, 'email') ?>

    <label for="role_id">Rol *</label>
    <select id="role_id" name="role_id" required <?= $isSelf ? 'disabled' : '' ?>>
        <?php foreach ($roles as $role): ?>
            <option value="<?= e($role['id']) ?>" <?= (string) $role['id'] === $formUser['role_id'] ? 'selected' : '' ?>>
                <?= e($role['label']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if ($isSelf): ?>
        <input type="hidden" name="role_id" value="<?= e($formUser['role_id']) ?>">
        <small>No puede cambiar su propio rol.</small>
    <?php endif; ?>
    <?= field_error($errors, 'role_id') ?>

    <label>
        <input type="checkbox" name="is_active" value="1" <?= $formUser['is_active'] === '1' ? 'checked' : '' ?>
            <?= $isSelf ? 'disabled' : '' ?>>
        Cuenta activa (puede iniciar sesión)
    </label>
    <?php if ($isSelf): ?>
        <input type="hidden" name="is_active" value="1">
        <small>No puede desactivar su propia cuenta.</small>
    <?php endif; ?>
    <?= field_error($errors, 'is_active') ?>

    <label for="password">Contraseña <?= $isEdit ? '(dejar vacía para conservar la actual)' : '*' ?></label>
    <input type="password" id="password" name="password" maxlength="72" autocomplete="new-password" <?= $isEdit ? '' : 'required' ?>>
    <small>Entre 8 y 72 caracteres, con al menos una letra y un número.</small>
    <?= field_error($errors, 'password') ?>

    <label for="password_confirmation">Confirmar contraseña</label>
    <input type="password" id="password_confirmation" name="password_confirmation" maxlength="72" autocomplete="new-password">
    <?= field_error($errors, 'password_confirmation') ?>

    <button type="submit"><?= e($submitLabel) ?></button>
    <a href="<?= e(url('pages/users/index.php')) ?>">Cancelar</a>
</form>
