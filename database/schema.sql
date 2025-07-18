-- IEBC Voting Portal Database Schema
-- Run this script in your MySQL database to set up all required tables

-- Users (Voters)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voter_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seats (Positions available for contest)
CREATE TABLE seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- Candidates
CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    seat_id INT NOT NULL,
    manifesto TEXT,
    documents VARCHAR(255),
    photo VARCHAR(255),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (seat_id) REFERENCES seats(id)
);

-- Admins (Election officials)
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Elections
CREATE TABLE elections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('upcoming','ongoing','completed') DEFAULT 'upcoming'
);

-- Votes (encrypted + anonymous)
CREATE TABLE votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT NOT NULL,
    seat_id INT NOT NULL,
    candidate_id INT NOT NULL,
    voter_id INT NOT NULL,
    vote_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    encrypted_vote VARCHAR(255) NOT NULL,
    FOREIGN KEY (election_id) REFERENCES elections(id),
    FOREIGN KEY (seat_id) REFERENCES seats(id),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id),
    FOREIGN KEY (voter_id) REFERENCES users(id)
);

-- Audit Logs (Transparency & tracking of actions)
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('admin','voter','candidate') NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
); 