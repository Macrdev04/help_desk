-- =====================================================================
-- SISTEMA DE TICKETS v2.0 - ESTRUCTURA SQL OPTIMIZADA
-- =====================================================================
-- Helpdesk Sistema de tickets
-- Desarrollado por: [Manuel Carreño @Macrdev04]
-- Fecha: 22-05-2025
-- Descripción: Estructura de base de datos optimizada para un sistema de tickets
-- =====================================================================
-- TABLAS PRINCIPALES HELPDESK_DB @Macrdev04
-- =====================================================================

-- 1. USUARIOS
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'agent', 'user') DEFAULT 'user',
  open_tickets_count INT DEFAULT 0,
  last_login TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_role (role)
);

-- 2. ESTADO DE USUARIOS
CREATE TABLE user_status (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  last_status_change TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_status (user_id, status)
);

-- 3. ESTADÍSTICAS DE USUARIOS (CONSOLIDADA)
CREATE TABLE user_statistics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  open_tickets_count INT DEFAULT 0,
  closed_tickets_count INT DEFAULT 0,
  total_tickets_created INT DEFAULT 0,
  avg_resolution_time_hours DECIMAL(10,2) DEFAULT 0,
  last_activity TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_stats (user_id)
);

-- 4. TIPOS DE TICKET
CREATE TABLE ticket_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name ENUM('incident', 'request') NOT NULL,
  description VARCHAR(255),
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_type_name (name)
);

-- Datos iniciales
INSERT INTO ticket_types (name, description) VALUES 
  ('incident', 'Fallo crítico que interrumpe servicios'),
  ('request', 'Petición de servicio o mejora');

-- 5. NIVELES DE GRAVEDAD
CREATE TABLE severity_levels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_type_id INT NOT NULL,
  name VARCHAR(50) NOT NULL,
  priority INT NOT NULL,
  color_code VARCHAR(7) DEFAULT '#808080',
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id) ON DELETE CASCADE,
  UNIQUE KEY unique_type_severity (ticket_type_id, name),
  INDEX idx_priority (ticket_type_id, priority)
);

-- Gravedades para Incidentes
INSERT INTO severity_levels (ticket_type_id, name, priority, color_code) VALUES 
  (1, 'Crítico', 1, '#DC2626'),
  (1, 'Alto', 2, '#EA580C'),
  (1, 'Medio', 3, '#D97706'),
  (1, 'Bajo', 4, '#65A30D');

-- Gravedades para Solicitudes
INSERT INTO severity_levels (ticket_type_id, name, priority, color_code) VALUES 
  (2, 'Urgente', 1, '#DC2626'),
  (2, 'Alta', 2, '#EA580C'),
  (2, 'Media', 3, '#D97706'),
  (2, 'Baja', 4, '#65A30D');

-- 6. POLÍTICAS DE SLA
CREATE TABLE sla_policies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_type_id INT NOT NULL,
  severity_level_id INT NOT NULL,
  response_time_hours INT NOT NULL,
  resolution_time_hours INT NOT NULL,
  escalation_time_hours INT NULL,
  business_hours_only BOOLEAN DEFAULT TRUE,
  description TEXT,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id) ON DELETE CASCADE,
  FOREIGN KEY (severity_level_id) REFERENCES severity_levels(id) ON DELETE CASCADE,
  UNIQUE KEY unique_sla_policy (ticket_type_id, severity_level_id)
);

-- SLAs para Incidentes
INSERT INTO sla_policies (ticket_type_id, severity_level_id, response_time_hours, resolution_time_hours, escalation_time_hours, description) VALUES 
  (1, 1, 1, 2, 4, 'Incidente Crítico: Respuesta 1h, Resolución 2h'),
  (1, 2, 2, 8, 12, 'Incidente Alto: Respuesta 2h, Resolución 8h'),
  (1, 3, 4, 24, 36, 'Incidente Medio: Respuesta 4h, Resolución 24h'),
  (1, 4, 8, 48, 72, 'Incidente Bajo: Respuesta 8h, Resolución 48h');

-- SLAs para Solicitudes
INSERT INTO sla_policies (ticket_type_id, severity_level_id, response_time_hours, resolution_time_hours, escalation_time_hours, description) VALUES 
  (2, 5, 4, 24, 48, 'Solicitud Urgente: Respuesta 4h, Resolución 24h'),
  (2, 6, 8, 72, 96, 'Solicitud Alta: Respuesta 8h, Resolución 72h'),
  (2, 7, 24, 120, 168, 'Solicitud Media: Respuesta 24h, Resolución 5 días'),
  (2, 8, 48, 240, 336, 'Solicitud Baja: Respuesta 48h, Resolución 10 días');

-- 7. TICKETS PRINCIPAL
CREATE TABLE tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  ticket_type_id INT NOT NULL,
  severity_level_id INT NOT NULL,
  status ENUM('open', 'in_progress', 'pending', 'resolved', 'closed', 'cancelled') DEFAULT 'open',
  priority_score INT DEFAULT 0,
  user_id INT NOT NULL,
  assigned_to INT NULL,
  first_response_at TIMESTAMP NULL,
  resolved_at TIMESTAMP NULL,
  closed_at TIMESTAMP NULL,
  due_date TIMESTAMP NULL,
  sla_breach BOOLEAN DEFAULT FALSE,
  resolution_time_hours DECIMAL(10,2) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_type_id) REFERENCES ticket_types(id),
  FOREIGN KEY (severity_level_id) REFERENCES severity_levels(id),
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (assigned_to) REFERENCES users(id),
  INDEX idx_status_priority (status, priority_score DESC),
  INDEX idx_assigned_status (assigned_to, status),
  INDEX idx_user_created (user_id, created_at DESC),
  INDEX idx_type_severity (ticket_type_id, severity_level_id),
  INDEX idx_sla_breach (sla_breach, due_date),
  INDEX idx_created_date (created_at DESC)
);

-- =====================================================================
-- TABLAS DE SOPORTE
-- =====================================================================

-- 8. COMENTARIOS
CREATE TABLE comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  user_id INT NOT NULL,
  content TEXT NOT NULL,
  is_internal BOOLEAN DEFAULT FALSE,
  is_system BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_ticket_comments (ticket_id, created_at),
  INDEX idx_user_comments (user_id, created_at DESC)
);

-- 9. HISTORIAL DE CAMBIOS (OPTIMIZADO)
CREATE TABLE ticket_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  user_id INT NOT NULL,
  action_type ENUM('created', 'updated', 'assigned', 'status_changed', 'priority_changed', 'commented') NOT NULL,
  field_name VARCHAR(50) NULL,
  old_value TEXT NULL,
  new_value TEXT NULL,
  additional_data JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_ticket_history (ticket_id, created_at DESC),
  INDEX idx_action_type (action_type, created_at DESC)
);

-- 10. NOTIFICACIONES (MEJORADAS)
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
  title VARCHAR(100) NOT NULL,
  message TEXT NOT NULL,
  is_read BOOLEAN DEFAULT FALSE,
  is_email_sent BOOLEAN DEFAULT FALSE,
  related_ticket_id INT NULL,
  action_url VARCHAR(255) NULL,
  expires_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  read_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (related_ticket_id) REFERENCES tickets(id) ON DELETE SET NULL,
  INDEX idx_user_notifications (user_id, is_read, created_at DESC),
  INDEX idx_expires (expires_at)
);

-- 11. ARCHIVOS ADJUNTOS (MEJORADO)
CREATE TABLE attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  comment_id INT NULL,
  user_id INT NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_size_bytes INT NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  is_image BOOLEAN DEFAULT FALSE,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE SET NULL,
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_ticket_attachments (ticket_id, uploaded_at DESC)
);

-- 12. ACTIVIDAD DE USUARIOS
CREATE TABLE user_activity (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action VARCHAR(100) NOT NULL,
  resource_type ENUM('ticket', 'comment', 'user', 'system') NOT NULL,
  resource_id INT NULL,
  details JSON NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_activity (user_id, created_at DESC),
  INDEX idx_resource (resource_type, resource_id),
  INDEX idx_action_date (action, created_at DESC)
);

-- 13. CONFIGURACIÓN GLOBAL (EXPANDIDA)
CREATE TABLE system_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category VARCHAR(50) NOT NULL DEFAULT 'general',
  setting_name VARCHAR(100) NOT NULL,
  setting_value TEXT NOT NULL,
  data_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
  description TEXT,
  is_editable BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_setting (setting_name),
  INDEX idx_category (category)
);

-- Configuraciones iniciales
INSERT INTO system_settings (category, setting_name, setting_value, data_type, description) VALUES
  ('tickets', 'max_open_tickets_per_agent', '10', 'integer', 'Límite máximo de tickets asignados por agente'),
  ('tickets', 'auto_assign_tickets', 'true', 'boolean', 'Asignación automática de tickets'),
  ('tickets', 'allow_user_priority_change', 'false', 'boolean', 'Permitir que usuarios cambien prioridad'),
  ('business', 'business_hours_start', '09:00:00', 'string', 'Inicio de horario laboral'),
  ('business', 'business_hours_end', '18:00:00', 'string', 'Fin de horario laboral'),
  ('business', 'business_days', '[1,2,3,4,5]', 'json', 'Días laborales (1=Lunes, 7=Domingo)'),
  ('notifications', 'email_notifications_enabled', 'true', 'boolean', 'Habilitar notificaciones por email'),
  ('notifications', 'sla_warning_threshold', '80', 'integer', 'Porcentaje de SLA para enviar alerta'),
  ('system', 'max_file_upload_size_mb', '10', 'integer', 'Tamaño máximo de archivos en MB'),
  ('system', 'ticket_number_prefix', 'TKT', 'string', 'Prefijo para numeración de tickets');

-- =====================================================================
-- TRIGGERS OPTIMIZADOS
-- =====================================================================

-- Trigger para calcular fecha de vencimiento SLA
DELIMITER //
CREATE TRIGGER calculate_sla_due_date
BEFORE INSERT ON tickets
FOR EACH ROW
BEGIN
  DECLARE resolution_hours INT DEFAULT 24;
  
  SELECT sp.resolution_time_hours INTO resolution_hours
  FROM sla_policies sp
  WHERE sp.ticket_type_id = NEW.ticket_type_id 
    AND sp.severity_level_id = NEW.severity_level_id
    AND sp.is_active = TRUE;
  
  SET NEW.due_date = DATE_ADD(NEW.created_at, INTERVAL resolution_hours HOUR);
  
  -- Calcular score de prioridad
  SELECT (sl.priority * 10) + tt.id INTO NEW.priority_score
  FROM severity_levels sl, ticket_types tt
  WHERE sl.id = NEW.severity_level_id AND tt.id = NEW.ticket_type_id;
END//
DELIMITER ;

-- Trigger principal para manejo de cambios de ticket
DELIMITER //
CREATE TRIGGER manage_ticket_updates
AFTER UPDATE ON tickets
FOR EACH ROW
BEGIN
  DECLARE agent_limit INT DEFAULT 10;
  DECLARE current_count INT DEFAULT 0;
  
  -- Obtener límite de tickets por agente
  SELECT CAST(setting_value AS SIGNED) INTO agent_limit
  FROM system_settings 
  WHERE setting_name = 'max_open_tickets_per_agent';
  
  -- Manejar cambios de asignación
  IF OLD.assigned_to != NEW.assigned_to THEN
    -- Decrementar contador del agente anterior
    IF OLD.assigned_to IS NOT NULL THEN
      UPDATE users 
      SET open_tickets_count = GREATEST(0, open_tickets_count - 1)
      WHERE id = OLD.assigned_to;
    END IF;
    
    -- Incrementar contador del nuevo agente (con validación)
    IF NEW.assigned_to IS NOT NULL THEN
      SELECT open_tickets_count INTO current_count
      FROM users WHERE id = NEW.assigned_to;
      
      IF current_count >= agent_limit THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Agent has reached maximum ticket limit';
      END IF;
      
      UPDATE users 
      SET open_tickets_count = open_tickets_count + 1
      WHERE id = NEW.assigned_to;
      
      -- Notificar al agente
      INSERT INTO notifications (user_id, type, title, message, related_ticket_id, action_url)
      VALUES (NEW.assigned_to, 'info', 'Ticket Asignado', 
              CONCAT('Se te ha asignado el ticket #', NEW.id, ': ', NEW.title),
              NEW.id, CONCAT('/tickets/', NEW.id));
    END IF;
    
    -- Registrar en historial
    INSERT INTO ticket_history (ticket_id, user_id, action_type, field_name, old_value, new_value)
    VALUES (NEW.id, NEW.assigned_to, 'assigned', 'assigned_to',
            IFNULL((SELECT name FROM users WHERE id = OLD.assigned_to), 'Sin asignar'),
            IFNULL((SELECT name FROM users WHERE id = NEW.assigned_to), 'Sin asignar'));
  END IF;
  
  -- Manejar cambios de estado
  IF OLD.status != NEW.status THEN
    -- Actualizar timestamps según el nuevo estado
    IF NEW.status = 'resolved' AND OLD.status != 'resolved' THEN
      SET NEW.resolved_at = NOW();
      SET NEW.resolution_time_hours = TIMESTAMPDIFF(MINUTE, NEW.created_at, NOW()) / 60;
    END IF;
    
    IF NEW.status = 'closed' AND OLD.status != 'closed' THEN
      SET NEW.closed_at = NOW();
      -- Decrementar contador si se cierra
      IF NEW.assigned_to IS NOT NULL THEN
        UPDATE users 
        SET open_tickets_count = GREATEST(0, open_tickets_count - 1)
        WHERE id = NEW.assigned_to;
      END IF;
      
      -- Notificar al usuario
      INSERT INTO notifications (user_id, type, title, message, related_ticket_id)
      VALUES (NEW.user_id, 'success', 'Ticket Cerrado',
              CONCAT('Tu ticket #', NEW.id, ' ha sido resuelto y cerrado'),
              NEW.id);
    END IF;
    
    -- Verificar incumplimiento de SLA
    IF NEW.status IN ('resolved', 'closed') THEN
      UPDATE tickets 
      SET sla_breach = (resolved_at > due_date)
      WHERE id = NEW.id;
    END IF;
    
    -- Registrar cambio en historial
    INSERT INTO ticket_history (ticket_id, user_id, action_type, field_name, old_value, new_value)
    VALUES (NEW.id, NEW.assigned_to, 'status_changed', 'status', OLD.status, NEW.status);
  END IF;
  
  -- Actualizar estadísticas del usuario
  CALL update_user_statistics(NEW.user_id);
  IF NEW.assigned_to IS NOT NULL THEN
    CALL update_user_statistics(NEW.assigned_to);
  END IF;
END//
DELIMITER ;

-- Trigger para nuevos tickets
DELIMITER //
CREATE TRIGGER after_ticket_created
AFTER INSERT ON tickets
FOR EACH ROW
BEGIN
  -- Registrar creación en historial
  INSERT INTO ticket_history (ticket_id, user_id, action_type, field_name, new_value)
  VALUES (NEW.id, NEW.user_id, 'created', 'ticket', 'Ticket creado');
  
  -- Registrar actividad
  INSERT INTO user_activity (user_id, action, resource_type, resource_id, details)
  VALUES (NEW.user_id, 'ticket_created', 'ticket', NEW.id,
          JSON_OBJECT('title', NEW.title, 'type', NEW.ticket_type_id));
  
  -- Manejar asignación inicial
  IF NEW.assigned_to IS NOT NULL THEN
    UPDATE users 
    SET open_tickets_count = open_tickets_count + 1
    WHERE id = NEW.assigned_to;
    
    INSERT INTO notifications (user_id, type, title, message, related_ticket_id)
    VALUES (NEW.assigned_to, 'info', 'Nuevo Ticket',
            CONCAT('Nuevo ticket asignado #', NEW.id, ': ', NEW.title),
            NEW.id);
  END IF;
END//
DELIMITER ;

-- Trigger para comentarios
DELIMITER //
CREATE TRIGGER after_comment_added
AFTER INSERT ON comments
FOR EACH ROW
BEGIN
  DECLARE ticket_user_id INT;
  DECLARE ticket_assigned_to INT;
  DECLARE ticket_title VARCHAR(200);
  
  SELECT user_id, assigned_to, title INTO ticket_user_id, ticket_assigned_to, ticket_title
  FROM tickets WHERE id = NEW.ticket_id;
  
  -- Notificar al creador del ticket (si no es quien comenta)
  IF ticket_user_id != NEW.user_id AND NOT NEW.is_internal THEN
    INSERT INTO notifications (user_id, type, title, message, related_ticket_id)
    VALUES (ticket_user_id, 'info', 'Nuevo Comentario',
            CONCAT('Nuevo comentario en tu ticket #', NEW.ticket_id),
            NEW.ticket_id);
  END IF;
  
  -- Notificar al agente asignado (si no es quien comenta)
  IF ticket_assigned_to IS NOT NULL AND ticket_assigned_to != NEW.user_id THEN
    INSERT INTO notifications (user_id, type, title, message, related_ticket_id)
    VALUES (ticket_assigned_to, 'info', 'Nuevo Comentario',
            CONCAT('Nuevo comentario en ticket #', NEW.ticket_id),
            NEW.ticket_id);
  END IF;
  
  -- Actualizar primera respuesta si es del agente
  IF NEW.user_id = ticket_assigned_to THEN
    UPDATE tickets 
    SET first_response_at = COALESCE(first_response_at, NOW())
    WHERE id = NEW.ticket_id;
  END IF;
  
  -- Registrar en historial
  INSERT INTO ticket_history (ticket_id, user_id, action_type, additional_data)
  VALUES (NEW.ticket_id, NEW.user_id, 'commented',
          JSON_OBJECT('comment_id', NEW.id, 'is_internal', NEW.is_internal));
END//
DELIMITER ;

-- =====================================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================================

-- Procedimiento para actualizar estadísticas de usuario
DELIMITER //
CREATE PROCEDURE update_user_statistics(IN p_user_id INT)
BEGIN
  INSERT INTO user_statistics (user_id, open_tickets_count, closed_tickets_count, total_tickets_created, avg_resolution_time_hours, last_activity)
  SELECT 
    p_user_id,
    COUNT(CASE WHEN status IN ('open', 'in_progress', 'pending') THEN 1 END),
    COUNT(CASE WHEN status = 'closed' THEN 1 END),
    COUNT(*),
    AVG(CASE WHEN resolution_time_hours IS NOT NULL THEN resolution_time_hours END),
    NOW()
  FROM tickets 
  WHERE user_id = p_user_id OR assigned_to = p_user_id
  ON DUPLICATE KEY UPDATE
    open_tickets_count = VALUES(open_tickets_count),
    closed_tickets_count = VALUES(closed_tickets_count),
    total_tickets_created = VALUES(total_tickets_created),
    avg_resolution_time_hours = VALUES(avg_resolution_time_hours),
    last_activity = VALUES(last_activity),
    updated_at = NOW();
END//
DELIMITER ;

-- Procedimiento para verificar SLA
DELIMITER //
CREATE PROCEDURE check_sla_breaches()
BEGIN
  UPDATE tickets t
  JOIN sla_policies sp ON t.ticket_type_id = sp.ticket_type_id 
    AND t.severity_level_id = sp.severity_level_id
  SET t.sla_breach = TRUE
  WHERE t.status NOT IN ('resolved', 'closed', 'cancelled')
    AND NOW() > t.due_date
    AND t.sla_breach = FALSE;
    
  -- Crear notificaciones para tickets que incumplen SLA
  INSERT INTO notifications (user_id, type, title, message, related_ticket_id)
  SELECT DISTINCT 
    t.assigned_to,
    'warning',
    'Incumplimiento SLA',
    CONCAT('El ticket #', t.id, ' ha incumplido el SLA'),
    t.id
  FROM tickets t
  WHERE t.sla_breach = TRUE 
    AND t.assigned_to IS NOT NULL
    AND t.status NOT IN ('resolved', 'closed', 'cancelled')
    AND NOT EXISTS (
      SELECT 1 FROM notifications n 
      WHERE n.related_ticket_id = t.id 
        AND n.title = 'Incumplimiento SLA'
        AND n.created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    );
END//
DELIMITER ;

-- =====================================================================
-- VISTAS ÚTILES
-- =====================================================================

-- Vista para dashboard de tickets
CREATE VIEW ticket_dashboard AS
SELECT 
  t.id,
  t.title,
  t.status,
  tt.name as ticket_type,
  sl.name as severity,
  sl.color_code,
  u1.name as creator,
  u2.name as assigned_agent,
  t.created_at,
  t.due_date,
  t.sla_breach,
  CASE 
    WHEN t.status IN ('resolved', 'closed') THEN 'Completado'
    WHEN NOW() > t.due_date THEN 'Vencido'
    WHEN TIMESTAMPDIFF(HOUR, NOW(), t.due_date) <= 4 THEN 'Urgente'
    ELSE 'Normal'
  END as urgency_status
FROM tickets t
JOIN ticket_types tt ON t.ticket_type_id = tt.id
JOIN severity_levels sl ON t.severity_level_id = sl.id
JOIN users u1 ON t.user_id = u1.id
LEFT JOIN users u2 ON t.assigned_to = u2.id;

-- Vista para métricas de agentes
CREATE VIEW agent_metrics AS
SELECT 
  u.id,
  u.name,
  us.open_tickets_count,
  us.closed_tickets_count,
  us.avg_resolution_time_hours,
  COUNT(CASE WHEN t.sla_breach = FALSE AND t.status = 'closed' THEN 1 END) as sla_compliant_tickets,
  COUNT(CASE WHEN t.sla_breach = TRUE THEN 1 END) as sla_breached_tickets
FROM users u
LEFT JOIN user_statistics us ON u.id = us.user_id
LEFT JOIN tickets t ON u.id = t.assigned_to
WHERE u.role = 'agent'
GROUP BY u.id, u.name, us.open_tickets_count, us.closed_tickets_count, us.avg_resolution_time_hours;

-- =====================================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================================

-- Índices compuestos para consultas frecuentes
CREATE INDEX idx_tickets_status_priority ON tickets(status, priority_score DESC, created_at);
CREATE INDEX idx_tickets_sla_monitoring ON tickets(status, due_date, sla_breach);
CREATE INDEX idx_comments_ticket_date ON comments(ticket_id, created_at DESC, is_internal);
CREATE INDEX idx_notifications_user_unread ON notifications(user_id, is_read, created_at DESC);
CREATE INDEX idx_history_ticket_date ON ticket_history(ticket_id, created_at DESC, action_type);

-- =====================================================================
-- EVENTO PARA VERIFICACIÓN AUTOMÁTICA DE SLA
-- =====================================================================

-- Habilitar el scheduler de eventos
SET GLOBAL event_scheduler = ON;

-- Evento para verificar SLA cada 30 minutos
DELIMITER //
CREATE EVENT check_sla_violations
ON SCHEDULE EVERY 30 MINUTE
STARTS CURRENT_TIMESTAMP
DO
BEGIN
  CALL check_sla_breaches();
END//
DELIMITER ;

-- =====================================================================
-- DATOS DE EJEMPLO (OPCIONAL)
-- =====================================================================

-- Usuarios de ejemplo
INSERT INTO users (name, email, password, role) VALUES
  ('Admin User', 'admin@company.com', '$2y$10$example_hash', 'admin'),
  ('John Agent', 'john@company.com', '$2y$10$example_hash', 'agent'),
  ('Jane Agent', 'jane@company.com', '$2y$10$example_hash', 'agent'),
  ('Bob User', 'bob@company.com', '$2y$10$example_hash', 'user');

-- Estados iniciales
INSERT INTO user_status (user_id, status) 
SELECT id, 'active' FROM users;

-- Estadísticas iniciales
INSERT INTO user_statistics (user_id) 
SELECT id FROM users;