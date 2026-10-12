<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

$pdo = getDB();

// Handle Approve / Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $providerId = (int)($_POST['provider_id'] ?? 0);
    $action = $_POST['action'];

    if ($providerId > 0) {
        if ($action === 'approve') {
            $upd = $pdo->prepare("UPDATE providers SET verification_status = 'approved' WHERE id = ?");
            $upd->execute([$providerId]);
            setFlash('success', "Provider #{$providerId} approved successfully. They now appear in search results.");
        } elseif ($action === 'reject') {
            $upd = $pdo->prepare("UPDATE providers SET verification_status = 'rejected' WHERE id = ?");
            $upd->execute([$providerId]);
            setFlash('success', "Provider #{$providerId} verification rejected.");
        }
    }
    header('Location: providers.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit;
}

$statusFilter = sanitize($_GET['status'] ?? 'all');
$sql = "
    SELECT p.*, s.name as service_name, u.name as owner_name, u.email as owner_email, u.phone as owner_phone, u.address as owner_address
    FROM providers p
    JOIN services s ON p.service_id = s.id
    JOIN users u ON p.user_id = u.id
";
$params = [];

if (in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
    $sql .= " WHERE p.verification_status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$providers = $stmt->fetchAll();

$pageTitle = "Manage Providers";
$activeNav = "providers";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 24px;">
  <span class="eyebrow">Administration</span>
  <h1>Service Providers Directory</h1>
  <p class="subtitle">Review verification requests and manage registered service professionals.</p>
</div>

<!-- Filter Chips (Material Style) -->
<div style="display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap;">
  <a href="providers.php" class="btn-sm <?= $statusFilter === 'all' ? 'btn' : 'btn-outline' ?>" style="border-radius: var(--radius-full); text-decoration:none;">
    All Providers
  </a>
  <a href="providers.php?status=pending" class="btn-sm <?= $statusFilter === 'pending' ? 'btn' : 'btn-outline' ?>" style="border-radius: var(--radius-full); text-decoration:none;">
    Pending Review
  </a>
  <a href="providers.php?status=approved" class="btn-sm <?= $statusFilter === 'approved' ? 'btn' : 'btn-outline' ?>" style="border-radius: var(--radius-full); text-decoration:none;">
    Approved
  </a>
  <a href="providers.php?status=rejected" class="btn-sm <?= $statusFilter === 'rejected' ? 'btn' : 'btn-outline' ?>" style="border-radius: var(--radius-full); text-decoration:none;">
    Rejected
  </a>
</div>

<div class="table-responsive">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Business & Service</th>
        <th>Owner Details</th>
        <th>Fee & Exp</th>
        <th>Rating</th>
        <th>Status</th>
        <th>Verification Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($providers) > 0): ?>
        <?php foreach ($providers as $p): ?>
          <tr>
            <td><span style="font-weight: 600; color: #64748B;">#<?= $p['id'] ?></span></td>
            <td>
              <div style="font-weight: 600; color: #0F172A;"><?= htmlspecialchars($p['business_name']) ?></div>
              <div style="font-size: 0.82rem; color: #64748B;"><?= htmlspecialchars($p['service_name']) ?></div>
              <div style="font-size: 0.8rem; color: #475569; margin-top: 2px; max-width: 220px;">
                <?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 70, '...')) ?>
              </div>
            </td>
            <td>
              <div style="font-weight: 600; color: #0F172A;"><?= htmlspecialchars($p['owner_name']) ?></div>
              <div style="font-size: 0.8rem; color: #64748B;">&#9742; <?= htmlspecialchars($p['owner_phone'] ?? 'N/A') ?></div>
              <div style="font-size: 0.8rem; color: #64748B;">&#9993; <?= htmlspecialchars($p['owner_email']) ?></div>
            </td>
            <td>
              <div style="font-size: 0.9rem; color: #0F172A;">&#8377;<?= number_format($p['fee'], 2) ?></div>
              <div style="font-size: 0.8rem; color: #64748B;"><?= htmlspecialchars($p['experience']) ?></div>
            </td>
            <td>
              <strong style="color: #D97706;">&#9733; <?= number_format($p['rating'], 1) ?></strong>
            </td>
            <td>
              <?php
                $st = $p['verification_status'];
                $cls = 'badge-pending';
                if ($st === 'approved') $cls = 'badge-approved';
                elseif ($st === 'rejected') $cls = 'badge-rejected';
              ?>
              <span class="badge <?= $cls ?>"><?= strtoupper($st) ?></span>
            </td>
            <td>
              <div style="display: flex; gap: 6px;">
                <?php if ($p['verification_status'] !== 'approved'): ?>
                  <form action="providers.php<?= !empty($_GET['status']) ? '?status='.urlencode($_GET['status']) : '' ?>" method="POST" style="margin: 0;">
                    <input type="hidden" name="action" value="approve" />
                    <input type="hidden" name="provider_id" value="<?= $p['id'] ?>" />
                    <button type="submit" class="btn btn-sm btn-secondary" title="Approve Provider">&#10003; Approve</button>
                  </form>
                <?php endif; ?>

                <?php if ($p['verification_status'] !== 'rejected'): ?>
                  <form action="providers.php<?= !empty($_GET['status']) ? '?status='.urlencode($_GET['status']) : '' ?>" method="POST" style="margin: 0;" onsubmit="return confirm('Reject this provider account?');">
                    <input type="hidden" name="action" value="reject" />
                    <input type="hidden" name="provider_id" value="<?= $p['id'] ?>" />
                    <button type="submit" class="btn btn-sm btn-outline" style="color: #EF4444; border-color: #FECACA;" title="Reject Provider">&#10005; Reject</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="7" style="text-align: center; padding: 36px; color: #64748B;">
            No providers found matching status: <strong><?= htmlspecialchars($statusFilter) ?></strong>
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
