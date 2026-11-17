<?php

declare(strict_types=1);

require __DIR__ . '/../../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/equipment.php';
require_once ROOT_PATH . '/includes/images.php';

$user = require_role('admin', 'operator');
$isAdmin = $user['role'] === 'admin';

$equipmentId = to_id(query_string('id'));
$equipment = $equipmentId === null ? null : find_equipment($equipmentId);

if ($equipment === null) {
    render_error_page(404, 'El equipo no existe o fue eliminado.');
}

remember_recent_equipment($equipmentId);

$activeLoans = list_active_loans_for_equipment($equipmentId);
$images = list_equipment_images($equipmentId);
$allowedExtensions = array_keys(config('uploads.allowed_types', []));
$maxMb = round((int) config('uploads.max_size_bytes') / 1024 / 1024, 1);
$pageTitle = $equipment['code'];

require ROOT_PATH . '/includes/templates/header.php';
?>
<h1><?= e($equipment['code']) ?> · <?= e($equipment['name']) ?></h1>

<table>
    <tr><th>Categoría</th><td><?= e($equipment['category_name']) ?></td></tr>
    <tr><th>Estado</th><td><?= e(EQUIPMENT_STATUSES[$equipment['status']]) ?></td></tr>
    <tr><th>Días máximos de préstamo</th><td><?= e($equipment['max_loan_days']) ?></td></tr>
    <tr><th>Descripción</th><td><?= e($equipment['description'] ?? '') ?></td></tr>
</table>

<h2>Imágenes</h2>
<?php if ($images === []): ?>
    <p>El equipo no tiene imágenes.</p>
<?php else: ?>
    <div class="gallery">
        <?php foreach ($images as $image): ?>
            <figure>
                <a href="<?= e(url('image.php?id=' . $image['id'])) ?>">
                    <img src="<?= e(url('image.php?id=' . $image['id'])) ?>" alt="<?= e($image['original_name']) ?>">
                </a>
                <figcaption><?= e($image['original_name']) ?></figcaption>
                <?php if ($isAdmin): ?>
                    <form method="post" action="<?= e(url('actions/equipment/delete_image.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= e($image['id']) ?>">
                        <button type="submit">Eliminar imagen</button>
                    </form>
                <?php endif; ?>
            </figure>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($isAdmin && count($images) < MAX_IMAGES_PER_EQUIPMENT): ?>
    <form method="post" enctype="multipart/form-data" class="form"
          action="<?= e(url('actions/equipment/upload_images.php?id=' . $equipmentId)) ?>">
        <?= csrf_field() ?>
        <label for="images">
            Agregar imágenes (<?= e(implode(', ', $allowedExtensions)) ?>; máximo <?= e($maxMb) ?> MB cada una,
            hasta <?= e(MAX_IMAGES_PER_UPLOAD) ?> a la vez)
        </label>
        <input type="file" id="images" name="images[]" multiple required
               accept="<?= e('.' . implode(',.', $allowedExtensions)) ?>">
        <button type="submit">Subir imágenes</button>
    </form>
<?php endif; ?>

<h2>Préstamos activos</h2>
<?php if ($activeLoans === []): ?>
    <p>El equipo no tiene préstamos activos.</p>
<?php else: ?>
    <table>
        <thead>
        <tr><th>Préstamo</th><th>Solicitante</th><th>Estado</th><th>Inicio</th><th>Fin previsto</th></tr>
        </thead>
        <tbody>
        <?php foreach ($activeLoans as $loan): ?>
            <tr>
                <td>#<?= e($loan['id']) ?></td>
                <td><?= e($loan['borrower_name']) ?></td>
                <td><?= e(LOAN_STATUSES[$loan['status']]) ?></td>
                <td><?= e($loan['start_at']) ?></td>
                <td><?= e($loan['end_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p>
    <a href="<?= e(url('pages/equipment/index.php')) ?>">Volver al listado</a>
    <?php if ($isAdmin): ?>
        · <a href="<?= e(url('pages/equipment/edit.php?id=' . $equipmentId)) ?>">Editar</a>
    <?php endif; ?>
</p>
<?php require ROOT_PATH . '/includes/templates/footer.php'; ?>
