<?php
require_once __DIR__ . '/includes/auth.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['name'] ?? '');
    $email   = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if (empty($name)) {
        $errors[] = 'Your name is required.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
    if (empty($subject)) {
        $errors[] = 'Please choose an inquiry subject.';
    }
    if (empty($message)) {
        $errors[] = 'Please enter your message.';
    }

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);

        setFlash('success', 'Thank you, ' . htmlspecialchars($name) . '! Your message has been delivered to the administration team.');
        header('Location: contact.php');
        exit;
    }
}

$pageTitle = "Contact Us";
$activeNav = "contact";
require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 32px;">
  <span class="eyebrow">WE ARE HERE TO HELP</span>
  <h1>Contact Us</h1>
  <p class="subtitle">Have a question about finding or providing a service? Send us a message.</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 1.4fr; gap: 32px; align-items: start;">
  <!-- Left Side: Helpful Information -->
  <div class="card card-secondary">
    <h2>Let's talk</h2>
    <p style="color: #444; margin-bottom: 24px;">
      Fill out the contact form and include a few details so we know how to assist you promptly.
    </p>

    <div style="background: #FFFFFF; border: 2px solid var(--border-color); border-radius: 6px; padding: 16px; margin-bottom: 16px; box-shadow: var(--shadow-sm);">
      <strong>Looking for a service?</strong><br />
      Visit our <a href="services.php">services page</a> to search and compare verified electricians, plumbers, and technicians.
    </div>

    <div style="background: #FFFFFF; border: 2px solid var(--border-color); border-radius: 6px; padding: 16px; margin-bottom: 24px; box-shadow: var(--shadow-sm);">
      <strong>Providing a service?</strong><br />
      You can <a href="signup.php">create a provider account</a> to list your business and receive local customer bookings.
    </div>

    <div style="font-size: 0.9rem; color: #555;">
      <div><strong>Support Hours:</strong> Monday &ndash; Saturday, 9:00 AM &ndash; 6:00 PM</div>
      <div><strong>Response Time:</strong> Within 24 business hours</div>
    </div>
  </div>

  <!-- Right Side: Contact Form -->
  <div class="card">
    <h3 style="margin-bottom: 16px;">Send a Direct Message</h3>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger" style="margin-bottom: 20px; display: block;">
        <ul style="padding-left: 20px; margin: 0;">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form class="contact-form" action="contact.php" method="POST">
      <div>
        <label for="contact-name">Your Full Name</label>
        <input id="contact-name" name="name" type="text" autocomplete="name" placeholder="e.g. Kunal Kapoor" value="<?= htmlspecialchars($_POST['name'] ?? ($user['name'] ?? '')) ?>" required />
      </div>

      <div>
        <label for="contact-email">Email Address</label>
        <input id="contact-email" name="email" type="email" autocomplete="email" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? ($user['email'] ?? '')) ?>" required />
      </div>

      <div>
        <label for="contact-subject">Topic / Subject</label>
        <select id="contact-subject" name="subject" required>
          <option value="">Choose a topic</option>
          <option value="Finding a service" <?= (isset($_POST['subject']) && $_POST['subject'] === 'Finding a service') ? 'selected' : '' ?>>Finding a service</option>
          <option value="Joining as a provider" <?= (isset($_POST['subject']) && $_POST['subject'] === 'Joining as a provider') ? 'selected' : '' ?>>Joining as a provider</option>
          <option value="Website question" <?= (isset($_POST['subject']) && $_POST['subject'] === 'Website question') ? 'selected' : '' ?>>Website question</option>
          <option value="Other" <?= (isset($_POST['subject']) && $_POST['subject'] === 'Other') ? 'selected' : '' ?>>Other</option>
        </select>
      </div>

      <div>
        <label for="contact-message">Message</label>
        <textarea id="contact-message" name="message" rows="5" placeholder="How can we help you?" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn" style="padding: 12px 24px; font-size: 1rem;">
        Send Message &rarr;
      </button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
