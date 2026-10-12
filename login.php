<?php
require_once __DIR__ . '/includes/auth.php';
requireGuest();

$redirect = sanitize($_GET['redirect'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = sanitize($_POST['redirect'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please provide both email and password.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Setup session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['user_address'] = $user['address'];

            setFlash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

            if (!empty($redirect) && strpos($redirect, 'http') === false) {
                header('Location: ' . $redirect);
                exit;
            }

            // Route based on role
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } elseif ($user['role'] === 'provider') {
                header('Location: provider/dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = "Login";
$activeNav = "login";
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <a class="back-link" href="index.php">&larr; Back to home</a>

  <section class="card" style="box-shadow: var(--elevation-2);">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
      <img class="brand-mark" src="images/local-service-finder-mark.png" alt="Logo" />
      <div>
        <span class="eyebrow" style="margin-bottom: 0;">Account Access</span>
      </div>
    </div>

    <h1 style="font-size: 1.85rem; margin-bottom: 4px;">Welcome back</h1>
    <p class="subtitle" style="margin-bottom: 20px;">Log in to manage bookings, find services, or view your provider workspace.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" style="margin-bottom: 20px;">
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>" />

      <div>
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" autocomplete="email" required />
      </div>

      <div>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required />
      </div>

      <button type="submit" class="btn btn-block" style="padding: 11px; font-size: 0.95rem; margin-top: 6px;">
        Sign In &rarr;
      </button>
    </form>

    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); font-size: 0.9rem;">
      <p style="margin-bottom: 6px;">
        New to Local Service Finder? <a href="signup.php">Create an account</a>
      </p>
      <p style="margin-bottom: 0;">
        System Administrator? <a href="admin-login.php">Admin login portal &rarr;</a>
      </p>
    </div>

    <!-- Quick Demo Accounts Helper (Material Style) -->
    <div style="margin-top: 20px; padding: 14px; background: #F8FAFC; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.82rem; color: #475569;">
      <strong style="display: block; margin-bottom: 6px; color: #0F172A;">Demo Test Credentials:</strong>
      <ul style="padding-left: 18px; margin: 0; line-height: 1.6;">
        <li>Customer: <code>customer@example.com</code> / <code>password123</code></li>
        <li>Electrician: <code>electrician@example.com</code> / <code>password123</code></li>
        <li>Plumber: <code>plumber@example.com</code> / <code>password123</code></li>
        <li>Admin: <code>admin@example.com</code> / <code>admin123</code></li>
      </ul>
    </div>
  </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
