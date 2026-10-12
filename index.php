<?php
$pageTitle = "Home";
$activeNav = "home";
require_once __DIR__ . '/includes/header.php';
$pdo = getDB();

// Fetch service categories with provider count
$servicesStmt = $pdo->query("
    SELECT s.*, COUNT(p.id) as provider_count 
    FROM services s 
    LEFT JOIN providers p ON s.id = p.service_id AND p.verification_status = 'approved'
    GROUP BY s.id 
    ORDER BY s.id ASC
");
$services = $servicesStmt->fetchAll();

// Fetch top approved providers
$providersStmt = $pdo->query("
    SELECT p.*, s.name as service_name, u.address as provider_city 
    FROM providers p 
    JOIN services s ON p.service_id = s.id 
    JOIN users u ON p.user_id = u.id 
    WHERE p.verification_status = 'approved'
    ORDER BY p.rating DESC, p.id ASC 
    LIMIT 6
");
$featuredProviders = $providersStmt->fetchAll();
?>

<!-- Hero Section -->
<section style="background: var(--bg-secondary); border: var(--border-width) solid var(--border-color); border-radius: var(--border-radius); box-shadow: var(--shadow); padding: 48px 36px; margin-bottom: 36px;">
  <div style="max-width: 800px;">
    <span class="eyebrow">LOCAL SERVICE FINDER</span>
    <h1 style="font-size: 2.8rem; margin-bottom: 12px; line-height: 1.15;">Good help is closer than you think.</h1>
    <p style="font-size: 1.15rem; color: #333; margin-bottom: 28px;">
      Find trusted local professionals for the little fixes and big projects that make your home feel right.
    </p>

    <!-- Search Form -->
    <form class="search-bar-neo" action="services.php" method="GET">
      <div>
        <label for="search-service">Service Category</label>
        <select id="search-service" name="service">
          <option value="">All Services</option>
          <?php foreach ($services as $srv): ?>
            <option value="<?= htmlspecialchars($srv['name']) ?>"><?= htmlspecialchars($srv['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="search-location">Location / Area</label>
        <input type="text" id="search-location" name="location" placeholder="e.g. Bandra, Andheri, Mumbai" />
      </div>

      <div>
        <button type="submit" class="btn" style="width: 100%; height: 48px;">
          Find Help
        </button>
      </div>
    </form>
  </div>
</section>

<!-- Browse Categories -->
<section style="margin-bottom: 48px;">
  <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
    <div>
      <span class="eyebrow">EXPLORE</span>
      <h2>Popular Service Categories</h2>
    </div>
    <a href="services.php" class="btn btn-outline btn-sm">View All Categories &rarr;</a>
  </div>

  <div class="service-grid">
    <?php foreach ($services as $srv): ?>
      <a href="services.php?service=<?= urlencode($srv['name']) ?>" class="service-card card">
        <div>
          <span class="badge badge-completed" style="margin-bottom: 10px;"><?= htmlspecialchars($srv['category'] ?? 'Service') ?></span>
          <h3 style="margin-top: 4px;"><?= htmlspecialchars($srv['name']) ?></h3>
          <p style="font-size: 0.95rem; color: #555;"><?= htmlspecialchars($srv['description']) ?></p>
        </div>
        <div style="margin-top: 14px; padding-top: 10px; border-top: 1.5px dashed var(--border-color); font-size: 0.85rem; font-weight: 800;">
          <?= (int)$srv['provider_count'] ?> Active Providers &rarr;
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- Featured Approved Service Providers -->
<section style="margin-bottom: 48px;">
  <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
    <div>
      <span class="eyebrow">VERIFIED PROFESSIONALS</span>
      <h2>Top-Rated Local Providers</h2>
    </div>
    <a href="services.php" class="btn btn-outline btn-sm">Browse All Providers &rarr;</a>
  </div>

  <div class="provider-grid">
    <?php foreach ($featuredProviders as $p): ?>
      <article class="provider-card">
        <div>
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
            <span class="badge badge-accepted">Verified &bull; Approved</span>
            <span class="rating-stars">&#9733; <?= number_format($p['rating'], 1) ?></span>
          </div>

          <h3 style="margin-bottom: 4px;"><?= htmlspecialchars($p['business_name']) ?></h3>
          <div style="font-weight: 700; color: #555; font-size: 0.9rem; margin-bottom: 12px;">
            <?= htmlspecialchars($p['service_name']) ?> &bull; <?= htmlspecialchars($p['experience'] ?? '2+ years') ?> exp
          </div>

          <p style="font-size: 0.9rem; color: #444; margin-bottom: 14px;">
            <?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 110, '...')) ?>
          </p>

          <div class="provider-meta">
            <div><strong>Fee:</strong> &#8377;<?= number_format($p['fee'], 2) ?> standard visit</div>
            <div><strong>Hours:</strong> <?= htmlspecialchars($p['availability']) ?></div>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 14px; padding-top: 14px; border-top: 1.5px solid #E0DFD8;">
          <a href="provider-details.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">Details</a>
          <a href="booking.php?provider_id=<?= $p['id'] ?>" class="btn btn-sm">Book</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- How It Works Section -->
<section class="card card-secondary" style="margin-bottom: 48px; padding: 36px 28px;">
  <div style="text-align: center; max-width: 650px; margin: 0 auto 32px auto;">
    <span class="eyebrow">SIMPLE 3-STEP PROCESS</span>
    <h2>How Local Service Finder Works</h2>
    <p>A dependable platform connecting local residents with verified neighbourhood handymen and professionals.</p>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px;">
    <div style="background: #FFFFFF; border: var(--border-width) solid var(--border-color); border-radius: var(--border-radius); box-shadow: var(--shadow-sm); padding: 24px;">
      <div style="font-size: 2rem; font-weight: 900; margin-bottom: 8px;">01</div>
      <h3 style="font-size: 1.2rem;">Search & Compare</h3>
      <p style="font-size: 0.92rem; color: #555;">Search for electricians, plumbers, or tutors in your locality. Check genuine experience, inspection fee, and client ratings.</p>
    </div>

    <div style="background: #FFFFFF; border: var(--border-width) solid var(--border-color); border-radius: var(--border-radius); box-shadow: var(--shadow-sm); padding: 24px;">
      <div style="font-size: 2rem; font-weight: 900; margin-bottom: 8px;">02</div>
      <h3 style="font-size: 1.2rem;">Book An Appointment</h3>
      <p style="font-size: 0.92rem; color: #555;">Choose your preferred date, time slot, and describe the issue. The provider accepts your request directly from their workspace.</p>
    </div>

    <div style="background: #FFFFFF; border: var(--border-width) solid var(--border-color); border-radius: var(--border-radius); box-shadow: var(--shadow-sm); padding: 24px;">
      <div style="font-size: 2rem; font-weight: 900; margin-bottom: 8px;">03</div>
      <h3 style="font-size: 1.2rem;">Service & Review</h3>
      <p style="font-size: 0.92rem; color: #555;">Once the job is completed by the professional, leave an authentic 1 to 5 star rating and review to guide other neighbours.</p>
    </div>
  </div>
</section>

<!-- Call to Action Banner -->
<section class="card" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; padding: 36px 32px;">
  <div>
    <span class="eyebrow">JOIN AS A PARTNER</span>
    <h2>Are you a service professional?</h2>
    <p style="margin-bottom: 0;">Register your business today to receive direct service inquiries and booking requests from local clients.</p>
  </div>
  <div>
    <a href="signup.php" class="btn" style="padding: 12px 24px;">Register as a Provider &rarr;</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
