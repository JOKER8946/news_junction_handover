<?php

declare(strict_types=1);

/**
 * Popup-alert poll + acknowledge endpoint.
 *
 *   GET  ?p=api/alerts            -> { ok, alerts: [...] }  unseen alerts for the caller
 *   POST ?p=api/alerts  ids[]=..  -> { ok, cleared: n }      mark them seen
 *
 * The POST carries `_csrf` from the <meta name="csrf-token"> tag in layout.php,
 * so it satisfies the CSRF gate in index.php without needing an exemption
 * (index.php's gate runs before routing and reads $_POST['_csrf']).
 *
 * Always answers JSON, including on auth failure — the poller runs on every
 * page, so a session that expires mid-poll must not get an HTML redirect
 * parsed as JSON.
 */

header('Content-Type: application/json');
header('Cache-Control: no-store');

$role   = function_exists('auth_role') ? auth_role() : null;
$userId = function_exists('auth_user_id') ? auth_user_id() : null;

if (!in_array($role, ['merchant', 'super_admin'], true) || !$userId) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized', 'alerts' => []]);
    exit;
}

if (request_method() === 'POST') {
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids)) {
        $ids = [$ids];
    }
    $cleared = alerts_mark_seen($db, $ids, (string)$role, (int)$userId);
    echo json_encode(['ok' => true, 'cleared' => $cleared]);
    exit;
}

$alerts = alerts_fetch_unseen($db, (string)$role, (int)$userId);

echo json_encode([
    'ok'     => true,
    'alerts' => array_map(static function (array $a): array {
        return [
            'id'    => (int)$a['id'],
            'type'  => (string)$a['type'],
            'title' => (string)$a['title'],
            'body'  => (string)$a['body'],
            'link'  => $a['link'] !== null && $a['link'] !== '' ? (string)$a['link'] : null,
        ];
    }, $alerts),
]);
exit;
