-- =====================================================
-- LIBRARY MANAGEMENT SYSTEM - DATABASE SCHEMA
-- =====================================================

CREATE DATABASE IF NOT EXISTS library_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE library_db;

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'librarian', 'member') NOT NULL DEFAULT 'member',
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT,
    membership_validity DATE DEFAULT NULL,
    borrow_limit INT DEFAULT 5,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- CATEGORIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- BOOKS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    isbn VARCHAR(20) UNIQUE,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(200) NOT NULL,
    publisher VARCHAR(200),
    category_id INT,
    type ENUM('book', 'magazine', 'newspaper') DEFAULT 'book',
    issuable ENUM('yes', 'no') DEFAULT 'yes',
    description TEXT,
    shelf_location VARCHAR(50),
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    price DECIMAL(10,2) DEFAULT 0.00,
    pages INT,
    language VARCHAR(50) DEFAULT 'English',
    cover_image VARCHAR(255) DEFAULT NULL,
    qr_code VARCHAR(255) DEFAULT NULL,
    status ENUM('available', 'reserved', 'maintenance', 'lost') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- =====================================================
-- TRANSACTIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    issued_by INT,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    renewal_count INT DEFAULT 0,
    status ENUM('issued', 'returned', 'overdue', 'lost') DEFAULT 'issued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- FINES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS fines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_id INT NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    is_paid BOOLEAN DEFAULT FALSE,
    payment_date DATE DEFAULT NULL,
    payment_method VARCHAR(50) DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- NOTIFICATIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- RESERVATIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    reserved_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('active', 'fulfilled', 'cancelled', 'expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- VENDORS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS vendors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- PROCUREMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS procurements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    book_title VARCHAR(255) NOT NULL,
    author VARCHAR(200),
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    order_date DATE,
    received_date DATE DEFAULT NULL,
    status ENUM('ordered', 'received', 'cancelled') DEFAULT 'ordered',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
);

-- =====================================================
-- MAINTENANCE LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    issue_type ENUM('damaged', 'lost', 'missing_pages', 'binding_loose', 'cleaning', 'other') NOT NULL,
    description TEXT,
    reported_by INT,
    reported_date DATE NOT NULL,
    resolved_date DATE DEFAULT NULL,
    cost DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('open', 'in_progress', 'resolved') DEFAULT 'open',
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- SETTINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- ACTIVITY LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- SAMPLE DATA INSERTION
-- =====================================================

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('library_name', 'Nexus Library', 'Name of the library'),
('library_address', '123 Knowledge Avenue, Tech City, TC 12345', 'Library address'),
('library_phone', '+1 234 567 8900', 'Library contact number'),
('library_email', 'info@nexuslibrary.com', 'Library email'),
('borrow_limit_member', '5', 'Maximum books a member can borrow'),
('borrow_limit_librarian', '10', 'Maximum books a librarian can borrow'),
('borrow_days_member', '14', 'Days a member can borrow'),
('borrow_days_librarian', '30', 'Days a librarian can borrow'),
('fine_per_day', '1.00', 'Fine amount per day after due date'),
('grace_period_days', '7', 'Grace period before fines apply'),
('late_fee_cap', '50.00', 'Maximum late fee cap');

-- Insert categories
INSERT INTO categories (name, description) VALUES
('Fiction', 'Novels, short stories, and other fictional works'),
('Non-Fiction', 'Biographies, histories, and factual books'),
('Science', 'Physics, chemistry, biology, and other sciences'),
('Technology', 'Computing, engineering, and tech books'),
('History', 'Historical events and civilizations'),
('Philosophy', 'Philosophical works and thought'),
('Art', 'Art, photography, and design'),
('Business', 'Business, economics, and management'),
('Children', 'Children literature and picture books'),
('Reference', 'Dictionaries, encyclopedias, and reference materials');

-- Insert admin user (password: admin123)
INSERT INTO users (name, email, password, role, phone, address, membership_validity, borrow_limit, status) VALUES
('Alex Rivera', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '+1 555 0100', 'Admin Quarters, Library Building', '2027-12-31', 50, 'active'),
('Jordan Smith', 'librarian@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'librarian', '+1 555 0101', 'Staff Quarters, Library Building', '2026-12-31', 10, 'active'),
('Casey Williams', 'member1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', '+1 555 0102', '123 Reader Lane, Bookworm Street', '2026-06-15', 5, 'active'),
('Morgan Taylor', 'member2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', '+1 555 0103', '456 Study Avenue, Scholar Town', '2026-08-20', 5, 'active'),
('Taylor Brown', 'member3@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', '+1 555 0104', '789 Library Circle, Academic Heights', '2026-09-10', 5, 'active');

-- Insert sample books
INSERT INTO books (isbn, title, author, publisher, category_id, type, description, shelf_location, total_copies, available_copies, pages, language) VALUES
('978-0-7475-3269-9', 'Harry Potter and the Philosophers Stone', 'J.K. Rowling', 'Bloomsbury', 1, 'book', 'The magical journey of a young wizard begins', 'A-101', 5, 4, 223, 'English'),
('978-0-06-112008-4', 'To Kill a Mockingbird', 'Harper Lee', 'J.B. Lippincott & Co.', 1, 'book', 'A classic tale of racial injustice and childhood innocence', 'A-102', 3, 2, 281, 'English'),
('978-0-7432-7356-5', '1984', 'George Orwell', 'Secker & Warburg', 1, 'book', 'A dystopian social science fiction novel', 'A-103', 4, 3, 328, 'English'),
('978-0-316-76948-0', 'The Catcher in the Rye', 'J.D. Salinger', 'Little, Brown and Company', 1, 'book', 'The story of teenage angst and alienation', 'A-104', 2, 2, 234, 'English'),
('978-0-452-28423-4', 'Brave New World', 'Aldous Huxley', 'Chatto & Windus', 1, 'book', 'A vision of future society controlled by technology', 'A-105', 3, 2, 288, 'English'),
('978-0-14-028329-7', 'The Great Gatsby', 'F. Scott Fitzgerald', 'Charles Scribners Sons', 1, 'book', 'A tale of decadence and excess in the Jazz Age', 'A-106', 4, 4, 180, 'English'),
('978-0-679-72020-1', 'One Hundred Years of Solitude', 'Gabriel Garcia Marquez', 'Harper & Row', 1, 'book', 'The multi-generational story of the Buendia family', 'A-107', 2, 1, 417, 'English'),
('978-0-375-42578-4', 'The Lord of the Rings', 'J.R.R. Tolkien', 'George Allen & Unwin', 1, 'book', 'An epic high-fantasy adventure', 'A-108', 6, 5, 1178, 'English'),
('978-0-06-093546-7', 'To Kill a Mockingbird', 'Harper Lee', 'Grand Central Publishing', 1, 'book', 'Classic American literature', 'A-102', 2, 2, 324, 'English'),
('978-0-13-468599-1', 'The Pragmatic Programmer', 'David Thomas, Andrew Hunt', 'Addison-Wesley', 4, 'book', 'Your journey to mastery in software development', 'B-201', 3, 3, 352, 'English'),
('978-1-59327-828-6', 'Clean Code', 'Robert C. Martin', 'Prentice Hall', 4, 'book', 'A handbook of agile software craftsmanship', 'B-202', 4, 3, 464, 'English'),
('978-1-4919-5009-4', 'The DevOps Handbook', 'Gene Kim', 'IT Revolution Press', 4, 'book', 'Transform your organization for digital transformation', 'B-203', 2, 2, 480, 'English'),
('978-0-07-054235-9', 'Design Patterns', 'Erich Gamma', 'Addison-Wesley', 4, 'book', 'Elements of reusable object-oriented software', 'B-204', 3, 2, 395, 'English'),
('978-1-61729-201-9', 'Introduction to Algorithms', 'Thomas H. Cormen', 'MIT Press', 3, 'book', 'The comprehensive guide to algorithms', 'C-301', 5, 4, 1312, 'English'),
('978-0-07-239860-3', 'A Brief History of Time', 'Stephen Hawking', 'Bantam Books', 3, 'book', 'From the Big Bang to black holes', 'C-302', 3, 3, 212, 'English'),
('978-0-307-27777-7', 'Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', 'Harper', 5, 'book', 'A brief history of human civilization', 'C-303', 4, 3, 443, 'English'),
('978-0-14-044913-6', 'Crime and Punishment', 'Fyodor Dostoevsky', 'Penguin Classics', 1, 'book', 'A psychological drama of guilt and redemption', 'A-109', 3, 3, 671, 'English'),
('978-0-14-143951-8', 'Pride and Prejudice', 'Jane Austen', 'Penguin Classics', 1, 'book', 'A witty romance of manners', 'A-110', 4, 4, 432, 'English'),
('978-0-06-245771-8', 'The Art of War', 'Sun Tzu', 'Oxford University Press', 6, 'book', 'Ancient Chinese military strategy treatise', 'D-401', 5, 5, 273, 'English'),
('978-0-14-044930-3', 'Meditations', 'Marcus Aurelius', 'Penguin Classics', 6, 'book', 'Stoic philosophy and personal reflections', 'D-402', 3, 3, 254, 'English'),
('978-1-85554-000-1', 'National Geographic - March 2024', 'National Geographic Society', 'National Geographic Partners', 1, 'magazine', 'Exploration and discovery magazine', 'M-101', 10, 8, 120, 'English'),
('978-1-85554-001-8', 'Time Magazine - April 2024', 'Time USA LLC', 'Time USA LLC', 1, 'magazine', 'Weekly news and current affairs', 'M-102', 15, 12, 80, 'English'),
('978-1-85554-002-5', 'The New York Times - Daily', 'New York Times Company', 'New York Times', 1, 'newspaper', 'Daily edition of The New York Times', 'N-101', 20, 18, 40, 'English');

-- Insert sample transactions
INSERT INTO transactions (book_id, user_id, issued_by, issue_date, due_date, return_date, status) VALUES
(1, 3, 2, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), NULL, 'issued'),
(2, 3, 2, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 6 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'returned'),
(3, 4, 2, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 1 DAY), NULL, 'overdue'),
(5, 4, 2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 9 DAY), NULL, 'issued'),
(8, 5, 2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 11 DAY), NULL, 'issued'),
(10, 5, 2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), NULL, 'issued'),
(11, 3, 2, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 16 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY), 'returned'),
(14, 4, 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), NULL, 'issued');

-- Insert fines for returned/overdue books
INSERT INTO fines (transaction_id, user_id, amount, paid_amount, is_paid, payment_date, payment_method) VALUES
(2, 3, 4.00, 4.00, TRUE, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'cash'),
(11, 3, 2.00, 2.00, TRUE, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 'card');

-- Insert sample notifications
INSERT INTO notifications (user_id, title, message, type) VALUES
(3, 'Book Due Soon', 'Your borrowed book is due in 3 days. Please return it on time.', 'warning'),
(4, 'Overdue Notice', 'You have an overdue book. Please return it immediately to avoid further fines.', 'danger'),
(3, 'Welcome to Nexus Library', 'Thank you for being a member. Happy reading!', 'success'),
(5, 'New Arrivals', 'Check out our latest collection of programming books!', 'info');

-- Insert sample vendors
INSERT INTO vendors (name, contact_person, email, phone, address) VALUES
('Penguin Random House', 'Sarah Johnson', 'orders@penguinrandomhouse.com', '+1 212 782 9000', '1745 Broadway, New York, NY 10019'),
('HarperCollins', 'Michael Chen', 'sales@harpercollins.com', '+1 212 207 7000', '195 Broadway, New York, NY 10007'),
('Simon & Schuster', 'Emily Davis', 'info@simonschuster.com', '+1 212 698 7000', '1230 Avenue of the Americas, New York, NY 10020'),
('Oxford University Press', 'Robert Wilson', 'enquiries@oup.com', '+44 1865 556767', 'Great Clarendon Street, Oxford, OX2 6DP, UK');

-- Insert sample procurements
INSERT INTO procurements (vendor_id, book_title, author, quantity, unit_price, total_amount, order_date, received_date, status) VALUES
(1, 'The Hobbit', 'J.R.R. Tolkien', 10, 15.99, 159.90, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 25 DAY), 'received'),
(2, 'Atomic Habits', 'James Clear', 5, 18.99, 94.95, DATE_SUB(CURDATE(), INTERVAL 15 DAY), NULL, 'ordered'),
(3, 'Deep Work', 'Cal Newport', 8, 16.99, 135.92, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 18 DAY), 'received');

-- Insert sample maintenance logs
INSERT INTO maintenance_logs (book_id, issue_type, description, reported_by, reported_date, cost, status, resolution_notes) VALUES
(1, 'damaged', 'Cover pages slightly torn at the corner', 2, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 5.00, 'resolved', 'Repaired with book binding tape'),
(4, 'cleaning', 'General cleaning and dust removal needed', 2, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 0.00, 'resolved', 'Cleaned with soft brush and cloth'),
(7, 'missing_pages', 'Pages 45-50 are missing from the book', 2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 0.00, 'open', 'Pending replacement from publisher');

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX idx_books_isbn ON books(isbn);
CREATE INDEX idx_books_title ON books(title);
CREATE INDEX idx_books_author ON books(author);
CREATE INDEX idx_books_category ON books(category_id);
CREATE INDEX idx_transactions_book ON transactions(book_id);
CREATE INDEX idx_transactions_user ON transactions(user_id);
CREATE INDEX idx_transactions_status ON transactions(status);
CREATE INDEX idx_transactions_issue_date ON transactions(issue_date);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_fines_user ON fines(user_id);
CREATE INDEX idx_fines_paid ON fines(is_paid);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);
CREATE INDEX idx_reservations_book ON reservations(book_id);
CREATE INDEX idx_reservations_user ON reservations(user_id);
