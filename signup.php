<?php
require_once __DIR__ . '/includes/auth.php';
requireGuest();

$pdo = getDB();
$services = $pdo->query("SELECT * FROM services ORDER BY name ASC")->fetchAll();

$errors = [];
$accountType = $_POST['account-type'] ?? 'customer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountType   = sanitize($_POST['account-type'] ?? 'customer');
    $name          = sanitize($_POST['name'] ?? '');
    $email         = sanitize($_POST['email'] ?? '');
    $phone         = sanitize($_POST['phone'] ?? '');
    $address       = sanitize($_POST['address'] ?? '');
    $password      = $_POST['password'] ?? '';
    $confirmPass   = $_POST['confirm-password'] ?? '';

    // Provider specific fields
    $businessName  = sanitize($_POST['business-name'] ?? '');
    $serviceId     = (int)($_POST['service-category'] ?? 0);
    $experience    = sanitize($_POST['experience'] ?? '1 year');
    $fee           = floatval($_POST['fee'] ?? 300);
    $availability  = sanitize($_POST['availability'] ?? 'Mon - Sat (9am - 6pm)');
    $description   = sanitize($_POST['description'] ?? '');

    // Validation
    if (empty($name)) {
        $errors[] = 'Full name is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirmPass) {
        $errors[] = 'Passwords do not match.';
    }

    // Check if email already taken
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->fetch()) {
        $errors[] = 'An account with this email address already exists. Please log in.';
    }

    if ($accountType === 'provider') {
        if (empty($businessName)) {
            $errors[] = 'Business or Profile Name is required for providers.';
        }
        if ($serviceId <= 0) {
            $errors[] = 'Please select a valid service category.';
        }
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $role = ($accountType === 'provider') ? 'provider' : 'customer';

        $pdo->beginTransaction();
        try {
            // Insert user record
            $userStmt = $pdo->prepare("
                INSERT INTO users (name, email, phone, password, role, address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $userStmt->execute([$name, $email, $phone, $hashedPassword, $role, $address]);
            $userId = $pdo->lastInsertId();

            if ($role === 'provider') {
                $provStmt = $pdo->prepare("
                    INSERT INTO providers (user_id, service_id, business_name, experience, fee, availability, description, verification_status, rating)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 0.00)
                ");
                $provStmt->execute([$userId, $serviceId, $businessName, $experience, $fee, $availability, $description]);
            }

            $pdo->commit();

            // Auto-login newly registered user
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $role;
            $_SESSION['user_phone'] = $phone;
            $_SESSION['user_address'] = $address;

            if ($role === 'provider') {
                setFlash('info', 'Provider account created! Your profile is currently PENDING admin verification. You can review your dashboard.');
                header('Location: provider/dashboard.php');
            } else {
                setFlash('success', 'Welcome to Local Service Finder, ' . htmlspecialchars($name) . '! Your account is ready.');
                header('Location: dashboard.php');
            }
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Registration failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Create Account";
$activeNav = "signup";
require_once __DIR__ . '/includes/header.php';
?>

<div class="signup-page">
  <a class="back-link" href="index.php">&larr; Back to home</a>

  <section class="card" style="box-shadow: var(--elevation-2);">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
      <img class="brand-mark" src="images/local-service-finder-mark.png" alt="Logo" />
      <div>
        <span class="eyebrow" style="margin-bottom: 0;">Get Started</span>
      </div>
    </div>

    <h1 style="font-size: 1.85rem; margin-bottom: 4px;">Create your account</h1>
    <p class="subtitle" style="margin-bottom: 20px;">Choose whether you want to book services or offer your expertise locally.</p>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger" style="margin-bottom: 20px; display: block;">
        <strong style="display: block; margin-bottom: 4px;">Please fix the following issues:</strong>
        <ul style="padding-left: 20px; margin: 0;">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="signup-form" action="signup.php" method="POST">
      <fieldset class="account-types">
        <legend>Account Type</legend>
        <label class="type-option">
          <input type="radio" name="account-type" value="customer" <?= $accountType === 'customer' ? 'checked' : '' ?> />
          <span><strong>Customer</strong><small>Find & book local services</small></span>
        </label>
        <label class="type-option">
          <input type="radio" name="account-type" value="provider" <?= $accountType === 'provider' ? 'checked' : '' ?> />
          <span><strong>Service Provider</strong><small>Receive client service requests</small></span>
        </label>
      </fieldset>

      <div>
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="e.g. Priya Sharma" autocomplete="name" required />
      </div>

      <div class="form-row">
        <div>
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com" autocomplete="email" required />
        </div>
        <div>
          <label for="phone">Contact Phone</label>
          <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="e.g. 9811223344" required />
        </div>
      </div>

      <div>
        <label for="address">Address / Neighbourhood Area</label>
        <input type="text" id="address" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" placeholder="e.g. 402 Sea View, Bandra West, Mumbai" required />
      </div>

      <!-- Provider Specific Section -->
      <div id="provider-fields" style="<?= $accountType === 'provider' ? '' : 'display: none;' ?> background: #FAF7ED; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 18px; margin-top: 4px; display: flex; flex-direction: column; gap: 14px;">
        <span class="eyebrow" style="margin-bottom: 0;">Provider Profile Details</span>

        <div>
          <label for="business-name">Business / Trade Name</label>
          <input type="text" id="business-name" name="business-name" value="<?= htmlspecialchars($_POST['business-name'] ?? '') ?>" placeholder="e.g. Sharma Electrical Solutions" />
        </div>

        <div class="form-row">
          <div>
            <label for="service-category">Primary Service</label>
            <select id="service-category" name="service-category">
              <option value="">Select Service</option>
              <?php foreach ($services as $srv): ?>
                <option value="<?= $srv['id'] ?>" <?= (isset($_POST['service-category']) && $_POST['service-category'] == $srv['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($srv['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="experience">Experience</label>
            <input type="text" id="experience" name="experience" value="<?= htmlspecialchars($_POST['experience'] ?? '3 years') ?>" placeholder="e.g. 4 years" />
          </div>
        </div>

        <div class="form-row">
          <div>
            <label for="fee">Standard Visit Fee (&#8377;)</label>
            <input type="number" id="fee" name="fee" value="<?= htmlspecialchars($_POST['fee'] ?? '400') ?>" step="50" min="50" />
          </div>
          <div>
            <label for="availability">Availability Hours</label>
            <input type="text" id="availability" name="availability" value="<?= htmlspecialchars($_POST['availability'] ?? 'Mon - Sat (9:00 AM - 7:00 PM)') ?>" placeholder="e.g. Daily 9am - 8pm" />
          </div>
        </div>

        <div>
          <label for="description">Short Bio / Service Description</label>
          <textarea id="description" name="description" rows="3" placeholder="Describe the types of repairs, tools, and background experience you bring..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="form-row">
        <div>
          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="At least 8 characters" minlength="8" required />
        </div>
        <div>
          <label for="confirm-password">Confirm Password</label>
          <input type="password" id="confirm-password" name="confirm-password" placeholder="Re-enter password" minlength="8" required />
        </div>
      </div>

      <button type="submit" class="btn btn-block" style="padding: 12px; font-size: 0.95rem; margin-top: 6px;">
        Create Account &rarr;
      </button>
    </form>

    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); font-size: 0.9rem;">
      <p style="margin-bottom: 0;">
        Already have an account? <a href="login.php">Log in to your account</a>
      </p>
    </div>
  </section>
</div>

<script>
  const accountTypeRadios = document.querySelectorAll('input[name="account-type"]');
  const providerFields = document.getElementById('provider-fields');
  const businessName = document.getElementById('business-name');
  const serviceCategory = document.getElementById('service-category');

  function updateFields() {
    const isProvider = document.querySelector('input[name="account-type"]:checked').value === 'provider';
    if (isProvider) {
      providerFields.style.display = 'flex';
      businessName.required = true;
      serviceCategory.required = true;
    } else {
      providerFields.style.display = 'none';
      businessName.required = false;
      serviceCategory.required = false;
    }
  }

  accountTypeRadios.forEach(radio => radio.addEventListener('change', updateFields));
  updateFields();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
