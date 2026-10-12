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

// Select relevant sample service work image if available
$serviceImg = 'assets/images/service_electrical.jpg';
if (stripos($provider['service_name'], 'plumb') !== false) {
    $serviceImg = 'assets/images/service_plumbing.jpg';
}
?>

<div style="margin-bottom: 20px;">
  <a href="services.php" class="back-link">&larr; Back to all services</a>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 32px; align-items: start;">
  <!-- Main Details Column -->
  <div>
    <div class="card" style="margin-bottom: 28px;">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; gap: 16px; align-items: flex-start;">
          <div class="provider-avatar" style="width: 58px; height: 58px; font-size: 1.3rem;">
            <?= getProviderInitials($provider['business_name']) ?>
          </div>
          <div>
            <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 4px;">
              <span class="badge badge-accepted"><?= htmlspecialchars(ucfirst($provider['verification_status'])) ?> Provider</span>
              <span style="font-size: 0.85rem; color: #77736A;">&bull; <?= htmlspecialchars($provider['service_name']) ?></span>
            </div>
            <h1 style="font-size: 1.85rem; margin-bottom: 4px; color: #242424;"><?= htmlspecialchars($provider['business_name']) ?></h1>
            <p style="font-size: 0.95rem; color: #77736A; margin-bottom: 0;">
              Owner: <strong><?= htmlspecialchars($provider['owner_name']) ?></strong> &bull; <?= htmlspecialchars($provider['provider_address'] ?? 'Local coverage') ?>
            </p>
          </div>
        </div>

        <div style="background: #FAF7ED; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 12px 18px; text-align: right;">
          <div style="font-size: 1.6rem; font-weight: 700; color: #D97706; line-height: 1;">
            &#9733; <?= number_format($provider['rating'], 1) ?>
          </div>
          <div style="font-size: 0.78rem; color: #77736A; margin-top: 4px;">
            <?= $reviewCount ?> verified review<?= $reviewCount === 1 ? '' : 's' ?>
          </div>
        </div>
      </div>

      <!-- Feature service work image -->
      <div style="margin-bottom: 24px; border-radius: var(--radius-sm); overflow: hidden; max-height: 260px; border: 1px solid var(--border-color);">
        <img src="<?= $serviceImg ?>" alt="Service Work Preview" style="width: 100%; height: 260px; object-fit: cover;" />
      </div>

      <h3 style="margin-bottom: 8px; color: #242424;">About the Business</h3>
      <p style="font-size: 0.98rem; line-height: 1.6; color: #524E46; margin-bottom: 24px;">
        <?= nl2br(htmlspecialchars($provider['description'] ?? 'No additional description provided.')) ?>
      </p>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; background: #FAF7ED; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 16px;">
        <div>
          <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #77736A;">Experience</span>
          <strong style="font-size: 1.05rem; color: #242424;"><?= htmlspecialchars($provider['experience'] ?? 'Experienced') ?></strong>
        </div>
        <div>
          <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #77736A;">Standard Visit Fee</span>
          <strong style="font-size: 1.05rem; color: #242424;">&#8377;<?= number_format($provider['fee'], 2) ?></strong>
        </div>
        <div>
          <span style="display: block; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #77736A;">Working Hours</span>
          <strong style="font-size: 0.95rem; color: #242424;"><?= htmlspecialchars($provider['availability']) ?></strong>
        </div>
      </div>
    </div>

    <!-- Reviews Section -->
    <div class="card">
      <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
        <h3 style="font-size: 1.25rem; color: #242424;">Client Reviews (<?= $reviewCount ?>)</h3>
        <span class="rating-stars">&#9733; <?= number_format($provider['rating'], 1) ?> Average Rating</span>
      </div>

      <?php if ($reviewCount > 0): ?>
        <div style="display: flex; flex-direction: column; gap: 14px;">
          <?php foreach ($reviews as $rev): ?>
            <div style="border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 16px; background: #FFFCF7;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <strong style="font-size: 0.95rem; color: #242424;"><?= htmlspecialchars($rev['customer_name']) ?></strong>
                <div>
                  <span style="color: #D97706; font-size: 0.95rem;">
                    <?= str_repeat('&#9733;', (int)$rev['rating']) . str_repeat('&#9734;', 5 - (int)$rev['rating']) ?>
                  </span>
                  <span style="font-size: 0.8rem; color: #77736A; margin-left: 8px;"><?= date('M d, Y', strtotime($rev['created_at'])) ?></span>
                </div>
              </div>
              <p style="margin-bottom: 0; color: #524E46; font-size: 0.92rem; line-height: 1.5;">
                <?= nl2br(htmlspecialchars($rev['review'])) ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div style="padding: 24px; text-align: center; background: #FAF7ED; border: 1px dashed var(--border-color); border-radius: var(--radius-sm);">
          <p style="margin-bottom: 0; color: #77736A; font-size: 0.92rem;">No customer reviews yet. Book this provider and share your feedback after the service!</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Sidebar Column: Booking CTA -->
  <div>
    <div class="card" style="position: sticky; top: 80px; box-shadow: var(--elevation-2);">
      <span class="eyebrow">Schedule Appointment</span>
      <h3 style="margin-bottom: 6px; font-size: 1.2rem; color: #242424;">Book This Provider</h3>
      <p style="font-size: 0.9rem; color: #77736A; margin-bottom: 16px;">
        Choose your preferred date and address. Provider confirms your request directly.
      </p>

      <div style="margin-bottom: 20px; padding: 14px; background: #FAF7ED; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
        <div style="font-size: 0.8rem; font-weight: 600; color: #77736A; text-transform: uppercase;">Standard Inspection Fee</div>
        <div style="font-size: 1.6rem; font-weight: 700; color: #242424; margin: 2px 0;">
          &#8377;<?= number_format($provider['fee'], 2) ?>
        </div>
        <div style="font-size: 0.78rem; color: #77736A;">Pay directly to technician after inspection</div>
      </div>

      <a href="booking.php?provider_id=<?= $provider['id'] ?>" class="btn btn-block" style="padding: 12px; font-size: 0.95rem; margin-bottom: 16px;">
        Book Service Now &rarr;
      </a>

      <hr style="border: none; border-top: 1px solid var(--border-color); margin: 16px 0;" />

      <h4 style="font-size: 0.95rem; margin-bottom: 10px; color: #242424;">Direct Information</h4>
      <div style="font-size: 0.88rem; color: #524E46; display: flex; flex-direction: column; gap: 8px;">
        <div><strong>Service Area:</strong> <?= htmlspecialchars($provider['provider_address'] ?? 'Local coverage') ?></div>
        <div><strong>Phone:</strong> <?= htmlspecialchars($provider['provider_phone'] ?? 'Available on booking') ?></div>
        <div><strong>Email:</strong> <?= htmlspecialchars($provider['provider_email'] ?? 'Available on booking') ?></div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
