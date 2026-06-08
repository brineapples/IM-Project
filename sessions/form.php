<?php if ($errors): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <div><?= e($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!$coaches): ?>
    <div class="alert alert-warning">Create a Coach user before creating sessions.</div>
<?php endif; ?>

<form method="post">
    <?php if (!empty($session['session_id'])): ?>
        <input type="hidden" name="id" value="<?= e((string) $session['session_id']) ?>">
    <?php endif; ?>

    <div class="form-row">
        <div class="form-group col-md-8">
            <label for="session_title">Title</label>
            <input class="form-control" id="session_title" name="session_title" value="<?= e($session['session_title'] ?? '') ?>" required>
        </div>
        <div class="form-group col-md-4">
            <label for="session_date">Date</label>
            <input class="form-control" id="session_date" type="date" name="session_date" value="<?= e($session['session_date'] ?? '') ?>" required>
        </div>
    </div>

    <div class="form-row">
        <div class="form-group col-md-4">
            <label for="start_time">Start Time</label>
            <input class="form-control" id="start_time" type="time" name="start_time" value="<?= e(substr((string) ($session['start_time'] ?? ''), 0, 5)) ?>" required>
        </div>
        <div class="form-group col-md-4">
            <label for="end_time">End Time</label>
            <input class="form-control" id="end_time" type="time" name="end_time" value="<?= e(substr((string) ($session['end_time'] ?? ''), 0, 5)) ?>" required>
        </div>
        <div class="form-group col-md-4">
            <label for="coach_user_id">Coach</label>
            <select class="form-control" id="coach_user_id" name="coach_user_id" required>
                <option value="">Select coach</option>
                <?php foreach ($coaches as $coach): ?>
                    <option value="<?= e((string) $coach['user_id']) ?>" <?= (int) ($session['coach_user_id'] ?? $session['instructor_user_id'] ?? 0) === (int) $coach['user_id'] ? 'selected' : '' ?>>
                        <?= e($coach['username'] . ' - ' . displayRoleName($coach['role_name'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="location">Location</label>
        <input class="form-control" id="location" name="location" value="<?= e($session['location'] ?? '') ?>" required>
    </div>

    <button class="btn btn-primary" type="submit" <?= !$coaches ? 'disabled' : '' ?>>Save Session</button>
</form>
