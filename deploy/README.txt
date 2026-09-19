============================================================
GCM NETTING SOLUTIONS — DEPLOYMENT GUIDE
Hostinger Shared Hosting (MySQL + PHP 8.x)
============================================================

FILES IN THIS FOLDER
────────────────────
  GCM-INSTALL-ALL.sql   → Run this ONCE in phpMyAdmin to create all tables
  create-admin.php      → Run once to create your admin login, then DELETE it
  README.txt            → This file

============================================================
STEP-BY-STEP DEPLOYMENT
============================================================

STEP 1 — Upload the ZIP
  • In Hostinger hPanel → File Manager → public_html
  • Upload gcmsafetynets-deploy.zip
  • Extract it (all files land in public_html/)

STEP 2 — Import the Database
  • hPanel → Databases → phpMyAdmin
  • Select database: u271370596_gcm
  • Click "Import" tab → Choose file: deploy/GCM-INSTALL-ALL.sql
  • Click "Go" — takes ~5 seconds
  • Verify: run  SELECT * FROM v_site_summary;
    Expected: 64 keywords, 188 areas

STEP 3 — Create Admin Account
  • Visit: https://www.gcmsafetynets.in/deploy/create-admin.php
  • Deploy Secret (default): GCM-SETUP-2024
    *** Change this in create-admin.php line ~35 before uploading! ***
  • Fill username + password → Submit
  • *** DELETE deploy/create-admin.php immediately after! ***

STEP 4 — Set Environment Variables (Optional but Recommended)
  hPanel → Hosting → PHP Configuration → Environment Variables:
    GCM_DB_PASS    = your-mysql-password
    GCM_SMTP_PASS  = your-gmail-app-password
    GCM_GEMINI_KEY = your-gemini-api-key
  (Without these, the fallback values in config.php are used)

STEP 5 — Set File Permissions
  chmod 755  /public_html/
  chmod 755  /public_html/assets/img/uploads/
  chmod 755  /public_html/assets/img/uploads/gallery/
  chmod 755  /public_html/data/
  chmod 644  /public_html/config/config.php

STEP 6 — Test the Site
  • https://www.gcmsafetynets.in/           → Homepage
  • https://www.gcmsafetynets.in/admin/login.php → Admin login
  • https://www.gcmsafetynets.in/gallery.php → Gallery
  • https://www.gcmsafetynets.in/about.php   → About page

============================================================
SQL FILES — WHAT WAS CONSOLIDATED
============================================================
These 18 SQL files are now merged into GCM-INSTALL-ALL.sql:

  config/MASTER-DATABASE-COMPLETE.sql       → services + areas + pages
  config/COMPLETE-DATABASE-KEYWORDS-AREAS.sql → 64 keywords + 188 areas
  config/complete-database-schema.sql       → core schema
  config/billing-schema.sql                 → billing tables (SQLite)
  config/init-homepage-tables.sql           → homepage content tables
  config/init-keywords-services.sql         → keyword/service linking
  create-visitor-tracking-table.sql         → visitor sessions
  sql/DEPLOY-ALL-TABLES.sql                 → all tables combined
  sql/api-key-management.sql                → API key tracking
  sql/automated-blog-system.sql             → blog automation
  sql/backlinks-table.sql                   → backlink tracking
  sql/complete-page-structure.sql           → page management
  sql/contact_inquiries.sql                 → contact form storage
  sql/page-deletion-log.sql                 → page deletion audit
  sql/seo-optimization-log.sql              → SEO change log
  sql/seo-rankings-real-data.sql            → keyword rankings
  sql/service-highlights.sql                → homepage highlights
  sql/visitor-tracking-system.sql           → visitor analytics

All 18 files → 1 clean file: GCM-INSTALL-ALL.sql (safe to run multiple times)

============================================================
TROUBLESHOOTING
============================================================
  "Table already exists" error → Safe to ignore, uses IF NOT EXISTS
  Admin login fails            → Re-run create-admin.php
  Images not showing           → Check uploads/ folder permissions (755)
  Gallery broken               → Confirm gallery_images.image_url column exists
  500 error                    → Check logs/ folder, enable display_errors temporarily

============================================================
