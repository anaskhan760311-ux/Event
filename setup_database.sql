-- Database setup for KhanEvent Event Platform
-- Creates the event_platform database, the events and bookings tables,
-- and seeds the events table with sample data.

CREATE DATABASE IF NOT EXISTS event_platform
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE event_platform;

-- ---------------------------------------------------------
-- Table: users
-- Stores registered users with password_hash() output only.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Table: events
-- Stores event name, category, date, location and price.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    event_date DATE NOT NULL,
    location VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    image VARCHAR(255) DEFAULT 'images/ban.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Table: bookings
-- Stores customer name, email, event ID, ticket quantity and booking status.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    event_id INT NOT NULL,
    ticket_quantity INT NOT NULL DEFAULT 1,
    status ENUM('Pending','Confirmed','Cancelled') NOT NULL DEFAULT 'Confirmed',
    booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Seed sample events (the 6 events shown on the website)
-- ---------------------------------------------------------
DELETE FROM events;
INSERT INTO events (name, category, event_date, location, price, image) VALUES
('Rock Night Concert', 'Music', '2026-12-15', 'Mumbai', 1200.00, 'images/music.jpg'),
('Tech Summit 2026', 'Technology', '2026-12-20', 'Bangalore', 2500.00, 'images/work.jpg'),
('JavaScript Workshop', 'Workshop', '2026-12-22', 'Pune', 800.00, 'images/OJ.jpg'),
('Premier League Final', 'Sports', '2026-12-25', 'Delhi', 3000.00, 'images/sport.jpg'),
('Jazz Evening', 'Music', '2026-12-28', 'Goa', 1500.00, 'images/musicc.jpg'),
('AI & ML Conference', 'Technology', '2027-01-05', 'Hyderabad', 2200.00, 'images/akk.jpg');

SELECT 'Database, tables created and 6 events seeded successfully' AS message;
