<?php
// $project must be a ProjectShowcase row with _source === 'showcase'
$scStatusMap = [
    'Ongoing'   => 'admin-badge-active',
    'Completed' => 'admin-badge-track',
    'On Hold'   => 'admin-badge-inspection',
    'Cancelled' => 'admin-badge-cancelled',
];
$scBadge   = $scStatusMap[$project['Status']] ?? 'admin-badge-track';
$scImg     = !empty($project['ImageURL'])
    ? BASE_URL . '/' . ltrim(htmlspecialchars($project['ImageURL']), '/')
    : null;
$scEditId   = 'editShowcase_'   . (int)$project['ProjectShowcaseID'];
$scDetailId = 'detailShowcase_' . (int)$project['ProjectShowcaseID'];
?>
<div class="pj-list-item">

    <!-- ── Main row ───────────────────────────────────────── -->
    <div class="pj-list-row">

        <!-- Left: identity (with thumbnail) -->
        <div class="pj-list-identity">
            <div style="display:flex;align-items:center;gap:12px;">
                <?php if ($scImg): ?>
                    <img src="<?= $scImg ?>" alt=""
                         style="width:48px;height:38px;object-fit:cover;border-radius:4px;flex-shrink:0;border:1px solid var(--admin-border);">
                <?php endif; ?>
                <div>
                    <div class="pj-list-source-tag pj-list-source-web">Website</div>
                    <div class="pj-list-title"><?= htmlspecialchars($project['Title']) ?></div>
                    <div class="pj-list-sub">ID #<?= (int)$project['ProjectShowcaseID'] ?></div>
                </div>
            </div>
        </div>

        <!-- Centre: meta pills -->
        <div class="pj-list-meta">
            <div class="pj-list-meta-item">
                <span>Start</span>
                <?= $project['StartDate'] ? date('M d, Y', strtotime($project['StartDate'])) : '—' ?>
            </div>
            <div class="pj-list-meta-item">
                <span>End</span>
                <?= $project['EndDate'] ? date('M d, Y', strtotime($project['EndDate'])) : '—' ?>
            </div>
            <?php if (!empty($project['Summary'])): ?>
            <div class="pj-list-meta-item" style="grid-column:1/-1;">
                <span>Summary</span>
                <?= htmlspecialchars(strlen($project['Summary'])>80 ? substr($project['Summary'],0,80).'…' : $project['Summary']) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: status + actions -->
        <div class="pj-list-actions">
            <span class="admin-badge <?= $scBadge ?>"><?= htmlspecialchars($project['Status']) ?></span>
            <button type="button" class="admin-btn admin-btn-outline admin-btn-sm"
                    onclick="toggleDetail('<?= $scDetailId ?>', this)">
                Details
            </button>
            <button type="button" class="admin-btn admin-btn-outline admin-btn-sm"
                    onclick="document.getElementById('<?= $scEditId ?>').style.display='flex'">
                Edit
            </button>
        </div>
    </div>

    <!-- ── Expandable detail panel ────────────────────────── -->
    <div id="<?= $scDetailId ?>" class="pj-list-detail" style="display:none;">

        <?php if (!empty($project['Summary'])): ?>
        <div class="pj-detail-section">
            <div class="pj-detail-label">Full Summary</div>
            <div class="pj-detail-text"><?= htmlspecialchars($project['Summary']) ?></div>
        </div>
        <?php endif; ?>

        <!-- Quick status + end date -->
        <div class="pj-detail-section">
            <div class="pj-detail-label">Quick Update</div>
            <form method="POST" class="d-flex gap-2 align-items-center flex-wrap js-track-form">
                <input type="hidden" name="showcase_id" value="<?= (int)$project['ProjectShowcaseID'] ?>">
                <div style="display:flex;align-items:center;gap:8px;flex:1;flex-wrap:wrap;">
                    <select name="showcase_status" data-original="<?= htmlspecialchars($project['Status']) ?>" style="flex:1;min-width:130px;">
                        <?php foreach (['Ongoing','On Hold','Completed','Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $project['Status']===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="showcase_end_date"
                           value="<?= htmlspecialchars($project['EndDate']??'') ?>"
                           data-original="<?= htmlspecialchars($project['EndDate']??'') ?>"
                           style="flex:1;min-width:140px;">
                </div>
                <button type="submit" name="update_showcase" class="admin-btn admin-btn-primary admin-btn-sm" disabled>Save</button>
            </form>
        </div>

    </div><!-- /.pj-list-detail -->
</div><!-- /.pj-list-item -->

<!-- ── Edit Modal ─────────────────────────────────────────── -->
<div id="<?= $scEditId ?>"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:10px;padding:32px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto;position:relative;">
        <button type="button" onclick="document.getElementById('<?= $scEditId ?>').style.display='none'"
                style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#6b7280;">✕</button>
        <h2 class="admin-page-title" style="font-size:1.05rem;margin-bottom:4px;">Edit Website Project</h2>
        <p class="admin-page-sub" style="margin-bottom:20px;">Changes reflect immediately on the public website.</p>
        <form method="POST" class="js-edit-modal-form">
            <input type="hidden" name="showcase_id" value="<?= (int)$project['ProjectShowcaseID'] ?>">
            <div class="admin-field">
                <label>Title <span style="color:red">*</span></label>
                <input type="text" name="showcase_title" value="<?= htmlspecialchars($project['Title']) ?>" data-original="<?= htmlspecialchars($project['Title']) ?>" required>
            </div>
            <div class="admin-field">
                <label>Summary</label>
                <textarea name="showcase_summary" rows="4" data-original="<?= htmlspecialchars($project['Summary']??'') ?>"><?= htmlspecialchars($project['Summary']??'') ?></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="admin-field">
                    <label>Start Date</label>
                    <input type="date" name="showcase_start_date" value="<?= htmlspecialchars($project['StartDate']??'') ?>" data-original="<?= htmlspecialchars($project['StartDate']??'') ?>">
                </div>
                <div class="admin-field">
                    <label>End Date</label>
                    <input type="date" name="showcase_end_date" value="<?= htmlspecialchars($project['EndDate']??'') ?>" data-original="<?= htmlspecialchars($project['EndDate']??'') ?>">
                </div>
            </div>
            <div class="admin-field">
                <label>Status</label>
                <select name="showcase_status" data-original="<?= htmlspecialchars($project['Status']) ?>">
                    <?php foreach (['Ongoing','On Hold','Completed','Cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $project['Status']===$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-flex gap-2 justify-content-end mt-2">
                <button type="button" class="admin-btn admin-btn-outline" onclick="document.getElementById('<?= $scEditId ?>').style.display='none'">Cancel</button>
                <button type="submit" name="edit_showcase" class="admin-btn admin-btn-primary" disabled>Save Changes</button>
            </div>
        </form>
    </div>
</div>
