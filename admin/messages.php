<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin('admin');

$pdo = getDB();
$messages = $pdo->query("SELECT * FROM contacts ORDER BY id DESC")->fetchAll();

$pageTitle = "Contact Inquiries";
$activeNav = "messages";
require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 24px;">
  <span class="eyebrow">ADMINISTRATION</span>
  <h1>Customer & Provider Inquiries</h1>
  <p class="subtitle">Messages and feedback submitted through the public contact form.</p>
</div>

<div class="table-responsive">
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Sender</th>
        <th>Subject</th>
        <th>Message Content</th>
        <th>Date Received</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($messages) > 0): ?>
        <?php foreach ($messages as $m): ?>
          <tr>
            <td><strong>#<?= $m['id'] ?></strong></td>
            <td>
              <div style="font-weight: 800;"><?= htmlspecialchars($m['name']) ?></div>
              <div style="font-size: 0.85rem; color: #555;">&#9993; <a href="mailto:<?= htmlspecialchars($m['email']) ?>"><?= htmlspecialchars($m['email']) ?></a></div>
            </td>
            <td>
              <span class="badge badge-completed"><?= htmlspecialchars($m['subject']) ?></span>
            </td>
            <td style="max-width: 400px; font-size: 0.92rem; line-height: 1.5;">
              <?= nl2br(htmlspecialchars($m['message'])) ?>
            </td>
            <td style="font-size: 0.85rem; color: #555; white-space: nowrap;">
              <?= date('M d, Y h:i A', strtotime($m['created_at'])) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="5" style="text-align: center; padding: 36px; color: #666;">
            No contact messages received yet.
          </td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
