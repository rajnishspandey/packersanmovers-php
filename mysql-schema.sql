-- PackersAnMovers MySQL Database Schema
-- Run this script to create the database structure

-- Create database (run separately if needed)
-- CREATE DATABASE packersanmovers_prod;
-- CREATE DATABASE packersanmovers_dev;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'viewer',
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Roles table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(20) UNIQUE NOT NULL,
    role_label VARCHAR(50) NOT NULL,
    permissions TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Leads table
CREATE TABLE leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(15) NOT NULL,
    move_from VARCHAR(200) NOT NULL,
    move_to VARCHAR(200) NOT NULL,
    move_date DATE NOT NULL,
    home_size VARCHAR(50) NOT NULL,
    additional_services TEXT,
    notes TEXT,
    status VARCHAR(50) DEFAULT 'inquiry',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quotes table
CREATE TABLE quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT,
    quote_number VARCHAR(50) UNIQUE,
    secure_token VARCHAR(64) UNIQUE,
    items TEXT,
    subtotal DECIMAL(10,2),
    service_rate DECIMAL(5,2) DEFAULT 0,
    service_amount DECIMAL(10,2) DEFAULT 0,
    tax_rate DECIMAL(5,2) DEFAULT 18.00,
    tax_amount DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    tax_enabled TINYINT(1) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'draft',
    valid_until DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Invoices table
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT,
    invoice_number VARCHAR(50) UNIQUE,
    secure_token VARCHAR(64) UNIQUE,
    parent_invoice_id INT DEFAULT NULL,
    invoice_type VARCHAR(20) DEFAULT 'original',
    items TEXT,
    subtotal DECIMAL(10,2),
    service_rate DECIMAL(5,2) DEFAULT 0,
    service_amount DECIMAL(10,2) DEFAULT 0,
    tax_rate DECIMAL(5,2),
    tax_amount DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    tax_enabled TINYINT(1) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'pending',
    due_date DATE,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_invoice_id) REFERENCES invoices(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Expenses table
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT,
    category VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    receipt_file VARCHAR(255),
    expense_date DATE NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Communications table
CREATE TABLE communications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT,
    type VARCHAR(20) NOT NULL,
    subject VARCHAR(200),
    message TEXT,
    status VARCHAR(20) DEFAULT 'sent',
    sent_by INT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (sent_by) REFERENCES users(id)
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method VARCHAR(20) DEFAULT 'cash',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Status configurations table
CREATE TABLE statuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(20) NOT NULL,
    status_key VARCHAR(50) NOT NULL,
    status_label VARCHAR(100) NOT NULL,
    status_color VARCHAR(20) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
);

-- Settings table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT INTO roles (role_name, role_label, permissions) VALUES
('admin', 'Administrator', '{"leads":"crud","quotes":"crud","invoices":"crud","users":"crud","analytics":"read"}'),
('manager', 'Manager', '{"leads":"crud","quotes":"crud","invoices":"crud","users":"read","analytics":"read"}'),
('sales', 'Sales Executive', '{"leads":"crud","quotes":"crud","invoices":"read","users":"none","analytics":"read"}'),
('accounts', 'Accounts', '{"leads":"read","quotes":"read","invoices":"crud","users":"none","analytics":"read"}'),
('viewer', 'Viewer', '{"leads":"read","quotes":"read","invoices":"read","users":"none","analytics":"read"}');

-- Insert default lead statuses
INSERT INTO statuses (entity_type, status_key, status_label, status_color, sort_order) VALUES
('lead', 'inquiry', 'New Inquiry', 'primary', 1),
('lead', 'survey_scheduled', 'Survey Scheduled', 'info', 2),
('lead', 'survey_done', 'Survey Done', 'warning', 3),
('lead', 'draft', 'Draft', 'secondary', 4),
('lead', 'cancelled', 'Cancelled', 'danger', 5);

-- Insert default quote statuses
INSERT INTO statuses (entity_type, status_key, status_label, status_color, sort_order) VALUES
('quote', 'draft', 'Draft', 'secondary', 1),
('quote', 'sent', 'Sent', 'primary', 2),
('quote', 'approved', 'Approved', 'success', 3),
('quote', 'rejected', 'Rejected', 'danger', 4),
('quote', 'expired', 'Expired', 'warning', 5),
('quote', 'abandoned', 'Abandoned', 'secondary', 6);

-- Insert default invoice statuses
INSERT INTO statuses (entity_type, status_key, status_label, status_color, sort_order) VALUES
('invoice', 'pending', 'Pending', 'warning', 1),
('invoice', 'partial', 'Partially Paid', 'info', 2),
('invoice', 'paid', 'Paid', 'success', 3),
('invoice', 'overdue', 'Overdue', 'danger', 4),
('invoice', 'abandoned', 'Abandoned', 'secondary', 5);

-- Insert default admin user (password: admin)
INSERT INTO users (username, email, password_hash, role, is_admin) VALUES
('admin', 'admin@packersanmovers.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- User Tokens table for temporary access
CREATE TABLE user_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    token_type VARCHAR(50) NOT NULL DEFAULT 'trial_access',
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Cashfree Orders table for payment tracking
CREATE TABLE cashfree_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(100) UNIQUE NOT NULL,
    cf_order_id VARCHAR(100),
    customer_email VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'INR',
    status VARCHAR(20) DEFAULT 'PENDING',
    payment_method VARCHAR(50),
    cf_payment_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Payment Refunds table
CREATE TABLE payment_refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(100) NOT NULL,
    refund_id VARCHAR(100) UNIQUE NOT NULL,
    cf_refund_id VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'PENDING',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES cashfree_orders(order_id) ON DELETE CASCADE
);

-- Subscription Plans table for SaaS
CREATE TABLE subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(100) NOT NULL,
    plan_type VARCHAR(20) NOT NULL DEFAULT 'monthly',
    price DECIMAL(10,2) NOT NULL,
    features JSON,
    max_leads INT DEFAULT 100,
    max_quotes INT DEFAULT 50,
    max_invoices INT DEFAULT 25,
    max_users INT DEFAULT 5,
    storage_limit_gb INT DEFAULT 1,
    is_popular TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Organizations table for SaaS multi-tenancy
CREATE TABLE organizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    org_name VARCHAR(200) NOT NULL,
    org_slug VARCHAR(100) UNIQUE NOT NULL,
    domain VARCHAR(100),
    owner_email VARCHAR(100) NOT NULL,
    owner_phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    country VARCHAR(50) DEFAULT 'India',
    subscription_plan_id INT,
    subscription_status VARCHAR(20) DEFAULT 'active',
    subscription_start_date DATE,
    subscription_end_date DATE,
    trial_end_date DATE,
    last_payment_date DATE,
    next_billing_date DATE,
    total_paid DECIMAL(10,2) DEFAULT 0,
    settings JSON,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_plan_id) REFERENCES subscription_plans(id)
);

-- Subscription transactions table
CREATE TABLE subscription_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    subscription_id VARCHAR(100),
    cf_subscription_id VARCHAR(100),
    plan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'INR',
    billing_cycle VARCHAR(20),
    status VARCHAR(20) DEFAULT 'pending',
    payment_date DATE,
    next_billing_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

-- Webhook Events table for tracking
CREATE TABLE webhook_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_data JSON,
    order_id VARCHAR(100),
    subscription_id VARCHAR(100),
    processed TINYINT(1) DEFAULT 0,
    processing_attempts INT DEFAULT 0,
    error_message TEXT,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL
);

-- Add organization_id to existing tables for multi-tenancy
ALTER TABLE users ADD COLUMN organization_id INT DEFAULT NULL;
ALTER TABLE leads ADD COLUMN organization_id INT DEFAULT NULL;
ALTER TABLE quotes ADD COLUMN organization_id INT DEFAULT NULL;
ALTER TABLE invoices ADD COLUMN organization_id INT DEFAULT NULL;
ALTER TABLE expenses ADD COLUMN organization_id INT DEFAULT NULL;

-- Add foreign key constraints
ALTER TABLE users ADD FOREIGN KEY (organization_id) REFERENCES organizations(id);
ALTER TABLE leads ADD FOREIGN KEY (organization_id) REFERENCES organizations(id);
ALTER TABLE quotes ADD FOREIGN KEY (organization_id) REFERENCES organizations(id);
ALTER TABLE invoices ADD FOREIGN KEY (organization_id) REFERENCES organizations(id);
ALTER TABLE expenses ADD FOREIGN KEY (organization_id) REFERENCES organizations(id);

-- Insert default subscription plans
INSERT INTO subscription_plans (plan_name, plan_type, price, features, max_leads, max_quotes, max_invoices, max_users, storage_limit_gb, is_popular) VALUES
('Starter Monthly', 'monthly', 999.00, '["Lead Management", "Quote Generation", "Basic Reports", "Email Support"]', 100, 50, 25, 3, 1, 0),
('Professional Monthly', 'monthly', 1999.00, '["All Starter Features", "Invoice Management", "Advanced Analytics", "Phone Support", "Custom Branding"]', 500, 250, 100, 10, 5, 1),
('Enterprise Monthly', 'monthly', 4999.00, '["All Professional Features", "Multi-user Access", "API Access", "Priority Support", "Custom Integrations"]', 2000, 1000, 500, 50, 20, 0),
('Starter Yearly', 'yearly', 9999.00, '["Lead Management", "Quote Generation", "Basic Reports", "Email Support", "2 Months Free"]', 100, 50, 25, 3, 1, 0),
('Professional Yearly', 'yearly', 19999.00, '["All Starter Features", "Invoice Management", "Advanced Analytics", "Phone Support", "Custom Branding", "2 Months Free"]', 500, 250, 100, 10, 5, 1),
('Enterprise Yearly', 'yearly', 49999.00, '["All Professional Features", "Multi-user Access", "API Access", "Priority Support", "Custom Integrations", "2 Months Free"]', 2000, 1000, 500, 50, 20, 0);

-- Insert default settings with static contact information
INSERT INTO settings (setting_key, setting_value) VALUES
('company_name', 'PackersAnMovers'),
('company_address', 'Shop No. 04, Vrindavan Society Shankara nagar, Dombivli East, Thane, Maharashtra 421203'),
('company_phone1', '+91 7710020974'),
('company_phone2', '+91 7710020975'),
('company_email', 'support@packersanmovers.com'),
('company_website', 'https://www.packersanmovers.com'),
('company_tagline', 'Professional Moving & Packing Services'),
('gst_number', ''),
('facebook_url', ''),
('twitter_url', ''),
('instagram_url', ''),
('linkedin_url', ''),
('website_name', 'PackersAnMovers'),
('website_url', 'https://www.packersanmovers.com'),
('support_emails', 'support@packersanmovers.com'),
('mail_host', ''),
('mail_port', '587'),
('mail_username', ''),
('mail_password', ''),
('mail_from', 'support@packersanmovers.com'),
('saas_enabled', '1'),
('trial_period_days', '14'),
('cashfree_client_id', 'CF_PLACEHOLDER_CLIENT_ID'),
('cashfree_client_secret', 'CF_PLACEHOLDER_SECRET'),
('cashfree_environment', 'sandbox');
