-- Create services table for hero slider
CREATE TABLE IF NOT EXISTS services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_name TEXT NOT NULL,
    service_slug TEXT UNIQUE NOT NULL,
    icon_class TEXT,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    display_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Create offers table for homepage offers
CREATE TABLE IF NOT EXISTS offers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    subtitle TEXT,
    badge_text TEXT,
    discount_text TEXT,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    display_order INTEGER DEFAULT 0,
    valid_until DATE,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Insert services data
INSERT OR IGNORE INTO services (service_name, service_slug, icon_class, description, is_active, display_order) VALUES
('Pigeon Safety Nets', 'pigeon-nets', 'fas fa-dove', 'Professional pigeon netting solutions to keep your property clean and bird-free', 1, 1),
('Balcony Safety Nets', 'balcony-safety-nets', 'fas fa-shield-alt', 'High-quality balcony safety nets for children and pet protection', 1, 2),
('Invisible Grills', 'invisible-grills', 'fas fa-border-none', 'Modern invisible grills for balconies with unobstructed views', 1, 3),
('Sports Nets', 'sports-nets', 'fas fa-volleyball-ball', 'Durable sports netting for cricket, football, and other sports facilities', 1, 4),
('Children Safety Nets', 'children-safety-nets', 'fas fa-child', 'Child-safe netting solutions for balconies, windows, and staircases', 1, 5),
('Cloth Drying Hangers', 'cloth-hangers', 'fas fa-tshirt', 'Space-saving ceiling and pulley cloth drying hanger systems', 1, 6);

-- Insert offers data
INSERT OR IGNORE INTO offers (title, subtitle, badge_text, discount_text, description, is_active, display_order, valid_until) VALUES
('Limited Time Offer!', 'Professional Safety Net Installation', 'Special Deal', 'Get 15% OFF on all services', 'Free Inspection, Same Day Service, 5 Year Warranty', 1, 1, date('now', '+30 days')),
('New Customer Special', 'First-Time Installation Discount', 'New Offer', 'Flat ₹500 OFF', 'Quality Materials, Expert Installation, Money-Back Guarantee', 1, 2, date('now', '+60 days'));
