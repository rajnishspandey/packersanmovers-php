-- Business Management Database for PackersAnMovers
-- Additional tables for managing customers, labours, and transports

-- Customers table (separate from leads for repeat customers)
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(15) NOT NULL,
    alternate_phone VARCHAR(15),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    pincode VARCHAR(10),
    customer_type ENUM('individual', 'corporate') DEFAULT 'individual',
    company_name VARCHAR(100),
    gst_number VARCHAR(20),
    rating DECIMAL(2,1) DEFAULT 0,
    total_moves INT DEFAULT 0,
    notes TEXT,
    status ENUM('active', 'inactive', 'blacklisted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Labour/Workers table
CREATE TABLE labours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    alternate_phone VARCHAR(15),
    address TEXT,
    city VARCHAR(50),
    aadhar_number VARCHAR(12),
    pan_number VARCHAR(10),
    experience_years INT DEFAULT 0,
    specialization ENUM('packing', 'loading', 'driving', 'supervisor', 'general') DEFAULT 'general',
    daily_rate DECIMAL(8,2) DEFAULT 0,
    rating DECIMAL(2,1) DEFAULT 0,
    availability ENUM('available', 'busy', 'on_leave', 'inactive') DEFAULT 'available',
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(15),
    joining_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Vehicles/Transport table
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_number VARCHAR(20) UNIQUE NOT NULL,
    vehicle_type ENUM('truck', 'tempo', 'mini_truck', 'container', 'van') NOT NULL,
    capacity_tons DECIMAL(4,2),
    capacity_cubic_feet INT,
    owner_type ENUM('owned', 'rented', 'partner') NOT NULL,
    owner_name VARCHAR(100),
    owner_phone VARCHAR(15),
    driver_id INT,
    insurance_number VARCHAR(50),
    insurance_expiry DATE,
    permit_number VARCHAR(50),
    permit_expiry DATE,
    fitness_expiry DATE,
    daily_rate DECIMAL(8,2) DEFAULT 0,
    per_km_rate DECIMAL(6,2) DEFAULT 0,
    status ENUM('available', 'in_use', 'maintenance', 'inactive') DEFAULT 'available',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES labours(id)
);

-- Job assignments table (links jobs with resources)
CREATE TABLE job_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_id INT NOT NULL,
    assignment_date DATE NOT NULL,
    supervisor_id INT,
    vehicle_id INT,
    estimated_hours INT DEFAULT 8,
    actual_hours INT,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (quote_id) REFERENCES quotes(id) ON DELETE CASCADE,
    FOREIGN KEY (supervisor_id) REFERENCES labours(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

-- Job labour assignments (many-to-many)
CREATE TABLE job_labour_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_assignment_id INT NOT NULL,
    labour_id INT NOT NULL,
    role ENUM('packer', 'loader', 'helper', 'driver', 'supervisor') DEFAULT 'helper',
    hours_worked DECIMAL(4,2) DEFAULT 0,
    daily_rate DECIMAL(8,2),
    total_payment DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('pending', 'paid') DEFAULT 'pending',
    performance_rating DECIMAL(2,1),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_assignment_id) REFERENCES job_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (labour_id) REFERENCES labours(id)
);

-- Suppliers table (for packing materials, etc.)
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(100),
    address TEXT,
    city VARCHAR(50),
    gst_number VARCHAR(20),
    supplier_type ENUM('materials', 'transport', 'services', 'other') NOT NULL,
    payment_terms VARCHAR(100),
    rating DECIMAL(2,1) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inventory/Materials table
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    category ENUM('boxes', 'tape', 'bubble_wrap', 'tools', 'other') NOT NULL,
    unit ENUM('pieces', 'rolls', 'meters', 'kg', 'liters') NOT NULL,
    current_stock INT DEFAULT 0,
    minimum_stock INT DEFAULT 0,
    unit_cost DECIMAL(8,2) DEFAULT 0,
    supplier_id INT,
    location VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Stock movements table
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type ENUM('purchase', 'job', 'adjustment', 'return') NOT NULL,
    reference_id INT,
    unit_cost DECIMAL(8,2),
    total_cost DECIMAL(10,2),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Customer feedback table
CREATE TABLE customer_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    quote_id INT,
    rating DECIMAL(2,1) NOT NULL,
    service_rating DECIMAL(2,1),
    punctuality_rating DECIMAL(2,1),
    staff_behavior_rating DECIMAL(2,1),
    feedback_text TEXT,
    would_recommend ENUM('yes', 'no', 'maybe') DEFAULT 'yes',
    feedback_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (quote_id) REFERENCES quotes(id)
);

-- Update leads table to link with customers
ALTER TABLE leads ADD COLUMN customer_id INT AFTER id;
ALTER TABLE leads ADD FOREIGN KEY (customer_id) REFERENCES customers(id);