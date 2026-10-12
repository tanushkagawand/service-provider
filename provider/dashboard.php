<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/icons.php';
requireLogin('provider');

$user = currentUser();
$pdo = getDB();

// Fetch provider profile
$provider = getProviderByUserId($user['id']);

if (!$provider) {
    die("Provider record not found for user ID: " . htmlspecialchars($user['id']));
}

$providerId = $provider['id'];

// Handle Booking Status Actions (Accept, Reject, Mark Completed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if (in_array($action, ['accept_booking', 'reject_booking', 'complete_booking'])) {
        $bookingId = (int)($_POST['booking_id'] ?? 0);

        // Security check: provider must own this booking
        $bCheck = $pdo->prepare("SELECT id, status FROM bookings WHERE id = ? AND provider_id = ?");
        $bCheck->execute([$bookingId, $providerId]);
        $targetBooking = $bCheck->fetch();

        if ($targetBooking) {
            $newStatus = null;
            if ($action === 'accept_booking' && $targetBooking['status'] === 'pending') {
                $newStatus = 'accepted';
                $msg = "Booking #{$bookingId} accepted successfully.";
            } elseif ($action === 'reject_booking' && $targetBooking['status'] === 'pending') {
                $newStatus = 'rejected';
                $msg = "Booking #{$bookingId} rejected.";
            } elseif ($action === 'complete_booking' && $targetBooking['status'] === 'accepted') {
                $newStatus = 'completed';
                $msg = "Booking #{$bookingId} marked as COMPLETED! Customer may now submit a review.";
            }

            if ($newStatus) {
                $update = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
                $update->execute([$newStatus, $bookingId]);
                setFlash('success', $msg);
            } else {
                setFlash('error', "Invalid booking status transition.");
            }
        } else {
            setFlash('error', "Unauthorized or invalid booking specified.");
        }
        header('Location: dashboard.php');
        exit;
    }

    // Handle Profile Update Action
    if ($action === 'update_profile') {
        $businessName = sanitize($_POST['business_name'] ?? '');
        $experience   = sanitize($_POST['experience'] ?? '');
        $fee          = floatval($_POST['fee'] ?? 0);
        $availability = sanitize($_POST['availability'] ?? '');
        $description  = sanitize($_POST['description'] ?? '');

        if (empty($businessName)) {
            setFlash('error', 'Business name is required.');
        } else {
            $updStmt = $pdo->prepare("
                UPDATE providers 
                SET business_name = ?, experience = ?, fee = ?, availability = ?, description = ?
                WHERE id = ?
            ");
            $updStmt->execute([$businessName, $experience, $fee, $availability, $description, $providerId]);
            setFlash('success', 'Provider profile details updated successfully.');
        }
        header('Location: dashboard.php#profile');
        exit;
    }
}

// Fetch Provider Statistics
$statsStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_cnt,
        SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_cnt,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_cnt
    FROM bookings 
    WHERE provider_id = ?
");
$statsStmt->execute([$providerId]);
$stats = $statsStmt->fetch();

// Fetch Provider Booking Requests with Customer Contact Info
$bookingsStmt = $pdo->prepare("
    SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, s.name as service_name
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.provider_id = ?
    ORDER BY b.id DESC
");
$bookingsStmt->execute([$providerId]);
$bookings = $bookingsStmt->fetchAll();

// Fetch Provider Reviews
$reviewsStmt = $pdo->prepare("
    SELECT r.*, u.name as customer_name
    FROM reviews r
    JOIN users u ON r.customer_id = u.id
    WHERE r.provider_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$reviewsStmt->execute([$providerId]);
$reviews = $reviewsStmt->fetchAll();

$pageTitle = "Provider Dashboard";
$activeNav = "dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 24px;">
  <span class="eyebrow">Provider Workspace</span>
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <h1 style="font-size: 1.85rem; margin-bottom: 2px;"><?= htmlspecialchars($provider['business_name']) ?></h1>
      <p class="subtitle" style="margin-bottom: 0;">
        Specialist: <strong><?= htmlspecialchars($provider['service_name'] ?? 'General') ?></strong> &bull; Managed by <?= htmlspecialchars($user['name']) ?>
      </p>
    </div>

    <div>
      <?php if ($provider['verification_status'] === 'approved'): ?>
        <span class="badge badge-accepted" style="font-size: 0.85rem; padding: 6px 14px;">
          &#10003; Approved &bull; Publicly Listed
        </span>
      <?php elseif ($provider['verification_status'] === 'pending'): ?>
        <span class="badge badge-pending" style="font-size: 0.85rem; padding: 6px 14px;">
          &#9888; Verification Pending (Admin Review Required)
        </span>
      <?php else: ?>
        <span class="badge badge-rejected" style="font-size: 0.85rem; padding: 6px 14px;">
          Verification Rejected
        </span>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($provider['verification_status'] === 'pending'): ?>
  <div class="alert alert-info" style="margin-bottom: 24px;">
    <div>
      <strong>Account Verification Note:</strong> Your business profile is currently in <strong>PENDING</strong> review. 
      Only approved providers appear in public customer search results. You can manage incoming requests and profile rates while waiting for admin validation.
    </div>
  </div>
<?php endif; ?>

<!-- Stats Overview (Material Stats Grid) -->
<div class="stats-grid">
  <div class="stat-card">
    <span>Total Bookings</span>
    <strong><?= (int)($stats['total'] ?? 0) ?></strong>
  </div>
  <div class="stat-card">
    <span>Pending Requests</span>
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
  <div class="stat-card">
    <span>Average Rating</span>
    <strong style="color: #D97706;">&#9733; <?= number_format($provider['rating'], 1) ?></strong>
  </div>
</div>

<!-- Customer Booking Requests Table -->
<section id="bookings" style="margin-bottom: 44px;">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <h2 style="font-size: 1.25rem;">Customer Booking Requests</h2>
    <span style="font-size: 0.85rem; font-weight: 500; color: #64748B;"><?= count($bookings) ?> requests</span>
  </div>

  <?php if (count($bookings) > 0): ?>
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Customer & Contact</th>
            <th>Appointment</th>
            <th>Location & Problem Details</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td><span style="font-weight: 600; color: #64748B;">#<?= $b['id'] ?></span></td>
              <td>
                <div style="font-weight: 600; font-size: 0.92rem; color: #0F172A;"><?= htmlspecialchars($b['customer_name']) ?></div>
                <div style="font-size: 0.8rem; color: #64748B;">&#9742; <?= htmlspecialchars($b['customer_phone'] ?? 'N/A') ?></div>
                <div style="font-size: 0.8rem; color: #64748B;">&#9993; <?= htmlspecialchars($b['customer_email']) ?></div>
              </td>
              <td>
                <div style="font-weight: 600; color: #0F172A;"><?= date('M d, Y', strtotime($b['booking_date'])) ?></div>
                <div style="font-size: 0.82rem; color: #64748B;"><?= date('h:i A', strtotime($b['booking_time'])) ?></div>
              </td>
              <td style="max-width: 260px; font-size: 0.88rem;">
                <div><strong>Address:</strong> <?= htmlspecialchars($b['address']) ?></div>
                <div style="margin-top: 4px; color: #475569;"><strong>Notes:</strong> <?= htmlspecialchars($b['problem_description']) ?></div>
              </td>
              <td>
                <?php
                  $st = $b['status'];
                  $badgeCls = 'badge-pending';
                  if ($st === 'accepted') $badgeCls = 'badge-accepted';
                  elseif ($st === 'completed') $badgeCls = 'badge-completed';
                  elseif ($st === 'rejected' || $st === 'cancelled') $badgeCls = 'badge-rejected';
                ?>
                <span class="badge <?= $badgeCls ?>"><?= strtoupper($st) ?></span>
              </td>
              <td>
                <?php if ($b['status'] === 'pending'): ?>
                  <div style="display: flex; gap: 6px;">
                    <form action="dashboard.php" method="POST" style="margin: 0;">
                      <input type="hidden" name="action" value="accept_booking" />
                      <input type="hidden" name="booking_id" value="<?= $b['id'] ?>" />
                      <button type="submit" class="btn btn-sm btn-secondary">Accept</button>
                    </form>

                    <form action="dashboard.php" method="POST" style="margin: 0;" onsubmit="return confirm('Decline this booking request?');">
                      <input type="hidden" name="action" value="reject_booking" />
                      <input type="hidden" name="booking_id" value="<?= $b['id'] ?>" />
                      <button type="submit" class="btn btn-sm btn-outline" style="color: #EF4444; border-color: #FECACA;">Reject</button>
                    </form>
                  </div>

                <?php elseif ($b['status'] === 'accepted'): ?>
                  <form action="dashboard.php" method="POST" style="margin: 0;" onsubmit="return confirm('Confirm that this service job is finished?');">
                    <input type="hidden" name="action" value="complete_booking" />
                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>" />
                    <button type="submit" class="btn btn-sm" style="background: #059669;">Mark Completed &#10003;</button>
                  </form>

                <?php elseif ($b['status'] === 'completed'): ?>
                  <span class="badge badge-completed">Job Finished</span>
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
    <div class="card" style="text-align: center; padding: 40px 20px; background: #FFFFFF;">
      <h3 style="margin-bottom: 4px; font-size: 1.15rem;">No customer booking requests yet</h3>
      <p style="color: #64748B; max-width: 460px; margin: 4px auto 0 auto; font-size: 0.9rem;">
        As soon as local customers schedule an appointment for your service category, requests will appear here.
      </p>
    </div>
  <?php endif; ?>
</section>

<!-- Provider Profile Editor & Recent Feedback (Material Cards) -->
<div style="display: grid; grid-template-columns: 3fr 2fr; gap: 28px; align-items: start;">
  <!-- Edit Profile Card -->
  <section id="profile" class="card">
    <span class="eyebrow">Profile Settings</span>
    <h2 style="font-size: 1.3rem;">Manage Service Rates & Information</h2>
    <p class="subtitle" style="margin-bottom: 20px;">Keep your rates, experience, and working hours up-to-date.</p>

    <form action="dashboard.php" method="POST">
      <input type="hidden" name="action" value="update_profile" />

      <div>
        <label for="business_name">Business or Profile Name</label>
        <input type="text" id="business_name" name="business_name" value="<?= htmlspecialchars($provider['business_name']) ?>" required />
      </div>

      <div class="form-row">
        <div>
          <label for="experience">Experience</label>
          <input type="text" id="experience" name="experience" value="<?= htmlspecialchars($provider['experience'] ?? '') ?>" placeholder="e.g. 5 years" required />
        </div>
        <div>
          <label for="fee">Standard Fee (&#8377;)</label>
          <input type="number" id="fee" name="fee" value="<?= htmlspecialchars($provider['fee']) ?>" step="50" min="50" required />
        </div>
      </div>

      <div>
        <label for="availability">Availability Schedule</label>
        <input type="text" id="availability" name="availability" value="<?= htmlspecialchars($provider['availability']) ?>" placeholder="e.g. Mon - Sat (9:00 AM - 7:00 PM)" required />
      </div>

      <div>
        <label for="description">Service Description / Bio</label>
        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($provider['description'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn" style="padding: 10px 22px;">Save Profile Changes</button>
    </form>
  </section>

  <!-- Recent Reviews Received -->
  <section class="card">
    <span class="eyebrow">Client Feedback</span>
    <h2 style="font-size: 1.3rem;">Recent Reviews</h2>
    <p class="subtitle" style="margin-bottom: 18px;">Ratings submitted by clients after completed jobs.</p>

    <?php if (count($reviews) > 0): ?>
      <div style="display: flex; flex-direction: column; gap: 12px;">
        <?php foreach ($reviews as $r): ?>
          <div style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; background: #F8FAFC;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
              <strong style="color: #0F172A; font-size: 0.9rem;"><?= htmlspecialchars($r['customer_name']) ?></strong>
              <span style="color: #D97706; font-size: 0.85rem; font-weight: 700;"><?= str_repeat('&#9733;', (int)$r['rating']) ?></span>
            </div>
            <p style="margin-bottom: 4px; font-size: 0.88rem; color: #475569; line-height: 1.45;">
              <?= nl2br(htmlspecialchars($r['review'])) ?>
            </p>
            <div style="font-size: 0.75rem; color: #94A3B8;"><?= date('M d, Y', strtotime($r['created_at'])) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div style="padding: 24px; text-align: center; background: #F8FAFC; border: 1px dashed var(--border-color); border-radius: var(--radius-sm);">
        <p style="margin-bottom: 0; color: #64748B; font-size: 0.88rem;">No customer reviews recorded yet.</p>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
