-- 1. USUARIOS
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'agent', 'user') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. TIPOS DE TICKET (INCIDENTE vs SOLICITUD)
CREATE TABLE ticket_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name ENUM('incident', 'request') NOT NULL,
  description VARCHAR(255),
  UNIQUE KEY (name)
);

-- Valores iniciales
INSERT INTO ticket_types (name, description) VALUES 
  ('incident', 'Fallo crítico que interrumpe servicios'),
  ('request', 'Petición de servicio o mejora');

-- 3. NIVELES DE GRAVEDAD (SEPARADOS POR TIPO)
CREATE TABLE severity_levels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_type_id INT NOT NULL,
  name VARCHAR(50) NOT NULL,          -- Ej: 'Crítico', 'Alto', 'Bajo'
  priority INT NOT NULL,              -- Orden (1=urgente)
  FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id),
  UNIQUE KEY (ticket_type_id, name)
);

-- Gravedades para Incidentes
INSERT INTO severity_levels (ticket_type_id, name, priority) VALUES 
  (1, 'Crítico', 1),  -- Ej: Servidor caído
  (1, 'Alto', 2),     -- Ej: Error en módulo clave
  (1, 'Medio', 3),    -- Ej: Problema intermitente
  (1, 'Bajo', 4);     -- Ej: Error cosmético

-- Gravedades para Solicitudes
INSERT INTO severity_levels (ticket_type_id, name, priority) VALUES 
  (2, 'Urgente', 1),  -- Ej: Requerimiento legal
  (2, 'Alta', 2),     -- Ej: Nueva funcionalidad
  (2, 'Media', 3),    -- Ej: Mejora de usabilidad
  (2, 'Baja', 4);     -- Ej: Consulta general

-- 4. POLÍTICAS DE SLA (POR TIPO Y GRAVEDAD)
CREATE TABLE sla_policies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_type_id INT NOT NULL,
  severity_level_id INT NOT NULL,
  resolution_time_hours INT NOT NULL,  -- Tiempo máximo para resolver
  response_time_hours INT NOT NULL,    -- Tiempo para primera respuesta
  description TEXT,
  FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id),
  FOREIGN KEY (severity_level_id) REFERENCES severity_levels(id),
  UNIQUE KEY (ticket_type_id, severity_level_id)
);

-- SLAs para Incidentes
INSERT INTO sla_policies VALUES 
  (NULL, 1, 1, 2, 1, 'Incidente Crítico: Resolver en 2h (Ej: Caída total)'),
  (NULL, 1, 2, 8, 2, 'Incidente Alto: Resolver en 8h (Ej: Error grave)'),
  (NULL, 1, 3, 24, 4, 'Incidente Medio: Resolver en 24h'),
  (NULL, 1, 4, 48, 8, 'Incidente Bajo: Resolver en 48h');

-- SLAs para Solicitudes
INSERT INTO sla_policies VALUES 
  (NULL, 2, 1, 24, 4, 'Solicitud Urgente: Resolver en 24h (Ej: Legal)'),
  (NULL, 2, 2, 72, 8, 'Solicitud Alta: Resolver en 72h (Ej: Nueva feature)'),
  (NULL, 2, 3, 120, 24, 'Solicitud Media: Resolver en 5 días'),
  (NULL, 2, 4, 240, 48, 'Solicitud Baja: Resolver en 10 días');

-- 5. TICKETS (CON TIPO Y GRAVEDAD EXPLÍCITOS)
CREATE TABLE tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  ticket_type_id INT NOT NULL,
  severity_level_id INT NOT NULL,
  status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
  user_id INT NOT NULL,              -- Quién reportó
  assigned_to INT,                   -- Agente asignado
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id),
  FOREIGN KEY (severity_level_id) REFERENCES severity_levels(id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (assigned_to) REFERENCES users(id)
);

-- -------------------------------------------------------------------
-- TABLAS DE SOPORTE
-- -------------------------------------------------------------------

-- 6. COMENTARIOS (INTERNOS Y PÚBLICOS)
CREATE TABLE comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  user_id INT NOT NULL,
  content TEXT NOT NULL,
  is_internal BOOLEAN DEFAULT 0,     -- ¿Visible para el usuario final?
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 7. HISTORIAL DE CAMBIOS (AUDITORÍA)
CREATE TABLE ticket_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  user_id INT NOT NULL,              -- Quién realizó el cambio
  changed_field ENUM('status', 'priority', 'assigned_to', 'severity') NOT NULL,
  old_value TEXT,
  new_value TEXT,
  changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 8. NOTIFICACIONES
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(100) NOT NULL,
  message TEXT NOT NULL,
  is_read BOOLEAN DEFAULT 0,
  related_ticket_id INT,             -- Ticket asociado (opcional)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (related_ticket_id) REFERENCES tickets(id)
);

-- 9. ARCHIVOS ADJUNTOS
CREATE TABLE attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  user_id INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,   -- Ruta en el servidor
  file_name VARCHAR(100) NOT NULL,   -- Nombre original
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 10. ACTIVIDAD DE USUARIOS (OPCIONAL)
CREATE TABLE user_activity (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action VARCHAR(50) NOT NULL,       -- Ej: 'login', 'ticket_created'
  details JSON,                      -- Datos adicionales
  ip_address VARCHAR(45),            -- Auditoría de seguridad
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);
-- 11. CONFIGURACION GLOBAL
CREATE TABLE system_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_name VARCHAR(100) UNIQUE NOT NULL,
  setting_value TEXT NOT NULL,
  description TEXT
);
-- Datos iniciales
INSERT INTO system_settings VALUES
    (NULL, 'max_open_tickets_per_agent', '5', 'Límite de tickets asignados simultáneamente'),
    (NULL, 'business_hours_start', '09:00:00', 'Inicio de horario laboral'),
    (NULL, 'business_hours_end', '18:00:00', 'Fin de horario laboral');
    
-- -------------------------------------------------------------------
-- ÍNDICES RECOMENDADOS (MEJORA DE RENDIMIENTO)
-- -------------------------------------------------------------------
-- Índices para consultas frecuentes
CREATE INDEX idx_tickets_type_status ON tickets(ticket_type_id, status);
CREATE INDEX idx_tickets_creator ON tickets(user_id, created_at);
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read, created_at);