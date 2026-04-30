<?php
// ─────────────────────────────────────────────────────────────
//  Keymaster API  –  api.php
//
//  Endpoints (all via GET or POST):
//
//  Generate a key
//    GET  api.php?action=generate&device_id=XXX&plan=basic&days=30&admin_token=TOKEN
//
//  Check key status (public)
//    GET  api.php?action=status&device_id=XXX
//
//  Revoke a key (admin only)
//    GET  api.php?action=revoke&device_id=XXX&admin_token=TOKEN
//
//  Toggle registrations (admin only)
//    GET  api.php?action=toggle_reg&value=0|1&admin_token=TOKEN
//
//  List all keys (admin only)
//    GET  api.php?action=list&admin_token=TOKEN
// ─────────────────────────────────────────────────────────────

require_once __DIR__ . '/config.php';

// Merge GET + POST first so we can read the action before setting headers
$params = array_merge($_GET, $_POST);
$action = $params['action'] ?? '';

// Public endpoints allow any origin; admin endpoints are locked down.
$public_actions = ['status'];
if (in_array($action, $public_actions, true)) {
    header('Access-Control-Allow-Origin: *');
} // else: no CORS header → browsers block cross-origin admin requests

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

switch ($action) {

    // ── Generate key ─────────────────────────────────────────
    case 'generate':
        api_require_admin_token($params);

        // Check registrations flag
        if (get_setting('registrations_open', '1') !== '1') {
            json_out(['success' => false, 'message' => 'Registrations are currently closed.'], 403);
        }

        $device_id = sanitise_device_id($params['device_id'] ?? '');
        if ($device_id === '') {
            json_out(['success' => false, 'message' => 'device_id is required.'], 400);
        }

        $plan = trim($params['plan'] ?? 'basic');
        $days = max(1, (int)($params['days'] ?? 30));

        $db = get_db();

        // Check if device already has a key
        $chk = $db->prepare("SELECT id FROM keys WHERE device_id = :d COLLATE NOCASE");
        $chk->execute([':d' => $device_id]);
        if ($chk->fetch()) {
            json_out(['success' => false, 'message' => 'A key for this device_id already exists.'], 409);
        }

        $api_key    = generate_api_key();
        $expires_at = (new DateTime("+{$days} days"))->format('Y-m-d H:i:s');

        $ins = $db->prepare("
            INSERT INTO keys (device_id, api_key, plan, days, expires_at)
            VALUES (:device_id, :api_key, :plan, :days, :expires_at)
        ");
        $ins->execute([
            ':device_id'  => $device_id,
            ':api_key'    => $api_key,
            ':plan'       => $plan,
            ':days'       => $days,
            ':expires_at' => $expires_at,
        ]);

        json_out([
            'success'    => true,
            'device_id'  => $device_id,
            'api_key'    => $api_key,
            'plan'       => $plan,
            'days'       => $days,
            'expires_at' => $expires_at,
        ]);

    // ── Key status (public) ───────────────────────────────────
    case 'status':
        $device_id = sanitise_device_id($params['device_id'] ?? '');
        if ($device_id === '') {
            json_out(['success' => false, 'message' => 'device_id is required.'], 400);
        }

        $row = get_key_status($device_id);
        if (!$row) {
            json_out(['success' => false, 'message' => 'No key found for this device_id.'], 404);
        }

        $message = $row['is_expired']
            ? 'Key Expired'
            : ($row['status'] === 'revoked' ? 'Key Revoked' : 'Key Active');

        json_out([
            'success'    => true,
            'device_id'  => $row['device_id'],
            'api_key'    => $row['api_key'],
            'plan'       => $row['plan'],
            'status'     => $row['status'],
            'message'    => $message,
            'days_left'  => $row['days_left'],
            'expires_at' => $row['expires_at'],
            'created_at' => $row['created_at'],
        ]);

    // ── Revoke key ────────────────────────────────────────────
    case 'revoke':
        api_require_admin_token($params);

        $device_id = sanitise_device_id($params['device_id'] ?? '');
        if ($device_id === '') {
            json_out(['success' => false, 'message' => 'device_id is required.'], 400);
        }

        $db  = get_db();
        $upd = $db->prepare(
            "UPDATE keys SET status = 'revoked' WHERE device_id = :d COLLATE NOCASE"
        );
        $upd->execute([':d' => $device_id]);

        if ($upd->rowCount() === 0) {
            json_out(['success' => false, 'message' => 'device_id not found.'], 404);
        }
        json_out(['success' => true, 'message' => "Key for {$device_id} revoked."]);

    // ── Toggle registrations ─────────────────────────────────
    case 'toggle_reg':
        api_require_admin_token($params);
        $value = ($params['value'] ?? '1') === '1' ? '1' : '0';
        set_setting('registrations_open', $value);
        $label = $value === '1' ? 'open' : 'closed';
        json_out(['success' => true, 'message' => "Registrations are now {$label}.", 'registrations_open' => (bool)$value]);

    // ── List all keys ─────────────────────────────────────────
    case 'list':
        api_require_admin_token($params);

        $db  = get_db();
        $stm = $db->query("SELECT * FROM keys ORDER BY created_at DESC");
        $rows = $stm->fetchAll();

        // Enrich each row with days_left
        $now = new DateTime('now');
        foreach ($rows as &$r) {
            $expires   = new DateTime($r['expires_at']);
            $diff      = $now->diff($expires);
            $r['days_left']  = ($expires > $now) ? (int)$diff->days : 0;
            $r['is_expired'] = ($expires <= $now);
            // Sync status
            if ($r['is_expired'] && $r['status'] === 'active') {
                $r['status'] = 'expired';
            }
        }
        unset($r);

        json_out(['success' => true, 'keys' => $rows]);

    default:
        json_out(['success' => false, 'message' => 'Unknown action.'], 400);
}

// ─────────────────────────────────────────────────────────────
//  Internal helper
// ─────────────────────────────────────────────────────────────
function api_require_admin_token(array $params): void
{
    $token = trim($params['admin_token'] ?? '');
    if (!hash_equals(ADMIN_TOKEN, $token)) {
        json_out(['success' => false, 'message' => 'Unauthorised – invalid admin_token.'], 401);
    }
}
