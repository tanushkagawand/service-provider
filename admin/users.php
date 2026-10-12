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
  <span class="eyebrow">Administration</span>
  <h1>Registered Users & Accounts</h1>
  <p class="subtitle">Platform customers, service providers, and administrators.</p>
</div>

<!-- Filter Chips -->
<div style="display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap;">
  <a href="users.php" class="btn-sm <?= $roleFilter === 'all' ? 'btn' : 'btn-outline' ?>" style="border-radius: var(--radius-full); text-decoration:none;">
    All Users (<?= count($users) ?>)
  </a>
  <a href="users.php?role=customer" class="btn-sm <?= $roleFilter === 'customer' ? 'btn' : 'btn-outline' ?>" style="border-radius: var(--radius-full); text-decoration:none;">
    Customers
  </a>
  <a href="users.php?role=provider" class="btn-sm <?= $roleFilter === 'provider' ? 'btn' : 'btn-outline' ?>" style="border-radius: var(--radius-full); text-decoration:none;">
    Providers
  </a>
  <a href="users.php?role=admin" class="btn-sm <?= $roleFilter === 'admin' ? 'btn' : 'btn-outline' ?>" style="border-radius: var(--radius-full); text-decoration:none;">
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
            <td><span style="font-weight: 600; color: #64748B;">#<?= $u['id'] ?></span></td>
            <td><strong style="color: #0F172A;"><?= htmlspecialchars($u['name']) ?></strong></td>
            <td>
              <div style="color: #0F172A;"><?= htmlspecialchars($u['email']) ?></div>
              <div style="font-size: 0.8rem; color: #64748B;"><?= htmlspecialchars($u['phone'] ?? 'N/A') ?></div>
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
            <td style="font-size: 0.88rem; max-width: 260px; color: #475569;">
              <?= htmlspecialchars($u['address'] ?? 'Not provided') ?>
            </td>
            <td style="font-size: 0.82rem; color: #64748B;">
              <?= date('M d, Y', strtotime($u['created_at'])) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="6" style="text-align: center; padding: 36px; color: #64748B;">
            No users found matching selected filter.
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
