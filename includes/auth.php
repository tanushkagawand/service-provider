<?php
// ============================================================================
// LOCAL SERVICE FINDER - AUTHENTICATION & SESSION MANAGEMENT
// ============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Checks if a user is currently logged in.
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Returns the currently authenticated user record from session/database.
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'      => $_SESSION['user_id'],
        'name'    => $_SESSION['user_name'] ?? 'User',
        'email'   => $_SESSION['user_email'] ?? '',
        'role'    => $_SESSION['user_role'] ?? 'customer',
        'phone'   => $_SESSION['user_phone'] ?? '',
        'address' => $_SESSION['user_address'] ?? ''
    ];
}

/**
 * Checks if the logged in user has a specific role ('customer', 'provider', 'admin').
 */
function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    return ($_SESSION['user_role'] ?? '') === $role;
}

/**
 * Restricts access to authenticated users with optional role check.
 */
function requireLogin($requiredRole = null) {
    if (!isLoggedIn()) {
        setFlash('error', 'Please log in to access this page.');
        header('Location: ' . getBasePath() . 'login.php');
        exit;
    }

    if ($requiredRole !== null && !hasRole($requiredRole)) {
        setFlash('error', 'Unauthorized access.');
        // Redirect to appropriate dashboard based on actual role
        $role = $_SESSION['user_role'] ?? 'customer';
        if ($role === 'admin') {
            header('Location: ' . getBasePath() . 'admin/dashboard.php');
        } elseif ($role === 'provider') {
            header('Location: ' . getBasePath() . 'provider/dashboard.php');
        } else {
            header('Location: ' . getBasePath() . 'dashboard.php');
        }
        exit;
    }
}

/**
 * Ensures user is a guest (not logged in), otherwise redirects to their dashboard.
 */
function requireGuest() {
    if (isLoggedIn()) {
        $role = $_SESSION['user_role'] ?? 'customer';
        if ($role === 'admin') {
            header('Location: ' . getBasePath() . 'admin/dashboard.php');
        } elseif ($role === 'provider') {
            header('Location: ' . getBasePath() . 'provider/dashboard.php');
        } else {
            header('Location: ' . getBasePath() . 'dashboard.php');
        }
        exit;
    }
}

/**
 * Computes base path relative to web root.
 */
function getBasePath() {
    // Determine relative path based on directory depth
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = str_replace('\\', '/', $scriptDir);
    if (substr($scriptDir, -1) !== '/') {
        $scriptDir .= '/';
    }
    
    // Check if we are inside a subfolder (admin, customer, provider)
    if (preg_match('#/(admin|customer|provider)/#', $_SERVER['SCRIPT_NAME'] ?? '')) {
        return '../';
    }
    return '';
}

/**
 * Flash notification messaging.
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'error', 'info'
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Basic input sanitizer.
 */
function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Helper to fetch provider details for logged in provider user.
 */
function getProviderByUserId($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT p.*, s.name as service_name, s.category as service_category 
                           FROM providers p 
                           LEFT JOIN services s ON p.service_id = s.id 
                           WHERE p.user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}
