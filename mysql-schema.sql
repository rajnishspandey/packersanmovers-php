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

-- Insert default settings (empty for configuration)
INSERT INTO settings (setting_key, setting_value) VALUES
('company_name', ''),
('company_address', ''),
('company_phone1', ''),
('company_phone2', ''),
('company_email', ''),
('company_website', ''),
('company_tagline', ''),
('gst_number', ''),
('facebook_url', ''),
('twitter_url', ''),
('instagram_url', ''),
('linkedin_url', ''),
('website_name', ''),
('website_url', ''),
('support_emails', ''),
('mail_host', ''),
('mail_port', '587'),
('mail_username', ''),
('mail_password', ''),
('mail_from', '');