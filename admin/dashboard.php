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

<div style="margin-bottom: 28px;">
  <span class="eyebrow">ADMINISTRATION CONSOLE</span>
  <h1>Website Management Overview</h1>
  <p class="subtitle">Platform monitoring, verification queue, and activity metrics.</p>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <span>Total Users</span>
    <strong><?= $totUsers ?></strong>
  </div>
  <div class="stat-card">
    <span>Registered Providers</span>
    <strong><?= $totProviders ?></strong>
  </div>
  <div class="stat-card" style="<?= $pendingProv > 0 ? 'border-color: #20242A; background: #FFF3CD;' : '' ?>">
    <span>Pending Verifications</span>
    <strong><?= $pendingProv ?></strong>
  </div>
  <div class="stat-card">
    <span>Total Bookings</span>
    <strong><?= $totBookings ?></strong>
  </div>
  <div class="stat-card">
    <span>Completed Jobs</span>
    <strong><?= $compBookings ?></strong>
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
      <h2>Pending Provider Verifications</h2>
      <p style="font-size: 0.95rem; color: #555; margin-bottom: 0;">
        New service providers awaiting admin validation before appearing in search results.
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
              <td><strong>#<?= $pp['id'] ?></strong></td>
              <td>
                <div style="font-weight: 800; font-size: 1rem;"><?= htmlspecialchars($pp['business_name']) ?></div>
                <div style="font-size: 0.85rem; color: #555;"><?= htmlspecialchars($pp['service_name']) ?></div>
                <div style="font-size: 0.85rem; color: #444; margin-top: 4px;">
                  <em><?= htmlspecialchars(mb_strimwidth($pp['description'] ?? '', 0, 90, '...')) ?></em>
                </div>
              </td>
              <td>
                <div style="font-weight: 700;"><?= htmlspecialchars($pp['owner_name']) ?></div>
                <div style="font-size: 0.85rem; color: #555;">&#9742; <?= htmlspecialchars($pp['owner_phone'] ?? 'N/A') ?></div>
                <div style="font-size: 0.85rem; color: #555;">&#9993; <?= htmlspecialchars($pp['owner_email']) ?></div>
              </td>
              <td>
                <div><strong>Exp:</strong> <?= htmlspecialchars($pp['experience']) ?></div>
                <div><strong>Fee:</strong> &#8377;<?= number_format($pp['fee'], 2) ?></div>
              </td>
              <td>
                <div style="display: flex; gap: 8px;">
                  <form action="dashboard.php" method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="approve_provider" />
                    <input type="hidden" name="provider_id" value="<?= $pp['id'] ?>" />
                    <button type="submit" class="btn btn-sm" style="background:#D1E7DD; color:#0F5132;">&#10003; Approve</button>
                  </form>

                  <form action="dashboard.php" method="POST" style="margin: 0;" onsubmit="return confirm('Reject this provider application?');">
                    <input type="hidden" name="action" value="reject_provider" />
                    <input type="hidden" name="provider_id" value="<?= $pp['id'] ?>" />
                    <button type="submit" class="btn btn-sm btn-danger">&#10005; Reject</button>
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
      <p style="margin-bottom: 0; font-weight: 700;">No pending provider approvals at this time. All registered providers are up to date.</p>
    </div>
  <?php endif; ?>
</section>

<!-- Recent Bookings Table -->
<section style="margin-bottom: 40px;">
  <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 16px;">
    <h2>Recent Platform Bookings</h2>
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
            <td><strong>#<?= $rb['id'] ?></strong></td>
            <td><?= htmlspecialchars($rb['customer_name']) ?></td>
            <td>
              <strong><?= htmlspecialchars($rb['business_name']) ?></strong>
              <div style="font-size: 0.85rem; color: #555;"><?= htmlspecialchars($rb['service_name'] ?? 'General') ?></div>
            </td>
            <td><?= date('M d, Y', strtotime($rb['booking_date'])) ?> &bull; <?= date('h:i A', strtotime($rb['booking_time'])) ?></td>
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
