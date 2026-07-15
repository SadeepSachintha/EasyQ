# Production Handover Plan - EasyQ Desktop Application

EasyQ has been successfully converted into an enterprise-grade, fully production-ready, offline-first desktop application using **NativePHP (Electron)**. This handover document serves as a guide for building, packaging, troubleshooting, and releasing the application.

---

## 1. Architectural Highlights & Offline Readiness

To make EasyQ operate independently as a local desktop program without needing a central cloud server, the following components were built into the runtime:

### A. Subdomain Routing Bypass
* **Mechanism:** In Electron, we registered Chromium CLI `host-rules` that map `*.localhost` requests to the loopback `127.0.0.1`.
* **Security Middleware override:** Overrode the base NativePHP security rules using a custom middleware [CustomPreventRegularBrowserAccess.php](file:///d:/xampp/htdocs/EasyQ/app/Http/Middleware/CustomPreventRegularBrowserAccess.php) bound in the IoC container. This allows offline requests on loopbacks (`localhost`, `127.0.0.1`) and subdomains (`*.localhost`) to bypass strict session security cookie requirements, resolving a silent `403 Forbidden` crash.

### B. Production-Grade SQLite Tenancy Manager
* **Database Driver:** MariaDB (port 3307) is used in local web development. In the compiled production package, NativePHP runs entirely on **SQLite** (under the hood of embedded PHP binaries).
* **Storage Location:** Databases are dynamically created and stored securely inside the user's roaming directory:
  `C:\Users\<username>\AppData\Roaming\easyq\database\`
* **Database-Agnostic Timing Queries:** Refactored MySQL-specific `TIMESTAMPDIFF()` queries inside [QueueController.php](file:///d:/xampp/htdocs/EasyQ/app/Http/Controllers/Tenant/QueueController.php) to inspect the active connection. When run on SQLite, it shifts queries to native seconds difference calculation:
  `select AVG(strftime("%s", completed_at) - strftime("%s", called_at))`
  This resolved the fatal `General error: 1 no such column: SECOND` crashes on the dashboard.

### C. Self-Contained WebSockets (Laravel Reverb)
* **Daemon Execution:** The Laravel Reverb WebSocket daemon starts automatically as a background child process when the Electron window launches, managed by [NativeAppServiceProvider.php](file:///d:/xampp/htdocs/EasyQ/app/Providers/NativeAppServiceProvider.php).
* **Client Fallbacks:** Added solid defaults inside [resources/js/bootstrap.js](file:///d:/xampp/htdocs/EasyQ/resources/js/bootstrap.js) to fall back to port `8080` and the correct default app key/dynamic host in production if environment variables are not injected, preventing Pusher instantiating exceptions.

---

## 2. Production Build Exclusions

To keep the final build directory light and clean:
1. **Developer Assets Exclusions:** Excluded development-only directories (`db-data`, `dist`) in [config/nativephp.php](file:///d:/xampp/htdocs/EasyQ/config/nativephp.php) to avoid locked binary file conflicts during compilation.
2. **Vite Hot-Reload Exclusion:** Added `public/hot` to `cleanup_exclude_files` to prevent local development hot-reload parameters from leaking into production, guaranteeing static production asset mounting.

---

## 3. Package Compilation & Local Testing

If you need to compile a standalone installer setup executable (`.exe`) on your machine:

### Step 1: Terminate Existing Instances
Ensure all previous backend PHP daemons and easyq processes are closed to release file handles:
```powershell
Get-Process -Name "easyq" -ErrorAction SilentlyContinue | Stop-Process -Force
Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object { $_.Path -like "*AppData*" } | Stop-Process -Force
```

### Step 2: Compile Production Assets
```powershell
# Compile Vite assets
npm.cmd run build
```

### Step 3: Package the Desktop Installer
```powershell
# Run the NativePHP builder
php artisan native:build win
```
When prompted for the CPU architecture choice, type `0` to select `x64` (standard 64-bit Windows). The output installer will be created at:
`D:\xampp\htdocs\EasyQ\dist\EasyQ-1.0.0-setup.exe`

---

## 4. Automated CI/CD Release Pipeline

New official releases are generated automatically via the GitHub Actions runner.

1. **Workflow Location:** [.github/workflows/release.yml](file:///d:/xampp/htdocs/EasyQ/.github/workflows/release.yml)
2. **Trigger:** Create and push a version tag matching the `v*` format:
   ```bash
   git tag v1.1.3
   git push origin v1.1.3
   ```
3. **Execution:** The pipeline kicks off parallel builds on Windows, macOS, and Linux runners, compiles the production code, packages the executable installers, and creates a draft release containing the distribution bundles under your GitHub Releases.
