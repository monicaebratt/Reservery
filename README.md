# Reservery

Plataforma web con API REST para la gestión centralizada de reservas de espacios y recursos compartidos.
Facultad de Ingenierías · Instituto Tecnológico Metropolitano · Medellín, 2026


## Sobre el proyecto
En muchas organizaciones los salones, laboratorios, auditorios y equipos se reservan por mensajes, llamadas o agendas físicas. Una persona tiene que revisar cada solicitud a mano, y eso termina en cruces de horario, dobles reservas y usuarios que nunca saben si su solicitud fue recibida.

Reservery reemplaza esa coordinación manual por un motor de disponibilidad en tiempo real. Cada organización registra su inventario y sus reglas, y sus miembros reservan desde la web o desde la API. El sistema valida cada solicitud al instante, resuelve los choques según la prioridad de cada reserva, propone alternativas cuando algo está ocupado y avisa de cada cambio.


## Roles

**Organización.** Registra la institución, invita a sus miembros y decide quién es administrador. También tiene los permisos de un administrador.

**Administrador.** Gestiona el inventario, los horarios, los bloqueos y las prioridades, aprueba las solicitudes y consulta los reportes.

**Usuario.** Consulta la disponibilidad, hace y cancela sus reservas y recibe notificaciones.


## Funcionalidades

- Inicio de sesión con correo o con Google, con confirmación del correo
- Disponibilidad en tiempo real por día o por semana
- Reservas compuestas: un espacio junto con sus recursos en una sola solicitud
- Prioridad por propósito, con reemplazo y aviso a la persona afectada
- Alternativas equivalentes y lista de espera cuando algo está ocupado
- Bloqueos por mantenimiento o eventos y calendario centralizado
- Reportes de ocupación, espacios poco usados y horas de mayor demanda, con descarga en PDF
- Notificaciones en la plataforma y por correo


## Arquitectura

El proyecto usa Laravel con una arquitectura por capas, y la web y la API comparten la misma lógica.

Las peticiones entran por las rutas y pasan por middleware que revisa la sesión, el correo confirmado, la organización habilitada y el rol. Los **controladores** solo reciben y validan; la lógica del negocio vive en los **servicios**, organizados por dominio: disponibilidad, reservas, prioridad, sustitución, lista de espera, notificaciones y analítica.

Los **modelos** aplican un filtro global para que cada organización vea únicamente su propia información.

El ciclo de vida de la reserva usa el **patrón State**. Toda reserva empieza *pendiente* y puede pasar a *aprobada*, *rechazada* o *cancelada*; una aprobada solo puede cancelarse o completarse. Cada estado conoce sus propias transiciones, así que no se puede, por ejemplo, aprobar una reserva rechazada.

Cuando una reserva cambia de estado se dispara un **evento** que atienden tres listeners por separado: uno notifica a la persona, otro revisa la lista de espera y otro resuelve los reemplazos pendientes.


## Modelo de datos

La base de datos en MySQL gira en torno a la organización y la reserva. Los espacios y los recursos se guardan juntos como objetos reservables, cada uno con su horario de atención y sus bloqueos. Una reserva pertenece a un usuario y a un propósito, puede incluir varios objetos a la vez y guarda el historial de sus cambios. Alrededor están los reemplazos, la lista de espera y las notificaciones.


## Tecnologías

Laravel 12, PHP 8.2 y MySQL 8 en el servidor, con Sanctum para la API y Socialite para Google. La interfaz usa Blade y Bootstrap 5, junto con Chart.js, DataTables y FullCalendar. Los PDF se generan con DomPDF y los festivos de Colombia se consultan en Nager.Date.

El proyecto cuenta con 75 pruebas automatizadas con PHPUnit.


## Estructura

```
app/
  Http/Controllers/       controladores web
  Http/Controllers/Api/   controladores de la API
  Http/Middleware/        filtros de rol y organización
  Services/               lógica del negocio
  States/                 estados de la reserva
  Events/ y Listeners/    reacciones a los cambios
  Models/                 modelos de datos
database/                 migraciones y datos de prueba
resources/views/          vistas
routes/                   rutas web y de la API
tests/                    pruebas automatizadas
```


## API

Se obtiene un token con `POST /api/auth/login` y se envía en cada petición con la cabecera `Authorization: Bearer {token}`.


## Datos de prueba

Con `php artisan migrate --seed` se crea una organización de ejemplo. Todos los usuarios usan la contraseña `password`.

- Organización: `org@reservery.com`
- Administrador: `admin@reservery.com`
- Usuario: `usuario@reservery.com`

Código para unirse a la organización: `DEMO2026`


## Equipo

Mónica Ebratt Castro  
Isabella Ortiz Tamayo  
Andrés Felipe Grajales Acevedo
