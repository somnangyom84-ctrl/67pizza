<?php
declare(strict_types=1);
/** Shared admin helpers: sortable headers, status badges, small components. */
function sort_link(string $label, string $col, string $currentSort, string $currentDir, array $query = []): string
{
    $dir = ($currentSort === $col && $currentDir === 'asc') ? 'desc' : 'asc';
    $icon = $currentSort === $col ? ($currentDir === 'asc' ? ' <i class="bi bi-caret-up-fill"></i>' : ' <i class="bi bi-caret-down-fill"></i>') : '';
    $q = http_build_query(array_merge($query, ['sort' => $col, 'dir' => $dir, 'page' => 1]));
    return '<a href="?' . e($q) . '" class="sort-link">' . e($label) . $icon . '</a>';
}

function bool_badge(int|string|null $v, string $yes = 'Active', string $no = 'Inactive'): string
{
    return (int) $v === 1 ? '<span class="badge-status-completed">' . e($yes) . '</span>' : '<span class="badge-status-cancelled">' . e($no) . '</span>';
}

function admin_delete_form(string $action, int $id, string $confirm = 'Delete this record? This cannot be undone.'): string
{
    return '<form method="post" class="d-inline" data-confirm="' . e($confirm) . '">' . csrf_field()
        . '<input type="hidden" name="action" value="' . e($action) . '"><input type="hidden" name="id" value="' . $id . '">'
        . '<button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button></form>';
}
