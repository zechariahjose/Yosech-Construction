<?php
$pId       = (int) $project['ProjectID'];
$pEditId   = 'editProject_'   . $pId;
$pDetailId = 'detailProject_' . $pId;
$updateCount = mysqli_num_rows($updatesResult);
mysqli_data_seek($updatesResult, 0);

$paymentBadge = match($project['ProjectPaymentStatus']) {
    'Paid'    => 'pj-pay-paid',
    'Partial' => 'pj-pay-partial',
    default   => 'pj-pay-unpaid',
};
?>
<div class="pj-item" id="pjItem_<?= $pId ?>">

    <!-- ── Row ────────────────────────────────────────────── -->
    <div class="pj-row">

        <div class="pj-row-identity">
            <div class="pj-source-chip">Internal</div>
            <div class="pj-title"><?= htmlspecialchars(adminProjectDisplayName($project)) ?></div>
            <div class="pj-subtitle">
                #<?= $pId ?>
                <?php if (!empty($project['ProjectLocation'])): ?>
                    <span class="pj-dot">·</span><?= htmlspecialchars($project['ProjectLocation']) ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="pj-row-meta">
            <div class="pj-meta-chip">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <?= htmlspecialchars($project['Client_FirstName'] . ' ' . $project['Client_LastName']) ?>
            </div>
            <div class="pj-meta-chip">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <?= htmlspecialchars(($project['StartDate'] ?? '—') . ' → ' . ($project['EndDate'] ?? '—')) ?>
            </div>
            <div class="pj-meta-chip pj-pay <?= $paymentBadge ?>">
                <?= htmlspecialchars($project['ProjectPaymentStatus']) ?>
            </div>
            <?php if ($updateCount > 0): ?>
            <div class="pj-meta-chip">
                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <?= $updateCount ?> update<?= $updateCount !== 1 ? 's' : '' ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="pj-row-actions">
            <span class="admin-badge <?= $status['class'] ?>"><?= htmlspecialchars($status['label']) ?></span>
            <button type="button" class="pj-action-btn"
                    onclick="pjToggleDetail('<?= $pDetailId ?>', '<?= $pEditId ?>', this)"
                    data-label-open="Details" data-label-close="Close">
                Details
            </button>
            <button type="button" class="pj-action-btn pj-action-edit"
                    onclick="pjToggleEdit('<?= $pEditId ?>', '<?= $pDetailId ?>')">
                Edit
            </button>
        </div>
    </div>

    <!-- ── Detail panel ───────────────────────────────────── -->
    <div id="<?= $pDetailId ?>" class="pj-detail" style="display:none;">
        <div class="pj-detail-inner">

            <?php if (!empty($project['Description'])): ?>
            <div class="pj-detail-block pj-detail-block-full">
                <div class="pj-detail-label">Scope of Work</div>
                <p class="pj-detail-text"><?= nl2br(htmlspecialchars($project['Description'])) ?></p>
            </div>
            <?php endif; ?>

            <div class="pj-detail-cols">
                <!-- Status update -->
                <div class="pj-detail-block">
                    <div class="pj-detail-label">Project Status</div>
                    <form method="post" class="pj-inline-form js-track-form">
                        <input type="hidden" name="project_id" value="<?= $pId ?>">
                        <select name="project_status" data-original="<?= htmlspecialchars($project['ProjectStatus']) ?>">
                            <?php foreach (['Ongoing','On Hold','Completed','Cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $project['ProjectStatus']===$s?'selected':'' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="admin-btn admin-btn-primary admin-btn-sm" disabled>Save</button>
                    </form>
                </div>

                <!-- Payment status -->
                <div class="pj-detail-block">
                    <div class="pj-detail-label">Payment Status</div>
                    <form method="post" class="pj-inline-form js-track-form">
                        <input type="hidden" name="project_id" value="<?= $pId ?>">
                        <select name="payment_status" data-original="<?= htmlspecialchars($project['ProjectPaymentStatus']) ?>">
                            <?php foreach (['Unpaid','Partial','Paid'] as $ps): ?>
                                <option value="<?= $ps ?>" <?= $project['ProjectPaymentStatus']===$ps?'selected':'' ?>><?= $ps ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_payment" class="admin-btn admin-btn-primary admin-btn-sm" disabled>Save</button>
                    </form>
                </div>

                <!-- Post update -->
                <div class="pj-detail-block pj-detail-block-full">
                    <div class="pj-detail-label">Post Field Update</div>
                    <form method="post" class="js-post-update-form">
                        <input type="hidden" name="project_id" value="<?= $pId ?>">
                        <input type="hidden" name="add_update" value="1">
                        <input type="hidden" name="update_status" value="Reviewed">
                        <textarea name="update_description" rows="2"
                                  placeholder="e.g. Foundation pour completed…" required></textarea>
                        <div class="pj-inline-form" style="margin-top:8px;">
                            <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm" disabled>Post Update</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Updates log -->
            <?php if ($updateCount > 0): ?>
            <div class="pj-detail-block pj-detail-block-full">
                <div class="pj-detail-label">Field Updates &amp; Inspections</div>
                <div class="pj-updates-list">
                    <?php while ($upd = mysqli_fetch_assoc($updatesResult)): ?>
                    <div class="pj-update-item">
                        <div class="pj-update-header">
                            <span class="admin-badge <?= $upd['Status']==='Pending'?'admin-badge-inspection':($upd['Status']==='Approved'?'admin-badge-approved':'admin-badge-track') ?>">
                                <?= htmlspecialchars($upd['Status']) ?>
                            </span>
                            <span class="pj-update-date"><?= htmlspecialchars($upd['UpdateDate']) ?></span>
                        </div>
                        <p class="pj-update-desc"><?= htmlspecialchars($upd['Description']) ?></p>
                        <form method="post" class="pj-inline-form js-track-form" style="margin-top:8px;">
                            <input type="hidden" name="update_id" value="<?= (int)$upd['UpdateID'] ?>">
                            <input type="hidden" name="review_update" value="1">
                            <select name="review_status" data-original="<?= htmlspecialchars($upd['Status']) ?>">
                                <option value="Reviewed"  <?= $upd['Status']==='Reviewed' ?'selected':'' ?>>Reviewed</option>
                                <option value="Pending"   <?= $upd['Status']==='Pending'  ?'selected':'' ?>>Pending Inspection</option>
                                <option value="Approved"  <?= $upd['Status']==='Approved' ?'selected':'' ?>>Approved</option>
                            </select>
                            <button class="admin-btn admin-btn-success admin-btn-sm" disabled>Update</button>
                        </form>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Delete -->
            <div class="pj-detail-footer">
                <form method="post" onsubmit="return confirm('Delete Project #<?= $pId ?>? This cannot be undone.');">
                    <input type="hidden" name="project_id" value="<?= $pId ?>">
                    <button type="submit" name="delete_project" class="admin-btn admin-btn-danger admin-btn-sm">
                        Delete Project
                    </button>
                </form>
            </div>

        </div>
    </div>

    <!-- ── Edit panel (inline slide-down) ─────────────────── -->
    <div id="<?= $pEditId ?>" class="pj-edit-panel" style="display:none;">
        <div class="pj-edit-inner">
            <div class="pj-edit-head">
                <div>
                    <div class="pj-edit-title">Edit Project</div>
                    <div class="pj-edit-sub">Site #<?= $pId ?> — update details below</div>
                </div>
                <button type="button" class="pj-edit-close"
                        onclick="pjCloseEdit('<?= $pEditId ?>')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" class="js-edit-modal-form">
                <input type="hidden" name="project_id" value="<?= $pId ?>">
                <div class="pj-edit-grid">
                    <div class="admin-field">
                        <label>Project Title</label>
                        <input type="text" name="edit_title"
                               value="<?= htmlspecialchars($project['ProjectTitle']??'') ?>"
                               data-original="<?= htmlspecialchars($project['ProjectTitle']??'') ?>">
                    </div>
                    <div class="admin-field">
                        <label>Location</label>
                        <input type="text" name="edit_location"
                               value="<?= htmlspecialchars($project['ProjectLocation']??'') ?>"
                               data-original="<?= htmlspecialchars($project['ProjectLocation']??'') ?>">
                    </div>
                    <div class="admin-field">
                        <label>Start Date</label>
                        <input type="date" name="edit_start_date"
                               value="<?= htmlspecialchars($project['StartDate']??'') ?>"
                               data-original="<?= htmlspecialchars($project['StartDate']??'') ?>">
                    </div>
                    <div class="admin-field">
                        <label>End Date</label>
                        <input type="date" name="edit_end_date"
                               value="<?= htmlspecialchars($project['EndDate']??'') ?>"
                               data-original="<?= htmlspecialchars($project['EndDate']??'') ?>">
                    </div>
                    <div class="admin-field">
                        <label>Project Status</label>
                        <select name="edit_project_status"
                                data-original="<?= htmlspecialchars($project['ProjectStatus']) ?>">
                            <?php foreach (['Ongoing','On Hold','Completed','Cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $project['ProjectStatus']===$s?'selected':'' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-field">
                        <label>Payment Status</label>
                        <select name="edit_payment_status"
                                data-original="<?= htmlspecialchars($project['ProjectPaymentStatus']) ?>">
                            <?php foreach (['Unpaid','Partial','Paid'] as $ps): ?>
                                <option value="<?= $ps ?>" <?= $project['ProjectPaymentStatus']===$ps?'selected':'' ?>><?= $ps ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="admin-field">
                    <label>Description / Scope of Work</label>
                    <textarea name="edit_description" rows="4"
                              data-original="<?= htmlspecialchars($project['Description']??'') ?>"><?= htmlspecialchars($project['Description']??'') ?></textarea>
                </div>
                <div class="pj-edit-actions">
                    <button type="button" class="admin-btn admin-btn-outline admin-btn-sm"
                            onclick="pjCloseEdit('<?= $pEditId ?>')">Cancel</button>
                    <button type="submit" name="edit_project" class="admin-btn admin-btn-primary admin-btn-sm" disabled>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</div><!-- /.pj-item -->
