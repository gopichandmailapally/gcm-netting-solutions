-- Contact Inquiries Table for Website Forms
-- Run this SQL to create the contact inquiries table

USE gcm_safety_nets;

CREATE TABLE IF NOT EXISTS contact_inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    service VARCHAR(100),
    area VARCHAR(100),
    message TEXT,
    phone_verified BOOLEAN DEFAULT FALSE,
    phone_verified_at DATETIME,
    status ENUM('new', 'contacted', 'quoted', 'in_progress', 'converted', 'closed', 'spam') DEFAULT 'new',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    assigned_to INT,
    notes TEXT,
    follow_up_date DATE,
    source VARCHAR(50) DEFAULT 'website',
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_phone (phone),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_phone_verified (phone_verified),
    INDEX idx_created_at (created_at),
    INDEX idx_follow_up_date (follow_up_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create inquiry responses table for tracking communication
CREATE TABLE IF NOT EXISTS inquiry_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inquiry_id INT NOT NULL,
    response_type ENUM('call', 'sms', 'email', 'whatsapp', 'site_visit', 'quote_sent') NOT NULL,
    response_text TEXT,
    responded_by INT,
    response_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inquiry_id) REFERENCES contact_inquiries(id) ON DELETE CASCADE,
    INDEX idx_inquiry_id (inquiry_id),
    INDEX idx_response_date (response_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create inquiry attachments table for quotes/documents
CREATE TABLE IF NOT EXISTS inquiry_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inquiry_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inquiry_id) REFERENCES contact_inquiries(id) ON DELETE CASCADE,
    INDEX idx_inquiry_id (inquiry_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for testing
INSERT INTO contact_inquiries (name, phone, email, service, area, message, phone_verified, status) VALUES
('Test Customer 1', '9876543210', 'test1@example.com', 'pigeon-nets', 'Kukatpally', 'Need pigeon nets for balcony', TRUE, 'new'),
('Test Customer 2', '9876543211', 'test2@example.com', 'safety-nets', 'Gachibowli', 'Safety nets for construction site', TRUE, 'contacted');

-- Create view for dashboard statistics
CREATE OR REPLACE VIEW inquiry_statistics AS
SELECT 
    DATE(created_at) as inquiry_date,
    COUNT(*) as total_inquiries,
    SUM(CASE WHEN phone_verified = 1 THEN 1 ELSE 0 END) as verified_inquiries,
    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_inquiries,
    SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) as contacted_inquiries,
    SUM(CASE WHEN status = 'converted' THEN 1 ELSE 0 END) as converted_inquiries
FROM contact_inquiries
GROUP BY DATE(created_at)
ORDER BY inquiry_date DESC;

-- Create stored procedure for inquiry follow-up
DELIMITER //

CREATE PROCEDURE get_pending_followups()
BEGIN
    SELECT 
        ci.*,
        DATEDIFF(follow_up_date, CURDATE()) as days_until_followup
    FROM contact_inquiries ci
    WHERE status NOT IN ('converted', 'closed', 'spam')
    AND follow_up_date IS NOT NULL
    AND follow_up_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ORDER BY follow_up_date ASC;
END //

DELIMITER ;

-- Grant permissions (adjust username as needed)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON gcm_safety_nets.contact_inquiries TO 'your_db_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON gcm_safety_nets.inquiry_responses TO 'your_db_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON gcm_safety_nets.inquiry_attachments TO 'your_db_user'@'localhost';
