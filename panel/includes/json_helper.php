<?php
function readJson(string $file): array {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    if ($content === false || trim($content) === '') return [];
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function writeJson(string $file, array $data): bool {
    return safeFileWrite($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function safeFileWrite(string $file, string $content): bool {
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $fp = fopen($file, 'c');
    if (!$fp) return false;
    if (flock($fp, LOCK_EX)) {
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $content);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }
    fclose($fp);
    return false;
}

function findById(array $data, string $id): ?array {
    foreach ($data as $item) {
        if (($item['id'] ?? '') === $id) return $item;
    }
    return null;
}

function updateById(array &$data, string $id, array $updates): bool {
    foreach ($data as &$item) {
        if (($item['id'] ?? '') === $id) {
            $item = array_merge($item, $updates);
            return true;
        }
    }
    return false;
}

function deleteById(array &$data, string $id): bool {
    foreach ($data as $k => $item) {
        if (($item['id'] ?? '') === $id) {
            unset($data[$k]);
            $data = array_values($data);
            return true;
        }
    }
    return false;
}

function generateId(string $prefix = 'id'): string {
    return $prefix . bin2hex(random_bytes(4));
}
