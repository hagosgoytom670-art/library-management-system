CREATE DATABASE library_system;
USE library_system;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','librarian','admin') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  author VARCHAR(100),
  category VARCHAR(50),
  isbn VARCHAR(20) UNIQUE,
  available INT DEFAULT 1,
  added_by INT,
  FOREIGN KEY (added_by) REFERENCES users(id)
);

CREATE TABLE borrow_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  book_id INT NOT NULL,
  borrow_date DATE NOT NULL,
  return_date DATE,
  status ENUM('borrowed','returned') DEFAULT 'borrowed',
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (book_id) REFERENCES books(id)
);

CREATE TABLE fines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  borrow_id INT NOT NULL,
  amount DECIMAL(6,2) NOT NULL,
  paid BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (borrow_id) REFERENCES borrow_records(id)
);

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  receiver_id INT NOT NULL,
  message TEXT NOT NULL,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (sender_id) REFERENCES users(id),
  FOREIGN KEY (receiver_id) REFERENCES users(id)
);

CREATE TABLE purchases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT NOT NULL,
  item VARCHAR(100) NOT NULL,
  cost DECIMAL(10,2) NOT NULL,
  purchase_date DATE NOT NULL,
  FOREIGN KEY (admin_id) REFERENCES users(id)
);
