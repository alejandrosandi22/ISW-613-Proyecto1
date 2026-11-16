<?php

declare(strict_types=1);

/**
 * Registros por página de un listado.
 * Prioridad: parámetro ?per_page= (se guarda en una cookie por módulo),
 * luego la cookie, luego el valor predeterminado.
 * La cookie es entrada no confiable: solo se aceptan valores de la lista blanca.
 */
function resolve_per_page(string $module): int
{
    $allowed = config('pagination.allowed_per_page', [10]);
    $default = (int) config('pagination.default_per_page', 10);
    $cookieName = config('cookies.per_page', 'sigel_per_page') . '_' . $module;

    $fromQuery = query_string('per_page');
    if ($fromQuery !== '') {
        $value = filter_var($fromQuery, FILTER_VALIDATE_INT);
        if ($value === false || !in_array($value, $allowed, true)) {
            return $default;
        }

        setcookie($cookieName, (string) $value, [
            'expires'  => time() + 60 * 60 * 24 * 365,
            'path'     => cookie_path(),
            'secure'   => is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        return $value;
    }

    $fromCookie = $_COOKIE[$cookieName] ?? null;
    if (is_string($fromCookie)) {
        $value = filter_var($fromCookie, FILTER_VALIDATE_INT);
        if ($value !== false && in_array($value, $allowed, true)) {
            return $value;
        }
    }

    return $default;
}

/**
 * Calcula la página actual (de ?page=), el total de páginas y el desplazamiento.
 */
function paginate(int $total, int $perPage): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = to_id(query_string('page')) ?? 1;
    $page = min($page, $pages);

    return [
        'total'    => $total,
        'per_page' => $perPage,
        'page'     => $page,
        'pages'    => $pages,
        'offset'   => ($page - 1) * $perPage,
    ];
}

/**
 * URL de un listado conservando los filtros; omite los parámetros vacíos.
 */
function list_url(string $path, array $params): string
{
    $params = array_filter($params, static fn ($value) => $value !== '' && $value !== null);

    return url($path) . ($params === [] ? '' : '?' . http_build_query($params));
}

/**
 * Patrón para LIKE con los comodines del usuario (% y _) escapados.
 */
function like_pattern(string $search): string
{
    return '%' . addcslashes($search, '%_\\') . '%';
}
