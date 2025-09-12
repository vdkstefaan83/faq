CREATE DATABASE IF NOT EXISTS faq_system;
USE faq_system;

CREATE TABLE IF NOT EXISTS articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO articles (title, content) VALUES 
('Sample Article 1', 'This is the content of the first sample article.'),
('Sample Article 2', 'This is the content of the second sample article.');