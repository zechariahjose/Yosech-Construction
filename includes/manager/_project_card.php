<?php
// $project = internal Project row; $status = managerProjectStatusLabel(); $updatesResult = query result
$pEditId = 'editProject_' . (int) $project['ProjectID'];
?>
<div class="admin-card">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h3 class="admin-card-title mb-1"><?= htmlspecialchars(adminProjectDisplayName($project)) ?></h3>
            <span class="admin-table-sub">Site #<?= (int) $project['ProjectID'] ?> · <?= htmlspecialchars($project['ProjectLocation'] ?? '—') ?></span>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
            <span class="admin-badge <?= $status['class'] ?>"><?= htmlspecialchars($status['label']) ?></span>
            <button type="button" class="admin-btn admin-btn-outline admin-btn-sm"
                    onclick="document.getElementById('<?= $pEditId ?>').style.display='flex'">
                Edit
            </button>
        </div>
    </div>

    <!-- Meta -->
    <div class="admin-meta-grid">
        <div class="admin-meta-item"><span>Client</span><?= htmlspecialchars($project['Client_FirstName'] . ' ' . $project['Client_LastName']) ?></div>
        <div class="admin-meta-item"><span>Contact</span><?= htmlspecialchars($project['Client_ContactNumber'] ?: '—') ?></div>
        <div class="admin-meta-item"><span>Timeline</span><?= htmlspecialchars(($project['StartDate'] ?? '—') . ' → ' . ($project['EndDate'] ?? '—')) ?></div>
        <div class="admin-meta-item"><span>Payment</span><?= htmlspecialchars($project['ProjectPaymentStatus']) ?></div>
    </div>

    <div class="admin-field">
        <label>Scope of Work</label>
        <div class="small text-muted" style="line-height:1.6;"><?= nl2br(htmlspecialchars($project['Description'] ?? '—')) ?></div>
    </div>

    <!-- Quick status update -->
    <form method="post" class="d-flex gap-2 align-items-end mb-0 js-track-form">
        <input type="hidden" name="project_id" value="<?= (int) $project['ProjectID'] ?>">
        <div class="admin-field mb-0 flex-grow-1">
            <label>Site Status</label>
            <select name="project_status" data-original="<?= htmlspecialchars($project['ProjectStatus']) ?>">
                <?php foreach (['Ongoing', 'On Hold', 'Completed', 'Cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $project['ProjectStatus'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="admin-btn admin-btn-primary admin-btn-sm" disabled>Update Status</button>
    </form>

    <hr class="admin-divider">

    <!-- Field Updates -->
    <h4 class="admin-card-title" style="font-size:0.88rem;">Field Updates &amp; Inspections</h4>
    <?php if (mysqli_num_rows($updatesResult) > 0): ?>
        <ul class="admin-activity-list mb-3" style="border:1px solid var(--admin-border);border-radius:6px;">
            <?php while ($upd = mysqli_fetch_assoc($updatesResult)): ?>
            <li class="admin-activity-item">
                <div class="d-flex justify-content-between gap-2">
                    <span class="admin-badge <?= $upd['Status'] === 'Pending' ? 'admin-badge-inspection' : 'admin-badge-track' ?>"><?= htmlspecialchars($upd['Status']) ?></span>
                    <span class="admin-activity-time"><?= htmlspecialchars($upd['UpdateDate']) ?></span>
                </div>
                <div class="admin-activity-desc mt-2"><?= htmlspecialchars($upd['Description']) ?></div>
                <?php if ($upd['Status'] === 'Pending'): ?>
                <form method="post" class="d-flex gap-2 mt-2">
                    <input type="hidden" name="update_id" value="<?= (int) $upd['UpdateID'] ?>">
                    <input type="hidden" name="review_update" value="1">
                    <select name="review_status" class="form-select form-select-sm" style="max-width:140px;">
                        <option value="Reviewed">Mark Reviewed</option>
                        <option value="Approved">Approve</option>
                    </select>
                    <button class="admin-btn admin-btn-success admin-btn-sm">Submit Review</button>
                </form>
                <?php endif; ?>
            </li>
            <?php endwhile; ?>
        </ul>
    <?php endif; ?>

    <form method="post" class="js-post-update-form">
        <input type="hidden" name="project_id" value="<?= (int) $project['ProjectID'] ?>">
        <input type="hidden" name="add_update" value="1">
        <div class="admin-field">
            <label>Post Site Update</label>
            <textarea name="update_description" rows="3" placeholder="e.g. Foundation pour completed, awaiting inspection..." required></textarea>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <select name="update_status" style="max-width:160px;">
                <option value="Reviewed">Reviewed</option>
                <option value="Pending">Pending Inspection</option>
                <option value="Approved">Approved</option>
            </select>
            <button class="admin-btn admin-btn-primary admin-btn-sm" disabled>Post Update</button>
        </div>
    </form>

    <hr class="admin-divider">

    <!-- Delete -->
    <form method="post" onsubmit="return confirm('Are you sure you want to delete Project #<?= (int) $project['ProjectID'] ?>? This cannot be undone.');">
        <input type="hidden" name="project_id" value="<?= (int) $project['ProjectID'] ?>">
        <button type="submit" name="delete_project" class="admin-btn admin-btn-danger admin-btn-sm w-100">Delete Project</button>
    </form>
</div>

<!-- ── Edit Modal ─────────────────────────────────────────── -->
<div id="<?= $pEditId ?>"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:10px;padding:32px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;position:relative;">
        <button type="button"
                onclick="document.getElementById('<?= $pEditId ?>').style.display='none'"
                style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#6b7280;">✕</button>

        <h2 class="admin-page-title" style="font-size:1.05rem;margin-bottom:4px;">Edit Project</h2>
        <p class="admin-page-sub" style="margin-bottom:20px;">Site #<?= (int) $project['ProjectID'] ?> — update project details below.</p>

        <form method="POST" class="js-edit-modal-form">
            <input type="hidden" name="project_id" value="<?= (int) $project['ProjectID'] ?>">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="admin-field">
                    <label>Project Title</label>
                    <input type="text" name="edit_title"
                           value="<?= htmlspecialchars($project['ProjectTitle'] ?? '') ?>"
                           data-original="<?= htmlspecialchars($project['ProjectTitle'] ?? '') ?>">
                </div>
                <div class="admin-field">
                    <label>Location</label>
                    <input type="text" name="edit_location"
                           value="<?= htmlspecialchars($project['ProjectLocation'] ?? '') ?>"
                           data-original="<?= htmlspecialchars($project['ProjectLocation'] ?? '') ?>">
                </div>
                <div class="admin-field">
                    <label>Start Date</label>
                    <input type="date" name="edit_start_date"
                           value="<?= htmlspecialchars($project['StartDate'] ?? '') ?>"
                           data-original="<?= htmlspecialchars($project['StartDate'] ?? '') ?>">
                </div>
                <div class="admin-field">
                    <label>End Date</label>
                    <input type="date" name="edit_end_date"
                           value="<?= htmlspecialchars($project['EndDate'] ?? '') ?>"
                           data-original="<?= htmlspecialchars($project['EndDate'] ?? '') ?>">
                </div>
                <div class="admin-field">
                    <label>Project Status</label>
                    <select name="edit_project_status" data-original="<?= htmlspecialchars($project['ProjectStatus']) ?>">
                        <?php foreach (['Ongoing', 'On Hold', 'Completed', 'Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $project['ProjectStatus'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-field">
                    <label>Payment Status</label>
                    <select name="edit_payment_status" data-original="<?= htmlspecialchars($project['ProjectPaymentStatus']) ?>">
                        <?php foreach (['Unpaid', 'Partial', 'Paid'] as $ps): ?>
                            <option value="<?= $ps ?>" <?= $project['ProjectPaymentStatus'] === $ps ? 'selected' : '' ?>><?= $ps ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="admin-field">
                <label>Description / Scope of Work</label>
                <textarea name="edit_description" rows="4"
                          data-original="<?= htmlspecialchars($project['Description'] ?? '') ?>"><?= htmlspecialchars($project['Description'] ?? '') ?></textarea>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-2">
                <button type="button" class="admin-btn admin-btn-outline"
                        onclick="document.getElementById('<?= $pEditId ?>').style.display='none'">Cancel</button>
                <button type="submit" name="edit_project" class="admin-btn admin-btn-primary" disabled>Save Changes</button>
            </div>
        </form>
    </div>
</div>
