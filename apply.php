<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

if (userCount() === 0) {
    redirect('setup.php');
}

$barangays = getBarangayOptions();
$chapters = db()->query('SELECT chapter_id, chapter_name FROM CHAPTER ORDER BY chapter_name')->fetchAll();
$genders = db()->query('SELECT gender_id, gender_name FROM GENDER ORDER BY gender_name')->fetchAll();
$memberStatuses = db()->query('SELECT member_status_id, status_name FROM MEMBER_STATUS ORDER BY status_name')->fetchAll();
$educationLevels = db()->query('SELECT educational_attainment_id, level_name FROM EDUCATIONAL_ATTAINMENT ORDER BY level_name')->fetchAll();

$fields = [
    'desired_username' => '',
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'address' => '',
    'birthday' => '',
    'member_status_id' => '',
    'phone_number' => '',
    'barangay_id' => '',
    'chapter_id' => '',
    'gender_id' => '',
    'educational_attainment_id' => '',
    'primary_school' => '',
    'primary_year_graduated' => '',
    'secondary_school' => '',
    'secondary_year_graduated' => '',
    'college_school' => '',
    'college_year_graduated' => '',
];

foreach ($fields as $field => $default) {
    $fields[$field] = trim($_POST[$field] ?? $default);
}

$errors = [];

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

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

            <form method="post" class="application-form">
                <h2 class="form-section-title">Account Request</h2>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="desired_username">Username</label>
                        <input class="form-control" id="desired_username" name="desired_username" value="<?= e($fields['desired_username']) ?>" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="password">Password</label>
                        <input class="form-control" id="password" type="password" name="password" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="confirm_password">Confirm Password</label>
                        <input class="form-control" id="confirm_password" type="password" name="confirm_password" required>
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

                <button class="btn btn-primary" type="submit">Submit Application</button>
            </form>
        </div>
    </div>
</section>
<?php renderFooter(); ?>
