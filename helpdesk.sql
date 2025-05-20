-- Ejemplo de tablas esenciales
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,  -- Hash almacenado
  role ENUM('admin', 'agent', 'user') DEFAULT 'user'
);

CREATE TABLE tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
  priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
  user_id INT NOT NULL,            -- Usuario que reporta
  assigned_to INT,                 -- Agente asignado
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Comentarios (seguimiento)
CREATE TABLE comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  user_id INT NOT NULL,
  comment TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
);