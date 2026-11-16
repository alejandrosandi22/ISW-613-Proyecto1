<?php

declare(strict_types=1);

/*
 * Validación en el servidor. La validación del navegador (required, maxlength)
 * es solo comodidad: aquí se aplican las reglas reales.
 * Cada función agrega el mensaje en $errors[$field] si el valor no es válido.
 */

/**
 * Texto de una línea: codificación UTF-8, sin caracteres de control y longitud entre $min y $max.
 * Con $min = 0 el campo es opcional.
 */
function validate_text(array &$errors, string $field, string $value, string $label, int $min, int $max): void
{
    if ($value === '') {
        if ($min > 0) {
            $errors[$field] = "El campo «{$label}» es obligatorio.";
        }
        return;
    }

    if (!mb_check_encoding($value, 'UTF-8') || preg_match('/[\x00-\x1F\x7F]/u', $value) === 1) {
        $errors[$field] = "El campo «{$label}» contiene caracteres no permitidos.";
        return;
    }

    $length = mb_strlen($value);
    if ($length < $min || $length > $max) {
        $errors[$field] = "El campo «{$label}» debe tener entre {$min} y {$max} caracteres.";
    }
}

/**
 * Número entero dentro de un rango. Devuelve el entero o null si no es válido.
 */
function validate_int_range(array &$errors, string $field, string $value, string $label, int $min, int $max): ?int
{
    $number = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $min, 'max_range' => $max],
    ]);

    if ($number === false) {
        $errors[$field] = "El campo «{$label}» debe ser un número entero entre {$min} y {$max}.";
        return null;
    }

    return $number;
}
