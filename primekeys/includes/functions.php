<?php
        function primeDataDir() {
            return __DIR__ . '/../data';
        }

        function primeNow() {
            return date('Y-m-d H:i:s');
        }

        function primeDefaultSettings() {
            return [
                'brand_name' => 'PRIME KEYS',
                'brand_tagline' => 'Owner & Reseller Key Control Panel',
                'theme_color' => '#0d6efd',
                'maintenance_mode' => false,
                'library_online' => true,
                'mod_name' => 'Prime Android Suite',
                'mod_status' => 'Online',
                'referral_mode' => 'single_use',
                'price_per_day' => 10,
                'features' => [
                    'esp' => true,
                    'item' => true,
                    'aimbot' => true,
                    'bullet_tracking' => false,
                    'memory' => true,
                ],
            ];
        }

        function primeDefaultData($file) {
            $settings = primeDefaultSettings();
            switch ($file) {
                case 'settings':
                    return $settings;
                case 'users':
                    return [
                        [
                            'id' => 'OWN001',
                            'name' => 'Prime Owner',
                            'username' => 'owner',
                            'password' => password_hash('owner123', PASSWORD_DEFAULT),
                            'role' => 'owner',
                            'email' => 'owner@primekeys.local',
                            'status' => 'active',
                            'balance' => 0,
                            'created_at' => primeNow(),
                            'last_login_at' => '',
                        ],
                        [
                            'id' => 'RES001',
                            'name' => 'Demo Reseller',
                            'username' => 'reseller',
                            'password' => password_hash('reseller123', PASSWORD_DEFAULT),
                            'role' => 'reseller',
                            'email' => 'reseller@primekeys.local',
                            'status' => 'active',
                            'balance' => 500,
                            'created_at' => primeNow(),
                            'last_login_at' => '',
                        ],
                    ];
                case 'end_users':
                    return [];
                case 'keys':
                    return [];
                case 'audit_logs':
                    return [];
                default:
                    return [];
            }
        }

        function primeBootstrapData() {
            $dir = primeDataDir();
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $protectFile = $dir . '/.htaccess';
            if (!file_exists($protectFile)) {
                file_put_contents($protectFile, "Require all denied
<IfModule !mod_authz_core.c>
    Deny from all
</IfModule>
");
            }
            foreach (['settings', 'users', 'end_users', 'keys', 'audit_logs'] as $file) {
                $path = $dir . '/' . $file . '.json';
                if (!file_exists($path)) {
                    file_put_contents($path, json_encode(primeDefaultData($file), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            }
        }

        function primeLoadData($file, $default = null) {
            primeBootstrapData();
            $path = primeDataDir() . '/' . $file . '.json';
            if (!file_exists($path)) {
                $default = $default ?? primeDefaultData($file);
                file_put_contents($path, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                return $default;
            }
            $decoded = json_decode(file_get_contents($path), true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                return $default ?? primeDefaultData($file);
            }
            return $decoded ?? ($default ?? primeDefaultData($file));
        }

        function primeSaveData($file, $data) {
            primeBootstrapData();
            return file_put_contents(
                primeDataDir() . '/' . $file . '.json',
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        }

        function primeGetSettings() {
            $settings = primeLoadData('settings', primeDefaultSettings());
            return array_replace_recursive(primeDefaultSettings(), $settings);
        }

        function primeSaveSettings($settings) {
            $merged = array_replace_recursive(primeDefaultSettings(), $settings);
            return primeSaveData('settings', $merged);
        }

        function primeSanitize($value) {
            return htmlspecialchars(strip_tags(trim((string)$value)), ENT_QUOTES, 'UTF-8');
        }

        function primeRaw($value) {
            return trim((string)$value);
        }

        function primeSetFlash($type, $message) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['primekeys_flash'] = ['type' => $type, 'message' => $message];
        }

        function primeGetFlash() {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (!isset($_SESSION['primekeys_flash'])) {
                return null;
            }
            $flash = $_SESSION['primekeys_flash'];
            unset($_SESSION['primekeys_flash']);
            return $flash;
        }

        function primeRenderFlash() {
            $flash = primeGetFlash();
            if (!$flash) {
                return '';
            }
            $type = $flash['type'] === 'error' ? 'danger' : $flash['type'];
            return '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">'
                . htmlspecialchars($flash['message'])
                . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        }

        function primeGenerateId($prefix = 'ID') {
            return strtoupper($prefix) . substr(bin2hex(random_bytes(4)), 0, 8);
        }

        function primeSlug($value) {
            $value = strtoupper(trim((string)$value));
            $value = preg_replace('/[^A-Z0-9]+/', '-', $value);
            $value = trim($value, '-');
            return $value ?: 'PRIME';
        }

        function primeFormatDate($value) {
            if (empty($value)) {
                return '-';
            }
            return date('d M Y', strtotime($value));
        }

        function primeFormatDateTime($value) {
            if (empty($value)) {
                return '-';
            }
            return date('d M Y h:i A', strtotime($value));
        }

        function primeGetBaseUrl() {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            $parts = explode('/', trim($scriptDir, '/'));
            $primeIdx = false;
            foreach (array_reverse($parts, true) as $i => $part) {
                if ($part === 'primekeys') {
                    $primeIdx = $i;
                    break;
                }
            }
            if ($primeIdx !== false) {
                $baseParts = array_slice($parts, 0, $primeIdx + 1);
                return $protocol . '://' . $host . '/' . implode('/', $baseParts);
            }
            return $protocol . '://' . $host . ($scriptDir === '/' ? '' : $scriptDir);
        }

        function primeFindUserById($id) {
            foreach (primeLoadData('users') as $user) {
                if (($user['id'] ?? '') === $id) {
                    return $user;
                }
            }
            return null;
        }

        function primeFindUserByUsername($username) {
            foreach (primeLoadData('users') as $user) {
                if (($user['username'] ?? '') === $username) {
                    return $user;
                }
            }
            return null;
        }

        function primeFindEndUserById($id) {
            foreach (primeLoadData('end_users') as $user) {
                if (($user['id'] ?? '') === $id) {
                    return $user;
                }
            }
            return null;
        }

        function primeFindEndUserByReferralCode($code) {
            foreach (primeLoadData('end_users') as $user) {
                if (($user['referral_code'] ?? '') === $code) {
                    return $user;
                }
            }
            return null;
        }

        function primeFindKeyById($id) {
            foreach (primeLoadData('keys') as $key) {
                if (($key['id'] ?? '') === $id) {
                    return $key;
                }
            }
            return null;
        }

        function primeStatusBadgeClass($status) {
            $map = [
                'active' => 'success',
                'unused' => 'secondary',
                'used' => 'primary',
                'expired' => 'danger',
                'inactive' => 'secondary',
                'suspended' => 'warning text-dark',
            ];
            return $map[$status] ?? 'secondary';
        }

        function primeCanAccessOwnerScope($currentUser, $ownerUserId) {
            return ($currentUser['role'] ?? '') === 'owner' || ($currentUser['id'] ?? '') === $ownerUserId;
        }

        function primePortalLockedFor($currentUser) {
            $settings = primeGetSettings();
            return !empty($settings['maintenance_mode']) && ($currentUser['role'] ?? '') !== 'owner';
        }

        function primeCreateReferralCode($name, $ignoreId = '') {
            $base = substr(primeSlug($name), 0, 8);
            do {
                $code = $base . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
                $exists = false;
                foreach (primeLoadData('end_users') as $user) {
                    if (($user['referral_code'] ?? '') === $code && ($user['id'] ?? '') !== $ignoreId) {
                        $exists = true;
                        break;
                    }
                }
            } while ($exists);
            return $code;
        }

        function primeReferralUsageCount($code, $ignoreId = '') {
            $count = 0;
            foreach (primeLoadData('end_users') as $user) {
                if (($user['referred_by_code'] ?? '') === $code && ($user['id'] ?? '') !== $ignoreId) {
                    $count++;
                }
            }
            return $count;
        }

        function primeCanUseReferralCode($code, $ignoreId = '') {
            if ($code === '') {
                return true;
            }
            $referrer = primeFindEndUserByReferralCode($code);
            if (!$referrer) {
                return false;
            }
            $settings = primeGetSettings();
            if (($settings['referral_mode'] ?? 'single_use') === 'unlimited') {
                return true;
            }
            return primeReferralUsageCount($code, $ignoreId) < 1;
        }

        function primePriceForDuration($days) {
            $settings = primeGetSettings();
            $days = max(1, (int)$days);
            return $days * (float)($settings['price_per_day'] ?? 0);
        }

        function primeCreateKeyCode($type, $seed = '', $existingCodes = []) {
            $type = in_array($type, ['random', 'name', 'name_random'], true) ? $type : 'random';
            $seed = primeSlug($seed);
            do {
                if ($type === 'name') {
                    $code = $seed . '-KEY-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
                } elseif ($type === 'name_random') {
                    $code = $seed . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)) . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
                } else {
                    $code = 'PK-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)) . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
                }
            } while (in_array($code, $existingCodes, true));
            return $code;
        }

        function primeAddAuditLog($actor, $action, $message, $meta = []) {
            $logs = primeLoadData('audit_logs');
            $logs[] = [
                'id' => primeGenerateId('LOG'),
                'action' => $action,
                'message' => $message,
                'actor_id' => $actor['id'] ?? 'system',
                'actor_name' => $actor['name'] ?? 'System',
                'actor_role' => $actor['role'] ?? 'system',
                'created_at' => primeNow(),
                'meta' => $meta,
            ];
            primeSaveData('audit_logs', array_slice($logs, -200));
        }

        function primeUpdateAdminUser($updatedUser) {
            $users = primeLoadData('users');
            foreach ($users as &$user) {
                if (($user['id'] ?? '') === ($updatedUser['id'] ?? '')) {
                    $user = $updatedUser;
                    break;
                }
            }
            primeSaveData('users', $users);
        }

        function primeSyncDataState() {
            $now = time();
            $keys = primeLoadData('keys');
            $keysChanged = false;
            foreach ($keys as &$key) {
                $computed = 'unused';
                if (!empty($key['expires_at']) && strtotime($key['expires_at']) < $now) {
                    $computed = 'expired';
                } elseif (!empty($key['assigned_to'])) {
                    $computed = 'used';
                }
                if (($key['status'] ?? '') !== $computed) {
                    $key['status'] = $computed;
                    $keysChanged = true;
                }
            }
            unset($key);
            if ($keysChanged) {
                primeSaveData('keys', $keys);
            }

            $endUsers = primeLoadData('end_users');
            $usersChanged = false;
            foreach ($endUsers as &$endUser) {
                $status = $endUser['status'] ?? 'active';
                if ($status !== 'inactive') {
                    $computed = (!empty($endUser['expiry_at']) && strtotime($endUser['expiry_at']) < $now) ? 'expired' : 'active';
                    if ($status !== $computed) {
                        $endUser['status'] = $computed;
                        $usersChanged = true;
                    }
                }
            }
            unset($endUser);
            if ($usersChanged) {
                primeSaveData('end_users', $endUsers);
            }
        }

        function primeFilterOwnedRecords($records, $currentUser, $ownerField = 'owner_user_id') {
            if (($currentUser['role'] ?? '') === 'owner') {
                return $records;
            }
            return array_values(array_filter($records, fn($record) => ($record[$ownerField] ?? '') === ($currentUser['id'] ?? '')));
        }

        function primeGetRecentLogs($currentUser, $limit = 8) {
            $logs = array_reverse(primeLoadData('audit_logs'));
            if (($currentUser['role'] ?? '') !== 'owner') {
                $logs = array_values(array_filter($logs, fn($log) => ($log['actor_id'] ?? '') === ($currentUser['id'] ?? '')));
            }
            return array_slice($logs, 0, $limit);
        }

        function primeGetDashboardStats($currentUser) {
            primeSyncDataState();
            $keys = primeFilterOwnedRecords(primeLoadData('keys'), $currentUser);
            $endUsers = primeFilterOwnedRecords(primeLoadData('end_users'), $currentUser);
            $users = primeLoadData('users');
            $stats = [
                'total_keys' => count($keys),
                'unused_keys' => count(array_filter($keys, fn($key) => ($key['status'] ?? '') === 'unused')),
                'used_keys' => count(array_filter($keys, fn($key) => ($key['status'] ?? '') === 'used')),
                'expired_keys' => count(array_filter($keys, fn($key) => ($key['status'] ?? '') === 'expired')),
                'end_users' => count($endUsers),
                'expiring_users' => count(array_filter($endUsers, fn($user) => !empty($user['expiry_at']) && strtotime($user['expiry_at']) > time() && strtotime($user['expiry_at']) <= strtotime('+3 days'))),
                'balance' => (float)($currentUser['balance'] ?? 0),
                'resellers' => count(array_filter($users, fn($user) => ($user['role'] ?? '') === 'reseller')),
                'reseller_balance' => array_sum(array_map(fn($user) => ($user['role'] ?? '') === 'reseller' ? (float)($user['balance'] ?? 0) : 0, $users)),
            ];
            return $stats;
        }
        ?>
