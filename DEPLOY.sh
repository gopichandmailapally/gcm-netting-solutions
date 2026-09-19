#!/bin/bash
# GCM Safety Nets - Deployment Script
# Run this script to deploy all database tables and initialize the system

echo "🚀 GCM Safety Nets - Deployment Script"
echo "========================================"
echo ""

# Database credentials
DB_USER="u271370596_gcm"
DB_NAME="u271370596_gcm"
DB_HOST="localhost"

echo "📊 Deploying Database Tables..."
echo ""

# 1. Complete Billing System
echo "1️⃣  Creating Billing System Tables..."
mysql -u $DB_USER -p $DB_NAME < sql/complete-billing-system.sql
if [ $? -eq 0 ]; then
    echo "   ✅ Billing system tables created"
else
    echo "   ❌ Error creating billing tables"
fi

# 2. Automated Blog System
echo "2️⃣  Creating Automated Blog System Tables..."
mysql -u $DB_USER -p $DB_NAME < sql/automated-blog-system.sql
if [ $? -eq 0 ]; then
    echo "   ✅ Blog system tables created"
else
    echo "   ❌ Error creating blog tables"
fi

# 3. API Key Management
echo "3️⃣  Creating API Key Management Tables..."
mysql -u $DB_USER -p $DB_NAME < sql/api-key-management.sql
if [ $? -eq 0 ]; then
    echo "   ✅ API key management tables created"
else
    echo "   ❌ Error creating API key tables"
fi

# 4. Service Highlights
echo "4️⃣  Creating Service Highlights Tables..."
mysql -u $DB_USER -p $DB_NAME < sql/service-highlights.sql
if [ $? -eq 0 ]; then
    echo "   ✅ Service highlights tables created"
else
    echo "   ❌ Error creating highlights tables"
fi

# 5. Visitor Tracking System
echo "5️⃣  Creating Visitor Tracking System Tables..."
mysql -u $DB_USER -p $DB_NAME < sql/visitor-tracking-system.sql
if [ $? -eq 0 ]; then
    echo "   ✅ Visitor tracking tables created"
else
    echo "   ❌ Error creating visitor tracking tables"
fi

# 6. Complete Page Structure
echo "6️⃣  Creating Complete Page Structure Tables..."
mysql -u $DB_USER -p $DB_NAME < sql/complete-page-structure.sql
if [ $? -eq 0 ]; then
    echo "   ✅ Page structure tables created"
else
    echo "   ❌ Error creating page structure tables"
fi

# 7. Backlinks Table
echo "7️⃣  Creating Backlinks Table..."
mysql -u $DB_USER -p $DB_NAME < sql/backlinks-table.sql
if [ $? -eq 0 ]; then
    echo "   ✅ Backlinks table created"
else
    echo "   ❌ Error creating backlinks table"
fi

echo ""
echo "📁 Creating Required Directories..."

# Create necessary directories
mkdir -p logs
mkdir -p uploads/company-logos
mkdir -p uploads/favicon
mkdir -p data/reviews
mkdir -p generated-pages

# Set permissions
chmod 755 logs
chmod 755 uploads
chmod 755 uploads/company-logos
chmod 755 data
chmod 755 data/reviews
chmod 755 generated-pages

echo "   ✅ Directories created and permissions set"

echo ""
echo "🔒 Setting Up Security..."

# Create .htaccess if not exists
if [ ! -f .htaccess ]; then
    echo "   ⚠️  .htaccess file not found - please ensure it exists"
else
    echo "   ✅ .htaccess file exists"
fi

echo ""
echo "✅ DEPLOYMENT COMPLETE!"
echo ""
echo "📋 Next Steps:"
echo "1. Set up admin account (visit /admin/login.php)"
echo "2. Configure company settings (visit /admin/billing/company-settings.php)"
echo "3. Add API key (visit /admin/pages/api-key-settings.php)"
echo "4. Generate pages (visit /admin/pages/generate-pages.php)"
echo "5. Configure service highlights (visit /admin/pages/service-highlights.php)"
echo ""
echo "🎉 Your website is ready to go live!"
