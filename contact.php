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

        setFlash('success', 'Thank you, ' . htmlspecialchars($name) . '! Your message has been delivered to our support team.');
        header('Location: contact.php');
        exit;
    }
}

$pageTitle = "Contact Us";
$activeNav = "contact";
require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 28px;">
  <span class="eyebrow">Support & Inquiries</span>
  <h1>Contact Us</h1>
  <p class="subtitle">Have questions about finding or providing a service? Our support team is here to help.</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 1.4fr; gap: 32px; align-items: start;">
  <!-- Left Side: Information Card (Material) -->
  <div class="card card-secondary">
    <h2 style="font-size: 1.3rem; margin-bottom: 8px;">Let's talk</h2>
    <p style="color: #64748B; margin-bottom: 24px; font-size: 0.95rem;">
      Fill out the contact form and include a few details so we know how to assist you promptly.
    </p>

    <div style="background: #FFFFFF; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 16px; margin-bottom: 14px;">
      <strong style="color: #0F172A;">Looking for a service?</strong><br />
      <span style="font-size: 0.9rem; color: #64748B;">Browse our <a href="services.php">services page</a> to search and compare verified electricians, plumbers, and technicians.</span>
    </div>

    <div style="background: #FFFFFF; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 16px; margin-bottom: 20px;">
      <strong style="color: #0F172A;">Providing a service?</strong><br />
      <span style="font-size: 0.9rem; color: #64748B;">You can <a href="signup.php">create a provider account</a> to list your business and receive local customer bookings.</span>
    </div>

    <div style="font-size: 0.85rem; color: #64748B; line-height: 1.6;">
      <div><strong>Support Hours:</strong> Mon &ndash; Sat, 9:00 AM &ndash; 6:00 PM</div>
      <div><strong>Response Time:</strong> Within 24 business hours</div>
    </div>
  </div>

  <!-- Right Side: Contact Form (Material) -->
  <div class="card" style="box-shadow: var(--elevation-2);">
    <h3 style="margin-bottom: 16px; font-size: 1.25rem;">Send a Message</h3>

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

      <button type="submit" class="btn" style="padding: 11px 22px; font-size: 0.95rem;">
        Send Message &rarr;
      </button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
