<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';

requireRole('admin');

$scripts = readJson(DATA_PATH . 'scripts.json');
$scripts = array_filter($scripts, fn($s) => ($s['status'] ?? 'active') === 'active');

$categories = [];
foreach ($scripts as $s) {
    $cat = $s['category'] ?? 'Other';
    if (!isset($categories[$cat])) $categories[$cat] = [];
    $categories[$cat][] = $s;
}

$filterCat = $_GET['cat'] ?? '';

renderHead('Script Store');
renderSidebar('admin', 'script_store');
renderTopbar('Script Marketplace');
?>

<div style="margin-bottom:16px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <a href="?" class="btn <?= !$filterCat ? 'btn-primary' : 'btn-secondary' ?>">All</a>
    <?php foreach (array_keys($categories) as $cat): ?>
    <a href="?cat=<?= urlencode($cat) ?>" class="btn <?= $filterCat === $cat ? 'btn-primary' : 'btn-secondary' ?>">
        <?= htmlspecialchars($cat) ?> (<?= count($categories[$cat]) ?>)
    </a>
    <?php endforeach; ?>
</div>

<?php
$displayScripts = $filterCat ? ($categories[$filterCat] ?? []) : array_merge(...array_values($categories) ?: [[]]);
?>

<?php if (empty($displayScripts)): ?>
<div class="empty-state"><span class="empty-icon">🛒</span><h3>No scripts available</h3><p>Upload scripts in the Scripts section.</p><a href="<?= BASE_URL ?>admin/scripts.php" class="btn btn-primary">Go to Scripts</a></div>
<?php else: ?>
<div class="script-grid">
    <?php foreach ($displayScripts as $s): ?>
    <div class="script-card animate-in">
        <div class="script-thumb">
            <?php if (!empty($s['thumbnail'])): ?>
            <img src="<?= htmlspecialchars($s['thumbnail']) ?>" alt="<?= htmlspecialchars($s['name']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:var(--radius-sm);">
            <?php else: ?>
            🚀
            <?php endif; ?>
        </div>
        <div>
            <div style="font-weight:700;font-size:15px;"><?= htmlspecialchars($s['name']) ?></div>
            <div class="script-meta">v<?= htmlspecialchars($s['version']) ?> · <?= htmlspecialchars($s['category']) ?></div>
        </div>
        <div style="font-size:13px;color:var(--text-muted);line-height:1.4;"><?= htmlspecialchars($s['description']) ?></div>
        <div style="display:flex;gap:6px;margin-top:auto;">
            <a href="<?= BASE_URL ?>admin/scripts.php" class="btn btn-sm btn-secondary" style="flex:1;justify-content:center;">✏️ Edit</a>
            <?php if (!empty($s['demo_link']) && $s['demo_link'] !== '#'): ?>
            <a href="<?= htmlspecialchars($s['demo_link']) ?>" target="_blank" class="btn btn-sm btn-info">🔗 Demo</a>
            <?php endif; ?>
        </div>
        <div style="font-size:11px;color:var(--text-muted);text-align:center;">
            <?= $s['size_kb'] > 0 ? ($s['size_kb'] >= 1024 ? round($s['size_kb']/1024,1).'MB' : $s['size_kb'].'KB') : 'File not uploaded' ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php renderFooter(); ?>
