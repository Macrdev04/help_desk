V1.0

/helpdesk
│
├── /includes                     # Lógica del sistema
│   ├── config.php                # Configuración DB (MySQLi)
│   ├── auth.php                  # Autenticación (login/logout/roles)
│   ├── ticket_functions.php      # CRUD de tickets
│   └── user_functions.php        # Gestión de usuarios (opcional)
│
├── /templates                    # Componentes reutilizables
│   ├── header.php                # Cabecera común (navbar)
│   └── footer.php                # Footer + scripts JS
│
├── /assets                       # Recursos estáticos
│   ├── /css
│   │   └── styles.css            # Estilos principales
│   └── /js
│       └── scripts.js            # Funcionalidad frontend
│
├── /admin                        # Panel de administración
│   ├── dashboard.php             # Estadísticas globales
│   ├── tickets.php               # Gestión completa de tickets
│   └── users.php                 # Administración de usuarios
│
├── /agent                        # Panel de agente
│   └── dashboard.php             # Tickets asignados + acciones
│
├── /user                         # Panel de usuario final
│   └── dashboard.php             # Mis tickets + creación rápida
│
├── /core                         # Páginas principales
│   ├── index.php                 # Redirección según rol
│   ├── login.php                 # Inicio de sesión seguro
│   ├── register.php              # Registro con hash
│   ├── tickets.php               # Listado de tickets (user/agent)
│   ├── create_ticket.php         # Formulario de nuevo ticket
│   └── view_ticket.php           # Detalle + comentarios
│
└── README.md                     # Documentación del proyecto

────────────────────────────────────────────────────────────────────
V2.0

/helpdesk
│
├── /config                             # ⚙️ Configuraciones centralizadas
│   ├── database.php                    # Configuración DB con PDO/MySQLi
│   ├── app_config.php                  # Constantes globales del sistema
│   ├── security.php                    # Configuraciones de seguridad
│   └── environment.php                 # Variables de entorno (.env loader)
│       
├── /src                                # 🧠 Lógica de negocio (MVC-like)
│   ├── /Models                         # Modelos de datos
│   │   ├── User.php                    # Modelo Usuario
│   │   ├── Ticket.php                  # Modelo Ticket
│   │   ├── Comment.php                 # Modelo Comentario
│   │   ├── Notification.php            # Modelo Notificación
│   │   └── BaseModel.php               # Clase base con métodos comunes
│   │
│   ├── /Controllers                    # Controladores de lógica
│   │   ├── AuthController.php          # Autenticación y autorización
│   │   ├── TicketController.php        # Operaciones de tickets
│   │   ├── UserController.php          # Gestión de usuarios
│   │   ├── DashboardController.php     # Métricas y estadísticas
│   │   └── NotificationController.php  # Sistema de notificaciones
│   │
│   ├── /Services                       # Servicios de aplicación
│   │   ├── EmailService.php            # Envío de emails
│   │   ├── SLAService.php              # Monitoreo y cálculos SLA
│   │   ├── FileUploadService.php       # Manejo de archivos
│   │   ├── NotificationService.php     # Lógica de notificaciones
│   │   └── ReportService.php           # Generación de reportes
│   │
│   ├── /Middleware                     # Middleware de seguridad
│   │   ├── AuthMiddleware.php          # Verificación de autenticación
│   │   ├── RoleMiddleware.php          # Control de acceso por roles
│   │   └── CSRFMiddleware.php          # Protección CSRF
│   │       
│   └── /Utils                          # Utilidades y helpers
│       ├── Database.php                # Conexión y operaciones DB
│       ├── Validator.php               # Validación de datos
│       ├── Helper.php                  # Funciones auxiliares
│       ├── Logger.php                  # Sistema de logs
│       └── Sanitizer.php               # Limpieza de datos
│       
├── /public                             # 🌐 Archivos públicos (DocumentRoot)
│   ├── index.php                       # Router principal
│   ├── .htaccess                       # Configuración Apache
│   │       
│   ├── /assets                         # Recursos estáticos
│   │   ├── /css        
│   │   │   ├── app.css                 # Estilos principales
│   │   │   ├── dashboard.css           # Estilos específicos dashboard
│   │   │   ├── tickets.css             # Estilos específicos tickets
│   │   │   └── components.css          # Componentes reutilizables
│   │   │       
│   │   ├── /js     
│   │   │   ├── app.js                  # JavaScript principal
│   │   │   ├── ticket-form.js          # Formularios de tickets
│   │   │   ├── notifications.js        # Sistema de notificaciones
│   │   │   ├── real-time.js            # WebSocket/SSE (opcional)
│   │   │   └── charts.js               # Gráficos y métricas
│   │   │       
│   │   ├── /images                     # Imágenes del sistema
│   │   │   ├── /avatars                # Avatares de usuarios
│   │   │   ├── /icons                  # Iconografía del sistema
│   │   │   └── /attachments            # Vista previa de archivos
│   │   │       
│   │   └── /uploads                    # Archivos subidos por usuarios
│   │       ├── /tickets                # Adjuntos de tickets
│   │       └── /temp                   # Archivos temporales
│   │       
│   └── /api                            # 🔌 API REST (opcional para AJAX)
│       ├── tickets.php                 # Endpoints de tickets
│       ├── users.php                   # Endpoints de usuarios
│       ├── notifications.php           # Endpoints de notificaciones
│       └── dashboard.php               # Endpoints de métricas
│       
├── /views                              # 🎨 Plantillas y vistas
│   ├── /layouts                        # Layouts principales
│   │   ├── app.php                     # Layout principal autenticado
│   │   ├── auth.php                    # Layout para login/register
│   │   └── error.php                   # Layout para páginas de error
│   │       
│   ├── /components                     # Componentes reutilizables
│   │   ├── header.php                  # Cabecera con navegación
│   │   ├── sidebar.php                 # Barra lateral por rol
│   │   ├── footer.php                  # Footer con scripts
│   │   ├── alerts.php                  # Sistema de alertas/mensajes
│   │   ├── pagination.php              # Componente de paginación
│   │   └── modals.php                  # Modales reutilizables
│   │       
│   ├── /auth                           # Vistas de autenticación
│   │   ├── login.php                   # Formulario de login
│   │   ├── register.php                # Formulario de registro
│   │   ├── forgot-password.php         # Recuperación de contraseña
│   │   └── reset-password.php          # Reset de contraseña
│   │       
│   ├── /dashboard                      # Dashboards por rol
│   │   ├── admin.php                   # Dashboard administrativo
│   │   ├── agent.php                   # Dashboard de agente
│   │   └── user.php                    # Dashboard de usuario
│   │       
│   ├── /tickets                        # Vistas de tickets
│   │   ├── index.php                   # Listado de tickets
│   │   ├── create.php                  # Creación de ticket
│   │   ├── edit.php                    # Edición de ticket
│   │   ├── view.php                    # Detalle del ticket
│   │   └── assign.php                  # Asignación masiva
│   │       
│   ├── /admin                          # Vistas administrativas
│   │   ├── users                       # Gestión de usuarios
│   │   │   ├── index.php               # Lista de usuarios
│   │   │   ├── create.php              # Crear usuario
│   │   │   └── edit.php                # Editar usuario
│   │   │       
│   │   ├── settings                    # Configuraciones del sistema
│   │   │   ├── general.php             # Configuraciones generales
│   │   │   ├── sla.php                 # Configuración SLA
│   │   │   └── notifications.php       # Config. notificaciones
│   │   │       
│   │   └── reports                     # Reportes y métricas
│   │       ├── overview.php            # Resumen general
│   │       ├── sla-report.php          # Reporte de SLA
│   │       └── agent-performance.php   # Rendimiento agentes
│   │
│   └── /errors                         # Páginas de error
│       ├── 404.php                     # Página no encontrada
│       ├── 403.php                     # Acceso denegado
│       └── 500.php                     # Error del servidor
│       
├── /storage                            # 📁 Almacenamiento temporal
│   ├── /logs                           # Archivos de log
│   │   ├── app.log                     # Log general de aplicación
│   │   ├── error.log                   # Log de errores
│   │   └── security.log                # Log de seguridad
│   │       
│   ├── /cache                          # Cache de aplicación
│   │   ├── /views                      # Cache de vistas renderizadas
│   │   └── /data                       # Cache de datos consultados
│   │       
│   └── /sessions                       # Archivos de sesión (si no usa DB)
│       
├── /database                           # 🗄️ Scripts de base de datos
│   ├── schema.sql                      # Estructura completa de DB
│   ├── seeds.sql                       # Datos iniciales
│   ├── /migrations                     # Migraciones de BD (opcional)
│   │   ├── 001_initial_schema.sql
│   │   ├── 002_add_sla_tables.sql
│   │   └── 003_add_notifications.sql
│   │
│   └── /backups                        # Respaldos de BD
│       
├── /tests                              # 🧪 Pruebas unitarias (opcional)
│   ├── /Unit                           # Pruebas unitarias
│   ├── /Integration                    # Pruebas de integración
│   └── TestCase.php                    # Clase base para pruebas
│       
├── /docs                               # 📖 Documentación
│   ├── API.md                          # Documentación de API
│   ├── INSTALLATION.md                 # Guía de instalación
│   ├── DEPLOYMENT.md                   # Guía de despliegue
│   └── /screenshots                    # Capturas de pantalla
│       
├── /scripts                            # 🔧 Scripts de mantenimiento
│   ├── backup.php                      # Script de respaldo
│   ├── cleanup.php                     # Limpieza de archivos temporales
│   ├── sla-check.php                   # Verificación manual de SLA
│   └── migrate.php                     # Script de migraciones
│       
├── .env.example                        # 🔐 Plantilla de variables de entorno
├── .env                                # Variables de entorno (no versionado)
├── .htaccess                           # Configuración Apache raíz
├── .gitignore                          # Archivos ignorados por Git
├── composer.json                       # Dependencias PHP (si usas Composer)
├── package.json                        # Dependencias frontend (opcional)
└── README.md                           # Documentación principal