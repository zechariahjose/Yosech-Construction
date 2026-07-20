<?php
$scStatusMap = [
    'Ongoing'   => 'admin-badge-active',
    'Completed' => 'admin-badge-track',
    'On Hold'   => 'admin-badge-inspection',
    'Cancelled' => 'admin-badge-cancelled',
];
$scBadge    = $scStatusMap[$project['Status']] ?? 'admin-badge-track';
$scImg      = !empty($project['ImageURL'])
    ? BASE_URL . '/' . ltrim(htmlspecialchars($project['ImageURL']), '/')
    : null;
$scId       = (int)$project['ProjectShowcaseID'];
$scEditId   = 'editShowcase_'   . $scId;
$scDetailId = 'detailShowcase_' . $scId;
?>
<div class="pj-item" id="pjItem_sc<?= $scId ?>">

    <!-- ── Row ────────────────────────────────────────────── -->
    <div class="pj-row">

        <div class="pj-row-identity">
            <div style="display:flex;align-items:center;gap:12px;">
                <?php if ($scImg): ?>
                    <img src="<?= $scImg ?>" alt=""
                         class="pj-thumb">
                <?php endif; ?>
                <div>
                    <div class="pj-source-chip pj-source-web">Website</div>
                    <div class="pj-title"><?= htmlspecialchars($project['Title']) ?></div>
                    <div class="pj-subtitle">Showcase #<?= $scId ?></div>
                </div>
            </div>
        </div>

        <div class="pj-row-meta">
            <div class="pj-meta-chip">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                Start: <?= $project['StartDate'] ? date('M d, Y', strtotime($project['StartDate'])) : '—' ?>
            </div>
            <div class="pj-meta-chip">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                End: <?= $project['EndDate'] ? date('M d, Y', strtotime($project['EndDate'])) : 'Ongoing' ?>
            </div>
            <?php if (!empty($project['Summary'])): ?>
            <div class="pj-meta-chip pj-meta-summary">
                <?= htmlspecialchars(strlen($project['Summary'])>90 ? substr($project['Summary'],0,90).'…' : $project['Summary']) ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="pj-row-actions">
            <span class="admin-badge <?= $scBadge ?>"><?= htmlspecialchars($project['Status']) ?></span>
            <button type="button" class="pj-action-btn"
                    onclick="pjToggleDetail('<?= $scDetailId ?>', '<?= $scEditId ?>', this)"
                    data-label-open="Details" data-label-close="Close">
                Details
            </button>
            <button type="button" class="pj-action-btn pj-action-edit"
                    onclick="pjToggleEdit('<?= $scEditId ?>', '<?= $scDetailId ?>')">
                Edit
            </button>
        </div>
    </div>

    <!-- ── Detail panel ───────────────────────────────────── -->
    <div id="<?= $scDetailId ?>" class="pj-detail" style="display:none;">
        <div class="pj-detail-inner">

            <?php if (!empty($project['Summary'])): ?>
            <div class="pj-detail-block pj-detail-block-full">
                <div class="pj-detail-label">Full Summary</div>
                <p class="pj-detail-text"><?= htmlspecialchars($project['Summary']) ?></p>
            </div>
            <?php endif; ?>

            <div class="pj-detail-block pj-detail-block-full">
                <div class="pj-detail-label">Quick Status Update</div>
                <form method="POST" class="pj-inline-form js-track-form">
                    <input type="hidden" name="showcase_id" value="<?= $scId ?>">
                    <select name="showcase_status" data-original="<?= htmlspecialchars($project['Status']) ?>">
                        <?php foreach (['Ongoing','On Hold','Completed','Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $project['Status']===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="date" name="showcase_end_date"
                           value="<?= htmlspecialchars($project['EndDate']??'') ?>"
                           data-original="<?= htmlspecialchars($project['EndDate']??'') ?>">
                    <button type="submit" name="update_showcase" class="admin-btn admin-btn-primary admin-btn-sm" disabled>Save</button>
                </form>
            </div>

        </div>
    </div>

    <!-- ── Edit panel (inline) ────────────────────────────── -->
    <div id="<?= $scEditId ?>" class="pj-edit-panel" style="display:none;">
        <div class="pj-edit-inner">
            <div class="pj-edit-head">
                <div>
                    <div class="pj-edit-title">Edit Website Project</div>
                    <div class="pj-edit-sub">Changes reflect immediately on the public website</div>
                </div>
                <button type="button" class="pj-edit-close" onclick="pjCloseEdit('<?= $scEditId ?>')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" class="js-edit-modal-form">
                <input type="hidden" name="showcase_id" value="<?= $scId ?>">
                <div class="admin-field">
                    <label>Title <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="showcase_title"
                           value="<?= htmlspecialchars($project['Title']) ?>"
                           data-original="<?= htmlspecialchars($project['Title']) ?>"
                           required>
                </div>
                <div class="admin-field">
                    <label>Summary</label>
                    <textarea name="showcase_summary" rows="4"
                              data-original="<?= htmlspecialchars($project['Summary']??'') ?>"><?= htmlspecialchars($project['Summary']??'') ?></textarea>
                </div>
                <div class="pj-edit-grid">
                    <div class="admin-field">
                        <label>Start Date</label>
                        <input type="date" name="showcase_start_date"
                               value="<?= htmlspecialchars($project['StartDate']??'') ?>"
                               data-original="<?= htmlspecialchars($project['StartDate']??'') ?>">
                    </div>
                    <div class="admin-field">
                        <label>End Date</label>
                        <input type="date" name="showcase_end_date"
                               value="<?= htmlspecialchars($project['EndDate']??'') ?>"
                               data-original="<?= htmlspecialchars($project['EndDate']??'') ?>">
                    </div>
                    <div class="admin-field">
                        <label>Status</label>
                        <select name="showcase_status" data-original="<?= htmlspecialchars($project['Status']) ?>">
                            <?php foreach (['Ongoing','On Hold','Completed','Cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $project['Status']===$s?'selected':'' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="pj-edit-actions" style="justify-content:space-between;">
                    <!-- Unpublish (left side, destructive) -->
                    <form method="POST" style="margin:0;"
                          onsubmit="return confirm('Remove this project from the public website? The project data will not be deleted — only the website listing will be removed.');">
                        <input type="hidden" name="project_id" value="<?= (int)($project['ProjectID'] ?? 0) ?>">
                        <input type="hidden" name="showcase_id" value="<?= $scId ?>">
                        <button type="submit" name="unpublish_from_website"
                                class="admin-btn admin-btn-danger admin-btn-sm"
                                style="display:inline-flex;align-items:center;gap:5px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                            Unpublish from Website
                        </button>
                    </form>
                    <!-- Save / Cancel (right side) -->
                    <div style="display:flex;gap:8px;">
                        <button type="button" class="admin-btn admin-btn-outline admin-btn-sm"
                                onclick="pjCloseEdit('<?= $scEditId ?>')">Cancel</button>
                        <button type="submit" name="edit_showcase" class="admin-btn admin-btn-primary admin-btn-sm">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div><!-- /.pj-item -->
