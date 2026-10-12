<?php
// ============================================================================
// LOCAL SERVICE FINDER - DATABASE CONNECTION
// Compatible with standard XAMPP (MySQL/MariaDB) and Standalone PHP (SQLite fallback)
// ============================================================================

define('DB_HOST', '127.0.0.1'); // Using 127.0.0.1 avoids IPv6 resolution delays
define('DB_PORT', 3306);
define('DB_NAME', 'local_service_finder');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // Fast check: is MySQL service running on port 3306?
    $isMysqlActive = false;
    $connection = @fsockopen(DB_HOST, DB_PORT, $errno, $errstr, 0.2);
    if (is_resource($connection)) {
        $isMysqlActive = true;
        fclose($connection);
    }

    if ($isMysqlActive) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e) {
            // MySQL is up, but database might not be created yet. Auto-create & import schema.
            try {
                $tempDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $tempPdo = new PDO($tempDsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

                $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);

                $sqlFile = __DIR__ . '/../database.sql';
                if (file_exists($sqlFile)) {
                    $sqlContent = file_get_contents($sqlFile);
                    $pdo->exec($sqlContent);
                }
                return $pdo;
            } catch (Exception $e2) {
                // Fall through to SQLite
            }
        }
    }

    // 2. Local SQLite Fallback (Runs instantly with zero setup)
    try {
        $sqlitePath = __DIR__ . '/local_service_finder.db';
        $needsInit = !file_exists($sqlitePath) || filesize($sqlitePath) === 0;

        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("PRAGMA foreign_keys = ON;");

        if ($needsInit) {
            initSqliteDatabase($pdo);
        }

        return $pdo;
    } catch (Exception $e3) {
        die("Database connection error: " . htmlspecialchars($e3->getMessage()));
    }
}

/**
 * Auto-initializes SQLite with identical schema and seed data if MySQL is not running.
 */
function initSqliteDatabase($pdo) {
    $schema = "
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      email TEXT NOT NULL UNIQUE,
      phone TEXT,
      password TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT 'customer',
      address TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS services (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      description TEXT,
      category TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS providers (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      service_id INTEGER,
      business_name TEXT NOT NULL,
      experience TEXT DEFAULT '2 years',
      fee REAL DEFAULT 350.00,
      availability TEXT DEFAULT 'Mon - Sat (9:00 AM - 7:00 PM)',
      description TEXT,
      verification_status TEXT NOT NULL DEFAULT 'pending',
      rating REAL DEFAULT 0.00,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS bookings (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      customer_id INTEGER NOT NULL,
      provider_id INTEGER NOT NULL,
      service_id INTEGER,
      booking_date TEXT NOT NULL,
      booking_time TEXT NOT NULL,
      address TEXT NOT NULL,
      problem_description TEXT NOT NULL,
      status TEXT NOT NULL DEFAULT 'pending',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
      FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS reviews (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      booking_id INTEGER NOT NULL UNIQUE,
      customer_id INTEGER NOT NULL,
      provider_id INTEGER NOT NULL,
      rating INTEGER NOT NULL,
      review TEXT NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
      FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS contacts (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      email TEXT NOT NULL,
      subject TEXT NOT NULL,
      message TEXT NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ";

    $pdo->exec($schema);

    // Seed services
    $stmt = $pdo->prepare("INSERT INTO services (id, name, description, category) VALUES (?, ?, ?, ?)");
    $services = [
        [1, 'Electrician', 'Wiring, lighting fixtures, circuit breakers, switches, and appliance installations.', 'Home Repairs'],
        [2, 'Plumber', 'Leak repairs, pipe installations, drainage unclogging, and tap replacements.', 'Home Repairs'],
        [3, 'Carpenter', 'Furniture assembly, wooden door repairs, custom shelves, and cabinetry work.', 'Woodwork'],
        [4, 'Mechanic', 'On-site two-wheeler and car diagnostics, battery jumps, and minor engine fixes.', 'Automotive'],
        [5, 'Tutor', 'Maths, Science, English, and primary-secondary school academic assistance.', 'Education'],
        [6, 'Cleaner', 'Deep house cleaning, kitchen scrubbing, bathroom sanitization, and sofa washing.', 'Cleaning'],
        [7, 'AC & Appliance Repair', 'Air conditioner servicing, gas refilling, refrigerator and washing machine fixes.', 'Appliance']
    ];
    foreach ($services as $s) {
        $stmt->execute($s);
    }

    $adminHash = '$2y$10$3ec.OkcOIw/4YBqo5Lw6RO6JBEdqqmlBauNYtOkayriZCTqntLehG';
    $userHash  = '$2y$10$86ySKYDSERjWmAYrnVH5r.XIvKaPTxieiNajqqGDoJyfjL1niUKBy';

    $stmtUser = $pdo->prepare("INSERT INTO users (id, name, email, phone, password, role, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $users = [
        [1, 'Administrator', 'admin@example.com', '9876543210', $adminHash, 'admin', 'LSF Administration Office, Central City'],
        [2, 'Rahul Sharma', 'electrician@example.com', '9820112233', $userHash, 'provider', 'Shop 4, Market Complex, Sector 12, Mumbai'],
        [3, 'Amit Verma', 'plumber@example.com', '9820223344', $userHash, 'provider', '14 Navjeevan Society, Andheri West, Mumbai'],
        [4, 'Suresh Patel', 'carpenter@example.com', '9820334455', $userHash, 'provider', '201 Wood Crafts, Link Road, Borivali, Mumbai'],
        [5, 'Anita Desai', 'tutor@example.com', '9820445566', $userHash, 'provider', 'B-12 Greenwood Apartments, Bandra, Mumbai'],
        [6, 'Sunita Rao', 'cleaner@example.com', '9820556677', $userHash, 'provider', 'Flat 5, Sunshine Colony, Thane'],
        [7, 'Vikram Singh', 'repair@example.com', '9820667788', $userHash, 'provider', 'G-8 Appliance Hub, Powai, Mumbai'],
        [8, 'Ramesh Auto Care', 'pendingprovider@example.com', '9820778899', $userHash, 'provider', 'Highway Service Station, Kurla, Mumbai'],
        [9, 'John Customer', 'customer@example.com', '9811001122', $userHash, 'customer', 'Flat 402, Sea View Residency, Bandra West, Mumbai'],
        [10, 'Priya Mehta', 'priya@example.com', '9811334455', $userHash, 'customer', '12 Rose Villa, Dadar East, Mumbai']
    ];
    foreach ($users as $u) {
        $stmtUser->execute($u);
    }

    $stmtProv = $pdo->prepare("INSERT INTO providers (id, user_id, service_id, business_name, experience, fee, availability, description, verification_status, rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $providers = [
        [1, 2, 1, 'Sharma Electrical Works', '6 years', 400.00, 'Mon - Sat (9:00 AM - 8:00 PM)', 'Certified local electrician specializing in safety inspections, LED lights, fan fittings, rewiring, and short circuit repairs.', 'approved', 4.80],
        [2, 3, 2, 'Apex Plumbing Solutions', '8 years', 450.00, 'Daily (8:00 AM - 9:00 PM)', 'Expert plumbing assistance for kitchen sinks, bathroom pipe leakages, water tank overflow, and geyser installations.', 'approved', 4.90],
        [3, 4, 3, 'Classic Carpentry Services', '5 years', 500.00, 'Mon - Fri (10:00 AM - 6:00 PM)', 'Quality woodwork, hinges repairs, custom wooden shelving, furniture assembly and lock installations.', 'approved', 4.70],
        [4, 5, 5, 'Bright Future Home Tutoring', '4 years', 600.00, 'Mon - Fri (4:00 PM - 8:00 PM)', 'Dedicated private tutor for Mathematics and Science from Grade 6 to Grade 10. Patient and concept-oriented teaching.', 'approved', 5.00],
        [5, 6, 6, 'Sparkle Home Cleaners', '3 years', 800.00, 'Mon - Sat (8:30 AM - 5:30 PM)', 'Thorough domestic cleaning including deep kitchen cleaning, floor buffing, bathroom sanitization and balcony washing.', 'approved', 4.50],
        [6, 7, 7, 'Cool Breeze AC & Appliance Fix', '7 years', 550.00, 'Daily (9:00 AM - 7:00 PM)', 'Prompt diagnostics and repair for split AC units, window ACs, refrigerators, and automatic washing machines.', 'approved', 4.60],
        [7, 8, 4, 'Ramesh Garage & Mechanic', '2 years', 350.00, 'Mon - Sat (9:00 AM - 6:00 PM)', 'Roadside breakdown assistance, bike servicing, car battery replacement and minor puncture/mechanical work.', 'pending', 0.00]
    ];
    foreach ($providers as $p) {
        $stmtProv->execute($p);
    }

    $stmtBk = $pdo->prepare("INSERT INTO bookings (id, customer_id, provider_id, service_id, booking_date, booking_time, address, problem_description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $bookings = [
        [1, 9, 1, 1, '2026-10-10', '11:00:00', 'Flat 402, Sea View Residency, Bandra West, Mumbai', 'Master bedroom ceiling fan is wobbling and main switchboard sparks occasionally.', 'completed'],
        [2, 9, 2, 2, '2026-10-14', '15:30:00', 'Flat 402, Sea View Residency, Bandra West, Mumbai', 'Under-sink kitchen drainage pipe is dripping water onto the floor cabinet.', 'accepted'],
        [3, 10, 1, 1, '2026-10-15', '10:00:00', '12 Rose Villa, Dadar East, Mumbai', 'Need installation of two new LED tube lights in living room.', 'pending']
    ];
    foreach ($bookings as $b) {
        $stmtBk->execute($b);
    }

    $stmtRev = $pdo->prepare("INSERT INTO reviews (id, booking_id, customer_id, provider_id, rating, review) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtRev->execute([1, 1, 9, 1, 5, 'Rahul arrived right on time, identified the loose wiring in the switchboard quickly, and fixed the fan mounting. Highly professional!']);

    $stmtCnt = $pdo->prepare("INSERT INTO contacts (id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
    $stmtCnt->execute([1, 'Kunal Kapoor', 'kunal@example.com', 'Joining as a provider', 'Hello, I run a pest control service in Mumbai. How long does the verification process take once I register?']);
    $stmtCnt->execute([2, 'Sneha Joshi', 'sneha@example.com', 'Website question', 'Is there any extra booking fee charged by the platform or do we pay the provider directly?']);
}
