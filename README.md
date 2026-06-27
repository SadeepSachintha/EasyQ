# EasyQ - Multi-Tenant Real-Time Queue & Booking SaaS

**EasyQ** is an enterprise-grade, high-performance, centralized SaaS application designed for local service-based businesses—such as salons, spas, medical clinics, and auto repair shops—to transition from manual scheduling systems to a real-time digital queue management and booking platform.

---

## Key Features

1. **Strict Multi-Tenant Data Isolation (Multi-DB)**
   - Dynamic database provisioning per business tenant (via separate MariaDB schemas) ensures GDPR compliance, data sovereignty, and performance isolation.
2. **Dynamic Subdomain Routing**
   - Automatically resolves and switches database connections based on request hostname (e.g., `salon.localhost` dynamically resolves to the `tenant_salon` database connection).
3. **Real-Time WebSocket Sync (Laravel Reverb)**
   - Pushes live status updates instantly across staff dashboards, client widgets, and lobby displays without page reloads.
4. **Interactive Customer Check-In Widget**
   - Clean client check-in page allowing walk-in customers to join the queue, pick service categories, anonymize their names for privacy, and receive a digital queue ticket.
5. **Staff Queue Controller Dashboard**
   - Staff drop-down desk/counter selector, call next customer, mark service completion, record no-shows, and view live statistics.
6. **Smart Wait-Time Estimation**
   - Built-in rolling average calculation computing dynamic wait times based on historical staff service durations and active counters.
7. **Lobby TV Display (Digital Signage)**
   - Dark-glassmorphic signage screen (`/display`) with an offline entry chime (Web Audio API) and text-to-speech announcement synthesis (Web Speech API) to guide customers to their designated counters.
8. **Multi-Counter & Multi-Agent Routing**
   - Supports routing tickets to specific counters (e.g. Counter 1, Desk B). The lobby display dynamically handles multiple active counters and displays a concurrent "Also Serving" list.
9. **SMS Alert Notifications**
   - Multi-driver system (simulated log & Twilio API) sending check-in welcomes, turn alerts, and near-turn reminders (notifying the next person in line to prepare).
10. **Rate Limiting & Background Queuing**
    - High-throughput security limits (max 5 check-ins/min per IP) and queued background SMS alerts processed asynchronously by database queue workers.
11. **NativePHP Desktop Wrapper**
    - Packaged into a cross-platform desktop application using Electron, featuring custom window dimensions and native title routing.
12. **Automated CI/CD Pipeline**
    - GitHub Actions build pipeline executing Pint code-styling validation and PHPUnit tests against a MySQL service container for complete database parity.

---

## Technical Stack

* **Backend Framework:** Laravel 12 (PHP 8.2)
* **Multi-Tenancy Engine:** Stancl/Tenancy (`stancl/tenancy` v3)
* **Frontend SPA Bridge:** Inertia.js (Vue 3, Tailwind CSS)
* **WebSockets Server:** Laravel Reverb (Native PHP WebSockets)
* **Database Manager:** MariaDB (scaffolded locally on port `3307`)
* **Desktop Wrapper:** NativePHP (Electron)
* **CI/CD Runner:** GitHub Actions (MySQL service container & Laravel Pint)

---

## Local Development Setup

To run the application locally on Windows, execute the following components:

### 1. Dedicated MariaDB (Port 3307)
We run a dedicated, isolated MariaDB process inside the workspace to avoid conflicts with global port 3306 constraints.
* **Initialize Data Directory:**
  ```powershell
  & "d:\xampp\mysql\bin\mysql_install_db.exe" --datadir="d:\xampp\htdocs\EasyQ\db-data"
  ```
* **Start Server:**
  ```powershell
  & "d:\xampp\mysql\bin\mysqld.exe" --datadir="d:\xampp\htdocs\EasyQ\db-data" --port=3307 --bind-address=127.0.0.1 --innodb-buffer-pool-size=16M
  ```

### 2. Configure Environment (`.env`)
Ensure your database parameters in `.env` connect to port 3307:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=easyq
DB_USERNAME=root
DB_PASSWORD=
QUEUE_CONNECTION=database
```

### 3. Migrate and Seed Central Schema
Run the migrations to create the central `tenants` and `domains` tables:
```bash
php artisan migrate
```

### 4. Launch Services
Open separate terminal instances to run the local server, asset compiler, and WebSocket server:
* **Laravel Web Server:**
  ```bash
  php artisan serve --port=8000
  ```
* **Vite Asset Watcher:**
  ```bash
  npm run dev
  ```
* **Laravel Reverb WS Server:**
  ```bash
  php artisan reverb:start
  ```
* **Queue Background Worker:**
  ```bash
  php artisan queue:work
  ```

---

## How to Test the Flow

1. **Central Sign-Up:**
   - Go to `http://localhost:8000/`.
   - Enter a subdomain (e.g., `salon`) under **Create Your Business Domain** and submit.
   - The engine automatically provisions the database (`tenantsalon`), runs tenant migrations, seeds standard services, and registers the `salon.localhost` domain.
   
2. **Staff Registration:**
   - You will be redirected to `http://salon.localhost:8000/register`. Register your administrative staff account.
   - Upon submission, you will enter the **Queue Controller Dashboard** at `http://salon.localhost:8000/dashboard`.
   - On the top right of the dashboard, choose your Counter/Desk (e.g. Counter 1, Desk B) from the dropdown. This is saved to browser storage.

3. **Customer Check-In:**
   - Open `http://salon.localhost:8000/` in another tab or device.
   - Click **Check-In Now**, select a service, fill in your name and phone number (for SMS alerts), and submit.
   - Take note of your ticket number (e.g., **Q-001**) and dynamic wait estimate.

4. **Lobby TV Display:**
   - Open the digital signage page at `http://salon.localhost:8000/display`.
   - Unmute the audio by clicking the top banner to enable entry chimes and voice readouts.

5. **Real-Time Calling:**
   - Arrange both browser windows (signage page and staff dashboard) side-by-side.
   - In the staff dashboard, click **Call Next Customer**.
   - You will see the lobby screen flash the ticket name/number, hear a pleasant sine-wave chime, and listen to the voice synthesizer guide the customer to the active counter. The other serving counters are rendered in the concurrent grid below.
   - Check `storage/tenantsalon/logs/sms.log` to view simulated SMS alerts (welcome alert on join, turn alert on call next, near-turn alert on queue updates).

---

## Testing & Quality Control

### Running Local Tests
We have built a robust, transaction-based multi-database testing framework. Before running the tests locally, ensure you have created a dedicated test database `easyq_test` in your MariaDB instance:
```sql
CREATE DATABASE IF NOT EXISTS easyq_test;
```
Then, execute the PHPUnit test suite:
```bash
php artisan test
```

### Running Pint Styling Checks
We enforce strict style guides. Run Pint to check or auto-format changes:
```bash
# Check code style
./vendor/bin/pint --test

# Auto-fix code style
./vendor/bin/pint
```

### NativePHP Desktop App Packaging
EasyQ is packaged inside a native Electron shell container via NativePHP.

* **Configuration**: Managed inside [config/nativephp.php](file:///d:/xampp/htdocs/EasyQ/config/nativephp.php) and [NativeAppServiceProvider.php](file:///d:/xampp/htdocs/EasyQ/app/Providers/NativeAppServiceProvider.php) (window size, title bar routing, background workers).
* **Run in Development**: Start the Electron shell and auto-reload:
  ```bash
  php artisan native:serve
  ```
* **Build Standalone Installer**: Compile a native production installer (e.g., `.exe` for Windows) containing the PHP runtime, Electron engine, and project code:
  ```bash
  php artisan native:build
  ```
  The installer executable will be saved in the `dist/` directory at the project root.
* **Automated Release Pipeline**: A GitHub Actions workflow configured in [.github/workflows/release.yml](file:///d:/xampp/htdocs/EasyQ/.github/workflows/release.yml) triggers automatically when a version tag matching `v*` (e.g., `v1.0.0`) is pushed to the repository, or via manual `workflow_dispatch`. It executes parallel matrix builds for Windows, macOS, and Linux runners, and uploads the generated standalone installer packages directly to a GitHub Release draft.