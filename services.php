<?php
$pageTitle = "Browse Services & Providers";
$activeNav = "services";
require_once __DIR__ . '/includes/header.php';
$pdo = getDB();

$searchService = sanitize($_GET['service'] ?? '');
$searchLocation = sanitize($_GET['location'] ?? ($_GET['q'] ?? ''));

// Fetch all service categories for dropdown and pills
$allServices = $pdo->query("SELECT * FROM services ORDER BY name ASC")->fetchAll();

// Build query for approved providers
$sql = "
    SELECT p.*, s.name as service_name, s.category as service_category, u.name as owner_name, u.address as provider_address
    FROM providers p
    JOIN services s ON p.service_id = s.id
    JOIN users u ON p.user_id = u.id
    WHERE p.verification_status = 'approved'
";
$params = [];

if (!empty($searchService)) {
    $sql .= " AND s.name = ?";
    $params[] = $searchService;
}

if (!empty($searchLocation)) {
    $sql .= " AND (u.address LIKE ? OR p.business_name LIKE ? OR p.description LIKE ?)";
    $locPattern = '%' . $searchLocation . '%';
    $params[] = $locPattern;
    $params[] = $locPattern;
    $params[] = $locPattern;
}

$sql .= " ORDER BY p.rating DESC, p.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$providers = $stmt->fetchAll();
?>

<div style="margin-bottom: 28px;">
  <span class="eyebrow">SERVICES DIRECTORY</span>
  <h1>Find Local Service Providers</h1>
  <p class="subtitle">Search and connect with background-verified independent handymen and technicians near you.</p>
</div>

<!-- Search & Filter Form -->
<form class="search-bar-neo" action="services.php" method="GET">
  <div>
    <label for="filter-service">Service Category</label>
    <select id="filter-service" name="service">
      <option value="">All Services</option>
      <?php foreach ($allServices as $s): ?>
        <option value="<?= htmlspecialchars($s['name']) ?>" <?= $searchService === $s['name'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($s['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div>
    <label for="filter-location">Area / Location / Keyword</label>
    <input type="text" id="filter-location" name="location" value="<?= htmlspecialchars($searchLocation) ?>" placeholder="e.g. Bandra, Andheri, Powai" />
  </div>

  <div style="display: flex; gap: 8px;">
    <button type="submit" class="btn" style="height: 48px; flex: 1;">Filter</button>
    <?php if (!empty($searchService) || !empty($searchLocation)): ?>
      <a href="services.php" class="btn btn-outline" style="height: 48px; padding: 0 14px;" title="Reset filters">Reset</a>
    <?php endif; ?>
  </div>
</form>

<!-- Quick Category Pills -->
<div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 32px;">
  <a href="services.php" class="btn-sm <?= empty($searchService) ? 'btn' : 'btn-outline' ?>" style="text-decoration: none;">
    All Categories
  </a>
  <?php foreach ($allServices as $s): ?>
    <a href="services.php?service=<?= urlencode($s['name']) ?><?= !empty($searchLocation) ? '&location='.urlencode($searchLocation) : '' ?>" 
       class="btn-sm <?= $searchService === $s['name'] ? 'btn' : 'btn-outline' ?>" style="text-decoration: none;">
      <?= htmlspecialchars($s['name']) ?>
    </a>
  <?php endforeach; ?>
</div>

<!-- Results Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
  <h2>
    Available Providers 
    <span style="font-size: 1rem; font-weight: 700; color: #555;">(<?= count($providers) ?> found)</span>
  </h2>
</div>

<!-- Provider Cards -->
<?php if (count($providers) > 0): ?>
  <div class="provider-grid">
    <?php foreach ($providers as $p): ?>
      <article class="provider-card">
        <div>
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
            <span class="badge badge-accepted">Verified &bull; Approved</span>
            <span class="rating-stars">&#9733; <?= number_format($p['rating'], 1) ?></span>
          </div>

          <h3 style="margin-bottom: 4px;"><?= htmlspecialchars($p['business_name']) ?></h3>
          <div style="font-weight: 700; color: #555; font-size: 0.9rem; margin-bottom: 10px;">
            <?= htmlspecialchars($p['service_name']) ?> &bull; <?= htmlspecialchars($p['experience'] ?? '2+ years') ?> exp
          </div>

          <p style="font-size: 0.9rem; color: #444; margin-bottom: 14px;">
            <?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 120, '...')) ?>
          </p>

          <div class="provider-meta">
            <div><strong>Location:</strong> <?= htmlspecialchars($p['provider_address'] ?? 'Local service area') ?></div>
            <div><strong>Visit Fee:</strong> &#8377;<?= number_format($p['fee'], 2) ?></div>
            <div><strong>Availability:</strong> <?= htmlspecialchars($p['availability']) ?></div>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px; padding-top: 14px; border-top: 1.5px solid #E0DFD8;">
          <a href="provider-details.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">View Profile</a>
          <a href="booking.php?provider_id=<?= $p['id'] ?>" class="btn btn-sm">Book Service</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="card" style="text-align: center; padding: 48px 24px; background: var(--bg-secondary);">
    <h3 style="margin-bottom: 8px;">No approved service providers found</h3>
    <p style="color: #555; max-width: 480px; margin: 0 auto 20px auto;">
      We couldn't find any verified professionals matching your current search criteria. Try selecting another category or clearing your location filter.
    </p>
    <a href="services.php" class="btn">Clear All Filters</a>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
