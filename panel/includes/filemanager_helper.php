<?php
function validatePath(string $basePath, string $requestedPath): ?string {
    $realBase = realpath($basePath);
    if ($realBase === false) return null;

    // Build absolute path
    if (substr($requestedPath, 0, 1) === '/') {
        $combined = $realBase . $requestedPath;
    } else {
        $combined = $realBase . '/' . $requestedPath;
    }

    // Normalize the path
    $parts = explode('/', str_replace('\\', '/', $combined));
    $normalized = [];
    foreach ($parts as $part) {
        if ($part === '' || $part === '.') continue;
        if ($part === '..') {
            array_pop($normalized);
        } else {
            $normalized[] = $part;
        }
    }
    $resolved = '/' . implode('/', $normalized);

    // Verify it stays within base
    if (strpos($resolved . '/', $realBase . '/') !== 0) return null;
    return $resolved;
}

function listDirectory(string $path): array {
    if (!is_dir($path)) return [];
    $items = [];
    $entries = scandir($path);
    if ($entries === false) return [];
    foreach ($entries as $item) {
        if ($item === '.' || $item === '..') continue;
        $full = $path . '/' . $item;
        $items[] = [
            'name' => $item,
            'type' => is_dir($full) ? 'dir' : 'file',
            'size' => is_file($full) ? filesize($full) : 0,
            'modified' => filemtime($full),
            'permissions' => substr(sprintf('%o', fileperms($full)), -4),
            'is_writable' => is_writable($full),
        ];
    }
    // Sort: dirs first, then files
    usort($items, fn($a, $b) => $a['type'] === $b['type'] ? strcasecmp($a['name'], $b['name']) : ($a['type'] === 'dir' ? -1 : 1));
    return $items;
}

function getFileExtension(string $name): string {
    return strtolower(pathinfo($name, PATHINFO_EXTENSION));
}

function isTextFile(string $name): bool {
    $textExts = ['php', 'html', 'htm', 'css', 'js', 'json', 'txt', 'xml', 'md', 'htaccess', 'env', 'sql', 'sh', 'yaml', 'yml', 'ini', 'conf', 'log'];
    return in_array(getFileExtension($name), $textExts) || strpos($name, '.') === false;
}

function getFileIcon(string $name): string {
    $ext = getFileExtension($name);
    $icons = [
        'php' => '🐘', 'html' => '🌐', 'htm' => '🌐', 'css' => '🎨',
        'js' => '⚡', 'json' => '📋', 'txt' => '📄', 'md' => '📝',
        'zip' => '📦', 'tar' => '📦', 'gz' => '📦',
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️', 'svg' => '🖼️',
        'pdf' => '📕', 'sql' => '🗄️',
    ];
    return $icons[$ext] ?? '📄';
}
