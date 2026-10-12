<?php
if (!isset($basePath)) {
    $basePath = (preg_match('#/(admin|customer|provider)/#', $_SERVER['SCRIPT_NAME'] ?? '')) ? '../' : '';
}
require_once __DIR__ . '/auth.php';
$user = currentUser();
$flash = getFlash();
$title = isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | Local Service Finder' : 'Local Service Finder - On-Demand Local Services';
$active = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $title ?></title>
  <link rel="stylesheet" href="<?= $basePath ?>assets/css/style.css" />
  <link rel="icon" type="image/png" href="<?= $basePath ?>images/local-service-finder-mark.png" />
</head>
<body>

<header class="navbar">
  <div class="nav-container">
    <a class="brand" href="<?= $basePath ?>index.php">
      <img class="brand-mark" src="<?= $basePath ?>images/local-service-finder-mark.png" alt="Logo" />
      <span>Local Service Finder</span>
    </a>

    <nav class="nav-links" aria-label="Main navigation">
      <?php if (!$user): ?>
        <a class="<?= $active === 'home' ? 'active' : '' ?>" href="<?= $basePath ?>index.php">Home</a>
        <a class="<?= $active === 'services' ? 'active' : '' ?>" href="<?= $basePath ?>services.php">Services</a>
        <a class="<?= $active === 'contact' ? 'active' : '' ?>" href="<?= $basePath ?>contact.php">Contact</a>
        <a class="btn-nav" href="<?= $basePath ?>login.php">Login</a>
        <a class="btn-outline btn-sm" href="<?= $basePath ?>signup.php">Sign Up</a>
        <a class="<?= $active === 'admin' ? 'active' : '' ?>" href="<?= $basePath ?>admin-login.php">Admin</a>

      <?php elseif ($user['role'] === 'customer'): ?>
        <a class="<?= $active === 'home' ? 'active' : '' ?>" href="<?= $basePath ?>index.php">Home</a>
        <a class="<?= $active === 'services' ? 'active' : '' ?>" href="<?= $basePath ?>services.php">Services</a>
        <a class="<?= $active === 'booking' ? 'active' : '' ?>" href="<?= $basePath ?>booking.php">Book Service</a>
        <a class="<?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= $basePath ?>dashboard.php">My Bookings</a>
        <a class="<?= $active === 'contact' ? 'active' : '' ?>" href="<?= $basePath ?>contact.php">Contact</a>
        <span class="user-badge"><?= htmlspecialchars($user['name']) ?> (Customer)</span>
        <a class="btn-sm btn-outline" href="<?= $basePath ?>logout.php">Logout</a>

      <?php elseif ($user['role'] === 'provider'): ?>
        <a class="<?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= $basePath ?>provider/dashboard.php">Dashboard</a>
        <a class="<?= $active === 'bookings' ? 'active' : '' ?>" href="<?= $basePath ?>provider/dashboard.php#bookings">Requests</a>
        <a class="<?= $active === 'profile' ? 'active' : '' ?>" href="<?= $basePath ?>provider/dashboard.php#profile">My Profile</a>
        <a class="<?= $active === 'services' ? 'active' : '' ?>" href="<?= $basePath ?>services.php">Directory</a>
        <span class="user-badge"><?= htmlspecialchars($user['name']) ?> (Provider)</span>
        <a class="btn-sm btn-outline" href="<?= $basePath ?>logout.php">Logout</a>

      <?php elseif ($user['role'] === 'admin'): ?>
        <a class="<?= $active === 'dashboard' ? 'active' : '' ?>" href="<?= $basePath ?>admin/dashboard.php">Overview</a>
        <a class="<?= $active === 'providers' ? 'active' : '' ?>" href="<?= $basePath ?>admin/providers.php">Providers</a>
        <a class="<?= $active === 'users' ? 'active' : '' ?>" href="<?= $basePath ?>admin/users.php">Users</a>
        <a class="<?= $active === 'bookings' ? 'active' : '' ?>" href="<?= $basePath ?>admin/bookings.php">Bookings</a>
        <a class="<?= $active === 'messages' ? 'active' : '' ?>" href="<?= $basePath ?>admin/messages.php">Inquiries</a>
        <span class="user-badge">Admin</span>
        <a class="btn-sm btn-outline" href="<?= $basePath ?>logout.php">Logout</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<div class="container">
  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info') ?>">
      <span><?= htmlspecialchars($flash['message']) ?></span>
      <button type="button" onclick="this.parentElement.remove()" style="background:transparent;border:none;box-shadow:none;color:inherit;font-weight:900;font-size:1.2rem;cursor:pointer;padding:0;">&times;</button>
    </div>
  <?php endif; ?>
