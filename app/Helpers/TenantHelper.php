<?php

if (!function_exists('tenant_url')) {
    function tenant_url($path = '') {
        $host = request()->getHost();
        return "https://{$host}" . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('tenant_asset')) {
    function tenant_asset($path) {
        return tenant_url('storage/' . ltrim($path, '/'));
    }
}
