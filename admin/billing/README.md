# 🧾 Billing System - Complete Documentation

## 📋 Overview

A comprehensive billing and invoicing system with **multi-company support**, full GST compliance, automatic calculations, and professional invoice generation. Perfect for managing multiple businesses from one admin panel.

## ✨ Features

### 📄 Document Types (ALL READY!)
1. ✅ **Estimation (Without GST)** - Simple quotations
2. ✅ **Estimation with GST** - GST quotations with tax breakdown
3. ✅ **Cash Bill (Without GST)** - Simple cash receipts
4. ✅ **Invoice with GST** - Complete GST tax invoices
5. ✅ **Warranty Card** - Product warranty certificates

### 🎯 Key Features
- ✅ **Multi-Company Support** - Manage multiple companies from one admin panel
- ✅ **Company Logo Upload** - Logos appear on all invoices and documents
- ✅ **Automatic GST Calculation** - CGST, SGST, IGST
- ✅ **HSN Code Management** - Editable HSN codes for all products
- ✅ **Dynamic GST Rates** - Change GST % anytime (5%, 12%, 18%, 28%)
- ✅ **Auto-fill Product Details** - HSN and GST auto-populate on product selection
- ✅ **Real-time Calculations** - Automatic amount calculations
- ✅ **Amount in Words** - Grand total displayed in words (Indian format)
- ✅ **Bank Details Integration** - Mandatory for GST invoices
- ✅ **Professional Templates** - Modern, print-ready designs
- ✅ **Multiple Bank Accounts** - Manage multiple bank accounts
- ✅ **Product Catalog** - Pre-configured with 9 products

## 🚀 Getting Started

### Step 1: Initialize Database

**IMPORTANT:** Run this first to set up all tables and default data.

```
http://localhost:8000/admin/billing/init-billing-db.php
```

This will create:
- ✅ Products table with 9 pre-configured products
- ✅ Bank details table
- ✅ Invoices and invoice items tables
- ✅ Company settings table

### Step 2: Add Your Company

Navigate to: **Billing Dashboard → Company Settings**

**Add your company details:**
- Company name
- **Upload company logo** (appears on all documents)
- Address, phone, email
- GSTIN and PAN
- Invoice/Estimation/Warranty prefixes
- Default terms & conditions

**Multi-Company Support:**
- Add multiple companies
- Each company has its own logo and settings
- Select company when creating documents

### Step 3: Configure Products & GST

Navigate to: **Billing Dashboard → Products & GST**

**Pre-configured Products:**
| Product | HSN Code | GST % |
|---------|----------|-------|
| Anti Bird Net | 39269099 | 5% |
| Sports/Cricket Net | 56069990 | 5% |
| Safety Net | 56089090 | 5% |
| Artificial Grass | 57033100 | 5% |
| Artificial Cricket Pitch Turf | 57033100 | 5% |
| Invisible Grill | 73144990 | 18% |
| Cloth Hanger | 73262090 | 18% |
| Mosquito Mesh Velcro Type | 70199090 | 18% |
| Mosquito Mesh for Balcony | 70199090 | 18% |

**You can:**
- ✏️ Edit any HSN code
- ✏️ Change any GST percentage
- ➕ Add new products
- 🗑️ Delete products
- 💰 Set default rates

**Changes take effect immediately** - All new invoices will use updated settings.

### Step 3: Add Bank Details

Navigate to: **Billing Dashboard → Bank Details**

**Required for:**
- ✅ GST Invoices
- ✅ GST Estimations

**Bank Information:**
- Bank Name
- Account Holder Name
- Account Number
- IFSC Code
- Branch Name (optional)
- UPI ID (optional)
- Set as default account

You can add multiple bank accounts and set one as default.

### Step 4: Create Your First Invoice

Navigate to: **Billing Dashboard → Create Invoice (GST)**

## 📊 How to Create GST Invoice

### Customer Details
1. Enter customer name (required)
2. Add phone, email, address
3. Enter customer GSTIN (for GST compliance)
4. Select invoice date
5. Choose bank account (required)

### Adding Items
1. Click **"Add Item"** button
2. Select product from dropdown
3. **HSN Code & GST % auto-fill** ✨
4. Enter quantity
5. Enter rate per unit
6. **Amounts calculate automatically** ✨

### Automatic Calculations
- **Amount** = Quantity × Rate
- **CGST** = (Amount × GST%) ÷ 2
- **SGST** = (Amount × GST%) ÷ 2
- **Total** = Amount + CGST + SGST
- **Grand Total** = Sum of all items

### Amount in Words
Grand total is automatically converted to words in Indian format:
- Example: ₹12,500.00 → "Twelve Thousand Five Hundred Rupees Only"

### Save & Print
1. Add notes (optional)
2. Add terms & conditions (optional)
3. Click **"Save Invoice"**
4. Invoice is saved and ready to print

## 🎨 User Interface

### Modern Design Features
- 🎨 **Glass-morphism effects** - Modern frosted glass UI
- 🌈 **Gradient backgrounds** - Vibrant color schemes
- ✨ **Smooth animations** - Professional transitions
- 📱 **Fully responsive** - Works on all devices
- 🖱️ **Interactive hover effects** - Enhanced user experience

### Dashboard Statistics
- 📊 Total Invoices count
- 📊 Total Estimations count
- 📊 Warranty Cards count
- 💰 Total Revenue (from paid invoices)

### Quick Actions
- Fast access to all document types
- One-click invoice creation
- Easy product management
- Quick bank account setup

## 🔧 Product Management

### Edit Products
1. Go to **Products & GST** page
2. Click **Edit** button on any product
3. Modify:
   - Product name
   - HSN code
   - GST percentage
   - Default rate
   - Unit of measurement
4. Click **Save**

### Add New Product
1. Click **"Add New Product"**
2. Enter product details
3. Set HSN code (8 digits)
4. Select GST % (0%, 5%, 12%, 18%, 28%)
5. Set unit (sqft, unit, meter, kg, etc.)
6. Click **Save**

### Delete Product
1. Click **Delete** button
2. Confirm deletion
3. Product is deactivated (not permanently deleted)

## 🏦 Bank Account Management

### Add Bank Account
1. Go to **Bank Details** page
2. Click **"Add Bank Account"**
3. Enter:
   - Bank name
   - Account holder name
   - Account number
   - IFSC code
   - Branch name
   - UPI ID
4. Check "Set as default" if needed
5. Click **Save**

### Edit Bank Account
1. Click **Edit** on any bank card
2. Update details
3. Change default status if needed
4. Click **Save**

### Default Bank Account
- Only one account can be default
- Default account is pre-selected in invoices
- Marked with ⭐ badge

## 📝 Invoice Types

### 1. Estimation (Without GST)
- Simple quotation
- No tax calculations
- Basic pricing

### 2. Estimation with GST
- GST quotation
- CGST + SGST breakdown
- Bank details included
- Professional format

### 3. Cash Bill (Without GST)
- Simple cash receipt
- No GST details
- Quick billing

### 4. Invoice (Without GST)
- Tax invoice without GST
- Professional format
- Payment tracking

### 5. Invoice with GST ⭐ (Most Used)
- Complete GST compliance
- CGST + SGST calculation
- HSN codes included
- Bank details mandatory
- Amount in words
- Professional template

### 6. Warranty Card
- Product warranty certificate
- Warranty period
- Terms & conditions
- Professional design

## 💡 Tips & Best Practices

### GST Compliance
1. ✅ Always verify HSN codes are correct
2. ✅ Ensure GST percentages match government rates
3. ✅ Include customer GSTIN for B2B transactions
4. ✅ Add bank details to all GST invoices
5. ✅ Keep invoice numbering sequential

### Product Setup
1. 🎯 Set default rates for frequently used products
2. 🎯 Use consistent units (sqft for most products)
3. 🎯 Review and update HSN codes annually
4. 🎯 Keep product names clear and descriptive

### Invoice Creation
1. 📋 Double-check quantities and rates
2. 📋 Verify automatic calculations
3. 📋 Add clear notes for special terms
4. 📋 Include payment terms in T&C
5. 📋 Review before saving

### Bank Details
1. 🏦 Keep at least one bank account active
2. 🏦 Update UPI ID for digital payments
3. 🏦 Verify IFSC codes are correct
4. 🏦 Set most-used account as default

## 🔐 Security Features

- ✅ Admin authentication required
- ✅ Session management
- ✅ SQL injection prevention
- ✅ Input sanitization
- ✅ Secure database queries

## 📱 Responsive Design

Works perfectly on:
- 💻 Desktop (1920px+)
- 💻 Laptop (1366px - 1920px)
- 📱 Tablet (768px - 1366px)
- 📱 Mobile (320px - 768px)

## 🗂️ File Structure

```
admin/billing/
├── index.php                    # Billing Dashboard ✅
├── init-billing-db.php          # Database Initialization ✅
├── products.php                 # Products & GST Management ✅
├── bank-details.php             # Bank Account Management ✅
├── company-settings.php         # Company & Logo Management ✅
├── create-invoice-gst.php       # GST Invoice Creator ✅
├── create-estimation.php        # Estimation Creator ✅
├── create-estimation-gst.php    # GST Estimation Creator ✅
├── create-cash-bill.php         # Cash Bill Creator ✅
├── create-warranty.php          # Warranty Card Creator ✅
├── view-invoice.php             # Invoice Viewer (Coming Soon)
├── print-invoice.php            # Print Template (Coming Soon)
└── README.md                    # This file

config/
└── billing-schema.sql           # Database Schema ✅

uploads/
└── company-logos/               # Company logo uploads
```

## 🎯 Access Points

### Main Dashboard
```
http://localhost:8000/admin/billing/index.php
```

### Products Management
```
http://localhost:8000/admin/billing/products.php
```

### Bank Details
```
http://localhost:8000/admin/billing/bank-details.php
```

### Create GST Invoice
```
http://localhost:8000/admin/billing/create-invoice-gst.php
```

### Initialize Database (Run Once)
```
http://localhost:8000/admin/billing/init-billing-db.php
```

## 📊 Database Tables

### billing_products
- Product catalog with HSN and GST
- Editable at any time
- Default rates optional

### billing_bank_details
- Multiple bank accounts
- Default account selection
- UPI integration

### billing_invoices
- All invoice types
- Customer information
- Tax calculations
- Status tracking

### billing_invoice_items
- Line items for each invoice
- Product details
- Quantity, rate, amounts
- GST breakdown

### billing_company_settings
- Company information
- Invoice numbering
- Default terms & conditions
- Logo management

## 🚀 Completed Features ✅

- ✅ Create Estimation (without GST)
- ✅ Create Estimation with GST
- ✅ Create Cash Bill
- ✅ Create Invoice with GST
- ✅ Create Warranty Card
- ✅ Multi-Company Support
- ✅ Company Logo Upload
- ✅ Products & GST Management
- ✅ Bank Details Management
- ✅ Automatic Calculations
- ✅ Amount in Words (Indian Format)

## 🔮 Future Enhancements

- [ ] View/Edit existing invoices
- [ ] Print templates for all document types
- [ ] PDF generation
- [ ] Email invoices to customers
- [ ] Payment tracking
- [ ] Invoice history & reports
- [ ] Customer database
- [ ] Recurring invoices
- [ ] Multi-currency support

## ⚠️ Important Notes

1. **Run init-billing-db.php first** - This sets up all tables and default data
2. **HSN codes are editable** - Change them anytime in Products page
3. **GST rates are editable** - Update percentages as needed
4. **Bank details are mandatory** - Required for GST invoices
5. **Changes take effect immediately** - No restart needed
6. **Amount in words** - Automatically generated in Indian format
7. **Auto-calculations** - All amounts calculate automatically

## 🆘 Troubleshooting

### Database not initialized
- Run: `http://localhost:8000/admin/billing/init-billing-db.php`

### Products not showing
- Check if database is initialized
- Verify products table has data

### GST not calculating
- Ensure product has GST % set
- Check if quantity and rate are entered
- Verify JavaScript is enabled

### Bank details not saving
- Check all required fields are filled
- Verify IFSC code format

## 📞 Support

For issues or questions:
1. Check this README first
2. Verify database is initialized
3. Check browser console for errors
4. Review product and bank settings

## 🎉 Success!

Your billing system is now ready to use! Start by:
1. ✅ Running the database initialization
2. ✅ Reviewing pre-configured products
3. ✅ Adding your bank account
4. ✅ Creating your first GST invoice

Happy Billing! 🧾✨
