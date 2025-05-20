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