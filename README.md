# MenaProtection

Plugin de protección de áreas para PocketMine-MP que permite a los jugadores proteger sus casas usando un bloque especial llamado "Mena".

## Características

- **Sistema de Protección**: Los jugadores pueden proteger un área de 15x15x15 bloques (configurable)
- **Bloque Mena**: Usa bloques de amatista como base para crear protecciones
- **Distancia Mínima**: Las protecciones deben estar separadas por al menos 50 bloques
- **Sistema de Logs**: Registra todas las actividades de protección
- **Comandos de Administración**: Herramientas completas para administradores
- **Protección Automática**: Previene construcción no autorizada en áreas protegidas

## Instalación

1. Descarga el plugin desde GitHub
2. Coloca la carpeta `MenaProtection` en la carpeta `plugins` de tu servidor
3. Reinicia el servidor
4. El plugin se habilitará automáticamente

## Configuración

El plugin incluye un archivo `config.yml` con las siguientes opciones:

### Configuración del Bloque Mena
```yaml
mena:
  block_id: 153  # ID del bloque (153 = Amatista)
  item_name: "§6§lMena"  # Nombre del ítem
  item_lore: []  # Descripción del ítem
```

### Configuración de Protección
```yaml
protection:
  radius: 15  # Radio de protección en bloques
  min_distance: 50  # Distancia mínima entre protecciones
  max_height: 0  # Altura máxima (0 = sin límite)
  min_height: 0  # Altura mínima (0 = sin límite)
```

### Configuración de Logs
```yaml
logs:
  enabled: true  # Habilitar sistema de logs
  max_logs: 1000  # Número máximo de logs
```

## Uso

### Para Jugadores

1. **Obtener una Mena**: Los jugadores reciben automáticamente una Mena al entrar al servidor
2. **Crear Protección**: Coloca el bloque Mena en el lugar donde quieres proteger tu área
3. **Eliminar Protección**: Rompe el bloque Mena para eliminar la protección

### Para Administradores

#### Comandos Disponibles

- `/mena list` - Ver todas las protecciones activas
- `/mena remove <jugador>` - Eliminar la protección de un jugador
- `/mena info <jugador>` - Ver información de la protección de un jugador
- `/mena give <jugador>` - Dar una Mena a un jugador
- `/mena logs [página]` - Ver logs de actividad
- `/mena reload` - Recargar la configuración

#### Permisos

- `menaprotection.admin` - Acceso a todos los comandos de administración
- `menaprotection.give` - Poder dar bloques Mena a otros jugadores
- `menaprotection.bypass` - Poder construir en áreas protegidas

## Archivos del Plugin

```
MenaProtection/
├── Main.php                 # Clase principal del plugin
├── plugin.yml               # Información del plugin
├── config.yml               # Configuración del plugin
├── Commands/
│   └── MenaCommand.php      # Comandos de administración
├── Events/
│   └── ProtectionListener.php # Eventos de protección
└── Managers/
    └── ProtectionManager.php # Gestor de protecciones
```

## Funcionalidades

### Sistema de Protección
- Cada jugador puede tener una sola protección
- El radio de protección es configurable
- Las protecciones deben estar separadas por una distancia mínima
- Se previene la construcción no autorizada en áreas protegidas

### Protección de Contenedores
- Los cofres y otros contenedores están protegidos
- Solo el propietario puede acceder a los contenedores en su área
- Los administradores con permisos pueden hacer bypass

### Sistema de Logs
- Registra todas las creaciones y eliminaciones de protecciones
- Incluye información detallada de cada acción
- Los logs se guardan automáticamente

## Compatibilidad

- **API**: PocketMine-MP 5.0.0+
- **Versión**: 1.0.0
- **Autor**: TuNombre

## Contribuir

Si quieres contribuir al proyecto:

1. Haz un fork del repositorio
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crea un Pull Request

## Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## Soporte

Si tienes problemas o preguntas:

1. Revisa la documentación
2. Busca en los issues existentes
3. Crea un nuevo issue con detalles del problema

## Changelog

### v1.0.0
- Lanzamiento inicial
- Sistema básico de protección
- Comandos de administración
- Sistema de logs
- Protección de contenedores 