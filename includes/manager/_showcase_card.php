<?php
// $project must be a ProjectShowcase row with _source === 'showcase'
$scStatusMap = [
    'Ongoing'   => 'admin-badge-active',
    'Completed' => 'admin-badge-track',
    'On Hold'   => 'admin-badge-inspection',
    'Cancelled' => 'admin-badge-danger',
];
$scBadge = $scStatusMap[$project['Status']] ?? 'admin-badge-track';
$scImg   = !empty($project['ImageURL'])
    ? BASE_URL . '/' . ltrim(htmlspecialchars($project['ImageURL']), '/')
    : null;
?>
<div class="admin-card">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
        <div style="display:flex;align-items:center;gap:12px;">
            <?php if ($scImg): ?>
                <img src="<?= $scImg ?>" alt=""
                     style="width:52px;height:42px;object-fit:cover;border-radius:4px;flex-shrink:0;border:1px solid var(--admin-border);">
            <?php endif; ?>
            <div>
                <h3 class="admin-card-title mb-1"><?= htmlspecialchars($project['Title']) ?></h3>
                <span class="admin-table-sub">
                    Website Showcase · ID #<?= (int) $project['ProjectShowcaseID'] ?>
                </span>
            </div>
        </div>
        <span class="admin-badge <?= $scBadge ?>"><?= htmlspecialchars($project['Status']) ?></span>
    </div>

    <div class="admin-meta-grid">
        <div class="admin-meta-item">
            <span>Start Date</span>
            <?= $project['StartDate'] ? date('M d, Y', strtotime($project['StartDate'])) : '—' ?>
        </div>
        <div class="admin-meta-item">
            <span>End Date</span>
            <?= $project['EndDate'] ? date('M d, Y', strtotime($project['EndDate'])) : '—' ?>
        </div>
    </div>

    <?php if (!empty($project['Summary'])): ?>
    <div class="admin-field">
        <label>Summary</label>
        <div class="small text-muted" style="line-height:1.6;"><?= htmlspecialchars($project['Summary']) ?></div>
    </div>
    <?php endif; ?>

    <!-- Update status + end date -->
    <form method="POST" class="d-flex gap-2 align-items-end flex-wrap mb-0 js-track-form">
        <input type="hidden" name="showcase_id" value="<?= (int) $project['ProjectShowcaseID'] ?>">
        <div class="admin-field mb-0 flex-grow-1">
            <label>Status</label>
            <select name="showcase_status" data-original="<?= htmlspecialchars($project['Status']) ?>">
                <?php foreach (['Ongoing', 'On Hold', 'Completed', 'Cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $project['Status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-field mb-0">
            <label>End Date</label>
            <input type="date" name="showcase_end_date"
                   value="<?= htmlspecialchars($project['EndDate'] ?? '') ?>"
                   data-original="<?= htmlspecialchars($project['EndDate'] ?? '') ?>">
        </div>
        <button type="submit" name="update_showcase" class="admin-btn admin-btn-primary admin-btn-sm" disabled>Save</button>
    </form>
</div>
