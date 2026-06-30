<?php
// $project must be a ProjectShowcase row with _source === 'showcase'
$scStatusMap = [
    'Ongoing'   => 'admin-badge-active',
    'Completed' => 'admin-badge-track',
    'On Hold'   => 'admin-badge-inspection',
    'Cancelled' => 'admin-badge-danger',
];
$scBadge  = $scStatusMap[$project['Status']] ?? 'admin-badge-track';
$scImg    = !empty($project['ImageURL'])
    ? BASE_URL . '/' . ltrim(htmlspecialchars($project['ImageURL']), '/')
    : null;
$scEditId = 'editShowcase_' . (int) $project['ProjectShowcaseID'];
?>
<div class="admin-card">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
        <div style="display:flex;align-items:center;gap:12px;">
            <?php if ($scImg): ?>
                <img src="<?= $scImg ?>" alt=""
                     style="width:52px;height:42px;object-fit:cover;border-radius:4px;flex-shrink:0;border:1px solid var(--admin-border);">
            <?php endif; ?>
            <div>
                <h3 class="admin-card-title mb-1"><?= htmlspecialchars($project['Title']) ?></h3>
                <span class="admin-table-sub">Website Showcase · ID #<?= (int) $project['ProjectShowcaseID'] ?></span>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
            <span class="admin-badge <?= $scBadge ?>"><?= htmlspecialchars($project['Status']) ?></span>
            <button type="button" class="admin-btn admin-btn-outline admin-btn-sm"
                    onclick="document.getElementById('<?= $scEditId ?>').style.display='flex'">
                Edit
            </button>
        </div>
    </div>

    <!-- Meta -->
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

    <!-- Quick status + end date -->
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

<!-- ── Edit Modal ─────────────────────────────────────────── -->
<div id="<?= $scEditId ?>"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:10px;padding:32px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto;position:relative;">
        <button type="button"
                onclick="document.getElementById('<?= $scEditId ?>').style.display='none'"
                style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#6b7280;">✕</button>

        <h2 class="admin-page-title" style="font-size:1.05rem;margin-bottom:4px;">Edit Website Project</h2>
        <p class="admin-page-sub" style="margin-bottom:20px;">Changes will reflect immediately on the public website.</p>

        <form method="POST" class="js-edit-modal-form">
            <input type="hidden" name="showcase_id" value="<?= (int) $project['ProjectShowcaseID'] ?>">

            <div class="admin-field">
                <label>Title <span style="color:red">*</span></label>
                <input type="text" name="showcase_title"
                       value="<?= htmlspecialchars($project['Title']) ?>"
                       data-original="<?= htmlspecialchars($project['Title']) ?>"
                       required>
            </div>

            <div class="admin-field">
                <label>Summary</label>
                <textarea name="showcase_summary" rows="4"
                          data-original="<?= htmlspecialchars($project['Summary'] ?? '') ?>"><?= htmlspecialchars($project['Summary'] ?? '') ?></textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="admin-field">
                    <label>Start Date</label>
                    <input type="date" name="showcase_start_date"
                           value="<?= htmlspecialchars($project['StartDate'] ?? '') ?>"
                           data-original="<?= htmlspecialchars($project['StartDate'] ?? '') ?>">
                </div>
                <div class="admin-field">
                    <label>End Date</label>
                    <input type="date" name="showcase_end_date"
                           value="<?= htmlspecialchars($project['EndDate'] ?? '') ?>"
                           data-original="<?= htmlspecialchars($project['EndDate'] ?? '') ?>">
                </div>
            </div>

            <div class="admin-field">
                <label>Status</label>
                <select name="showcase_status" data-original="<?= htmlspecialchars($project['Status']) ?>">
                    <?php foreach (['Ongoing', 'On Hold', 'Completed', 'Cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $project['Status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-2">
                <button type="button" class="admin-btn admin-btn-outline"
                        onclick="document.getElementById('<?= $scEditId ?>').style.display='none'">Cancel</button>
                <button type="submit" name="edit_showcase" class="admin-btn admin-btn-primary" disabled>Save Changes</button>
            </div>
        </form>
    </div>
</div>
