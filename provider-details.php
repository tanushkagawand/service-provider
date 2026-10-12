<?php
$pageTitle = "Provider Profile";
$activeNav = "services";
require_once __DIR__ . '/includes/header.php';
$pdo = getDB();

$providerId = (int)($_GET['id'] ?? 0);

if ($providerId <= 0) {
    setFlash('error', 'Invalid provider ID specified.');
    header('Location: services.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, s.name as service_name, s.category as service_category, 
           u.name as owner_name, u.email as provider_email, u.phone as provider_phone, u.address as provider_address
    FROM providers p
    JOIN services s ON p.service_id = s.id
    JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$providerId]);
$provider = $stmt->fetch();

if (!$provider) {
    setFlash('error', 'Provider profile not found.');
    header('Location: services.php');
    exit;
}

// Fetch all reviews for this provider
$revStmt = $pdo->prepare("
    SELECT r.*, u.name as customer_name
    FROM reviews r
    JOIN users u ON r.customer_id = u.id
    WHERE r.provider_id = ?
    ORDER BY r.created_at DESC
");
$revStmt->execute([$providerId]);
$reviews = $revStmt->fetchAll();
$reviewCount = count($reviews);
?>

<div style="margin-bottom: 24px;">
  <a href="services.php" class="back-link">&larr; Back to all services</a>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px; align-items: start;">
  <!-- Main Details Column -->
  <div>
    <div class="card" style="margin-bottom: 32px;">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
        <div>
          <span class="badge badge-accepted" style="margin-bottom: 8px;">
            <?= htmlspecialchars(ucfirst($provider['verification_status'])) ?> Provider
          </span>
          <h1 style="font-size: 2.2rem; margin-bottom: 4px;"><?= htmlspecialchars($provider['business_name']) ?></h1>
          <p style="font-size: 1.1rem; color: #555; margin-bottom: 0;">
            Managed by <strong><?= htmlspecialchars($provider['owner_name']) ?></strong> &bull; <?= htmlspecialchars($provider['service_name']) ?>
          </p>
        </div>

        <div style="text-align: right; background: var(--bg-secondary); border: 2px solid var(--border-color); border-radius: 6px; padding: 12px 18px; box-shadow: var(--shadow-sm);">
          <div style="font-size: 1.8rem; font-weight: 900; line-height: 1;">
            &#9733; <?= number_format($provider['rating'], 1) ?>
          </div>
          <div style="font-size: 0.8rem; font-weight: 700; color: #555; margin-top: 4px;">
            <?= $reviewCount ?> verified review<?= $reviewCount === 1 ? '' : 's' ?>
          </div>
        </div>
      </div>

      <hr style="border: none; border-top: 2px solid #E0DFD8; margin: 20px 0;" />

      <h3>About This Provider</h3>
      <p style="font-size: 1.05rem; line-height: 1.7; color: #333; margin-bottom: 24px;">
        <?= nl2br(htmlspecialchars($provider['description'] ?? 'No additional description provided.')) ?>
      </p>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; background: var(--bg-secondary); border: 2px solid var(--border-color); border-radius: 6px; padding: 20px;">
        <div>
          <span style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">Professional Experience</span>
          <strong style="font-size: 1.1rem;"><?= htmlspecialchars($provider['experience'] ?? 'Experienced') ?></strong>
        </div>
        <div>
          <span style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">Standard Inspection Fee</span>
          <strong style="font-size: 1.1rem;">&#8377;<?= number_format($provider['fee'], 2) ?></strong>
        </div>
        <div>
          <span style="display: block; font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">Working Availability</span>
          <strong style="font-size: 1.05rem;"><?= htmlspecialchars($provider['availability']) ?></strong>
        </div>
      </div>
    </div>

    <!-- Reviews Section -->
    <div class="card">
      <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
        <h3>Customer Reviews (<?= $reviewCount ?>)</h3>
        <span class="rating-stars">&#9733; <?= number_format($provider['rating'], 1) ?> Average</span>
      </div>

      <?php if ($reviewCount > 0): ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <?php foreach ($reviews as $rev): ?>
            <div style="border: 2px solid var(--border-color); border-radius: 6px; padding: 18px; background: #FAF9F5; box-shadow: var(--shadow-sm);">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <strong><?= htmlspecialchars($rev['customer_name']) ?></strong>
                <div>
                  <span style="color: #20242A; font-weight: 800;">
                    <?= str_repeat('&#9733;', (int)$rev['rating']) . str_repeat('&#9734;', 5 - (int)$rev['rating']) ?>
                  </span>
                  <span style="font-size: 0.8rem; color: #666; margin-left: 8px;"><?= date('M d, Y', strtotime($rev['created_at'])) ?></span>
                </div>
              </div>
              <p style="margin-bottom: 0; color: #333; font-size: 0.95rem;">
                <?= nl2br(htmlspecialchars($rev['review'])) ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div style="padding: 24px; text-align: center; background: var(--bg-secondary); border: 2px dashed var(--border-color); border-radius: 6px;">
          <p style="margin-bottom: 0; color: #555;">No customer reviews yet. Book this provider and share your feedback after the service!</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Sidebar Column: Booking & Contact CTA -->
  <div>
    <div class="card" style="border-width: 3px; position: sticky; top: 90px;">
      <span class="eyebrow">BOOK SERVICE</span>
      <h3 style="margin-bottom: 8px;">Schedule an Appointment</h3>
      <p style="font-size: 0.95rem; color: #555; margin-bottom: 18px;">
        Choose your convenient date and address. Provider confirms your request directly.
      </p>

      <div style="margin-bottom: 20px; padding: 14px; background: var(--bg-secondary); border: 2px solid var(--border-color); border-radius: 6px;">
        <div style="font-size: 0.85rem; font-weight: 700; color: #555;">Inspection / Visit Charge</div>
        <div style="font-size: 1.6rem; font-weight: 900; color: var(--text-dark);">
          &#8377;<?= number_format($provider['fee'], 2) ?>
        </div>
        <div style="font-size: 0.75rem; color: #666; margin-top: 2px;">Pay directly to provider after work completion</div>
      </div>

      <a href="booking.php?provider_id=<?= $provider['id'] ?>" class="btn btn-block" style="padding: 14px; font-size: 1.05rem; margin-bottom: 16px;">
        Book This Provider Now &rarr;
      </a>

      <hr style="border: none; border-top: 1.5px solid #E0DFD8; margin: 16px 0;" />

      <h4 style="font-size: 1rem; margin-bottom: 10px;">Contact & Location</h4>
      <div style="font-size: 0.9rem; color: #333; display: flex; flex-direction: column; gap: 8px;">
        <div><strong>Service Area:</strong> <?= htmlspecialchars($provider['provider_address'] ?? 'Not specified') ?></div>
        <div><strong>Phone:</strong> <?= htmlspecialchars($provider['provider_phone'] ?? 'Available on booking') ?></div>
        <div><strong>Email:</strong> <?= htmlspecialchars($provider['provider_email'] ?? 'Available on booking') ?></div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
