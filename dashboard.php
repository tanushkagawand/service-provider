<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();

// Direct providers and admins to their dedicated dashboards
if ($user['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit;
} elseif ($user['role'] === 'provider') {
    header('Location: provider/dashboard.php');
    exit;
}

$pdo = getDB();

// Handle Cancel Action (only allowed for pending bookings)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT id, status FROM bookings WHERE id = ? AND customer_id = ?");
    $stmt->execute([$bookingId, $user['id']]);
    $booking = $stmt->fetch();

    if ($booking && $booking['status'] === 'pending') {
        $update = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $update->execute([$bookingId]);
        setFlash('success', 'Booking #' . $bookingId . ' has been cancelled successfully.');
    } else {
        setFlash('error', 'Only pending bookings can be cancelled.');
    }
    header('Location: dashboard.php');
    exit;
}

// Handle Review Submission (only for completed bookings)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $rating    = (int)($_POST['rating'] ?? 5);
    $review    = sanitize($_POST['review'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }

    if (empty($review)) {
        setFlash('error', 'Please write a brief review feedback.');
        header('Location: dashboard.php');
        exit;
    }

    // Verify booking belongs to this customer, is completed, and not yet reviewed
    $bStmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND customer_id = ?");
    $bStmt->execute([$bookingId, $user['id']]);
    $bk = $bStmt->fetch();

    if (!$bk || $bk['status'] !== 'completed') {
        setFlash('error', 'Reviews can only be submitted for completed services.');
        header('Location: dashboard.php');
        exit;
    }

    $revCheck = $pdo->prepare("SELECT id FROM reviews WHERE booking_id = ?");
    $revCheck->execute([$bookingId]);
    if ($revCheck->fetch()) {
        setFlash('error', 'You have already submitted a review for this booking.');
        header('Location: dashboard.php');
        exit;
    }

    // Insert review
    $inRev = $pdo->prepare("
        INSERT INTO reviews (booking_id, customer_id, provider_id, rating, review)
        VALUES (?, ?, ?, ?, ?)
    ");
    $inRev->execute([$bookingId, $user['id'], $bk['provider_id'], $rating, $review]);

    // Recalculate provider average rating
    $avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE provider_id = ?");
    $avgStmt->execute([$bk['provider_id']]);
    $avgData = $avgStmt->fetch();
    $newAvg = round((float)($avgData['avg_rating'] ?? 0), 2);

    $updProv = $pdo->prepare("UPDATE providers SET rating = ? WHERE id = ?");
    $updProv->execute([$newAvg, $bk['provider_id']]);

    setFlash('success', 'Thank you! Your ' . $rating . '-star review has been published.');
    header('Location: dashboard.php');
    exit;
}

// Fetch Customer Statistics
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_cnt,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_cnt,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_cnt
    FROM bookings 
    WHERE customer_id = ?
");
$statsStmt->execute([$user['id']]);
$stats = $statsStmt->fetch();

// Fetch Customer Bookings with Provider Details and Review Status
$bookingsStmt = $pdo->prepare("
    SELECT b.*, p.business_name, p.fee, s.name as service_name, r.id as review_id, r.rating as review_rating
    FROM bookings b
    JOIN providers p ON b.provider_id = p.id
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN reviews r ON b.id = r.booking_id
    WHERE b.customer_id = ?
    ORDER BY b.id DESC
");
$bookingsStmt->execute([$user['id']]);
$bookings = $bookingsStmt->fetchAll();

$pageTitle = "Customer Dashboard";
$activeNav = "dashboard";
require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
  <div>
    <span class="eyebrow">Customer Dashboard</span>
    <h1 style="font-size: 1.85rem; margin-bottom: 2px;">Welcome, <?= htmlspecialchars($user['name']) ?></h1>
    <p class="subtitle" style="margin-bottom: 0;">Track appointment requests and view provider responses.</p>
  </div>
  <div>
    <a href="booking.php" class="btn" style="padding: 10px 18px;">+ Book New Service</a>
  </div>
</div>

<!-- Material Stats Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <span>Total Bookings</span>
    <strong><?= (int)($stats['total'] ?? 0) ?></strong>
  </div>
  <div class="stat-card">
    <span>Pending Confirmation</span>
    <strong style="color: #D97706;"><?= (int)($stats['pending_cnt'] ?? 0) ?></strong>
  </div>
  <div class="stat-card">
    <span>Accepted / Scheduled</span>
    <strong style="color: #2563EB;"><?= (int)($stats['accepted_cnt'] ?? 0) ?></strong>
  </div>
  <div class="stat-card">
    <span>Completed Jobs</span>
    <strong style="color: #059669;"><?= (int)($stats['completed_cnt'] ?? 0) ?></strong>
  </div>
</div>

<!-- Bookings Table Section -->
<section style="margin-bottom: 40px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <h2 style="font-size: 1.25rem;">Your Service Appointments</h2>
    <span style="font-size: 0.85rem; font-weight: 500; color: #64748B;"><?= count($bookings) ?> total bookings</span>
  </div>

  <?php if (count($bookings) > 0): ?>
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Service & Provider</th>
            <th>Schedule</th>
            <th>Address</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td><span style="font-weight: 600; color: #64748B;">#<?= $b['id'] ?></span></td>
              <td>
                <div style="font-weight: 600; font-size: 0.95rem; color: #0F172A;"><?= htmlspecialchars($b['business_name']) ?></div>
                <div style="font-size: 0.82rem; color: #64748B;"><?= htmlspecialchars($b['service_name'] ?? 'General') ?> &bull; &#8377;<?= number_format($b['fee'], 2) ?></div>
                <div style="font-size: 0.82rem; color: #475569; margin-top: 4px; max-width: 260px;">
                  <em>"<?= htmlspecialchars($b['problem_description']) ?>"</em>
                </div>
              </td>
              <td>
                <div style="font-weight: 600; color: #0F172A;"><?= date('M d, Y', strtotime($b['booking_date'])) ?></div>
                <div style="font-size: 0.82rem; color: #64748B;"><?= date('h:i A', strtotime($b['booking_time'])) ?></div>
              </td>
              <td style="max-width: 220px; font-size: 0.88rem; color: #475569;">
                <?= htmlspecialchars($b['address']) ?>
              </td>
              <td>
                <?php
                  $status = $b['status'];
                  $badgeClass = 'badge-pending';
                  if ($status === 'accepted') $badgeClass = 'badge-accepted';
                  elseif ($status === 'completed') $badgeClass = 'badge-completed';
                  elseif ($status === 'rejected' || $status === 'cancelled') $badgeClass = 'badge-rejected';
                ?>
                <span class="badge <?= $badgeClass ?>"><?= strtoupper($status) ?></span>
              </td>
              <td>
                <?php if ($b['status'] === 'pending'): ?>
                  <form action="dashboard.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this pending booking?');" style="margin: 0;">
                    <input type="hidden" name="action" value="cancel_booking" />
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>" />
                    <button type="submit" class="btn btn-sm btn-outline" style="color: #EF4444; border-color: #FECACA;">Cancel</button>
                  </form>

                <?php elseif ($b['status'] === 'completed'): ?>
                  <?php if ($b['review_id']): ?>
                    <span class="badge badge-accepted">
                      &#9733; Reviewed (<?= $b['review_rating'] ?>/5)
                    </span>
                  <?php else: ?>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="openReviewModal(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['business_name'])) ?>')">
                      Leave Review &rarr;
                    </button>
                  <?php endif; ?>

                <?php else: ?>
                  <span style="font-size: 0.85rem; color: #94A3B8;">&mdash;</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="card" style="text-align: center; padding: 48px 24px; background: #FFFFFF;">
      <div style="width: 56px; height: 56px; border-radius: 50%; background: #F1F5F9; color: #64748B; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; font-size: 1.5rem;">
        &#128197;
      </div>
      <h3 style="margin-bottom: 6px;">You have no service bookings yet</h3>
      <p style="color: #64748B; max-width: 480px; margin: 4px auto 20px auto; font-size: 0.92rem;">
        Browse our directory of verified electricians, plumbers, mechanics, and other local pros to book your first service.
      </p>
      <a href="services.php" class="btn">Browse Services & Providers &rarr;</a>
    </div>
  <?php endif; ?>
</section>

<!-- Material Dialog Modal for Reviews -->
<div id="review-modal" class="modal-overlay" style="display: none;">
  <div class="modal-card">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
      <div>
        <span class="eyebrow" style="margin-bottom: 4px;">Service Feedback</span>
        <h3 id="modal-provider-title" style="margin-top: 2px; font-size: 1.25rem;">Rate Completed Service</h3>
      </div>
      <button type="button" onclick="closeReviewModal()" style="background:none;border:none;box-shadow:none;font-size:1.4rem;color:#64748B;cursor:pointer;padding:0;line-height:1;">&times;</button>
    </div>

    <form action="dashboard.php" method="POST">
      <input type="hidden" name="action" value="submit_review" />
      <input type="hidden" id="modal-booking-id" name="booking_id" value="" />

      <div>
        <label for="review-rating">Rating (1 to 5 Stars)</label>
        <select id="review-rating" name="rating" required>
          <option value="5">&#9733;&#9733;&#9733;&#9733;&#9733; 5 Stars (Excellent)</option>
          <option value="4">&#9733;&#9733;&#9733;&#9733;&#9734; 4 Stars (Good)</option>
          <option value="3">&#9733;&#9733;&#9733;&#9734;&#9734; 3 Stars (Average)</option>
          <option value="2">&#9733;&#9733;&#9734;&#9734;&#9734; 2 Stars (Below Expectations)</option>
          <option value="1">&#9733;&#9734;&#9734;&#9734;&#9734; 1 Star (Poor)</option>
        </select>
      </div>

      <div>
        <label for="review-text">Feedback & Experience</label>
        <textarea id="review-text" name="review" rows="4" placeholder="How was the service? Was the technician punctual, polite, and effective?" required></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px;">
        <button type="button" class="btn btn-outline" onclick="closeReviewModal()">Cancel</button>
        <button type="submit" class="btn">Submit Review</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openReviewModal(bookingId, businessName) {
    document.getElementById('modal-booking-id').value = bookingId;
    document.getElementById('modal-provider-title').textContent = 'Rate ' + businessName;
    document.getElementById('review-modal').style.display = 'flex';
  }
  function closeReviewModal() {
    document.getElementById('review-modal').style.display = 'none';
  }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
