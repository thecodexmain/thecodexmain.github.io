<?php
// ─────────────────────────────────────────────────────────────
//  Keymaster API  –  api.php
//
//  Endpoints (all via GET or POST):
//
//  Submit a registration request (public)
//    GET  api.php?action=request&device_id=XXX&plan=basic[&note=TEXT]
//
//  Check key/request status (public)
//    GET  api.php?action=status&device_id=XXX
//
//  Approve a pending request (admin only)
//    GET  api.php?action=approve&device_id=XXX&days=30&admin_token=TOKEN
//
//  Reject a pending request (admin only)
//    GET  api.php?action=reject&device_id=XXX&admin_token=TOKEN
//
//  Generate a key directly (admin only – bypasses request flow)
//    GET  api.php?action=generate&device_id=XXX&plan=basic&days=30&admin_token=TOKEN
//
//  Revoke a key (admin only)
//    GET  api.php?action=revoke&device_id=XXX&admin_token=TOKEN
//
//  Toggle registrations (admin only)
//    GET  api.php?action=toggle_reg&value=0|1&admin_token=TOKEN
//
//  List all keys (admin only)
//    GET  api.php?action=list&admin_token=TOKEN
//
//  List all pending requests (admin only)
//    GET  api.php?action=list_requests&admin_token=TOKEN
// ─────────────────────────────────────────────────────────────

require_once __DIR__ . '/config.php';

// Merge GET + POST first so we can read the action before setting headers
$params = array_merge($_GET, $_POST);
$action = $params['action'] ?? '';

// Public endpoints allow any origin; admin endpoints are locked down.
$public_actions = ['status', 'request'];
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

    // ── Submit registration request (public) ──────────────────
    case 'request':
        if (get_setting('registrations_open', '1') !== '1') {
            json_out(['success' => false, 'message' => 'Registrations are currently closed.'], 403);
        }

        $device_id = sanitise_device_id($params['device_id'] ?? '');
        if ($device_id === '') {
            json_out(['success' => false, 'message' => 'device_id is required.'], 400);
        }

        $plan = trim($params['plan'] ?? 'basic');
        $note = substr(trim($params['note'] ?? ''), 0, 200);

        $db = get_db();

        // Already has an approved key?
        $chk = $db->prepare("SELECT id FROM keys WHERE device_id = :d COLLATE NOCASE");
        $chk->execute([':d' => $device_id]);
        if ($chk->fetch()) {
            json_out(['success' => false, 'message' => 'A key for this device_id already exists.'], 409);
        }

        // Already has a pending/approved request?
        $rchk = $db->prepare("SELECT status FROM requests WHERE device_id = :d COLLATE NOCASE");
        $rchk->execute([':d' => $device_id]);
        $req = $rchk->fetch();
        if ($req) {
            if ($req['status'] === 'pending') {
                json_out(['success' => false, 'message' => 'A request for this device_id is already pending approval.'], 409);
            }
            if ($req['status'] === 'rejected') {
                json_out(['success' => false, 'message' => 'Your previous request was rejected. Please contact support.'], 403);
            }
        }

        $ins = $db->prepare("
            INSERT INTO requests (device_id, plan, note)
            VALUES (:device_id, :plan, :note)
        ");
        $ins->execute([
            ':device_id' => $device_id,
            ':plan'      => $plan,
            ':note'      => $note,
        ]);

        json_out([
            'success'   => true,
            'message'   => 'Request submitted. Awaiting admin approval.',
            'device_id' => $device_id,
            'plan'      => $plan,
            'status'    => 'pending',
        ]);

    // ── Approve a pending request (admin only) ────────────────
    case 'approve':
        api_require_admin_token($params);

        $device_id = sanitise_device_id($params['device_id'] ?? '');
        if ($device_id === '') {
            json_out(['success' => false, 'message' => 'device_id is required.'], 400);
        }

        $db = get_db();

        // Find the pending request
        $rchk = $db->prepare("SELECT * FROM requests WHERE device_id = :d COLLATE NOCASE");
        $rchk->execute([':d' => $device_id]);
        $req = $rchk->fetch();

        if (!$req) {
            json_out(['success' => false, 'message' => 'No request found for this device_id.'], 404);
        }
        if ($req['status'] !== 'pending') {
            json_out(['success' => false, 'message' => "Request status is '{$req['status']}', not pending."], 409);
        }

        // Allow admin to override plan/days at approval time
        $plan = trim($params['plan'] ?? $req['plan']);
        $days = max(1, (int)($params['days'] ?? 30));

        $api_key    = generate_api_key();
        $expires_at = (new DateTime("+{$days} days"))->format('Y-m-d H:i:s');

        // Check no key exists yet (race condition guard)
        $ck = $db->prepare("SELECT id FROM keys WHERE device_id = :d COLLATE NOCASE");
        $ck->execute([':d' => $device_id]);
        if ($ck->fetch()) {
            json_out(['success' => false, 'message' => 'A key for this device_id already exists.'], 409);
        }

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

        // Mark request as approved
        $db->prepare("UPDATE requests SET status = 'approved' WHERE device_id = :d COLLATE NOCASE")
           ->execute([':d' => $device_id]);

        json_out([
            'success'    => true,
            'message'    => "Request for {$device_id} approved. Key generated.",
            'device_id'  => $device_id,
            'api_key'    => $api_key,
            'plan'       => $plan,
            'days'       => $days,
            'expires_at' => $expires_at,
        ]);

    // ── Reject a pending request (admin only) ─────────────────
    case 'reject':
        api_require_admin_token($params);

        $device_id = sanitise_device_id($params['device_id'] ?? '');
        if ($device_id === '') {
            json_out(['success' => false, 'message' => 'device_id is required.'], 400);
        }

        $db = get_db();

        $upd = $db->prepare(
            "UPDATE requests SET status = 'rejected' WHERE device_id = :d COLLATE NOCASE AND status = 'pending'"
        );
        $upd->execute([':d' => $device_id]);

        if ($upd->rowCount() === 0) {
            json_out(['success' => false, 'message' => 'No pending request found for this device_id.'], 404);
        }
        json_out(['success' => true, 'message' => "Request for {$device_id} rejected."]);

    // ── Generate key (admin only – bypasses request flow) ─────
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

        // Auto-mark any matching request as approved
        $db->prepare("UPDATE requests SET status = 'approved' WHERE device_id = :d COLLATE NOCASE")
           ->execute([':d' => $device_id]);

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
            // Check if a request exists for this device_id
            $db   = get_db();
            $rchk = $db->prepare("SELECT status, plan, requested_at FROM requests WHERE device_id = :d COLLATE NOCASE");
            $rchk->execute([':d' => $device_id]);
            $req = $rchk->fetch();

            if ($req) {
                $msg = match($req['status']) {
                    'pending'  => 'Request Pending Approval',
                    'rejected' => 'Request Rejected',
                    default    => 'Request ' . ucfirst($req['status']),
                };
                json_out([
                    'success'      => false,
                    'device_id'    => $device_id,
                    'plan'         => $req['plan'],
                    'status'       => $req['status'],
                    'message'      => $msg,
                    'requested_at' => $req['requested_at'],
                ], $req['status'] === 'pending' ? 202 : 403);
            }

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

    // ── List all pending requests ─────────────────────────────
    case 'list_requests':
        api_require_admin_token($params);

        $db  = get_db();
        $status_filter = $params['status'] ?? 'pending';
        if (!in_array($status_filter, ['pending', 'approved', 'rejected', 'all'], true)) {
            $status_filter = 'pending';
        }
        if ($status_filter === 'all') {
            $rows = $db->query("SELECT * FROM requests ORDER BY requested_at DESC")->fetchAll();
        } else {
            $stm = $db->prepare("SELECT * FROM requests WHERE status = :s ORDER BY requested_at DESC");
            $stm->execute([':s' => $status_filter]);
            $rows = $stm->fetchAll();
        }

        json_out(['success' => true, 'requests' => $rows]);

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
