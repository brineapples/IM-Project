<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

// -----------------------------
// Page Setup
// -----------------------------

if (userCount() === 0) {
    redirect('setup.php');
}

// -----------------------------
// Lookup Data
// -----------------------------

$applyTestModeFile = __DIR__ . '/config/apply_test_mode.php';
$applyTestMode = is_file($applyTestModeFile) ? require $applyTestModeFile : [];
$applyTestModeEnabled = !empty($applyTestMode['enabled']);

$barangays = getBarangayOptions();
$chapters = db()->query('SELECT chapter_id, chapter_name FROM CHAPTER ORDER BY chapter_name')->fetchAll();
$genders = db()->query('SELECT gender_id, gender_name FROM GENDER ORDER BY gender_name')->fetchAll();
$memberStatuses = db()->query('SELECT member_status_id, status_name FROM MEMBER_STATUS ORDER BY status_name')->fetchAll();
$educationLevels = db()->query('SELECT educational_attainment_id, level_name FROM EDUCATIONAL_ATTAINMENT ORDER BY level_name')->fetchAll();
$policyDocuments = policyDocuments();

$firstChapterId = (string) ($chapters[0]['chapter_id'] ?? '');
$firstGenderId = (string) ($genders[0]['gender_id'] ?? '');
$firstMemberStatusId = (string) ($memberStatuses[0]['member_status_id'] ?? '');
$firstEducationId = (string) ($educationLevels[0]['educational_attainment_id'] ?? '');
$testUsername = ($applyTestMode['username_prefix'] ?? 'TEST_APPLY_') . date('YmdHis');
$testPassword = $applyTestModeEnabled ? (string) ($applyTestMode['password'] ?? 'TestPass123!') : '';

// -----------------------------
// Form Defaults
// -----------------------------

$fields = [
    'desired_username' => $applyTestModeEnabled ? $testUsername : '',
    'first_name' => $applyTestModeEnabled ? (string) ($applyTestMode['first_name'] ?? '') : '',
    'middle_name' => $applyTestModeEnabled ? (string) ($applyTestMode['middle_name'] ?? '') : '',
    'last_name' => $applyTestModeEnabled ? (string) ($applyTestMode['last_name'] ?? '') : '',
    'address' => $applyTestModeEnabled ? (string) ($applyTestMode['address'] ?? '') : '',
    'birthday' => $applyTestModeEnabled ? (string) ($applyTestMode['birthday'] ?? '') : '',
    'member_status_id' => $applyTestModeEnabled ? $firstMemberStatusId : '',
    'phone_number' => $applyTestModeEnabled ? (string) ($applyTestMode['phone_number'] ?? '') : '',
    'barangay_id' => $applyTestModeEnabled ? '28' : '',
    'chapter_id' => $applyTestModeEnabled ? $firstChapterId : '',
    'gender_id' => $applyTestModeEnabled ? $firstGenderId : '',
    'educational_attainment_id' => $applyTestModeEnabled ? $firstEducationId : '',
    'primary_school' => $applyTestModeEnabled ? (string) ($applyTestMode['primary_school'] ?? '') : '',
    'primary_year_graduated' => $applyTestModeEnabled ? (string) ($applyTestMode['primary_year_graduated'] ?? '') : '',
    'secondary_school' => $applyTestModeEnabled ? (string) ($applyTestMode['secondary_school'] ?? '') : '',
    'secondary_year_graduated' => $applyTestModeEnabled ? (string) ($applyTestMode['secondary_year_graduated'] ?? '') : '',
    'college_school' => $applyTestModeEnabled ? (string) ($applyTestMode['college_school'] ?? '') : '',
    'college_year_graduated' => $applyTestModeEnabled ? (string) ($applyTestMode['college_year_graduated'] ?? '') : '',
];

foreach ($fields as $field => $default) {
    $fields[$field] = trim($_POST[$field] ?? $default);
}

$errors = [];

// -----------------------------
// Validation Helpers
// -----------------------------

function lookupHasId(string $table, string $idColumn, int $id): bool
{
    $allowedTables = [
        'CHAPTER' => 'chapter_id',
        'GENDER' => 'gender_id',
        'MEMBER_STATUS' => 'member_status_id',
        'EDUCATIONAL_ATTAINMENT' => 'educational_attainment_id',
    ];

    if (($allowedTables[$table] ?? null) !== $idColumn) {
        return false;
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM `' . $table . '` WHERE `' . $idColumn . '` = ?');
    $stmt->execute([$id]);

    return (int) $stmt->fetchColumn() > 0;
}

function optionalYearValue(string $value, array &$errors, string $label): ?int
{
    if ($value === '') {
        return null;
    }

    if (!ctype_digit($value)) {
        $errors[] = $label . ' must be a valid year.';
        return null;
    }

    $year = (int) $value;
    $currentYear = (int) date('Y');
    if ($year < 1901 || $year > $currentYear) {
        $errors[] = $label . ' must be between 1901 and ' . $currentYear . '.';
        return null;
    }

    return $year;
}

// -----------------------------
// Form Handling
// -----------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $acceptedTerms = ($_POST['accept_terms'] ?? '') === '1';
    $acceptedPrivacy = ($_POST['accept_privacy'] ?? '') === '1';
    $acceptedFitnessRisk = ($_POST['accept_fitness_risk'] ?? '') === '1';

    foreach ([
        'desired_username' => 'Username',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'address' => 'Address',
        'birthday' => 'Birthday',
        'phone_number' => 'Phone Number',
    ] as $field => $label) {
        if ($fields[$field] === '') {
            $errors[] = $label . ' is required.';
        }
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$acceptedTerms) {
        $errors[] = 'You must agree to the Terms of Use and Terms of Service.';
    }

    if (!$acceptedPrivacy) {
        $errors[] = 'You must consent to the Privacy Statement.';
    }

    if (!$acceptedFitnessRisk) {
        $errors[] = 'You must acknowledge the fitness activity risk statement.';
    }

    $birthdayTime = strtotime($fields['birthday']);
    if ($fields['birthday'] === '' || $birthdayTime === false || date('Y-m-d', $birthdayTime) !== $fields['birthday']) {
        $errors[] = 'Birthday must be a valid date.';
    } elseif ($birthdayTime > strtotime('today')) {
        $errors[] = 'Birthday cannot be in the future.';
    }

    $barangayId = (int) $fields['barangay_id'];
    $chapterId = (int) $fields['chapter_id'];
    $genderId = (int) $fields['gender_id'];
    $memberStatusId = (int) $fields['member_status_id'];
    $educationId = (int) $fields['educational_attainment_id'];

    if (!isValidBarangayId($barangayId)) {
        $errors[] = 'Barangay is required.';
    }
    if (!lookupHasId('CHAPTER', 'chapter_id', $chapterId)) {
        $errors[] = 'Chapter is required.';
    }
    if (!lookupHasId('GENDER', 'gender_id', $genderId)) {
        $errors[] = 'Gender is required.';
    }
    if (!lookupHasId('MEMBER_STATUS', 'member_status_id', $memberStatusId)) {
        $errors[] = 'Status is required.';
    }
    if (!lookupHasId('EDUCATIONAL_ATTAINMENT', 'educational_attainment_id', $educationId)) {
        $errors[] = 'Educational attainment is required.';
    }

    $primaryYear = optionalYearValue($fields['primary_year_graduated'], $errors, 'Primary year graduated');
    $secondaryYear = optionalYearValue($fields['secondary_year_graduated'], $errors, 'Secondary year graduated');
    $collegeYear = optionalYearValue($fields['college_year_graduated'], $errors, 'College year graduated');

    $stmt = db()->prepare('SELECT COUNT(*) FROM USER_ACCOUNT WHERE username = ?');
    $stmt->execute([$fields['desired_username']]);
    if ((int) $stmt->fetchColumn() > 0) {
        $errors[] = 'Username is already taken.';
    }

    $stmt = db()->prepare('SELECT COUNT(*) FROM MEMBERSHIP_APPLICATION WHERE desired_username = ?');
    $stmt->execute([$fields['desired_username']]);
    if ((int) $stmt->fetchColumn() > 0) {
        $errors[] = 'An application already uses this username.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO MEMBERSHIP_APPLICATION (
                desired_username, password, first_name, middle_name, last_name, address, birthday,
                member_status_id, phone_number, barangay_id, chapter_id, gender_id, educational_attainment_id,
                primary_school, primary_year_graduated, secondary_school, secondary_year_graduated,
                college_school, college_year_graduated, application_status_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $fields['desired_username'],
            password_hash($password, PASSWORD_DEFAULT),
            $fields['first_name'],
            $fields['middle_name'] === '' ? null : $fields['middle_name'],
            $fields['last_name'],
            $fields['address'],
            $fields['birthday'],
            $memberStatusId,
            $fields['phone_number'],
            $barangayId,
            $chapterId,
            $genderId,
            $educationId,
            $fields['primary_school'] === '' ? null : $fields['primary_school'],
            $primaryYear,
            $fields['secondary_school'] === '' ? null : $fields['secondary_school'],
            $secondaryYear,
            $fields['college_school'] === '' ? null : $fields['college_school'],
            $collegeYear,
            getApplicationStatusId('Pending'),
        ]);

        $applicationId = (int) db()->lastInsertId();
        logSystemActivity('CREATE_APPLICATION', 'Anonymous applicant submitted membership application #' . $applicationId . '.');
        flash('success', 'Application submitted. Please wait for admin approval before logging in.');
        redirect('apply.php');
    }
}

// -----------------------------
// HTML Output
// -----------------------------

renderPublicHeader('Apply for Membership', 'apply');
?>
<section class="section app-section">
    <div class="container">
        <div class="app-panel">
            <div class="app-heading">
                <div>
                    <h1>Membership <em>Application</em></h1>
                </div>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($applyTestModeEnabled): ?>
                <div class="alert alert-warning">
                    Application test mode is active. Delete <strong>config/apply_test_mode.php</strong> to restore the normal blank form.
                </div>
            <?php endif; ?>

            <form method="post" class="application-form" data-application-policy-form>
                <h2 class="form-section-title">Account Request</h2>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="desired_username">Username</label>
                        <input class="form-control" id="desired_username" name="desired_username" value="<?= e($fields['desired_username']) ?>" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="password">Password</label>
                        <input class="form-control" id="password" type="password" name="password" value="<?= e($testPassword) ?>" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="confirm_password">Confirm Password</label>
                        <input class="form-control" id="confirm_password" type="password" name="confirm_password" value="<?= e($testPassword) ?>" required>
                    </div>
                </div>

                <h2 class="form-section-title">Personal Details</h2>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="first_name">First Name</label>
                        <input class="form-control" id="first_name" name="first_name" value="<?= e($fields['first_name']) ?>" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="middle_name">Middle Name</label>
                        <input class="form-control" id="middle_name" name="middle_name" value="<?= e($fields['middle_name']) ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="last_name">Last Name</label>
                        <input class="form-control" id="last_name" name="last_name" value="<?= e($fields['last_name']) ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="address">Address</label>
                        <input class="form-control" id="address" name="address" value="<?= e($fields['address']) ?>" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="birthday">Birthday</label>
                        <input class="form-control" id="birthday" type="date" name="birthday" value="<?= e($fields['birthday']) ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="barangay_id">Barangay</label>
                        <select class="form-control" id="barangay_id" name="barangay_id" required>
                            <option value="">Select barangay</option>
                            <?php foreach ($barangays as $id => $name): ?>
                                <option value="<?= e((string) $id) ?>" <?= (int) $fields['barangay_id'] === (int) $id ? 'selected' : '' ?>>
                                    <?= e($id . ' - ' . $name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="chapter_id">Chapter</label>
                        <select class="form-control" id="chapter_id" name="chapter_id" required>
                            <option value="">Select chapter</option>
                            <?php foreach ($chapters as $chapter): ?>
                                <option value="<?= e((string) $chapter['chapter_id']) ?>" <?= (int) $fields['chapter_id'] === (int) $chapter['chapter_id'] ? 'selected' : '' ?>>
                                    <?= e($chapter['chapter_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="phone_number">Phone Number</label>
                        <input class="form-control" id="phone_number" name="phone_number" value="<?= e($fields['phone_number']) ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="gender_id">Gender</label>
                        <select class="form-control" id="gender_id" name="gender_id" required>
                            <option value="">Select gender</option>
                            <?php foreach ($genders as $gender): ?>
                                <option value="<?= e((string) $gender['gender_id']) ?>" <?= (int) $fields['gender_id'] === (int) $gender['gender_id'] ? 'selected' : '' ?>>
                                    <?= e($gender['gender_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="member_status_id">Status</label>
                        <select class="form-control" id="member_status_id" name="member_status_id" required>
                            <option value="">Select status</option>
                            <?php foreach ($memberStatuses as $status): ?>
                                <option value="<?= e((string) $status['member_status_id']) ?>" <?= (int) $fields['member_status_id'] === (int) $status['member_status_id'] ? 'selected' : '' ?>>
                                    <?= e($status['status_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h2 class="form-section-title">Education</h2>
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="educational_attainment_id">Educational Attainment</label>
                        <select class="form-control" id="educational_attainment_id" name="educational_attainment_id" required>
                            <option value="">Select educational attainment</option>
                            <?php foreach ($educationLevels as $level): ?>
                                <option value="<?= e((string) $level['educational_attainment_id']) ?>" <?= (int) $fields['educational_attainment_id'] === (int) $level['educational_attainment_id'] ? 'selected' : '' ?>>
                                    <?= e($level['level_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="primary_school">Primary School</label>
                        <input class="form-control" id="primary_school" name="primary_school" value="<?= e($fields['primary_school']) ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="primary_year_graduated">Primary Year Graduated</label>
                        <input class="form-control" id="primary_year_graduated" type="number" min="1901" max="<?= e(date('Y')) ?>" step="1" name="primary_year_graduated" value="<?= e($fields['primary_year_graduated']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="secondary_school">Secondary School</label>
                        <input class="form-control" id="secondary_school" name="secondary_school" value="<?= e($fields['secondary_school']) ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="secondary_year_graduated">Secondary Year Graduated</label>
                        <input class="form-control" id="secondary_year_graduated" type="number" min="1901" max="<?= e(date('Y')) ?>" step="1" name="secondary_year_graduated" value="<?= e($fields['secondary_year_graduated']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="college_school">College School</label>
                        <input class="form-control" id="college_school" name="college_school" value="<?= e($fields['college_school']) ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="college_year_graduated">College Year Graduated</label>
                        <input class="form-control" id="college_year_graduated" type="number" min="1901" max="<?= e(date('Y')) ?>" step="1" name="college_year_graduated" value="<?= e($fields['college_year_graduated']) ?>">
                    </div>
                </div>

                <input type="hidden" name="accept_terms" value="" data-policy-hidden="terms">
                <input type="hidden" name="accept_privacy" value="" data-policy-hidden="privacy">
                <input type="hidden" name="accept_fitness_risk" value="" data-policy-hidden="fitness_risk">

                <button class="btn btn-primary" type="submit">Submit Application</button>
            </form>
        </div>
    </div>
</section>

<?php foreach ($policyDocuments as $type => $document): ?>
    <div class="policy-modal" data-policy-modal="<?= e($type) ?>" aria-hidden="true">
        <div class="policy-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="policy-modal-title-<?= e($type) ?>">
            <button class="policy-modal-close" type="button" data-policy-modal-close aria-label="Close policy document">&times;</button>
            <h2 id="policy-modal-title-<?= e($type) ?>"><?= e($document['title']) ?></h2>
            <p class="app-muted">Version <?= e($document['version']) ?> | Effective <?= e(date('F j, Y', strtotime($document['effective_date']))) ?></p>
            <div class="policy-modal-body" data-policy-modal-scroll="<?= e($type) ?>" tabindex="0">
                <?= nl2br(e($document['content'])) ?>
            </div>
            <div class="policy-modal-actions">
                <span class="policy-read-status" data-policy-modal-status="<?= e($type) ?>">Scroll to the bottom to continue</span>
                <button class="btn btn-primary" type="button" data-policy-modal-read="<?= e($type) ?>" disabled>I have read this</button>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<div class="policy-modal" data-policy-modal="fitness_risk" aria-hidden="true">
    <div class="policy-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="policy-modal-title-fitness-risk">
        <button class="policy-modal-close" type="button" data-policy-modal-close aria-label="Close policy document">&times;</button>
        <h2 id="policy-modal-title-fitness-risk">Fitness Activity Acknowledgment</h2>
        <p class="app-muted">Please review this acknowledgment before submitting your application.</p>
        <div class="policy-modal-body" data-policy-modal-scroll="fitness_risk" tabindex="0">
            I understand that Zumba and other fitness activities involve physical movement and may carry risks such as fatigue, strain, injury, or other health concerns.

            I confirm that I am responsible for considering my own health condition before joining any activity. This application and the information shown in the system do not replace professional medical advice, diagnosis, or treatment.

            If I have a medical condition, injury, or concern about participating in fitness activities, I understand that I should consult a qualified health professional before joining a session.
        </div>
        <div class="policy-modal-actions">
            <span class="policy-read-status" data-policy-modal-status="fitness_risk">Scroll to the bottom to continue</span>
            <button class="btn btn-primary" type="button" data-policy-modal-read="fitness_risk" disabled>I understand and agree</button>
        </div>
    </div>
</div>

<?php renderFooter(); ?>
