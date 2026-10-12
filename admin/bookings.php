<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

$pdo = getDB();
$statusFilter = sanitize($_GET['status'] ?? 'all');

$sql = "
    SELECT b.*, u.name as customer_name, u.phone as customer_phone, 
           p.business_name, p.fee, s.name as service_name
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    JOIN providers p ON b.provider_id = p.id
    LEFT JOIN services s ON b.service_id = s.id
";
$params = [];

if (in_array($statusFilter, ['pending', 'accepted', 'completed', 'cancelled', 'rejected'])) {
    $sql .= " WHERE b.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY b.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = "Manage Bookings";
$activeNav = "bookings";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 24px;">
  <span class="eyebrow">Administration</span>
  <h1>All Platform Service Bookings</h1>
  <p class="subtitle">Complete audit of customer appointments across all service categories.</p>
</div>

<!-- Filter Chips -->
<div style="display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap;">
  <a href="bookings.php" class="btn-sm <?= $statusFilter === 'all' ? 'btn' : 'btn-outline' ?>" style="border-radius: var(--radius-full); text-decoration:none;">
    All Bookings (<?= count($bookings) ?>)
  </a>
  <a href="bookings.php?status=pending" class="btn-sm <?= $statusFilter === 'pending' ? 'btn' : 'btn-outline' ?>" style="border-radius: var(--radius-full); text-decoration:none;">
    Pending
  </a>
  <a href="bookings.php?status=accepted" class="btn-sm <?= $statusFilter === 'accepted' ? 'btn' : 'btn-outline' ?>" style="border-radius: var(--radius-full); text-decoration:none;">
    Accepted
  </a>
  <a href="bookings.php?status=completed" class="btn-sm <?= $statusFilter === 'completed' ? 'btn' : 'btn-outline' ?>" style="border-radius: var(--radius-full); text-decoration:none;">
    Completed
  </a>
  <a href="bookings.php?status=cancelled" class="btn-sm <?= $statusFilter === 'cancelled' ? 'btn' : 'btn-outline' ?>" style="border-radius: var(--radius-full); text-decoration:none;">
    Cancelled
  </a>
</div>

<div class="table-responsive">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Customer</th>
        <th>Provider & Service</th>
        <th>Schedule</th>
        <th>Address & Notes</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($bookings) > 0): ?>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td><span style="font-weight: 600; color: #64748B;">#<?= $b['id'] ?></span></td>
            <td>
              <div style="font-weight: 600; color: #0F172A;"><?= htmlspecialchars($b['customer_name']) ?></div>
              <div style="font-size: 0.8rem; color: #64748B;">&#9742; <?= htmlspecialchars($b['customer_phone'] ?? 'N/A') ?></div>
            </td>
            <td>
              <div style="font-weight: 600; color: #0F172A;"><?= htmlspecialchars($b['business_name']) ?></div>
              <div style="font-size: 0.82rem; color: #64748B;"><?= htmlspecialchars($b['service_name'] ?? 'General') ?> &bull; &#8377;<?= number_format($b['fee'], 2) ?></div>
            </td>
            <td>
              <div style="font-weight: 600; color: #0F172A;"><?= date('M d, Y', strtotime($b['booking_date'])) ?></div>
              <div style="font-size: 0.8rem; color: #64748B;"><?= date('h:i A', strtotime($b['booking_time'])) ?></div>
            </td>
            <td style="font-size: 0.88rem; max-width: 260px;">
              <div><strong>Loc:</strong> <?= htmlspecialchars($b['address']) ?></div>
              <div style="margin-top: 3px; color: #475569;"><em>"<?= htmlspecialchars($b['problem_description']) ?>"</em></div>
            </td>
            <td>
              <?php
                $st = $b['status'];
                $bCls = 'badge-pending';
                if ($st === 'accepted') $bCls = 'badge-accepted';
                elseif ($st === 'completed') $bCls = 'badge-completed';
                elseif ($st === 'rejected' || $st === 'cancelled') $bCls = 'badge-rejected';
              ?>
              <span class="badge <?= $bCls ?>"><?= strtoupper($st) ?></span>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="6" style="text-align: center; padding: 36px; color: #64748B;">
            No bookings found matching selected status filter.
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
