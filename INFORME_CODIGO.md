# 📋 INFORME TÉCNICO - PROYECTO ARTESANOS

## 🎯 RESUMEN EJECUTIVO

**Artesanos** es una aplicación web social para artesanos que permite crear, compartir y gestionar álbumes de imágenes. El sistema está construido con **PHP 8.2**, **MySQL 8.0**, **JavaScript ES6** y **Bootstrap 5**, siguiendo una arquitectura **MVC (Modelo-Vista-Controlador)**.

---

## 📁 ESTRUCTURA DEL PROYECTO

```
Artesanos/
├── app/
│   ├── controllers/     # Controladores (lógica de negocio)
│   ├── models/          # Modelos (acceso a datos)
│   └── views/           # Vistas (interfaz de usuario)
├── config/              # Configuración (conexión BD, etc.)
├── public/              # Punto de entrada público
│   ├── assets/
│   │   ├── js/          # JavaScript del frontend
│   │   └── css/         # Estilos CSS
│   └── uploads/        # Archivos subidos (portadas, imágenes)
│       ├── portadas/   # Portadas de álbumes
│       └── imagenes/   # Imágenes de álbumes
└── scripts/            # Scripts SQL de instalación
```

---

## 🔧 ARQUITECTURA Y PATRÓN DE DISEÑO

### **Patrón MVC Implementado:**

1. **Modelos (`app/models/`):**
   - `albumModelo.php` - Operaciones CRUD de álbumes
     - `crearAlbum()` - Crear nuevo álbum
     - `actualizarAlbum()` - Actualizar álbum existente
     - `eliminarAlbum()` - Eliminar álbum y sus imágenes
     - `obtenerImagenesAlbum()` - Obtener imágenes de un álbum
     - `eliminarPortadaAlbum()` - Eliminar portada (usa `imagen.png` por defecto)
     - `obtenerTodasImagenesLikeadas()` - Obtener portadas e imágenes likeadas de un usuario seguido
     - `obtenerDatosUsuarioPorId()` - Obtener datos de usuario por ID
     - `contarAlbumesDeUsuario()` - Contar álbumes de un usuario
     - `contarSeguidoresDeUsuario()` - Contar seguidores de un usuario
   - `imagenModelo.php` - Operaciones CRUD de imágenes
     - `crearImagen()` - Crear nueva imagen
     - `eliminarImagen()` - Eliminar imagen individual
     - `actualizarImagen()` - Actualizar título y descripción de imagen
   - `comentarioModelo.php` - Gestión de comentarios
   - `usuarioHelper.php` - Utilidades de usuario

2. **Controladores (`app/controllers/`):**
   - `albumControlador.php` - Orquestación de operaciones de álbumes
   - Endpoints API específicos (editarAlbum, eliminarAlbum, etc.)

3. **Vistas (`app/views/`):**
   - `home.php` - Página principal con feed de álbumes
   - `perfil.php` - Perfil de usuario con álbumes propios
   - `crear_album.php` - Formulario de creación de álbumes
   - `login.php`, `registro.php` - Autenticación

### **Front Controller:**
- `public/index.php` - Enruta todas las peticiones HTTP

---

## 🚀 FUNCIONALIDADES PRINCIPALES

### 1. **GESTIÓN DE ÁLBUMES**

#### **Crear Álbum:**
- **Endpoint:** `/api/guardar-album` (POST)
- **Controlador:** `app/controllers/guardarAlbum.php`
- **Modelo:** `AlbumModelo::crearAlbum()`
- **Características:**
  - Título del álbum
  - Portada (imagen)
  - Privacidad (público/privado)
  - Múltiples imágenes con título y descripción
  - Notificaciones automáticas a seguidores

#### **Editar Álbum:**
- **Endpoint:** `/api/editarAlbum` (POST)
- **Controlador:** `app/controllers/editarAlbum.php`
- **Modelo:** `AlbumModelo::actualizarAlbum()`
- **Funcionalidades:**
  - ✅ Editar título
  - ✅ Cambiar portada (con eliminación de la anterior)
  - ✅ Modificar privacidad
  - ✅ Eliminar portada (usa `imagen.png` por defecto)
  - ✅ Gestión completa de imágenes (agregar, eliminar, editar)

#### **Eliminar Álbum:**
- **Endpoint:** `/api/eliminarAlbum` (POST)
- **Controlador:** `app/controllers/eliminarAlbum.php`
- **Funcionalidad:** Elimina álbum, imágenes asociadas y archivos físicos

#### **Ver Detalle de Álbum:**
- **Endpoint:** `/api/detalleAlbum` (GET)
- **Controlador:** `app/controllers/detalleAlbum.php`
- **Características:**
  - Modal con carrusel de imágenes
  - Información del usuario propietario
  - Botón "Seguir" con estado dinámico
  - Likes y comentarios por imagen
  - Tiempo relativo de publicación
  - Enlaces clickeables a perfil (nickname, @username, foto)

#### **Ver Álbum Virtual "Me gusta":**
- **Endpoint:** `/api/detalleLikesUsuario` (GET)
- **Controlador:** `app/controllers/detalleLikesUsuario.php`
- **Modelo:** `AlbumModelo::obtenerTodasImagenesLikeadas()`
- **Características:**
  - Muestra todas las imágenes likeadas de un usuario seguido
  - Incluye portadas de álbumes y imágenes individuales
  - Verifica que el seguimiento esté activo
  - Usa el mismo modal de detalle de álbum
  - Distingue entre portadas (sin interacción) e imágenes (con likes/comentarios)

---

### 2. **GESTIÓN DE IMÁGENES**

#### **Agregar Imágenes a Álbum Existente:**
- **Endpoint:** `/api/agregarImagenesAlbum` (POST)
- **Controlador:** `app/controllers/agregarImagenesAlbum.php`
- **Modelo:** `ImagenModelo::crearImagen()`
- **Características:**
  - Subida múltiple de imágenes
  - Validación de tipos de archivo
  - Generación de nombres únicos

#### **Eliminar Imagen Individual:**
- **Endpoint:** `/api/eliminarImagen` (POST)
- **Controlador:** `app/controllers/eliminarImagen.php`
- **Modelo:** `ImagenModelo::eliminarImagen()`
- **Funcionalidad:** Elimina imagen y archivo físico, verifica propiedad

#### **Editar Imagen (Título y Descripción):**
- **Endpoint:** `/api/actualizarImagen` (POST)
- **Controlador:** `app/controllers/actualizarImagen.php`
- **Modelo:** `ImagenModelo::actualizarImagen()`
- **Funcionalidad:** Actualiza metadatos de imagen individual

#### **Obtener Imágenes de un Álbum:**
- **Endpoint:** `/api/imagenesDeAlbum` (GET)
- **Controlador:** `app/controllers/imagenesDeAlbum.php`
- **Modelo:** `AlbumModelo::obtenerImagenesAlbum()`
- **Retorna:** JSON con array de imágenes para edición

---

### 3. **SISTEMA DE SEGUIMIENTO (FOLLOW/UNFOLLOW)**

#### **Seguir Usuario:**
- **Endpoint:** `/api/seguir` (POST)
- **Vista:** `app/views/seguir.php`
- **Estados:**
  - `pendiente` - Solicitud enviada (esperando aceptación)
  - `activo` - Seguimiento aceptado
- **Funcionalidad:** Crea registro en tabla `seguimiento` y envía notificación

#### **Dejar de Seguir:**
- **Endpoint:** `/api/dejarSeguir` (POST)
- **Vista:** `app/views/dejarSeguir.php`
- **Funcionalidad:** Elimina registro de seguimiento

#### **Verificar Estado de Seguimiento:**
- **Endpoint:** `/api/checkFollowStatus` (POST)
- **Vista:** `app/views/checkFollowStatus.php`
- **Retorna:** `'activo'`, `'pendiente'`, o `'none'`
- **Uso:** Actualización automática de botones en modales

#### **Responder Solicitud:**
- **Endpoint:** `/api/responderSolicitud` (POST)
- **Vista:** `app/views/responderSolicitud.php`
- **Acciones:** Aceptar o rechazar solicitud de seguimiento

---

### 4. **SISTEMA DE LIKES**

#### **Like/Unlike de Álbum:**
- **Endpoint:** `/api/megusta` (POST)
- **Vista:** `app/views/megusta.php`
- **Funcionalidad:** Toggle de like en álbum completo

#### **Like/Unlike de Imagen:**
- **Endpoint:** `/api/obtenerLikes` (GET/POST)
- **Controlador:** `app/controllers/obtenerLikes.php`
- **Funcionalidad:** Gestión de likes por imagen individual

#### **Like/Unlike de Álbum (Portada):**
- **Endpoint:** `/api/megusta` (POST)
- **Vista:** `app/views/megusta.php`
- **Funcionalidad:** Toggle de like en portada de álbum
- **Nota:** Las portadas aparecen en el álbum virtual "Me gusta" pero no permiten likes/comentarios individuales

---

### 5. **SISTEMA DE COMENTARIOS**

#### **Agregar Comentario:**
- **Endpoint:** `/api/agregarComentario` (POST)
- **Controlador:** `app/controllers/agregarComentario.php`
- **Modelo:** `ComentarioModelo`
- **Funcionalidad:** Comentarios asociados a imágenes específicas

---

### 6. **NOTIFICACIONES**

#### **Listar Notificaciones:**
- **Endpoint:** `/api/listarNotificaciones` (GET)
- **Controlador:** `app/controllers/listarNotificaciones.php`
- **Funcionalidad:** Obtiene notificaciones del usuario actual

#### **Gestionar Notificaciones:**
- **Endpoint:** `/api/notificaciones` (POST)
- **Controlador:** `app/controllers/notificacionesControl.php`
- **Tipos de notificaciones:**
  - `album_nuevo` - Nuevo álbum de usuario seguido
  - `solicitud_seguir` - Solicitud de seguimiento
  - `comentario` - Comentario en imagen
  - `like` - Like en álbum/imagen

---

## 🎨 INTERFAZ DE USUARIO (FRONTEND)

### **JavaScript Principal:**

#### **`public/assets/js/home.js`:**
- Gestión del modal de creación de álbumes
- Validación de formularios
- Carga y preview de imágenes
- Funcionalidad de likes en álbumes
- Modal de detalle de álbum con carrusel
- Sistema de seguimiento en modales
- Polling de estado de seguimiento (cada 5 segundos)

#### **`public/assets/js/perfil.js`:**
- Gestión de álbumes del perfil propio
- Modal de edición de álbumes
- Funcionalidad completa de edición:
  - Editar título, portada, privacidad
  - Agregar/eliminar imágenes
  - Editar título y descripción de imágenes
- Sistema de seguimiento en perfil
- Polling de estado de seguimiento
- **Función global:** `cargarDetalleLikesUsuario(idUsuario)` - Carga el álbum virtual "Me gusta"
- **Funciones globales:** `tiempoRelativo()`, `parseMySQLDateToMs()` - Utilidades de fecha
- Prevención de conflictos entre modales (cards de "Me gusta" vs álbumes normales)
- Corrección de accesibilidad en modales (`aria-hidden`, `aria-modal`)

### **Características UI:**
- ✅ Modales Bootstrap 5
- ✅ Carrusel de imágenes con navegación
- ✅ Preview de imágenes antes de subir
- ✅ Botones con estados dinámicos (Seguir/Pendiente/Siguiendo)
- ✅ Tiempo relativo de publicaciones
- ✅ Diseño responsive con Bootstrap

---

## 🔐 SEGURIDAD Y VALIDACIONES

### **Validaciones Implementadas:**

1. **Autenticación:**
   - Verificación de sesión en endpoints protegidos
   - Validación de propiedad antes de editar/eliminar

2. **Validación de Archivos:**
   - Tipos permitidos: `image/jpeg`, `image/png`, `image/gif`, `image/webp`
   - Validación de tamaño
   - Nombres únicos generados con `bin2hex(random_bytes(6))`

3. **Prepared Statements:**
   - Uso de `bind_param()` para prevenir SQL injection
   - Validación de tipos de datos (int, string)

4. **Verificación de Propiedad:**
   - Antes de editar/eliminar álbum: verifica `idUsuarioAlbum`
   - Antes de editar/eliminar imagen: verifica `idUsuario` del álbum

---

## 📊 BASE DE DATOS

### **Tablas Principales:**

1. **`album`:**
   - `idAlbum` (PK)
   - `tituloAlbum`
   - `esPublicoAlbum` (0/1)
   - `urlPortadaAlbum`
   - `idUsuarioAlbum` (FK)
   - `fechaCreacionAlbum`

2. **`imagen`:**
   - `idImagen` (PK)
   - `tituloImagen`
   - `descripcionImagen`
   - `urlImagen`
   - `idAlbumImagen` (FK)
   - `fechaImagen`

3. **`seguimiento`:**
   - `idSeguidor` (FK)
   - `idSeguido` (FK)
   - `estadoSeguimiento` ('pendiente', 'activo', 'rechazado')
   - `fechaSeguimiento`

4. **`notificaciones`:**
   - `idNotificacion` (PK)
   - `idUsuarioDestino` (FK)
   - `idUsuarioAccion` (FK)
   - `tipo` (tipo de notificación)
   - `mensaje`
   - `leida` (0/1)
   - `fecha`

5. **`usuario`:**
   - `idUsuario` (PK)
   - `usuario` (username)
   - `apodoUsuario` (nickname)
   - `fotoPerfil`
   - `contrasena` (hash)

---

## 🔄 FLUJOS DE TRABAJO PRINCIPALES

### **1. Crear Álbum:**
```
Usuario → Selecciona portada → Agrega imágenes → Completa datos
→ Envía formulario → API guarda álbum → Guarda imágenes
→ Envía notificaciones a seguidores → Actualiza vista
```

### **2. Editar Álbum:**
```
Usuario → Clic en "Editar álbum" → Modal se abre
→ Carga datos actuales → Usuario modifica
→ Guarda cambios → API actualiza BD → Actualiza vista
```

### **3. Seguir Usuario:**
```
Usuario A → Clic en "Seguir" → API crea registro 'pendiente'
→ Notificación a Usuario B → Usuario B acepta/rechaza
→ Estado cambia a 'activo' → Polling actualiza botón
```

### **4. Ver Detalle de Álbum:**
```
Usuario → Clic en álbum → Modal se abre
→ API carga imágenes → Verifica estado de seguimiento
→ Muestra carrusel → Permite likes/comentarios
```

### **5. Ver Álbum Virtual "Me gusta":**
```
Usuario → Perfil → Tab "Me gusta" → Clic en card de usuario seguido
→ Verifica seguimiento activo → API carga imágenes likeadas
→ Modal muestra portadas + imágenes → Permite navegación y comentarios
→ Solo visible si el seguimiento está activo
```

---

## 🛠️ FUNCIONALIDADES RECIENTES IMPLEMENTADAS

### **Sistema de Edición Completo de Álbumes:**
1. ✅ Edición de título, portada y privacidad
2. ✅ Eliminación de portada (usa `imagen.png` por defecto)
3. ✅ Agregar nuevas imágenes a álbum existente
4. ✅ Eliminar imágenes individuales
5. ✅ Editar título y descripción de imágenes
6. ✅ Preview de portada con botón de eliminación
7. ✅ Lista de imágenes con botones de edición/eliminación
8. ✅ Botones con estilos personalizados:
   - "Guardar cambios": `btn-orange-full` (naranja)
   - "Cancelar": `btn-secondary` (gris)
   - "Agregar imágenes": `btn-success-full` (verde)

### **Sistema de Seguimiento Mejorado:**
1. ✅ Verificación automática de estado al abrir modal
2. ✅ Polling cada 5 segundos para actualización automática
3. ✅ Botones con estados visuales correctos
4. ✅ Enlaces clickeables a perfil desde modal (nickname, @username, foto de perfil)
5. ✅ Redirección al perfil al hacer clic en "Seguir" (si no sigue)
6. ✅ Funciones globales para verificar y actualizar estado de seguimiento en modales

### **Sistema de "Me gusta" - Álbum Virtual:**
1. ✅ Álbum virtual dinámico en sección "Me gusta" del perfil
2. ✅ Muestra todas las fotos (portadas de álbumes + imágenes individuales) a las que el usuario dio like
3. ✅ Solo visible si el usuario sigue activamente al otro usuario (estado 'activo')
4. ✅ Agrupa contenido por usuario seguido
5. ✅ Muestra apodo y @username del usuario seguido
6. ✅ Usa el mismo modal de detalle de álbum para visualización
7. ✅ Distingue entre portadas (sin likes/comentarios) e imágenes individuales (con likes/comentarios)
8. ✅ Endpoint dedicado: `/api/detalleLikesUsuario`
9. ✅ Funcionalidad completa de likes y comentarios en imágenes individuales

### **Mejoras de Accesibilidad:**
1. ✅ Corrección de `aria-hidden` en modales Bootstrap
2. ✅ Atributo `aria-modal="true"` para mejor accesibilidad
3. ✅ Prevención de conflictos entre event listeners de modales
4. ✅ Identificación de cards especiales con `data-tipo="likes-usuario"`

---

## 📝 ENDPOINTS API COMPLETOS

### **Álbumes:**
- `POST /api/guardar-album` - Crear álbum
- `POST /api/editarAlbum` - Editar álbum
- `POST /api/eliminarAlbum` - Eliminar álbum
- `GET /api/detalleAlbum` - Obtener detalle de álbum
- `GET /api/obtenerDatosAlbum` - Obtener datos para edición
- `GET /api/imagenesDeAlbum` - Obtener imágenes de álbum
- `GET /api/detalleLikesUsuario` - Obtener álbum virtual "Me gusta" de un usuario seguido

### **Imágenes:**
- `POST /api/agregarImagenesAlbum` - Agregar imágenes a álbum
- `POST /api/eliminarImagen` - Eliminar imagen
- `POST /api/actualizarImagen` - Editar imagen
- `POST /api/eliminarPortadaAlbum` - Eliminar portada

### **Interacción Social:**
- `POST /api/seguir` - Seguir usuario
- `POST /api/dejarSeguir` - Dejar de seguir
- `POST /api/checkFollowStatus` - Verificar estado
- `POST /api/megusta` - Like/Unlike álbum
- `GET /api/obtenerLikes` - Obtener likes de imagen
- `POST /api/agregarComentario` - Agregar comentario
- `POST /api/responderSolicitud` - Responder solicitud de seguimiento

### **Notificaciones:**
- `GET /api/listarNotificaciones` - Listar notificaciones
- `POST /api/notificaciones` - Gestionar notificaciones

---

## 🎯 PUNTOS FUERTES DEL CÓDIGO

1. ✅ **Arquitectura MVC clara y organizada**
2. ✅ **Separación de responsabilidades**
3. ✅ **Uso de prepared statements (seguridad)**
4. ✅ **Validación de archivos y datos**
5. ✅ **Manejo de errores con try-catch**
6. ✅ **Interfaz responsive y moderna**
7. ✅ **Sistema de notificaciones en tiempo real**
8. ✅ **Polling para actualización automática de estados**
9. ✅ **Funciones globales reutilizables (tiempo relativo, fechas)**
10. ✅ **Prevención de conflictos entre event listeners**
11. ✅ **Mejoras de accesibilidad (ARIA attributes)**
12. ✅ **Álbum virtual dinámico basado en likes**

---

## 🔮 ÁREAS DE MEJORA POTENCIALES

1. **Optimización:**
   - Implementar caché para consultas frecuentes
   - Optimizar imágenes (compresión, thumbnails)
   - Lazy loading de imágenes

2. **Funcionalidades:**
   - Búsqueda avanzada de álbumes
   - Filtros por categorías/etiquetas
   - Compartir álbumes en redes sociales
   - Descarga de imágenes

3. **Seguridad:**
   - Rate limiting en endpoints
   - Validación más estricta de tipos MIME
   - Sanitización adicional de inputs

4. **UX:**
   - Drag & drop para subir imágenes
   - Preview en tiempo real de ediciones
   - Notificaciones push (WebSockets)

---

## 📞 CONCLUSIÓN

El proyecto **Artesanos** es una aplicación web funcional y bien estructurada que implementa las funcionalidades principales de una red social para artesanos. El código sigue buenas prácticas de desarrollo, con una arquitectura MVC clara, validaciones de seguridad y una interfaz de usuario moderna y responsive.

**Estado actual:** ✅ **FUNCIONAL Y COMPLETO**

---

## 📋 RESUMEN DE CAMBIOS RECIENTES

### **Versión 2.1 - Sistema de "Me gusta" y Mejoras de Edición:**

#### **Nuevos Endpoints:**
- `GET /api/detalleLikesUsuario` - Álbum virtual de contenido likeado

#### **Nuevos Métodos en Modelos:**
- `AlbumModelo::obtenerTodasImagenesLikeadas()` - Obtiene portadas e imágenes likeadas
- `AlbumModelo::obtenerDatosUsuarioPorId()` - Obtiene datos de usuario
- `ImagenModelo::eliminarImagen()` - Elimina imagen individual
- `ImagenModelo::actualizarImagen()` - Actualiza metadatos de imagen

#### **Nuevos Controladores:**
- `app/controllers/detalleLikesUsuario.php` - Gestiona el álbum virtual "Me gusta"
- `app/controllers/eliminarImagen.php` - Elimina imagen individual
- `app/controllers/actualizarImagen.php` - Actualiza imagen
- `app/controllers/agregarImagenesAlbum.php` - Agrega imágenes a álbum existente
- `app/controllers/obtenerDatosAlbum.php` - Obtiene datos para edición
- `app/controllers/eliminarPortadaAlbum.php` - Elimina portada de álbum

#### **Mejoras en Frontend:**
- Función global `cargarDetalleLikesUsuario()` para álbum virtual
- Funciones globales de fecha (`tiempoRelativo`, `parseMySQLDateToMs`)
- Prevención de conflictos entre modales
- Corrección de accesibilidad (ARIA)
- Estilos personalizados para botones de edición

#### **Mejoras en Backend:**
- Manejo correcto de `imagen.png` como portada por defecto
- Validación de seguimiento activo para álbum virtual
- Distinción entre portadas e imágenes individuales en likes
- Manejo de errores mejorado con `error_reporting`

---

*Informe actualizado: Diciembre 2024*
*Versión del proyecto: 2.1*

