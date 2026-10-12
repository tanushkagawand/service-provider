<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

$pdo = getDB();

// Handle quick provider approval/rejection from overview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $providerId = (int)($_POST['provider_id'] ?? 0);
    $action = $_POST['action'];

    if ($providerId > 0 && ($action === 'approve_provider' || $action === 'reject_provider')) {
        $newStatus = ($action === 'approve_provider') ? 'approved' : 'rejected';
        $upd = $pdo->prepare("UPDATE providers SET verification_status = ? WHERE id = ?");
        $upd->execute([$newStatus, $providerId]);

        setFlash('success', "Provider #{$providerId} verification updated to: " . strtoupper($newStatus));
    }
    header('Location: dashboard.php');
    exit;
}

// Fetch System Overview Statistics
$totUsers     = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totProviders = (int)$pdo->query("SELECT COUNT(*) FROM providers")->fetchColumn();
$pendingProv  = (int)$pdo->query("SELECT COUNT(*) FROM providers WHERE verification_status = 'pending'")->fetchColumn();
$totBookings  = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$compBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();
$totMessages  = (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();

// Fetch Pending Providers requiring verification
$pendingListStmt = $pdo->query("
    SELECT p.*, s.name as service_name, u.name as owner_name, u.email as owner_email, u.phone as owner_phone
    FROM providers p
    JOIN services s ON p.service_id = s.id
    JOIN users u ON p.user_id = u.id
    WHERE p.verification_status = 'pending'
    ORDER BY p.id ASC
");
$pendingProviders = $pendingListStmt->fetchAll();

// Fetch Recent Bookings
$recentBookings = $pdo->query("
    SELECT b.*, u.name as customer_name, p.business_name, s.name as service_name
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    JOIN providers p ON b.provider_id = p.id
    LEFT JOIN services s ON b.service_id = s.id
    ORDER BY b.id DESC
    LIMIT 5
")->fetchAll();

$pageTitle = "Admin Overview";
$activeNav = "dashboard";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 24px;">
  <span class="eyebrow">Administration Console</span>
  <h1>Website Management Overview</h1>
  <p class="subtitle">Platform monitoring, verification queue, and activity metrics.</p>
</div>

<!-- Material Stats Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <span>Total Users</span>
    <strong><?= $totUsers ?></strong>
  </div>
  <div class="stat-card">
    <span>Registered Providers</span>
    <strong><?= $totProviders ?></strong>
  </div>
  <div class="stat-card" style="<?= $pendingProv > 0 ? 'background: #FEF3C7; border-color: #FDE68A;' : '' ?>">
    <span>Pending Verifications</span>
    <strong style="<?= $pendingProv > 0 ? 'color: #B45309;' : '' ?>"><?= $pendingProv ?></strong>
  </div>
  <div class="stat-card">
    <span>Total Bookings</span>
    <strong><?= $totBookings ?></strong>
  </div>
  <div class="stat-card">
    <span>Completed Jobs</span>
    <strong style="color: #059669;"><?= $compBookings ?></strong>
  </div>
  <div class="stat-card">
    <span>Inquiry Messages</span>
    <strong><?= $totMessages ?></strong>
  </div>
</div>

<!-- Pending Provider Approvals Queue -->
<section style="margin-bottom: 40px;">
  <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 16px;">
    <div>
      <h2 style="font-size: 1.25rem; margin-bottom: 2px;">Pending Provider Verifications</h2>
      <p style="font-size: 0.88rem; color: #64748B; margin-bottom: 0;">
        New service providers awaiting administrator approval before appearing in search results.
      </p>
    </div>
    <a href="providers.php" class="btn btn-outline btn-sm">Manage All Providers &rarr;</a>
  </div>

  <?php if (count($pendingProviders) > 0): ?>
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Business & Specialist</th>
            <th>Owner Contact</th>
            <th>Experience & Fee</th>
            <th>Verification Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendingProviders as $pp): ?>
            <tr>
              <td><span style="font-weight: 600; color: #64748B;">#<?= $pp['id'] ?></span></td>
              <td>
                <div style="font-weight: 600; font-size: 0.95rem; color: #0F172A;"><?= htmlspecialchars($pp['business_name']) ?></div>
                <div style="font-size: 0.82rem; color: #64748B;"><?= htmlspecialchars($pp['service_name']) ?></div>
                <div style="font-size: 0.8rem; color: #475569; margin-top: 2px;">
                  <em><?= htmlspecialchars(mb_strimwidth($pp['description'] ?? '', 0, 85, '...')) ?></em>
                </div>
              </td>
              <td>
                <div style="font-weight: 600; color: #0F172A;"><?= htmlspecialchars($pp['owner_name']) ?></div>
                <div style="font-size: 0.8rem; color: #64748B;">&#9742; <?= htmlspecialchars($pp['owner_phone'] ?? 'N/A') ?></div>
                <div style="font-size: 0.8rem; color: #64748B;">&#9993; <?= htmlspecialchars($pp['owner_email']) ?></div>
              </td>
              <td>
                <div style="font-size: 0.88rem; color: #0F172A;">&#8377;<?= number_format($pp['fee'], 2) ?></div>
                <div style="font-size: 0.8rem; color: #64748B;">Exp: <?= htmlspecialchars($pp['experience']) ?></div>
              </td>
              <td>
                <div style="display: flex; gap: 6px;">
                  <form action="dashboard.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="approve_provider" />
                    <input type="hidden" name="provider_id" value="<?= $pp['id'] ?>" />
                    <button type="submit" class="btn btn-sm btn-secondary">&#10003; Approve</button>
                  </form>

                  <form action="dashboard.php" method="POST" style="margin: 0;" onsubmit="return confirm('Reject this provider application?');">
                    <input type="hidden" name="action" value="reject_provider" />
                    <input type="hidden" name="provider_id" value="<?= $pp['id'] ?>" />
                    <button type="submit" class="btn btn-sm btn-outline" style="color: #EF4444; border-color: #FECACA;">&#10005; Reject</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="card card-secondary" style="padding: 24px; text-align: center;">
      <p style="margin-bottom: 0; font-weight: 500; color: #64748B; font-size: 0.92rem;">
        No pending provider approvals at this time. All registered provider accounts are validated.
      </p>
    </div>
  <?php endif; ?>
</section>

<!-- Recent Bookings Table -->
<section style="margin-bottom: 40px;">
  <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 16px;">
    <h2 style="font-size: 1.25rem;">Recent Platform Bookings</h2>
    <a href="bookings.php" class="btn btn-outline btn-sm">View All Bookings &rarr;</a>
  </div>

  <div class="table-responsive">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Customer</th>
          <th>Provider</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentBookings as $rb): ?>
          <tr>
            <td><span style="font-weight: 600; color: #64748B;">#<?= $rb['id'] ?></span></td>
            <td style="font-weight: 500; color: #0F172A;"><?= htmlspecialchars($rb['customer_name']) ?></td>
            <td>
              <strong style="color: #0F172A;"><?= htmlspecialchars($rb['business_name']) ?></strong>
              <div style="font-size: 0.8rem; color: #64748B;"><?= htmlspecialchars($rb['service_name'] ?? 'General') ?></div>
            </td>
            <td>
              <div style="font-size: 0.88rem; color: #0F172A;"><?= date('M d, Y', strtotime($rb['booking_date'])) ?></div>
              <div style="font-size: 0.8rem; color: #64748B;"><?= date('h:i A', strtotime($rb['booking_time'])) ?></div>
            </td>
            <td>
              <?php
                $st = $rb['status'];
                $bCls = 'badge-pending';
                if ($st === 'accepted') $bCls = 'badge-accepted';
                elseif ($st === 'completed') $bCls = 'badge-completed';
                elseif ($st === 'rejected' || $st === 'cancelled') $bCls = 'badge-rejected';
              ?>
              <span class="badge <?= $bCls ?>"><?= strtoupper($st) ?></span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
