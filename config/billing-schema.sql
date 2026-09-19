-- Billing System Database Schema

-- Products Table
CREATE TABLE IF NOT EXISTS billing_products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_name VARCHAR(255) NOT NULL,
    hsn_code VARCHAR(50) NOT NULL,
    gst_percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
    default_rate DECIMAL(10,2) DEFAULT 0,
    unit VARCHAR(50) DEFAULT 'sqft',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Bank Details Table
CREATE TABLE IF NOT EXISTS billing_bank_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bank_name VARCHAR(255) NOT NULL,
    account_number VARCHAR(100) NOT NULL,
    ifsc_code VARCHAR(50) NOT NULL,
    branch_name VARCHAR(255),
    account_holder_name VARCHAR(255),
    upi_id VARCHAR(100),
    is_default TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Bills/Invoices Table
CREATE TABLE IF NOT EXISTS billing_invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_number VARCHAR(100) NOT NULL UNIQUE,
    invoice_type VARCHAR(50) NOT NULL, -- estimation, cash_bill, invoice, estimation_gst, invoice_gst, warranty
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20),
    customer_email VARCHAR(255),
    customer_address TEXT,
    customer_gstin VARCHAR(50),
    invoice_date DATE NOT NULL,
    due_date DATE,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    cgst_amount DECIMAL(12,2) DEFAULT 0,
    sgst_amount DECIMAL(12,2) DEFAULT 0,
    igst_amount DECIMAL(12,2) DEFAULT 0,
    total_gst DECIMAL(12,2) DEFAULT 0,
    grand_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes TEXT,
    terms_conditions TEXT,
    bank_details_id INTEGER,
    warranty_period VARCHAR(100),
    warranty_terms TEXT,
    status VARCHAR(50) DEFAULT 'draft', -- draft, sent, paid, cancelled
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bank_details_id) REFERENCES billing_bank_details(id)
);

-- Invoice Items Table
CREATE TABLE IF NOT EXISTS billing_invoice_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER NOT NULL,
    product_id INTEGER,
    product_name VARCHAR(255) NOT NULL,
    hsn_code VARCHAR(50),
    description TEXT,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(50) DEFAULT 'sqft',
    rate DECIMAL(10,2) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    gst_percentage DECIMAL(5,2) DEFAULT 0,
    cgst_amount DECIMAL(12,2) DEFAULT 0,
    sgst_amount DECIMAL(12,2) DEFAULT 0,
    igst_amount DECIMAL(12,2) DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES billing_invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES billing_products(id)
);

-- Company Settings Table
CREATE TABLE IF NOT EXISTS billing_company_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    company_name VARCHAR(255) NOT NULL,
    company_address TEXT,
    company_phone VARCHAR(20),
    company_email VARCHAR(255),
    company_gstin VARCHAR(50),
    company_pan VARCHAR(50),
    company_logo_path VARCHAR(500),
    invoice_prefix VARCHAR(20) DEFAULT 'INV',
    estimation_prefix VARCHAR(20) DEFAULT 'EST',
    warranty_prefix VARCHAR(20) DEFAULT 'WAR',
    next_invoice_number INTEGER DEFAULT 1,
    next_estimation_number INTEGER DEFAULT 1,
    next_warranty_number INTEGER DEFAULT 1,
    default_terms_conditions TEXT,
    default_warranty_terms TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert default products (exactly as specified by user)
INSERT OR IGNORE INTO billing_products (product_name, hsn_code, gst_percentage, unit) VALUES
('Anti Bird Net', '39269099', 5, 'sqft'),
('Sports/Cricket Net', '56069990', 5, 'sqft'),
('Safety Net', '56089090', 5, 'sqft'),
('Artificial Grass', '57033100', 5, 'sqft'),
('Artificial Cricket Pitch Turf', '57033100', 5, 'sqft'),
('Invisible Grill', '73144990', 18, 'sqft'),
('Cloth Hanger', '73262090', 18, 'unit'),
('Mosquito Mesh Velcro Type', '70199090', 18, 'sqft'),
('Mosquito Mesh for Balcony', '70199090', 18, 'sqft');

-- Insert default company settings
INSERT OR IGNORE INTO billing_company_settings (
    id,
    company_name, 
    company_address, 
    company_phone, 
    company_email,
    invoice_prefix,
    estimation_prefix,
    warranty_prefix
) VALUES (
    1,
    'GCM Netting Solutions',
    'Chennai, Tamil Nadu',
    '',
    '',
    'INV',
    'EST',
    'WAR'
);
