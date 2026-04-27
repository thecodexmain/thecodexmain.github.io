<?php
// REST API endpoint
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/api_helper.php';
require_once __DIR__ . '/includes/deploy.php';
require_once __DIR__ . '/includes/links_helper.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: X-API-Key, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Extract API key
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
if (!$apiKey) {
    http_response_code(401);
    echo json_encode(['error' => 'API key required. Send X-API-Key header or ?api_key= param.']);
    exit;
}

$authResult = validateApiKey($apiKey);
if (!$authResult['valid']) {
    http_response_code(401);
    echo json_encode(['error' => $authResult['error'] ?? 'Invalid or expired API key.']);
    exit;
}
$userId = $authResult['user_id'];

$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'get_sites':
        $users = readJson(DATA_PATH . 'users.json');
        $me = findById($users, $userId);
        echo json_encode(['success' => true, 'sites' => $me['sites'] ?? []]);
        break;

    case 'create_site':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $name = sanitize($input['name'] ?? '');
        $scriptId = sanitize($input['script_id'] ?? '');
        if (!$name) { http_response_code(400); echo json_encode(['error' => 'name required']); break; }
        $result = createSite($userId, $name, $scriptId);
        echo json_encode($result);
        break;

    case 'deploy_script':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $siteName = sanitize($input['site'] ?? '');
        $scriptId = sanitize($input['script_id'] ?? '');
        if (!$siteName || !$scriptId) { http_response_code(400); echo json_encode(['error' => 'site and script_id required']); break; }
        $result = deployScript($userId, $siteName, $scriptId);
        echo json_encode($result);
        break;

    case 'delete_site':
        if ($method !== 'DELETE' && $method !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST or DELETE required']); break; }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $siteName = sanitize($input['site'] ?? $_GET['site'] ?? '');
        if (!$siteName) { http_response_code(400); echo json_encode(['error' => 'site required']); break; }
        $result = deleteSite($userId, $siteName);
        echo json_encode($result);
        break;

    case 'create_link':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $target = sanitize($input['target'] ?? '');
        if (!$target || !filter_var($target, FILTER_VALIDATE_URL)) { http_response_code(400); echo json_encode(['error' => 'valid target URL required']); break; }
        $result = createShortLink($userId, $target, $input['code'] ?? null, $input['password'] ?? null, $input['expiry'] ?? null, $input['max_clicks'] ?? null);
        if ($result['success']) {
            $result['short_url'] = BASE_URL . 'l/' . $result['code'];
        }
        echo json_encode($result);
        break;

    case 'get_links':
        $allLinks = readJson(DATA_PATH . 'links.json');
        $myLinks = array_values(array_filter($allLinks, fn($l) => $l['user_id'] === $userId));
        echo json_encode(['success' => true, 'links' => $myLinks]);
        break;

    case 'get_scripts':
        $scripts = array_values(array_filter(readJson(DATA_PATH . 'scripts.json'), fn($s) => ($s['status'] ?? 'active') === 'active'));
        $clean = array_map(fn($s) => ['id' => $s['id'], 'name' => $s['name'], 'version' => $s['version'], 'category' => $s['category'], 'description' => $s['description']], $scripts);
        echo json_encode(['success' => true, 'scripts' => $clean]);
        break;

    case 'mark_notif_read':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST required']); break; }
        $notifs = readJson(DATA_PATH . 'notifications.json');
        $updated = 0;
        foreach ($notifs as &$n) {
            if ($n['user_id'] === $userId && !$n['read']) {
                $n['read'] = true; $updated++;
            }
        } unset($n);
        writeJson(DATA_PATH . 'notifications.json', $notifs);
        echo json_encode(['success' => true, 'marked' => $updated]);
        break;

    case 'create_backup':
        if ($method !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $siteName = sanitize($input['site'] ?? '');
        if (!$siteName) { http_response_code(400); echo json_encode(['error' => 'site required']); break; }
        $result = createBackup($userId, $siteName);
        echo json_encode($result);
        break;

    case 'ping':
        echo json_encode(['success' => true, 'message' => 'Prime Webs API is alive', 'user_id' => $userId, 'timestamp' => date('c')]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => "Unknown action: {$action}", 'available' => ['get_sites', 'create_site', 'deploy_script', 'delete_site', 'create_link', 'get_links', 'get_scripts', 'mark_notif_read', 'create_backup', 'ping']]);
}
