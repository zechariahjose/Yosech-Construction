<?php
// $project = internal Project row; $status = managerProjectStatusLabel(); $updatesResult = query result
$pEditId   = 'editProject_'   . (int) $project['ProjectID'];
$pDetailId = 'detailProject_' . (int) $project['ProjectID'];
$updateCount = mysqli_num_rows($updatesResult);
// Reset pointer so the while loop below works
mysqli_data_seek($updatesResult, 0);
?>
<div class="pj-list-item">

    <!-- ── Main row ───────────────────────────────────────── -->
    <div class="pj-list-row">

        <!-- Left: identity -->
        <div class="pj-list-identity">
            <div class="pj-list-source-tag">Internal</div>
            <div class="pj-list-title"><?= htmlspecialchars(adminProjectDisplayName($project)) ?></div>
            <div class="pj-list-sub">
                Site #<?= (int)$project['ProjectID'] ?>
                <?php if (!empty($project['ProjectLocation'])): ?>
                    · <?= htmlspecialchars($project['ProjectLocation']) ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Centre: meta pills -->
        <div class="pj-list-meta">
            <div class="pj-list-meta-item">
                <span>Client</span>
                <?= htmlspecialchars($project['Client_FirstName'] . ' ' . $project['Client_LastName']) ?>
            </div>
            <div class="pj-list-meta-item">
                <span>Timeline</span>
                <?= htmlspecialchars(($project['StartDate'] ?? '—') . ' → ' . ($project['EndDate'] ?? '—')) ?>
            </div>
            <div class="pj-list-meta-item">
                <span>Payment</span>
                <?= htmlspecialchars($project['ProjectPaymentStatus']) ?>
            </div>
            <div class="pj-list-meta-item">
                <span>Updates</span>
                <?= $updateCount ?> logged
            </div>
        </div>

        <!-- Right: status + actions -->
        <div class="pj-list-actions">
            <span class="admin-badge <?= $status['class'] ?>"><?= htmlspecialchars($status['label']) ?></span>
            <button type="button" class="admin-btn admin-btn-outline admin-btn-sm"
                    onclick="toggleDetail('<?= $pDetailId ?>', this)">
                Details
            </button>
            <button type="button" class="admin-btn admin-btn-outline admin-btn-sm"
                    onclick="document.getElementById('<?= $pEditId ?>').style.display='flex'">
                Edit
            </button>
        </div>
    </div>

    <!-- ── Expandable detail panel ────────────────────────── -->
    <div id="<?= $pDetailId ?>" class="pj-list-detail" style="display:none;">

        <!-- Scope of work -->
        <?php if (!empty($project['Description'])): ?>
        <div class="pj-detail-section">
            <div class="pj-detail-label">Scope of Work</div>
            <div class="pj-detail-text"><?= nl2br(htmlspecialchars($project['Description'])) ?></div>
        </div>
        <?php endif; ?>

        <div class="pj-detail-grid">

            <!-- Quick status update -->
            <div class="pj-detail-section">
                <div class="pj-detail-label">Update Status</div>
                <form method="post" class="d-flex gap-2 align-items-center js-track-form">
                    <input type="hidden" name="project_id" value="<?= (int)$project['ProjectID'] ?>">
                    <select name="project_status" data-original="<?= htmlspecialchars($project['ProjectStatus']) ?>" style="flex:1;">
                        <?php foreach (['Ongoing','On Hold','Completed','Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $project['ProjectStatus']===$s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="admin-btn admin-btn-primary admin-btn-sm" disabled>Save</button>
                </form>
            </div>

            <!-- Post update -->
            <div class="pj-detail-section">
                <div class="pj-detail-label">Post Field Update</div>
                <form method="post" class="js-post-update-form">
                    <input type="hidden" name="project_id" value="<?= (int)$project['ProjectID'] ?>">
                    <input type="hidden" name="add_update" value="1">
                    <textarea name="update_description" rows="2"
                              placeholder="e.g. Foundation pour completed…" required
                              style="width:100%;margin-bottom:8px;resize:vertical;"></textarea>
                    <div class="d-flex gap-2 align-items-center">
                        <select name="update_status" style="flex:1;">
                            <option value="Reviewed">Reviewed</option>
                            <option value="Pending">Pending Inspection</option>
                            <option value="Approved">Approved</option>
                        </select>
                        <button class="admin-btn admin-btn-primary admin-btn-sm" disabled>Post</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Field updates log -->
        <?php if ($updateCount > 0): ?>
        <div class="pj-detail-section">
            <div class="pj-detail-label">Field Updates &amp; Inspections</div>
            <ul class="admin-activity-list" style="border:1px solid var(--admin-border);border-radius:6px;margin:0;">
                <?php while ($upd = mysqli_fetch_assoc($updatesResult)): ?>
                <li class="admin-activity-item">
                    <div class="d-flex justify-content-between gap-2 align-items-center">
                        <span class="admin-badge <?= $upd['Status']==='Pending' ? 'admin-badge-inspection' : 'admin-badge-track' ?>">
                            <?= htmlspecialchars($upd['Status']) ?>
                        </span>
                        <span class="admin-activity-time"><?= htmlspecialchars($upd['UpdateDate']) ?></span>
                    </div>
                    <div class="admin-activity-desc" style="margin-top:6px;"><?= htmlspecialchars($upd['Description']) ?></div>
                    <?php if ($upd['Status']==='Pending'): ?>
                    <form method="post" class="d-flex gap-2 mt-2">
                        <input type="hidden" name="update_id" value="<?= (int)$upd['UpdateID'] ?>">
                        <input type="hidden" name="review_update" value="1">
                        <select name="review_status" style="font-size:0.78rem;padding:4px 8px;">
                            <option value="Reviewed">Mark Reviewed</option>
                            <option value="Approved">Approve</option>
                        </select>
                        <button class="admin-btn admin-btn-success admin-btn-sm">Submit</button>
                    </form>
                    <?php endif; ?>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Delete -->
        <div style="padding-top:12px;border-top:1px solid var(--admin-border-light,#f3f4f6);">
            <form method="post"
                  onsubmit="return confirm('Delete Project #<?= (int)$project['ProjectID'] ?>? This cannot be undone.');">
                <input type="hidden" name="project_id" value="<?= (int)$project['ProjectID'] ?>">
                <button type="submit" name="delete_project"
                        class="admin-btn admin-btn-danger admin-btn-sm">Delete Project</button>
            </form>
        </div>

    </div><!-- /.pj-list-detail -->
</div><!-- /.pj-list-item -->

<!-- ── Edit Modal ─────────────────────────────────────────── -->
<div id="<?= $pEditId ?>"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:2000;align-items:center;justify-content:center;"
     onclick="if(event.target===this)this.style.display='none'">
    <div style="background:#fff;border-radius:10px;padding:32px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;position:relative;">
        <button type="button" onclick="document.getElementById('<?= $pEditId ?>').style.display='none'"
                style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:1.2rem;cursor:pointer;color:#6b7280;">✕</button>
        <h2 class="admin-page-title" style="font-size:1.05rem;margin-bottom:4px;">Edit Project</h2>
        <p class="admin-page-sub" style="margin-bottom:20px;">Site #<?= (int)$project['ProjectID'] ?> — update project details below.</p>
        <form method="POST" class="js-edit-modal-form">
            <input type="hidden" name="project_id" value="<?= (int)$project['ProjectID'] ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="admin-field">
                    <label>Project Title</label>
                    <input type="text" name="edit_title" value="<?= htmlspecialchars($project['ProjectTitle']??'') ?>" data-original="<?= htmlspecialchars($project['ProjectTitle']??'') ?>">
                </div>
                <div class="admin-field">
                    <label>Location</label>
                    <input type="text" name="edit_location" value="<?= htmlspecialchars($project['ProjectLocation']??'') ?>" data-original="<?= htmlspecialchars($project['ProjectLocation']??'') ?>">
                </div>
                <div class="admin-field">
                    <label>Start Date</label>
                    <input type="date" name="edit_start_date" value="<?= htmlspecialchars($project['StartDate']??'') ?>" data-original="<?= htmlspecialchars($project['StartDate']??'') ?>">
                </div>
                <div class="admin-field">
                    <label>End Date</label>
                    <input type="date" name="edit_end_date" value="<?= htmlspecialchars($project['EndDate']??'') ?>" data-original="<?= htmlspecialchars($project['EndDate']??'') ?>">
                </div>
                <div class="admin-field">
                    <label>Project Status</label>
                    <select name="edit_project_status" data-original="<?= htmlspecialchars($project['ProjectStatus']) ?>">
                        <?php foreach (['Ongoing','On Hold','Completed','Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $project['ProjectStatus']===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="admin-field">
                    <label>Payment Status</label>
                    <select name="edit_payment_status" data-original="<?= htmlspecialchars($project['ProjectPaymentStatus']) ?>">
                        <?php foreach (['Unpaid','Partial','Paid'] as $ps): ?>
                            <option value="<?= $ps ?>" <?= $project['ProjectPaymentStatus']===$ps?'selected':'' ?>><?= $ps ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="admin-field">
                <label>Description / Scope of Work</label>
                <textarea name="edit_description" rows="4" data-original="<?= htmlspecialchars($project['Description']??'') ?>"><?= htmlspecialchars($project['Description']??'') ?></textarea>
            </div>
            <div class="d-flex gap-2 justify-content-end mt-2">
                <button type="button" class="admin-btn admin-btn-outline" onclick="document.getElementById('<?= $pEditId ?>').style.display='none'">Cancel</button>
                <button type="submit" name="edit_project" class="admin-btn admin-btn-primary" disabled>Save Changes</button>
            </div>
        </form>
    </div>
</div>
