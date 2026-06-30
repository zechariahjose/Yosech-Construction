<div class="admin-card">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h3 class="admin-card-title mb-1"><?= htmlspecialchars(adminProjectDisplayName($project)) ?></h3>
            <span class="admin-table-sub">Site #<?= (int) $project['ProjectID'] ?> · <?= htmlspecialchars($project['ProjectLocation'] ?? '—') ?></span>
        </div>
        <span class="admin-badge <?= $status['class'] ?>"><?= htmlspecialchars($status['label']) ?></span>
    </div>

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

    <!-- Update Status -->
    <form method="post" class="d-flex gap-2 align-items-end mb-0">
        <input type="hidden" name="project_id" value="<?= (int) $project['ProjectID'] ?>">
        <div class="admin-field mb-0 flex-grow-1">
            <label>Site Status</label>
            <select name="project_status">
                <?php foreach (['Ongoing', 'On Hold', 'Completed', 'Cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $project['ProjectStatus'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="admin-btn admin-btn-primary admin-btn-sm">Update Status</button>
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

    <form method="post">
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
            <button class="admin-btn admin-btn-primary admin-btn-sm">Post Update</button>
        </div>
    </form>

    <hr class="admin-divider">

    <!-- Delete Project -->
    <form method="post" onsubmit="return confirm('Are you sure you want to delete Project #<?= (int) $project['ProjectID'] ?>? This cannot be undone. The linked application will not be affected.');">
        <input type="hidden" name="project_id" value="<?= (int) $project['ProjectID'] ?>">
        <button type="submit" name="delete_project" class="admin-btn admin-btn-danger admin-btn-sm w-100">Delete Project</button>
    </form>
</div>
