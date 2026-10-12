<?php
require_once __DIR__ . '/includes/auth.php';

// Check login requirement
if (!isLoggedIn()) {
    $redirectUrl = 'booking.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    setFlash('info', 'Please log in to your customer account before booking a service.');
    header('Location: login.php?redirect=' . urlencode($redirectUrl));
    exit;
}

$user = currentUser();
if ($user['role'] !== 'customer') {
    setFlash('error', 'Only customer accounts can submit booking requests.');
    if ($user['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: provider/dashboard.php');
    }
    exit;
}

$pdo = getDB();
$selectedProviderId = (int)($_GET['provider_id'] ?? 0);
$errors = [];

// Fetch list of all approved providers
$providersStmt = $pdo->query("
    SELECT p.*, s.name as service_name, u.name as owner_name 
    FROM providers p 
    JOIN services s ON p.service_id = s.id 
    JOIN users u ON p.user_id = u.id 
    WHERE p.verification_status = 'approved' 
    ORDER BY p.business_name ASC
");
$approvedProviders = $providersStmt->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $providerId   = (int)($_POST['provider_id'] ?? 0);
    $bookingDate  = sanitize($_POST['date'] ?? '');
    $bookingTime  = sanitize($_POST['time'] ?? '');
    $address      = sanitize($_POST['address'] ?? '');
    $description  = sanitize($_POST['details'] ?? '');

    if ($providerId <= 0) {
        $errors[] = 'Please select a service provider.';
    }
    if (empty($bookingDate) || strtotime($bookingDate) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Please select a valid booking date (today or in the future).';
    }
    if (empty($bookingTime)) {
        $errors[] = 'Please specify a preferred time.';
    }
    if (empty($address)) {
        $errors[] = 'Please provide the service address.';
    }
    if (empty($description)) {
        $errors[] = 'Please describe the problem or service you require.';
    }

    if (empty($errors)) {
        // Verify provider is approved and retrieve service_id
        $provCheck = $pdo->prepare("SELECT id, service_id, business_name FROM providers WHERE id = ? AND verification_status = 'approved'");
        $provCheck->execute([$providerId]);
        $targetProvider = $provCheck->fetch();

        if (!$targetProvider) {
            $errors[] = 'The selected provider is not available or has not been approved.';
        } else {
            // Insert real booking
            $stmt = $pdo->prepare("
                INSERT INTO bookings (customer_id, provider_id, service_id, booking_date, booking_time, address, problem_description, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $user['id'],
                $providerId,
                $targetProvider['service_id'],
                $bookingDate,
                $bookingTime,
                $address,
                $description
            ]);

            setFlash('success', 'Booking request sent to ' . htmlspecialchars($targetProvider['business_name']) . '! Status: PENDING.');
            header('Location: dashboard.php');
            exit;
        }
    }
}

$pageTitle = "Book a Service";
$activeNav = "booking";
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 640px; margin: 0 auto;">
  <a class="back-link" href="dashboard.php">&larr; Back to dashboard</a>

  <section class="card" style="box-shadow: var(--elevation-2);">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
      <span class="eyebrow" style="margin-bottom: 0;">Schedule Service</span>
    </div>

    <h1 style="font-size: 1.85rem; margin-bottom: 4px;">Book a Service</h1>
    <p class="subtitle" style="margin-bottom: 24px;">Tell us what you need. Choose the verified provider, preferred date, and time that works for you.</p>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger" style="margin-bottom: 20px; display: block;">
        <strong style="display: block; margin-bottom: 4px;">Please correct the errors:</strong>
        <ul style="padding-left: 20px; margin: 0;">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="booking-form" action="booking.php<?= $selectedProviderId > 0 ? '?provider_id='.$selectedProviderId : '' ?>" method="POST">
      <div>
        <label for="provider_id">Select Service Provider</label>
        <select id="provider_id" name="provider_id" required>
          <option value="">Choose an approved provider</option>
          <?php foreach ($approvedProviders as $ap): ?>
            <option value="<?= $ap['id'] ?>" <?= ($selectedProviderId === (int)$ap['id'] || (isset($_POST['provider_id']) && (int)$_POST['provider_id'] === (int)$ap['id'])) ? 'selected' : '' ?>>
              <?= htmlspecialchars($ap['business_name']) ?> &mdash; <?= htmlspecialchars($ap['service_name']) ?> (Fee: &#8377;<?= number_format($ap['fee'], 0) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-row">
        <div>
          <label for="date">Preferred Date</label>
          <input id="date" name="date" type="date" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d', strtotime('+1 day'))) ?>" required />
        </div>
        <div>
          <label for="time">Preferred Time</label>
          <input id="time" name="time" type="time" value="<?= htmlspecialchars($_POST['time'] ?? '11:00') ?>" required />
        </div>
      </div>

      <div>
        <label for="address">Service Address / Apartment</label>
        <input id="address" name="address" type="text" placeholder="Your street address, floor, apartment number" value="<?= htmlspecialchars($_POST['address'] ?? ($user['address'] ?? '')) ?>" required />
      </div>

      <div>
        <label for="details">Problem Description</label>
        <textarea id="details" name="details" rows="4" placeholder="Add specific details: e.g. noisy motor, leaking pipe under the sink, or subjects required..." required><?= htmlspecialchars($_POST['details'] ?? '') ?></textarea>
      </div>

      <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; font-size: 0.88rem; color: #475569;">
        <strong>How it works:</strong> Your booking request is submitted with <strong>PENDING</strong> status. The provider will review and accept it from their workspace. No upfront online payment required.
      </div>

      <button type="submit" class="btn btn-block" style="padding: 12px; font-size: 0.95rem; margin-top: 4px;">
        Submit Booking Request &rarr;
      </button>
    </form>
  </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
