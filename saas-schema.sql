-- SaaS Platform Database Schema Extensions
-- Add these tables to support multi-tenant SaaS functionality

-- Subscription Plans table
CREATE TABLE subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(100) NOT NULL,
    plan_type VARCHAR(20) NOT NULL DEFAULT 'monthly', -- monthly, yearly
    price DECIMAL(10,2) NOT NULL,
    features TEXT, -- JSON array of features
    max_leads INT DEFAULT 100,
    max_quotes INT DEFAULT 50,
    max_invoices INT DEFAULT 25,
    max_users INT DEFAULT 5,
    storage_limit_gb INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Client Organizations (SaaS Tenants) table
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
    subscription_status VARCHAR(20) DEFAULT 'active', -- active, suspended, cancelled
    subscription_start_date DATE,
    subscription_end_date DATE,
    last_payment_date DATE,
    next_billing_date DATE,
    total_paid DECIMAL(10,2) DEFAULT 0,
    settings TEXT, -- JSON for org-specific settings
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_plan_id) REFERENCES subscription_plans(id)
);

-- Subscriptions and Billing table
CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    plan_id INT NOT NULL,
    cashfree_subscription_id VARCHAR(100),
    status VARCHAR(20) DEFAULT 'pending', -- pending, active, cancelled, expired
    current_period_start DATE,
    current_period_end DATE,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'INR',
    billing_cycle VARCHAR(20), -- monthly, yearly
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

-- Payment Transactions table
CREATE TABLE payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    subscription_id INT,
    cashfree_order_id VARCHAR(100),
    cashfree_payment_id VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'INR',
    status VARCHAR(20), -- success, failed, pending
    payment_method VARCHAR(50),
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    failure_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id)
);

-- Usage Analytics table
CREATE TABLE usage_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    metric_name VARCHAR(50) NOT NULL, -- leads_count, quotes_count, etc.
    metric_value INT NOT NULL,
    period_type VARCHAR(20) DEFAULT 'monthly', -- daily, weekly, monthly
    period_start DATE,
    period_end DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Add organization_id to existing tables for multi-tenancy
ALTER TABLE users ADD COLUMN organization_id INT DEFAULT NULL;
ALTER TABLE leads ADD COLUMN organization_id INT DEFAULT NULL;
ALTER TABLE quotes ADD COLUMN organization_id INT DEFAULT NULL;
ALTER TABLE invoices ADD COLUMN organization_id INT DEFAULT NULL;
ALTER TABLE expenses ADD COLUMN organization_id INT DEFAULT NULL;
ALTER TABLE communications ADD COLUMN organization_id INT DEFAULT NULL;
ALTER TABLE payments ADD COLUMN organization_id INT DEFAULT NULL;

-- Add foreign key constraints
ALTER TABLE users ADD FOREIGN KEY (organization_id) REFERENCES organizations(id);
ALTER TABLE leads ADD FOREIGN KEY (organization_id) REFERENCES organizations(id);
ALTER TABLE quotes ADD FOREIGN KEY (organization_id) REFERENCES organizations(id);
ALTER TABLE invoices ADD FOREIGN KEY (organization_id) REFERENCES organizations(id);
ALTER TABLE expenses ADD FOREIGN KEY (organization_id) REFERENCES organizations(id);
ALTER TABLE communications ADD FOREIGN KEY (organization_id) REFERENCES organizations(id);
ALTER TABLE payments ADD FOREIGN KEY (organization_id) REFERENCES organizations(id);

-- Insert default subscription plans
INSERT INTO subscription_plans (plan_name, plan_type, price, features, max_leads, max_quotes, max_invoices, max_users, storage_limit_gb) VALUES
('Starter Monthly', 'monthly', 999.00, '["Lead Management", "Quote Generation", "Basic Reports", "Email Support"]', 100, 50, 25, 3, 1),
('Professional Monthly', 'monthly', 1999.00, '["All Starter Features", "Invoice Management", "Advanced Analytics", "Phone Support", "Custom Branding"]', 500, 250, 100, 10, 5),
('Enterprise Monthly', 'monthly', 4999.00, '["All Professional Features", "Multi-user Access", "API Access", "Priority Support", "Custom Integrations"]', 2000, 1000, 500, 50, 20),
('Starter Yearly', 'yearly', 9999.00, '["Lead Management", "Quote Generation", "Basic Reports", "Email Support"]', 100, 50, 25, 3, 1),
('Professional Yearly', 'yearly', 19999.00, '["All Starter Features", "Invoice Management", "Advanced Analytics", "Phone Support", "Custom Branding"]', 500, 250, 100, 10, 5),
('Enterprise Yearly', 'yearly', 49999.00, '["All Professional Features", "Multi-user Access", "API Access", "Priority Support", "Custom Integrations"]', 2000, 1000, 500, 50, 20);

-- Add default settings for SaaS platform
INSERT INTO settings (setting_key, setting_value) VALUES
('saas_enabled', '1'),
('trial_period_days', '14'),
('cashfree_app_id', ''),
('cashfree_secret_key', ''),
('cashfree_environment', 'sandbox'),
('auto_suspend_days', '7'),
('grace_period_days', '3');
