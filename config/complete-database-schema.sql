-- Complete Database Schema for GCM Netting Solutions
-- Creates all necessary tables for full website functionality

-- Blog Posts Table
CREATE TABLE IF NOT EXISTS blog_posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    excerpt TEXT,
    content TEXT NOT NULL,
    featured_image TEXT,
    category TEXT,
    tags TEXT,
    author TEXT DEFAULT 'GCM Netting Solutions',
    is_published INTEGER DEFAULT 1,
    views INTEGER DEFAULT 0,
    published_at TEXT DEFAULT CURRENT_TIMESTAMP,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- FAQs Table
CREATE TABLE IF NOT EXISTS faqs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    category TEXT,
    display_order INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    views INTEGER DEFAULT 0,
    helpful_count INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Reviews Table (for database storage)
CREATE TABLE IF NOT EXISTS reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT NOT NULL,
    customer_email TEXT,
    customer_phone TEXT,
    service_type TEXT,
    rating INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
    review_text TEXT NOT NULL,
    location TEXT,
    is_verified INTEGER DEFAULT 0,
    is_featured INTEGER DEFAULT 0,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'rejected')),
    admin_notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    approved_at TEXT,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Gallery Images Table
CREATE TABLE IF NOT EXISTS gallery_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    image_path TEXT NOT NULL,
    thumbnail_path TEXT,
    category TEXT,
    service_type TEXT,
    location TEXT,
    display_order INTEGER DEFAULT 0,
    is_featured INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    views INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Videos Table
CREATE TABLE IF NOT EXISTS videos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    video_url TEXT NOT NULL,
    thumbnail_url TEXT,
    video_type TEXT DEFAULT 'youtube' CHECK(video_type IN ('youtube', 'vimeo', 'local')),
    category TEXT,
    service_type TEXT,
    duration TEXT,
    display_order INTEGER DEFAULT 0,
    is_featured INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    views INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Contact Form Submissions Table
CREATE TABLE IF NOT EXISTS contact_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT,
    phone TEXT NOT NULL,
    service TEXT,
    message TEXT,
    source_page TEXT,
    ip_address TEXT,
    user_agent TEXT,
    status TEXT DEFAULT 'new' CHECK(status IN ('new', 'contacted', 'converted', 'closed')),
    admin_notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Newsletter Subscribers Table
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    name TEXT,
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'unsubscribed')),
    subscribed_at TEXT DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TEXT,
    ip_address TEXT
);

-- Service Areas Table
CREATE TABLE IF NOT EXISTS service_areas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    area_name TEXT NOT NULL,
    area_slug TEXT UNIQUE NOT NULL,
    pincode TEXT,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    display_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Visitor Analytics Table
CREATE TABLE IF NOT EXISTS visitor_analytics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id TEXT,
    page_url TEXT,
    referrer TEXT,
    ip_address TEXT,
    user_agent TEXT,
    device_type TEXT,
    browser TEXT,
    os TEXT,
    country TEXT,
    city TEXT,
    visit_duration INTEGER DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Page Views Table
CREATE TABLE IF NOT EXISTS page_views (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_url TEXT NOT NULL,
    view_date DATE NOT NULL,
    view_count INTEGER DEFAULT 1,
    unique_visitors INTEGER DEFAULT 1,
    UNIQUE(page_url, view_date)
);

-- Insert sample blog posts
INSERT OR IGNORE INTO blog_posts (title, slug, excerpt, content, category, tags, is_published) VALUES
('Top 5 Benefits of Installing Safety Nets in Your Home', 'top-5-benefits-safety-nets', 'Discover why safety nets are essential for every home in Chennai', 'Safety nets have become an essential feature for modern homes, especially in high-rise buildings. Here are the top 5 benefits: 1. Child Safety - Prevents accidents from balconies and windows. 2. Pet Protection - Keeps your pets safe from falling. 3. Pigeon Control - Stops birds from nesting and creating mess. 4. Privacy - Adds an extra layer of privacy. 5. Peace of Mind - Sleep better knowing your family is protected.', 'Safety Tips', 'safety nets, home safety, child protection', 1),
('How to Choose the Right Pigeon Net for Your Balcony', 'choose-right-pigeon-net', 'Complete guide to selecting the perfect pigeon netting solution', 'Choosing the right pigeon net involves considering several factors: mesh size, material quality, UV resistance, color options, and installation method. HDPE nets are the most durable and weather-resistant option available in the market.', 'Product Guide', 'pigeon nets, balcony nets, bird control', 1),
('Invisible Grills vs Traditional Grills: Which is Better?', 'invisible-grills-vs-traditional-grills', 'Compare modern invisible grills with traditional window grills', 'Invisible grills offer unobstructed views while providing the same level of security as traditional grills. They are made of high-tensile steel cables that are virtually invisible from a distance, making them perfect for modern apartments.', 'Comparison', 'invisible grills, window grills, home security', 1);

-- Insert sample FAQs
INSERT OR IGNORE INTO faqs (question, answer, category, display_order, is_active) VALUES
('How long do safety nets last?', 'Our premium HDPE safety nets come with a 5-year warranty and typically last 7-10 years with proper maintenance. They are UV-stabilized and weather-resistant.', 'General', 1, 1),
('Do you provide installation services?', 'Yes, we provide professional installation services across all areas of Chennai. Our expert team ensures perfect fitting and secure installation.', 'Installation', 2, 1),
('What is the cost of pigeon net installation?', 'The cost varies based on area size, net quality, and installation complexity. Typically ranges from ₹25-45 per sq.ft. Contact us for a free inspection and accurate quote.', 'Pricing', 3, 1),
('Are invisible grills safe for children?', 'Absolutely! Invisible grills are made of high-tensile steel cables that can withstand significant force. They meet all safety standards and are specifically designed for child safety.', 'Safety', 4, 1),
('How soon can you complete the installation?', 'For standard installations, we can complete the work within 2-4 hours. We also offer same-day service for urgent requirements across Chennai.', 'Installation', 5, 1),
('Do you offer warranty on your products?', 'Yes, all our products come with a comprehensive 5-year warranty covering manufacturing defects and material quality. Installation warranty is also included.', 'Warranty', 6, 1),
('Can safety nets withstand heavy rain and wind?', 'Yes, our HDPE safety nets are specifically designed to withstand harsh weather conditions including heavy rain, strong winds, and extreme temperatures.', 'Product Quality', 7, 1),
('Do you service all areas of Chennai?', 'Yes, we provide services across all 188+ areas of Chennai including Tambaram, Gachibowli, Hitech City, Banjara Hills, and surrounding localities.', 'Service Area', 8, 1);

-- Insert sample service areas
INSERT OR IGNORE INTO service_areas (area_name, area_slug, pincode, is_active, display_order) VALUES
('Gachibowli', 'gachibowli', '500032', 1, 1),
('Hitech City', 'hitech-city', '500081', 1, 2),
('Banjara Hills', 'banjara-hills', '500034', 1, 3),
('Jubilee Hills', 'jubilee-hills', '500033', 1, 4),
('Madhapur', 'madhapur', '500081', 1, 5),
('Kukatpally', 'kukatpally', '500072', 1, 6),
('Miyapur', 'miyapur', '500049', 1, 7),
('Kondapur', 'kondapur', '500084', 1, 8),
('Tambaram', 'secunderabad', '500003', 1, 9),
('Begumpet', 'begumpet', '600002', 1, 10);

-- Insert sample videos
INSERT OR IGNORE INTO videos (title, description, video_url, video_type, category, service_type, is_featured, is_active) VALUES
('Pigeon Net Installation Process', 'Watch how our expert team installs pigeon nets professionally', 'https://www.youtube.com/watch?v=example1', 'youtube', 'Installation', 'Pigeon Nets', 1, 1),
('Safety Net Installation for Balcony', 'Complete guide to balcony safety net installation', 'https://www.youtube.com/watch?v=example2', 'youtube', 'Installation', 'Safety Nets', 1, 1),
('Invisible Grill Installation Demo', 'See how invisible grills are installed without drilling', 'https://www.youtube.com/watch?v=example3', 'youtube', 'Installation', 'Invisible Grills', 1, 1);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_blog_slug ON blog_posts(slug);
CREATE INDEX IF NOT EXISTS idx_blog_published ON blog_posts(is_published, published_at);
CREATE INDEX IF NOT EXISTS idx_faq_category ON faqs(category, display_order);
CREATE INDEX IF NOT EXISTS idx_reviews_status ON reviews(status, created_at);
CREATE INDEX IF NOT EXISTS idx_gallery_category ON gallery_images(category, is_active);
CREATE INDEX IF NOT EXISTS idx_videos_category ON videos(category, is_active);
CREATE INDEX IF NOT EXISTS idx_contact_status ON contact_submissions(status, created_at);
CREATE INDEX IF NOT EXISTS idx_page_views_url ON page_views(page_url, view_date);
