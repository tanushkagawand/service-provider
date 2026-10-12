<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

$pdo = getDB();
$roleFilter = sanitize($_GET['role'] ?? 'all');

$sql = "SELECT * FROM users";
$params = [];

if (in_array($roleFilter, ['customer', 'provider', 'admin'])) {
    $sql .= " WHERE role = ?";
    $params[] = $roleFilter;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = "Manage Users";
$activeNav = "users";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 24px;">
  <span class="eyebrow">ADMINISTRATION</span>
  <h1>Registered Users & Accounts</h1>
  <p class="subtitle">Platform customers, service providers, and administrators.</p>
</div>

<!-- Filter Tabs -->
<div style="display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap;">
  <a href="users.php" class="btn-sm <?= $roleFilter === 'all' ? 'btn' : 'btn-outline' ?>" style="text-decoration:none;">
    All Users (<?= count($users) ?>)
  </a>
  <a href="users.php?role=customer" class="btn-sm <?= $roleFilter === 'customer' ? 'btn' : 'btn-outline' ?>" style="text-decoration:none;">
    Customers
  </a>
  <a href="users.php?role=provider" class="btn-sm <?= $roleFilter === 'provider' ? 'btn' : 'btn-outline' ?>" style="text-decoration:none;">
    Providers
  </a>
  <a href="users.php?role=admin" class="btn-sm <?= $roleFilter === 'admin' ? 'btn' : 'btn-outline' ?>" style="text-decoration:none;">
    Administrators
  </a>
</div>

<div class="table-responsive">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Email & Phone</th>
        <th>Role</th>
        <th>Address / Location</th>
        <th>Joined Date</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($users) > 0): ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><strong>#<?= $u['id'] ?></strong></td>
            <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
            <td>
              <div><?= htmlspecialchars($u['email']) ?></div>
              <div style="font-size: 0.85rem; color: #555;"><?= htmlspecialchars($u['phone'] ?? 'N/A') ?></div>
            </td>
            <td>
              <?php
                $r = $u['role'];
                $rCls = 'badge-completed';
                if ($r === 'admin') $rCls = 'badge-accepted';
                elseif ($r === 'provider') $rCls = 'badge-pending';
              ?>
              <span class="badge <?= $rCls ?>"><?= strtoupper($r) ?></span>
            </td>
            <td style="font-size: 0.9rem; max-width: 260px;">
              <?= htmlspecialchars($u['address'] ?? 'Not provided') ?>
            </td>
            <td style="font-size: 0.85rem; color: #555;">
              <?= date('M d, Y', strtotime($u['created_at'])) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="6" style="text-align: center; padding: 36px; color: #666;">
            No users found matching selected filter.
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
