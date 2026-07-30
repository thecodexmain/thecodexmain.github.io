<?php
require_once __DIR__ . '/json_helper.php';
require_once __DIR__ . '/functions.php';

function extractZip(string $zipFile, string $destination): array {
    if (!file_exists($zipFile)) {
        return ['success' => false, 'error' => 'ZIP file not found'];
    }
    if (!class_exists('ZipArchive')) {
        return ['success' => false, 'error' => 'ZipArchive extension not available'];
    }
    $zip = new ZipArchive();
    $result = $zip->open($zipFile);
    if ($result !== true) {
        return ['success' => false, 'error' => 'Cannot open ZIP (code: ' . $result . ')'];
    }
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (strpos($name, '..') !== false || strpos($name, chr(0)) !== false || strpos($name, "\0") !== false) {
            $zip->close();
            return ['success' => false, 'error' => 'Invalid ZIP content (path traversal detected)'];
        }
    }
    $zip->extractTo($destination);
    $zip->close();
    return ['success' => true];
}

function deleteFolderRecursive(string $path): bool {
    if (!is_dir($path)) return false;
    $realRoot = realpath(USERS_PATH);
    $realPath = realpath($path);
    if (!$realPath || !$realRoot) return false;
    if (strpos($realPath . DIRECTORY_SEPARATOR, $realRoot . DIRECTORY_SEPARATOR) !== 0) return false;
    $it = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
    $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($ri as $file) {
        if ($file->isDir()) rmdir($file->getPathname());
        else unlink($file->getPathname());
    }
    return rmdir($path);
}

function createSite(string $userId, string $siteName, string $scriptId = ''): array {
    $users = readJson(DATA_PATH . 'users.json');
    $user = findById($users, $userId);
    if (!$user) return ['success' => false, 'error' => 'User not found'];

    $plan = getUserPlan($user['plan'] ?? 'basic');
    $maxSites = (int)($plan['max_sites'] ?? 1);

    $sites = readJson(DATA_PATH . 'sites.json');
    $userSites = array_filter($sites, fn($s) => $s['user_id'] === $userId);
    if (count($userSites) >= $maxSites) {
        return ['success' => false, 'error' => 'Site limit reached for your plan (' . $maxSites . ' max)'];
    }

    $siteName = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($siteName)));
    if (empty($siteName) || strlen($siteName) < 2) {
        return ['success' => false, 'error' => 'Invalid site name (min 2 chars, alphanumeric/dash/underscore)'];
    }

    foreach ($sites as $s) {
        if ($s['user_id'] === $userId && $s['name'] === $siteName) {
            return ['success' => false, 'error' => 'Site name already exists'];
        }
    }

    $siteDir = USERS_PATH . $user['username'] . '/' . $siteName;
    if (!is_dir($siteDir) && !mkdir($siteDir, 0755, true)) {
        return ['success' => false, 'error' => 'Cannot create site directory'];
    }

    $siteId = generateId('site');
    $sites[] = [
        'id' => $siteId,
        'user_id' => $userId,
        'username' => $user['username'],
        'name' => $siteName,
        'path' => $siteDir,
        'script_id' => $scriptId,
        'domain' => '',
        'status' => 'active',
        'created_at' => date('c'),
        'updated_at' => date('c'),
    ];
    writeJson(DATA_PATH . 'sites.json', $sites);

    if ($scriptId) {
        deployScript($siteId, $scriptId);
    }

    appLog('create_site', $user['username'], "Created site: {$siteName}");
    return ['success' => true, 'site_id' => $siteId, 'path' => $siteDir];
}

function deployScript(string $siteId, string $scriptId, bool $preserveConfig = false): array {
    $sites = readJson(DATA_PATH . 'sites.json');
    $site = findById($sites, $siteId);
    if (!$site) return ['success' => false, 'error' => 'Site not found'];

    $scripts = readJson(DATA_PATH . 'scripts.json');
    $script = findById($scripts, $scriptId);
    if (!$script) return ['success' => false, 'error' => 'Script not found'];

    $scriptFile = SCRIPTS_PATH . ($script['file'] ?? '');
    if (empty($script['file']) || !file_exists($scriptFile)) {
        return ['success' => false, 'error' => 'Script file not uploaded yet. Please upload the ZIP first.'];
    }

    $siteDir = $site['path'];

    $preserved = [];
    if ($preserveConfig && is_dir($siteDir)) {
        $configFiles = ['config.php', 'wp-config.php', '.env', 'config.json', 'settings.php'];
        foreach ($configFiles as $cf) {
            $cfPath = $siteDir . '/' . $cf;
            if (file_exists($cfPath)) {
                $preserved[$cf] = file_get_contents($cfPath);
            }
        }
    }

    if (is_dir($siteDir)) {
        $it = new RecursiveDirectoryIterator($siteDir, FilesystemIterator::SKIP_DOTS);
        $ri = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) {
            if ($file->isDir()) rmdir($file->getPathname());
            else unlink($file->getPathname());
        }
    }

    $result = extractZip($scriptFile, $siteDir);
    if (!$result['success']) return $result;

    foreach ($preserved as $name => $content) {
        file_put_contents($siteDir . '/' . $name, $content);
    }

    $sites = readJson(DATA_PATH . 'sites.json');
    updateById($sites, $siteId, ['script_id' => $scriptId, 'updated_at' => date('c')]);
    writeJson(DATA_PATH . 'sites.json', $sites);

    appLog('deploy_script', $site['username'] ?? '', "Deployed script '{$script['name']}' to site '{$site['name']}'");
    return ['success' => true];
}

function deleteSite(string $siteId, string $requestingUserId = '', string $requestingRole = ''): array {
    $sites = readJson(DATA_PATH . 'sites.json');
    $site = findById($sites, $siteId);
    if (!$site) return ['success' => false, 'error' => 'Site not found'];

    if ($requestingRole !== 'admin' && $site['user_id'] !== $requestingUserId) {
        return ['success' => false, 'error' => 'Access denied'];
    }

    if (is_dir($site['path'])) {
        deleteFolderRecursive($site['path']);
    }

    deleteById($sites, $siteId);
    writeJson(DATA_PATH . 'sites.json', $sites);

    appLog('delete_site', $site['username'] ?? '', "Deleted site: {$site['name']}");
    return ['success' => true];
}

function createBackup(string $userId, string $siteId): array {
    $sites = readJson(DATA_PATH . 'sites.json');
    $site = findById($sites, $siteId);
    if (!$site || $site['user_id'] !== $userId) {
        return ['success' => false, 'error' => 'Site not found'];
    }

    if (!is_dir($site['path'])) {
        return ['success' => false, 'error' => 'Site directory does not exist'];
    }

    $backupDir = UPLOADS_PATH . 'backups/' . $userId . '/';
    if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

    $backupName = $site['name'] . '_' . date('Ymd_His') . '.zip';
    $backupPath = $backupDir . $backupName;

    if (!class_exists('ZipArchive')) {
        return ['success' => false, 'error' => 'ZipArchive not available'];
    }

    $zip = new ZipArchive();
    if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return ['success' => false, 'error' => 'Cannot create backup ZIP'];
    }

    $siteDir = rtrim($site['path'], '/') . '/';
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($siteDir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->isFile()) {
            $relative = str_replace($siteDir, '', $file->getPathname());
            $zip->addFile($file->getPathname(), $relative);
        }
    }
    $zip->close();

    appLog('backup', $site['username'] ?? '', "Created backup: {$backupName}");
    return ['success' => true, 'backup' => $backupName, 'path' => $backupPath];
}
