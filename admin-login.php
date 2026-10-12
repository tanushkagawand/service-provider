<?php
require_once __DIR__ . '/includes/auth.php';
requireGuest();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both admin email and password.';
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] !== 'admin') {
                $error = 'Access denied. This account does not possess administrator privileges. Please use user login.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_phone'] = $user['phone'];
                $_SESSION['user_address'] = $user['address'];

                setFlash('success', 'Welcome to the Administration Console.');
                header('Location: admin/dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid admin credentials.';
        }
    }
}

$pageTitle = "Admin Login";
$activeNav = "admin";
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page">
  <a class="back-link" href="index.php">&larr; Back to home</a>

  <section class="card" style="box-shadow: var(--elevation-2);">
    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
      <img class="brand-mark" src="images/local-service-finder-mark.png" alt="Logo" />
      <div>
        <span class="eyebrow" style="margin-bottom: 0;">Administration Portal</span>
      </div>
    </div>

    <h1 style="font-size: 1.85rem; margin-bottom: 4px;">Admin Login</h1>
    <p class="subtitle" style="margin-bottom: 20px;">Sign in to verify providers, review bookings, and monitor accounts.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger" style="margin-bottom: 20px;">
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form action="admin-login.php" method="POST">
      <div>
        <label for="admin-email">Admin Email</label>
        <input type="email" id="admin-email" name="email" placeholder="admin@example.com" value="<?= htmlspecialchars($_POST['email'] ?? 'admin@example.com') ?>" autocomplete="username" required />
      </div>

      <div>
        <label for="admin-password">Password</label>
        <input type="password" id="admin-password" name="password" placeholder="Enter admin password" autocomplete="current-password" required />
      </div>

      <button type="submit" class="btn btn-block" style="padding: 11px; font-size: 0.95rem; margin-top: 6px;">
        Sign in as Admin &rarr;
      </button>
    </form>

    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; font-size: 0.88rem;">
      <a href="login.php">&larr; Regular User Login</a>
      <span style="color: #64748B;">Default: <code>admin@example.com</code> / <code>admin123</code></span>
    </div>
  </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
