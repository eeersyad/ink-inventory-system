Looking at your file structure, I see this is actually a streamlined custom PHP application rather than a heavy Laravel framework build. It perfectly isolates the admin and staff workflows and incorporates modern real-time updates using Server-Sent Events (SSE).

Here is a professional `README.md` tailored specifically to the file architecture of your `ink-system`.

---

# Sistem Permohonan Dakwat Printer (Ink Inventory Management System) 🖨️💧

## Overview

A comprehensive web-based inventory and request management system designed to streamline office printer ink requisitions. Originally deployed for local government council operations at Majlis Daerah Marang, this system digitizes the traditional pen-and-paper request workflow into a centralized, trackable, and real-time dashboard.

The application separates staff requisition portals from administrative inventory controls, featuring automated PDF receipt generation, live status updates, and integrated email notifications.

## Key Features

* **Dual-Role Dashboards:** Distinct interfaces for Staff (`staff_dashboard.php`) to submit requests and Administrators (`admin_dashboard.php`) to manage inventory and approve transactions.


* **Real-Time Updates:** Utilizes Server-Sent Events (SSE) via `api_admin_live.php` and `sse_requests.php` to push live data and notification updates without page reloads.


* **Automated Document Generation:** Instantly generates downloadable reports and PDF receipts (`generate_receipt.php`, `generate_staff_receipt.php`) for approved ink disbursements.


* **Email Notifications:** Integrated `PHPMailer` ensures staff are immediately alerted when their ink requests are processed or ready for collection.


* **Inventory & Transaction Tracking:** Maintains a complete historical ledger of all requests (`admin_request_history.php`) and stock movements (`admin_transactions.php`).



## Tech Stack

* **Backend:** Custom Core PHP
* **Database:** MySQL (`db.php`)


* **Real-Time API:** HTML5 Server-Sent Events (SSE)
* **Email Services:** PHPMailer for SMTP integration


* **Frontend:** HTML5, CSS3, JavaScript (Vanilla)

## Repository Structure

```text
├── admin_*.php          # Administrator views and inventory management logic[cite: 2]
├── staff_*.php          # Staff request portals and status tracking[cite: 2]
├── api_* / sse_*.php    # Real-time event streams for live dashboard updates[cite: 2]
├── generate_*.php       # PDF and receipt generation scripts[cite: 2]
├── db.php               # Database connection configuration[cite: 2]
├── PHPMailer/           # Third-party library for SMTP email dispatch[cite: 2]
└── images/              # System assets (including MDM and Terengganu crests)[cite: 2]

```

## Setup & Installation

1. **Clone the repository:**
```bash
git clone https://github.com/yourusername/ink-system.git
cd ink-system

```


2. **Database Configuration:**
* Import your SQL database schema into your local MySQL server.
* Open `db.php` and update the connection credentials to match your local environment.




3. **Email Configuration:**
* Navigate to the PHPMailer initialization (typically inside `process_request.php`).


* Input your SMTP server credentials, port, and authentication tokens to enable outbound alerts.


4. **Launch the Application:**
* Host the directory on a local PHP server environment (like XAMPP, Laragon, or MAMP).
* Navigate to `http://localhost/ink-system/login.php` to access the system.





## Developer

**Ahmad Irsyad Qayyum Bin Mohd Azam**
Developed to enhance internal logistical efficiency and digital record-keeping for administrative service centers.
