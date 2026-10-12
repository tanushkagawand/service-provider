# Local Service Finder &mdash; Full-Stack Service Management Application

A clean, modern, and limited-scope full-stack web application designed for connecting neighbourhood residents with verified local service providers (such as electricians, plumbers, carpenters, mechanics, tutors, cleaners, and appliance technicians).

Developed with **PHP** and **MySQL/MariaDB**, styled using a clean, light **Material Design / Material UI** visual language, and built strictly with vanilla web technologies suitable for college academic evaluation.

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Key Features by Role](#key-features-by-role)
3. [Design System & Material UI Architecture](#design-system--material-ui-architecture)
4. [Technology Stack](#technology-stack)
5. [Visual Assets & Imagery](#visual-assets--imagery)
6. [Directory Structure](#directory-structure)
7. [Database Architecture](#database-architecture)
8. [Installation & Setup](#installation--setup)
   - [Option A: Running with XAMPP (Apache + MySQL)](#option-a-running-with-xampp-apache--mysql)
   - [Option B: Running with Standalone PHP CLI](#option-b-running-with-standalone-php-cli)
9. [Sample Test Accounts](#sample-test-accounts)
10. [Step-by-Step Demonstration Flow](#step-by-step-demonstration-flow)
11. [Known Limitations & Future Scope](#known-limitations--future-scope)

---

## Project Overview

**Local Service Finder** solves everyday domestic repair and maintenance needs by offering a transparent directory of local technicians. Customers can easily search services by category or location, check fees and ratings, book an appointment slot, track service progress, and leave reviews. 

The application implements genuine three-tier role-based access control:
- **Customers**: Search, schedule appointments, cancel pending bookings, submit post-service reviews.
- **Service Providers**: Manage business rates/hours, accept or reject customer bookings, and mark services as completed.
- **Administrators**: Review platform statistics, verify newly registered providers before public listing, inspect all bookings, and review inquiries.

---

## Key Features by Role

### 1. Customer Flow
- **Browse & Search**: Filter approved service providers by trade (Electrician, Plumber, Carpenter, etc.) and location keywords.
- **Provider Transparency**: View verified experience, standard visit/inspection fee, working hours, and authentic client reviews.
- **Service Booking**: Submit an appointment specifying preferred date, time, address, and problem description.
- **Customer Dashboard**: Track status (`PENDING`, `ACCEPTED`, `COMPLETED`, `CANCELLED`).
- **Cancellation**: Ability to cancel any pending booking request.
- **Rating & Feedback**: Leave a 1 to 5 star rating and review after a service is marked `COMPLETED`. The provider's average rating is automatically recalculated.

### 2. Service Provider Flow
- **Registration & Verification**: Register a trade business; initially marked `PENDING` until approved by the admin.
- **Workspace Dashboard**: Review appointment requests from customers with contact phone numbers and problem notes.
- **Booking Lifecycle**: 
  - `Accept` or `Reject` incoming pending requests.
  - Mark accepted jobs as `Completed` once work is performed.
- **Profile Management**: Update visit fees, experience duration, availability schedule, and business bio.

### 3. Administrator Flow
- **Dedicated Admin Login**: Separate administrative portal authentication.
- **Platform Analytics**: Total users, registered providers, pending verifications, total bookings, and inquiry volume.
- **Provider Approval Queue**: Single-click `Approve` or `Reject` for new providers. Only approved providers appear in search.
- **User & Booking Audit**: Review all registered accounts and audit all service requests across the platform.
- **Inquiry Management**: Review messages submitted through the public contact form.

---

## Design System & Material UI Architecture

The application strictly implements a **Clean Light Material Design** visual language with a restrained, professional palette:

| Element | Color | Role |
| :--- | :--- | :--- |
| **Primary Background** | `#F8FAFC` | Slate-50: dominant light page background |
| **Surface / Cards** | `#FFFFFF` | Clean elevated white surfaces |
| **Primary Accent** | `#2563EB` | Blue-600: primary buttons, active links, focused states, important highlights |
| **Primary Text** | `#1E293B` | Slate-800: headings, labels, and primary copy |
| **Secondary Text** | `#64748B` | Slate-500: captions, descriptions, subheadings |
| **Borders** | `#E2E8F0` | Slate-200: subtle component separation |

### Material UI Principles Implemented:
- **Consistent 8px Spacing Grid** across containers, paddings, and margins.
- **Clean Typography**: Google Font `Roboto` with clear size hierarchy (Page heading, section heading, card heading, body, caption).
- **Subtle Elevation**: Multi-tiered soft box shadows (`--elevation-1`, `--elevation-2`, `--elevation-3`) replacing harsh borders.
- **8–12px Border Radii** for comfortable, modern visual hierarchy without excessive pill-shapes.
- **Material Form Controls**: Crisp borders with soft blue focus halos (`0 0 0 3px rgba(37, 99, 235, 0.15)`).
- **Material Status Chips**: Soft background badges for `PENDING` (amber), `ACCEPTED` (blue), `COMPLETED` (emerald), and `CANCELLED` (rose).
- **Material Dialogs & Snackbars**: Modals with backdrop blur and toast feedback messages.

---

## Technology Stack

- **Frontend**: HTML5, Vanilla CSS3 (Material UI Design Tokens), Vanilla JavaScript.
- **Backend**: PHP 8.x (Session management, PDO prepared statements, `password_hash`/`password_verify`).
- **Database**: MySQL / MariaDB (via PDO) with automated fallback to SQLite for immediate testing.
- **Environment**: Compatible with standard XAMPP (Apache + MySQL) and built-in PHP development server.

---

## Visual Assets & Imagery

A limited number of high-quality, professional, and bright assets are integrated into the UI:
1. **Homepage Hero Image** (`assets/images/hero_service_pro.jpg`): Realistic, bright photograph of a friendly local service technician with tools in a modern home.
2. **Category Visual Icons** (`includes/icons.php`): Clean Material vector icons for Electrician, Plumber, Carpenter, Mechanic, Tutor, Cleaner, and Appliance Repair.
3. **Service Work Preview** (`assets/images/service_electrical.jpg`, `service_plumbing.jpg`): High-resolution previews for provider detail pages.
4. **Provider Avatars**: Clean Material Design circular avatar badges with initial monograms and role status chips.

---

## Directory Structure

```
service-provider-main/
│
├── assets/
│   ├── css/
│   │   └── style.css            # Core Material Design / Material UI stylesheet
│   ├── images/
│   │   ├── hero_service_pro.jpg # High-res hero image
│   │   ├── service_electrical.jpg # Electrical service work photo
│   │   ├── service_plumbing.jpg   # Plumbing installation photo
│   │   └── local-service-finder-mark.png # Material blue brand mark
│   └── js/
│
├── includes/
│   ├── db.php                  # Centralized PDO connection (XAMPP MySQL + SQLite fallback)
│   ├── auth.php                # Session authentication, role checking, flash messaging
│   ├── icons.php               # Material Design SVG icons & avatar helper
│   ├── header.php              # Responsive Material app bar with role navigation
│   └── footer.php              # Standardized footer component
│
├── admin/
│   ├── dashboard.php           # Admin metrics and pending provider approval queue
│   ├── providers.php           # Provider directory and verification toggle
│   ├── users.php               # Customer and provider account directory
│   ├── bookings.php            # Platform-wide booking audit
│   └── messages.php            # Contact form inquiries viewer
│
├── provider/
│   ├── dashboard.php           # Provider workspace: accept, reject, complete bookings & edit profile
│   ├── bookings.php            # Shortcut redirect to bookings section
│   └── profile.php             # Shortcut redirect to profile section
│
├── customer/
│   ├── dashboard.php           # Shortcut redirect to customer dashboard
│   └── bookings.php            # Shortcut redirect to bookings list
│
├── database.sql                # Complete MySQL schema and seed data
├── index.php                   # Home page: hero, search bar, categories, top providers
├── services.php                # Directory with search, category filtering & provider cards
├── provider-details.php        # Provider profile, experience, fees, reviews & booking CTA
├── booking.php                 # Customer booking form with validation
├── dashboard.php               # Customer dashboard: tracking, cancellation & reviews
├── login.php                   # Secure user login (Customer & Provider)
├── admin-login.php             # Secure Administrator login
├── signup.php                  # User registration (Customer or Provider)
├── logout.php                  # Session logout
├── contact.php                 # Working inquiry contact form
├── .htaccess                   # Apache URL rewriting & legacy compatibility
└── README.md                   # Project documentation
```

---

## Database Architecture

The application database is named `local_service_finder` and consists of 6 relational tables:

1. **`users`**: Stores user authentication credentials, role (`customer`, `provider`, `admin`), phone, and address. Passwords hashed using bcrypt via `password_hash()`.
2. **`services`**: Defines standard categories (Electrician, Plumber, Carpenter, Mechanic, Tutor, Cleaner, AC & Appliance Repair).
3. **`providers`**: Provider profiles linked to `users(id)` and `services(id)` with `verification_status` (`pending`, `approved`, `rejected`), experience, visit fee, availability, and rating.
4. **`bookings`**: Service requests linked to customer and provider with appointment date, time, address, problem notes, and status (`pending`, `accepted`, `rejected`, `completed`, `cancelled`).
5. **`reviews`**: 1-to-5 star ratings and reviews linked to completed bookings with foreign keys to customer and provider.
6. **`contacts`**: Stores inquiries submitted through the contact page for administrative review.

---

## Installation & Setup

### Option A: Running with XAMPP (Apache + MySQL)

1. **Download / Copy Project**:
   Copy the `service-provider-main` folder into your XAMPP web root directory:
   ```
   C:\xampp\htdocs\service-provider-main
   ```
2. **Start Services**:
   Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.
3. **Import Database**:
   - Open your web browser and go to `http://localhost/phpmyadmin`.
   - Click on the **Import** tab.
   - Choose the file: `database.sql` located inside `service-provider-main/`.
   - Click **Go** / **Import**. The database `local_service_finder` and all sample tables/data will be created.
4. **Open in Browser**:
   Visit:
   ```
   http://localhost/service-provider-main/index.php
   ```

*(Note: `includes/db.php` is pre-configured with standard XAMPP settings: Host: `127.0.0.1`, Port: `3306`, User: `root`, Password: `""`.)*

---

### Option B: Running with Standalone PHP CLI

If you want to run the project instantly without configuring a database server:

1. Open PowerShell or Command Prompt in the project directory:
   ```bash
   cd c:\Users\razap\Downloads\service-provider-main
   ```
2. Start the built-in PHP development server:
   ```bash
   php -S 127.0.0.1:8000
   ```
3. Open your browser and navigate to:
   ```
   http://127.0.0.1:8000/index.php
   ```
*(The system automatically detects standalone mode and initializes an embedded SQLite database with identical schema and seed data with zero configuration.)*

---

## Sample Test Accounts

All sample passwords use industry standard bcrypt hashes.

| Role | Name / Business | Email Address | Password | Initial Status |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | Administrator Console | `admin@example.com` | `admin123` | Active Admin |
| **Customer** | John Customer | `customer@example.com` | `password123` | Active Customer |
| **Customer** | Priya Mehta | `priya@example.com` | `password123` | Active Customer |
| **Provider** | Sharma Electrical Works | `electrician@example.com` | `password123` | Approved (Rating 4.8) |
| **Provider** | Apex Plumbing Solutions | `plumber@example.com` | `password123` | Approved (Rating 4.9) |
| **Provider** | Classic Carpentry | `carpenter@example.com` | `password123` | Approved (Rating 4.7) |
| **Provider** | Bright Future Tutoring | `tutor@example.com` | `password123` | Approved (Rating 5.0) |
| **Provider** | Sparkle Home Cleaners | `cleaner@example.com` | `password123` | Approved (Rating 4.5) |
| **Provider** | Cool Breeze AC Repair | `repair@example.com` | `password123` | Approved (Rating 4.6) |
| **Provider** | Ramesh Garage & Mechanic | `pendingprovider@example.com` | `password123` | **Pending Verification** |

---

## Step-by-Step Demonstration Flow

To present a complete live demonstration of the mini-project:

1. **Browse & Search**:
   - Visit the home page (`index.php`). Notice the clean Material UI hero section, search form, and category cards.
   - Click **Services** to view the directory. Filter by **Electrician** or search by keyword `Mumbai`.
2. **Inspect Provider Profile**:
   - Click **View Profile** on *Sharma Electrical Works* (`provider-details.php?id=1`).
   - Notice the standard visit fee (₹400), verified chip, experience (6 years), and past customer reviews.
3. **Book an Appointment**:
   - Click **Book Service Now**.
   - If not logged in, you will be redirected to the login page with a friendly prompt.
   - Log in using `customer@example.com` / `password123`.
   - You are redirected directly to `booking.php` with Sharma Electrical Works pre-selected.
   - Fill in your preferred date, time slot, and issue description (e.g., *"Ceiling light humming loudly"*).
   - Submit the booking. A green confirmation banner appears and the request is listed on your dashboard with status **PENDING**.
4. **Provider Accepts & Completes the Job**:
   - Click **Logout**, then log in as the provider (`electrician@example.com` / `password123`).
   - You are routed to the **Provider Workspace** (`provider/dashboard.php`).
   - Notice the new incoming booking request with customer contact details and problem description.
   - Click **Accept**. The booking status updates to **ACCEPTED**.
   - Later, once work is done, click **Mark Completed ✓**. The status updates to **COMPLETED**.
5. **Customer Submits a Review**:
   - Log out and log back in as `customer@example.com`.
   - On the customer dashboard, the booking now shows a **Leave Review →** button.
   - Select 5 stars and submit feedback (e.g., *"Prompt and very thorough work!"*).
   - The review is published and the provider's overall rating is recalculated dynamically in the database.
6. **Admin Verification Flow**:
   - Click **Admin** in the navigation bar (`admin-login.php`).
   - Log in with `admin@example.com` / `admin123`.
   - The Admin Overview displays platform metrics and shows *Ramesh Garage & Mechanic* in the **Pending Provider Verifications** queue.
   - Click **✓ Approve**. The provider's status changes to **APPROVED**, and they now appear in the public search directory on `services.php`.
7. **Contact Inquiries**:
   - Visit `contact.php` and submit an inquiry.
   - Under the Admin console, click **Inquiries** (`admin/messages.php`) to see the submitted message.

---

## Known Limitations & Future Scope

In alignment with college mini-project scope guidelines, advanced features have intentionally been reserved for future development:

- **Live GPS Tracking**: Real-time map navigation of service vehicles.
- **Online Payment Gateway**: Integration with Razorpay, Stripe, or UPI for upfront escrow payments.
- **Real-Time WebSockets Chat**: Instant in-app messaging between customer and technician.
- **Push & SMS Notifications**: Automated SMS alerts for appointment dispatch via Twilio.
- **Mobile Application**: Native Android / iOS client built with Flutter or React Native.
- **AI Recommendation**: Algorithmic job dispatching based on proximity and historical completion velocity.

---

## Project Status

**Complete and Verified**: Fully functional PHP backend, normalized database schemas, role security checks, and Light Material Design / Material UI system. Tested end-to-end with zero frontend or backend errors.
