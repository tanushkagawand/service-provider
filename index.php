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

<!-- Warm Material Hero Section -->
<section style="background: #FFFFFF; border: 1px solid var(--border-color); border-radius: var(--radius-lg); box-shadow: var(--elevation-1); padding: 40px; margin-bottom: 40px;">
  <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 36px; align-items: center;">
    <div>
      <span class="eyebrow">Local Service Marketplace</span>
      <h1 style="font-size: 2.4rem; color: #242424; margin-bottom: 12px; font-weight: 800; line-height: 1.2;">
        Good help is closer than you think.
      </h1>
      <p style="font-size: 1.05rem; color: #77736A; margin-bottom: 28px; line-height: 1.6;">
        Find trusted local professionals for the little fixes and big projects that make your home feel right.
      </p>

      <!-- Search Bar with Yellow Action Button -->
      <form class="search-bar-material" action="services.php" method="GET" style="margin-bottom: 0;">
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
          <button type="submit" class="btn" style="width: 100%; height: 42px;">
            Find Help
          </button>
        </div>
      </form>
    </div>

    <!-- Secondary Hero Image with Warm Rounded Border -->
    <div style="display: flex; justify-content: center;">
      <img src="assets/images/hero_service_pro.jpg" alt="Local technician providing service" 
           style="width: 100%; max-height: 320px; object-fit: cover; border-radius: var(--radius); box-shadow: var(--elevation-2); border: 1px solid var(--border-color);" />
    </div>
  </div>
</section>

<!-- Browse Categories (White Cards with Warm Accents) -->
<section style="margin-bottom: 48px;">
  <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
    <div>
      <span class="eyebrow">Service Categories</span>
      <h2>What do you need help with?</h2>
    </div>
    <a href="services.php" class="btn btn-outline btn-sm">View All Categories &rarr;</a>
  </div>

  <div class="service-grid">
    <?php foreach ($services as $srv): ?>
      <a href="services.php?service=<?= urlencode($srv['name']) ?>" class="service-card">
        <div>
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
            <div style="width: 44px; height: 44px; border-radius: 8px; background: var(--primary-light); display: flex; align-items: center; justify-content: center; border: 1px solid #F5E296;">
              <?= getCategoryIcon($srv['name']) ?>
            </div>
            <span class="badge badge-pending"><?= (int)$srv['provider_count'] ?> Active</span>
          </div>
          <h3 style="margin-top: 2px; font-size: 1.15rem; color: #242424;"><?= htmlspecialchars($srv['name']) ?></h3>
          <p style="font-size: 0.88rem; color: #77736A; margin-bottom: 12px;"><?= htmlspecialchars($srv['description']) ?></p>
        </div>
        <div style="font-size: 0.82rem; font-weight: 600; color: #8A6D0B; display: flex; align-items: center; gap: 4px;">
          Explore providers &rarr;
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- Top-Rated Local Providers (White Cards, Yellow Actions) -->
<section style="margin-bottom: 48px;">
  <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px;">
    <div>
      <span class="eyebrow">Verified Professionals</span>
      <h2>Top-Rated Local Providers</h2>
    </div>
    <a href="services.php" class="btn btn-outline btn-sm">Browse All Providers &rarr;</a>
  </div>

  <div class="provider-grid">
    <?php foreach ($featuredProviders as $p): ?>
      <article class="provider-card">
        <div>
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
            <div class="provider-header" style="margin-bottom: 0;">
              <div class="provider-avatar">
                <?= getProviderInitials($p['business_name']) ?>
              </div>
              <div>
                <h3 style="margin-bottom: 2px; font-size: 1.05rem;"><?= htmlspecialchars($p['business_name']) ?></h3>
                <div style="font-size: 0.82rem; color: #77736A;">
                  <?= htmlspecialchars($p['service_name']) ?> &bull; <?= htmlspecialchars($p['experience'] ?? '2+ years') ?> exp
                </div>
              </div>
            </div>
            <span class="rating-stars">&#9733; <?= number_format($p['rating'], 1) ?></span>
          </div>

          <p style="font-size: 0.88rem; color: #524E46; margin-bottom: 14px; min-height: 40px;">
            <?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 105, '...')) ?>
          </p>

          <div class="provider-meta" style="background: #FAF7ED; padding: 10px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 14px;">
            <div><strong>Inspection Fee:</strong> &#8377;<?= number_format($p['fee'], 2) ?></div>
            <div><strong>Hours:</strong> <?= htmlspecialchars($p['availability']) ?></div>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding-top: 12px; border-top: 1px solid var(--border-color);">
          <a href="provider-details.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm">View Profile</a>
          <a href="booking.php?provider_id=<?= $p['id'] ?>" class="btn btn-sm">Book Service</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- 3-Step Process (Warm Quick-Commerce Clarity) -->
<section style="background: #FFFFFF; border: 1px solid var(--border-color); border-radius: var(--radius); padding: 36px 32px; margin-bottom: 48px; box-shadow: var(--elevation-1);">
  <div style="text-align: center; max-width: 600px; margin: 0 auto 32px auto;">
    <span class="eyebrow">How It Works</span>
    <h2>Three Simple Steps to Get Things Fixed</h2>
    <p style="font-size: 0.95rem; color: #77736A;">A dependable platform connecting local residents with verified neighbourhood professionals.</p>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px;">
    <div style="background: #FAF7ED; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 22px;">
      <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: #242424; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 12px; border: 1px solid #EAC833;">1</div>
      <h3 style="font-size: 1.1rem; margin-bottom: 6px; color: #242424;">Search & Compare</h3>
      <p style="font-size: 0.88rem; color: #77736A; margin-bottom: 0;">Search for verified plumbers, electricians, or tutors nearby. Review upfront inspection fees and client ratings.</p>
    </div>

    <div style="background: #FAF7ED; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 22px;">
      <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: #242424; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 12px; border: 1px solid #EAC833;">2</div>
      <h3 style="font-size: 1.1rem; margin-bottom: 6px; color: #242424;">Book an Appointment</h3>
      <p style="font-size: 0.88rem; color: #77736A; margin-bottom: 0;">Select your convenient date and preferred time slot. The provider confirms your request directly in their portal.</p>
    </div>

    <div style="background: #FAF7ED; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 22px;">
      <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: #242424; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-bottom: 12px; border: 1px solid #EAC833;">3</div>
      <h3 style="font-size: 1.1rem; margin-bottom: 6px; color: #242424;">Service & Review</h3>
      <p style="font-size: 0.88rem; color: #77736A; margin-bottom: 0;">Pay the technician directly upon job completion and leave a 1 to 5 star rating to help other residents.</p>
    </div>
  </div>
</section>

<!-- Call to Action Banner -->
<section style="background: #FFFFFF; border: 1px solid var(--border-color); border-radius: var(--radius); padding: 32px 36px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; box-shadow: var(--elevation-1);">
  <div>
    <span class="eyebrow">For Service Professionals</span>
    <h2 style="font-size: 1.5rem; margin-bottom: 4px; color: #242424;">Are you an independent technician?</h2>
    <p style="margin-bottom: 0; font-size: 0.95rem; color: #77736A;">Join Local Service Finder today to receive direct service bookings from customers in your area.</p>
  </div>
  <div>
    <a href="signup.php" class="btn" style="padding: 10px 22px;">Register as a Provider &rarr;</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
