Rewarity Web — Deployment Guide

Overview
- Codebase is a plain PHP app (admin + API), not Laravel.
- Deploy target: Hostinger shared hosting for `rewarity.com`.
- App keeps production credentials in `includes/env.php` on server only.

One-time server setup
1) Create the app directory and env file on the server:
   - SSH: `ssh -p 65002 u788248317@217.21.95.207`
   - Create `includes/env.php` at `/home/u788248317/domains/rewarity.com/public_html/includes/env.php` with:
     ```php
     <?php
     $host = 'localhost';
     $user = 'u788248317_rewarity';
     $pass = 'u788248317Rewarity';
     $db   = 'u788248317_rewarity';
     ```
   - Ensure the directory exists: `/home/u788248317/domains/rewarity.com/public_html/`

Manual deploy (local)
- Update defaults in `deploy.sh` already set for this server.
- Run a dry run: `./deploy.sh --dry-run`
- Run real deploy: `./deploy.sh`

Auto-deploy from GitHub
1) Add the following repository secrets:
   - `SSH_HOST` = `217.21.95.207`
   - `SSH_PORT` = `65002`
   - `SSH_USER` = `u788248317`
   - `SSH_PASS` = `Rewarity@12` (or, preferably, use an SSH key)
   - `REMOTE_DIR` = `/home/u788248317/domains/rewarity.com/public_html/`
   - `DB_HOST` = `localhost` (optional; defaults to localhost)
   - `DB_USER` = `u788248317_rewarity`
   - `DB_PASS` = `u788248317Rewarity`
   - `DB_NAME` = `u788248317_rewarity`
2) Push to `main` branch to trigger `.github/workflows/deploy.yml`.
    - The workflow ensures `includes/env.php` exists on the server (creates it once if missing) and then deploys code via rsync.

Notes
- `.deployignore` excludes `includes/env.php` and local artifacts from upload.
- The rsync deploy protects `includes/env.php` on the server from deletion/overwrite.
- If document root changes, adjust `REMOTE_DIR` accordingly.

Production DB setup (minimal)
- Use phpMyAdmin on the Hostinger DB `u788248317_rewarity`.
- Select the database, open the SQL tab, and paste the contents of `sql/production_schema_min.sql`.
- This creates essential tables (`admin_users`, `user_type`, `user_master`, `mobile_master`, `product_master`, `order_master`, `order_items`, `notification_master`, `address_master`) and seeds a super admin.
- Admin credentials after import:
  - Email: `admin@rewarity.com`
  - Password: `Admin@123`

Add remaining tables (extended)
- After the minimal schema, add more tables used by advanced features:
  - Open the SQL tab again and paste the contents of `sql/production_schema_more.sql`.
  - This adds: geographic masters, categories, colors, pricing, payment, reward, sales, schemes, points and related tables.
  - All statements are `CREATE TABLE IF NOT EXISTS` and are safe to run multiple times.
