<?php
require_once __DIR__ . '/../includes/mailer.php';

$limit = 20;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = (int)substr($arg, strlen('--limit='));
    }
}
if ($limit <= 0) $limit = 20;

$result = hostingDispatchMailQueue($limit);
echo "Mail dispatch complete: sent={$result['sent']} failed={$result['failed']}\n";

