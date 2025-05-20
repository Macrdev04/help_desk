/helpdesk
  /includes
    config.php         # Conexión DB y funciones
    auth.php           # Autenticación
    ticket_functions.php # Lógica de tickets
  /templates
    header.php         # Navbar común
    footer.php         # Scripts comunes
  /css
    styles.css         # Estilos principales
  /js
    scripts.js         # JavaScript básico
  index.php           # Dashboard
  login.php           # Inicio de sesión
  register.php        # Registro (opcional)
  tickets.php         # Listado de tickets
  create_ticket.php    # Crear nuevo ticket
  view_ticket.php      # Ver detalles + comentarios
  admin/              # Panel admin (opcional)
    dashboard.php     #resumen
    tickets.php       #listar completo
    users.php         #gestion usuarios
  agent/
    dashboard.php     # resumen para agentes
  user/
    dashboard.php     # creacion de tickets para usuario e historial
  