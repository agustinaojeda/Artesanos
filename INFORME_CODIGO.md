# 📋 INFORME TÉCNICO - PROYECTO ARTESANOS

## 👋 Hola compañeras!

Les escribo este informe para que entiendan bien cómo funciona **Artesanos**, qué tecnologías usamos y cuáles son las funcionalidades más importantes que implementamos. Espero que les sirva para entender el proyecto completo.

---

## 🎯 ¿QUÉ ES ARTESANOS?

**Artesanos** es una aplicación web social tipo Instagram/Pinterest pero enfocada en artesanos. La idea es que los usuarios puedan crear álbumes de fotos de sus trabajos artesanales, compartirlos, recibir likes y comentarios, y seguir a otros artesanos.

La aplicación está construida con:
- **Backend:** PHP 8.2 + MySQL 8.0
- **Frontend:** JavaScript ES6 + Bootstrap 5
- **Arquitectura:** MVC (Modelo-Vista-Controlador)
- **Servidor:** WAMP/XAMPP para desarrollo local

---

## 📁 ESTRUCTURA DEL PROYECTO

```
Artesanos/
├── app/
│   ├── controllers/     # Aquí está la lógica de negocio (endpoints API)
│   ├── models/          # Acceso a la base de datos (consultas SQL)
│   └── views/           # Las páginas HTML/PHP que ve el usuario
├── config/              # Configuración (conexión a BD, rutas, etc.)
├── public/              # Punto de entrada público
│   ├── assets/
│   │   ├── js/          # Todo el JavaScript del frontend
│   │   └── css/         # Estilos CSS
│   └── uploads/        # Imágenes subidas por usuarios
│       ├── portadas/   # Portadas de álbumes
│       ├── imagenes/   # Imágenes dentro de álbumes
│       └── avatars/    # Fotos de perfil
└── scripts/            # Scripts SQL para crear la BD
```

---

## 🔧 ARQUITECTURA MVC

Seguimos el patrón **MVC** para mantener el código organizado:

### **Modelos (`app/models/`):**
Son los que hablan directamente con la base de datos. Los más importantes son:

- **`albumModelo.php`** - Todo lo relacionado con álbumes:
  - `crearAlbum()` - Crea un nuevo álbum en la BD
  - `actualizarAlbum()` - Actualiza título, portada, privacidad
  - `eliminarAlbum()` - Elimina álbum y todas sus imágenes
  - `obtenerImagenesAlbum()` - Trae todas las imágenes de un álbum
  - `eliminarPortadaAlbum()` - Elimina la portada (usa `imagen.png` por defecto)
  - `obtenerTodasImagenesLikeadas()` - **Esta es compleja**: obtiene todas las fotos (portadas de álbumes + imágenes individuales) que un usuario le dio like a otro usuario seguido. Es para el álbum virtual "Me gusta"
  - `obtenerDatosUsuarioPorId()` - Obtiene datos de un usuario por su ID
  - `contarAlbumesDeUsuario()` - Cuenta cuántos álbumes tiene un usuario
  - `contarSeguidoresDeUsuario()` - Cuenta seguidores (solo los que tienen estado 'activo')

- **`imagenModelo.php`** - Operaciones con imágenes individuales:
  - `crearImagen()` - Crea una nueva imagen en un álbum
  - `eliminarImagen()` - Elimina una imagen específica (verifica que sea del usuario)
  - `actualizarImagen()` - Actualiza título y descripción de una imagen

- **`comentarioModelo.php`** - Gestión de comentarios en imágenes
- **`usuarioHelper.php`** - Funciones auxiliares (obtener avatar, etc.)

### **Controladores (`app/controllers/`):**
Son los endpoints de la API. Cada uno maneja una acción específica:

- `guardarAlbum.php` - Crea un nuevo álbum
- `editarAlbum.php` - Edita un álbum existente
- `eliminarAlbum.php` - Elimina un álbum
- `detalleAlbum.php` - Obtiene todos los datos de un álbum para mostrar en el modal
- `agregarImagenesAlbum.php` - Agrega nuevas imágenes a un álbum existente
- `eliminarImagen.php` - Elimina una imagen individual
- `actualizarImagen.php` - Actualiza título/descripción de una imagen
- `eliminarPortadaAlbum.php` - Elimina la portada de un álbum
- `obtenerDatosAlbum.php` - Obtiene datos básicos del álbum para el formulario de edición
- `detalleLikesUsuario.php` - **Esta es compleja**: genera el álbum virtual "Me gusta" de un usuario seguido
- `historialFotosPerfil.php` - Obtiene el historial de fotos de perfil
- `eliminarFotoPerfil.php` - Elimina la foto de perfil actual
- `eliminarFotoHistorial.php` - Elimina una foto del historial
- Y muchos más...

### **Vistas (`app/views/`):**
Son las páginas que ve el usuario:

- `home.php` - Página principal con el feed de álbumes públicos
- `perfil.php` - Perfil de usuario con sus álbumes y la sección "Me gusta"
- `editarPerfil.php` - Página para editar perfil con gestión de fotos
- `login.php` - Página de inicio de sesión
- `registro.php` - Página de registro
- `actualizarContrasena.php` - Página para cambiar contraseña después de recuperarla
- Y más...

### **Front Controller:**
- `public/index.php` - Este archivo recibe TODAS las peticiones HTTP y las enruta al controlador correcto. Es como el "director de tráfico" de la aplicación.

---

## 🚀 FUNCIONALIDADES PRINCIPALES

### 1. **GESTIÓN DE ÁLBUMES**

#### **Crear Álbum:**
Cuando un usuario quiere crear un álbum:
1. Selecciona una portada (imagen)
2. Agrega múltiples imágenes (una por una, con título y descripción)
3. Define si es público o solo para seguidores
4. Al guardar, se crea el álbum en la BD y se envían notificaciones a todos los seguidores

**Endpoint:** `POST /api/guardar-album`
**Archivos:** `app/controllers/guardarAlbum.php` → `AlbumModelo::crearAlbum()`

#### **Editar Álbum:**
Esta es una de las funcionalidades **más completas** que implementamos. Permite:
- ✅ Editar el título del álbum
- ✅ Cambiar la portada (subir nueva o eliminar la actual)
- ✅ Cambiar la privacidad (público/privado)
- ✅ Agregar nuevas imágenes al álbum
- ✅ Eliminar imágenes individuales
- ✅ Editar título y descripción de cada imagen

**Endpoint:** `POST /api/editarAlbum`
**Archivos:** `app/controllers/editarAlbum.php` + varios endpoints auxiliares

**Lo más complejo aquí:** El frontend tiene que manejar múltiples estados:
- Preview de la nueva portada antes de guardar
- Lista dinámica de imágenes con botones de editar/eliminar
- Formularios inline para editar cada imagen
- Validación de que solo el dueño puede editar

#### **Eliminar Álbum:**
Elimina el álbum, todas sus imágenes y los archivos físicos del servidor.

**Endpoint:** `POST /api/eliminarAlbum`

#### **Ver Detalle de Álbum:**
Cuando un usuario hace clic en un álbum, se abre un modal con:
- Carrusel de imágenes (Bootstrap Carousel)
- Información del usuario que publicó (foto, apodo, @username)
- Botón "Seguir" con estado dinámico (Seguir/Pendiente/Siguiendo)
- Likes y comentarios por cada imagen
- Tiempo relativo de publicación ("hace 2 horas", "hace 3 días", etc.)
- Enlaces clickeables al perfil del usuario (foto, apodo, @username)

**Endpoint:** `GET /api/detalleAlbum`
**Archivos:** `app/controllers/detalleAlbum.php` + `public/assets/js/home.js` y `perfil.js`

**Lo más complejo aquí:**
- El carrusel se actualiza dinámicamente cuando cambias de imagen
- Los likes y comentarios se cargan por imagen
- El botón "Seguir" se actualiza automáticamente cada 5 segundos (polling)
- Si eres el dueño del álbum, el botón "Seguir" no aparece
- Si el álbum es ajeno, aparece un menú de 3 puntitos naranjas con opciones de denuncia (sin funcionalidad aún)

---

### 2. **SISTEMA DE "ME GUSTA" - ÁLBUM VIRTUAL**

Esta es una de las funcionalidades **más complicadas** que implementamos. La idea es:

Cuando sigues a alguien y le das like a sus fotos, en tu perfil aparece un álbum virtual con todas las fotos que le diste like a esa persona.

**Características:**
- Solo aparece si sigues activamente a esa persona (estado 'activo' en la tabla `seguimiento`)
- Muestra tanto portadas de álbumes como imágenes individuales que le diste like
- Se agrupa por usuario seguido (cada usuario seguido tiene su propio álbum virtual)
- Usa el mismo modal de detalle de álbum para mostrarlo
- Las portadas no permiten likes/comentarios individuales, pero las imágenes sí

**Endpoint:** `GET /api/detalleLikesUsuario`
**Archivos:** 
- `app/controllers/detalleLikesUsuario.php`
- `app/models/albumModelo.php::obtenerTodasImagenesLikeadas()` - **Esta función es compleja**
- `public/assets/js/perfil.js::cargarDetalleLikesUsuario()` - **Función global compleja**

**Lo más complejo aquí:**
- La función `obtenerTodasImagenesLikeadas()` hace DOS consultas SQL:
  1. Una para obtener portadas de álbumes likeadas (que se tratan como "imágenes virtuales")
  2. Otra para obtener imágenes individuales likeadas
- Luego combina ambos resultados en un solo array
- Maneja correctamente el caso de `imagen.png` (portada por defecto)
- El frontend tiene que distinguir entre portadas e imágenes para mostrar/ocultar botones de like y comentarios
- Hay que prevenir conflictos entre el click en cards de "Me gusta" y cards de álbumes normales

---

### 3. **GESTIÓN DE IMÁGENES**

#### **Agregar Imágenes a Álbum Existente:**
Permite agregar nuevas imágenes a un álbum que ya existe.

**Endpoint:** `POST /api/agregarImagenesAlbum`

#### **Eliminar Imagen Individual:**
Elimina una imagen específica de un álbum (verifica que el usuario sea el dueño).

**Endpoint:** `POST /api/eliminarImagen`

#### **Editar Imagen:**
Permite editar el título y descripción de una imagen individual.

**Endpoint:** `POST /api/actualizarImagen`

**Lo más complejo aquí:** El frontend tiene que actualizar la vista en tiempo real sin recargar la página, y manejar múltiples formularios inline.

---

### 4. **SISTEMA DE SEGUIMIENTO (FOLLOW/UNFOLLOW)**

Este sistema tiene varios estados y es bastante completo:

#### **Estados de Seguimiento:**
- `pendiente` - Enviaste una solicitud, pero el otro usuario aún no la aceptó
- `activo` - El seguimiento está activo (el otro usuario aceptó)
- `rechazado` - El otro usuario rechazó tu solicitud

#### **Funcionalidades:**
- **Seguir usuario:** Crea una solicitud de seguimiento
- **Dejar de seguir:** Elimina el seguimiento (funciona tanto si está pendiente como activo)
- **Responder solicitud:** El usuario puede aceptar o rechazar una solicitud
- **Verificar estado:** Endpoint que devuelve el estado actual del seguimiento

**Endpoints:**
- `POST /api/seguir` - Seguir usuario
- `POST /api/dejarSeguir` - Dejar de seguir
- `POST /api/checkFollowStatus` - Verificar estado
- `POST /api/responderSolicitud` - Aceptar/rechazar solicitud

**Lo más complejo aquí:**
- El botón "Seguir" cambia de estado dinámicamente:
  - Si no sigues: muestra "Seguir" (naranja)
  - Si está pendiente: muestra "Pendiente" (gris)
  - Si ya sigues: muestra "Siguiendo" (verde)
- En los modales, el botón se actualiza automáticamente cada 5 segundos (polling)
- Si haces clic en "Seguir" y no sigues, te redirige al perfil del usuario
- Si eres el dueño del álbum, el botón "Seguir" no aparece

---

### 5. **SISTEMA DE LIKES**

Hay dos tipos de likes:

#### **Like de Álbum (Portada):**
Cuando le das like a la portada de un álbum, le estás dando like al álbum completo.

**Endpoint:** `POST /api/megusta`

#### **Like de Imagen Individual:**
Cada imagen dentro de un álbum puede tener sus propios likes.

**Endpoint:** `GET /api/obtenerLikes` (para obtener) y `POST /api/obtenerLikes` (para dar/quitar like)

**Lo más complejo aquí:**
- Los likes se actualizan en tiempo real sin recargar
- El icono cambia (corazón vacío vs corazón lleno)
- El contador se actualiza dinámicamente
- Las portadas likeadas aparecen en el álbum virtual "Me gusta", pero no permiten likes/comentarios individuales

---

### 6. **SISTEMA DE COMENTARIOS**

Los comentarios están asociados a imágenes específicas (no a álbumes completos).

**Endpoint:** `POST /api/agregarComentario`

**Características:**
- Se muestran debajo de cada imagen en el modal
- Se actualizan en tiempo real
- Muestran avatar, apodo y mensaje del usuario
- Máximo 200 caracteres por comentario

---

### 7. **GESTIÓN DE PERFIL**

#### **Editar Perfil:**
Permite editar:
- Apodo
- Descripción
- Foto de perfil (con preview inmediato antes de guardar)

**Endpoint:** `POST /api/editarPerfil`

#### **Historial de Fotos de Perfil:**
Esta es una funcionalidad **completa** que implementamos:
- Guarda un historial de todas las fotos de perfil que usaste
- Las muestra como círculos pequeños debajo de la foto actual
- Puedes hacer clic en una foto del historial para verla en grande
- Puedes seleccionar una foto del historial para usarla como foto actual
- Puedes eliminar fotos del historial
- Puedes eliminar la foto actual (vuelve a `imagen.png` por defecto)

**Endpoints:**
- `GET /api/historialFotosPerfil` - Obtiene el historial
- `GET /api/detalleFotoHistorial` - Obtiene detalles de una foto específica
- `POST /api/eliminarFotoPerfil` - Elimina la foto actual
- `POST /api/eliminarFotoHistorial` - Elimina una foto del historial

**Archivos:**
- `app/views/editarPerfil.php` - La vista con toda la lógica
- `app/controllers/historialFotosPerfil.php` y otros

**Lo más complejo aquí:**
- El preview de la nueva foto se muestra inmediatamente cuando seleccionas un archivo (sin guardar)
- El historial se carga dinámicamente con AJAX
- Cada foto del historial tiene un botón de eliminar (excepto la actual)
- La foto actual tiene un botón de eliminar que la vuelve a la imagen por defecto
- Todo usa SweetAlert2 para las confirmaciones (muy lindo visualmente)

---

### 8. **NOTIFICACIONES**

El sistema envía notificaciones automáticas cuando:
- Alguien te sigue
- Alguien comenta tu imagen
- Alguien le da like a tu álbum/imagen
- Un usuario que sigues crea un nuevo álbum

**Endpoint:** `GET /api/listarNotificaciones`

**Características:**
- Se muestran en un dropdown en la barra de navegación
- Tienen un contador de no leídas
- Se marcan como leídas al hacer clic

---

### 9. **SISTEMA DE DENUNCIAS**

Implementamos la UI para denunciar contenido, pero aún no tiene funcionalidad real:

- Menú de 3 puntitos naranjas en el modal de álbumes ajenos
- Opciones: "Denunciar álbum" y "Denunciar imagen actual"
- Por ahora solo están los botones, sin funcionalidad

---

## 🎨 FRONTEND - JAVASCRIPT

### **`public/assets/js/home.js`:**
Este archivo maneja toda la lógica del home:
- Modal de creación de álbumes
- Validación de formularios
- Preview de imágenes antes de subir
- Modal de detalle de álbum
- Sistema de seguimiento con polling
- Funciones para verificar y actualizar estado de seguimiento

**Funciones importantes:**
- `verificarEstadoSeguir()` - Verifica el estado de seguimiento
- `actualizarBotonSeguir()` - Actualiza visualmente el botón
- Polling cada 5 segundos para actualizar el botón "Seguir"

### **`public/assets/js/perfil.js`:**
Este archivo es **más complejo** porque maneja:
- Gestión completa de álbumes del perfil
- Modal de edición de álbumes (muy completo)
- Sistema de "Me gusta" con álbum virtual
- Gestión de fotos de perfil con historial

**Funciones globales importantes:**
- `tiempoRelativo()` - Convierte fechas MySQL a tiempo relativo ("hace 2 horas")
- `parseMySQLDateToMs()` - Convierte fechas MySQL a milisegundos
- `cargarDetalleLikesUsuario(idUsuario)` - **Función compleja**: carga el álbum virtual "Me gusta"
- `verificarEstadoSeguirModal()` - Verifica estado en modales
- `actualizarBotonSeguirModal()` - Actualiza botón en modales

**Lo más complejo aquí:**
- La función `cargarDetalleLikesUsuario()` tiene que:
  1. Abrir el modal de detalle
  2. Hacer una petición AJAX para obtener las imágenes likeadas
  3. Construir el HTML del carrusel dinámicamente
  4. Distinguir entre portadas (sin interacción) e imágenes (con likes/comentarios)
  5. Inicializar el carrusel de Bootstrap
  6. Manejar los comentarios y likes por imagen
  7. Prevenir conflictos con otros event listeners

---

## 🔐 SEGURIDAD

Implementamos varias medidas de seguridad:

1. **Prepared Statements:** Todas las consultas SQL usan `prepare()` y `bind_param()` para prevenir SQL injection
2. **Validación de Propiedad:** Antes de editar/eliminar, verificamos que el usuario sea el dueño
3. **Validación de Archivos:** Solo aceptamos imágenes (JPEG, PNG, GIF, WEBP) y validamos el tamaño
4. **Verificación de Sesión:** Todos los endpoints protegidos verifican que el usuario esté logueado
5. **Nombres Únicos:** Los archivos subidos se renombran con `bin2hex(random_bytes(6))` para evitar conflictos

---

## 📊 BASE DE DATOS

### **Tablas Principales:**

1. **`usuario`** - Datos de usuarios
   - `idUsuario`, `usuario` (username), `apodoUsuario`, `arrobaUsuario`
   - `idFotoPerfilUsuario` (FK a `fotosdeperfil`)

2. **`album`** - Álbumes de imágenes
   - `idAlbum`, `tituloAlbum`, `esPublicoAlbum`, `urlPortadaAlbum`
   - `idUsuarioAlbum` (FK), `fechaCreacionAlbum`

3. **`imagen`** - Imágenes dentro de álbumes
   - `idImagen`, `tituloImagen`, `descripcionImagen`, `urlImagen`
   - `idAlbumImagen` (FK), `fechaImagen`

4. **`seguimiento`** - Relación de seguimiento entre usuarios
   - `idSeguidor` (FK), `idSeguido` (FK)
   - `estadoSeguimiento` ('pendiente', 'activo', 'rechazado')
   - `fechaSeguimiento`

5. **`megusta`** - Likes en imágenes individuales
   - `idUsuarioLike` (FK), `idImagenLike` (FK), `fechaLike`

6. **`megusta_album`** - Likes en álbumes (portadas)
   - `idUsuarioLike` (FK), `idAlbumLike` (FK), `fechaLike`

7. **`comentario`** - Comentarios en imágenes
   - `idComentario`, `idUsuarioComentario` (FK), `idImagenComentario` (FK)
   - `mensajeComentario`, `fechaComentario`

8. **`notificaciones`** - Notificaciones del sistema
   - `idNotificacion`, `idUsuarioDestino` (FK), `idUsuarioAccion` (FK)
   - `tipo`, `mensaje`, `leida`, `fecha`

9. **`fotosdeperfil`** - Historial de fotos de perfil
   - `idFotoPerfil`, `idUsuarioFoto` (FK), `imagenPerfil`
   - `fechaUltimaUso`

---

## 🛠️ TECNOLOGÍAS Y HERRAMIENTAS

### **Backend:**
- **PHP 8.2** - Lenguaje principal del backend
- **MySQL 8.0** - Base de datos relacional
- **Prepared Statements** - Para seguridad en consultas SQL
- **Sesiones PHP** - Para autenticación de usuarios

### **Frontend:**
- **JavaScript ES6** - Lógica del frontend
- **Bootstrap 5** - Framework CSS para diseño responsive
- **Bootstrap Icons** - Iconos
- **SweetAlert2** - Alertas bonitas (solo en edición de perfil y recuperación de contraseña)
- **Fetch API** - Para peticiones AJAX
- **Bootstrap Carousel** - Para el carrusel de imágenes en modales

### **Arquitectura:**
- **MVC (Modelo-Vista-Controlador)** - Separación de responsabilidades
- **Front Controller Pattern** - `public/index.php` enruta todas las peticiones
- **RESTful API** - Endpoints organizados por recurso

---

## 🎯 FUNCIONES MÁS COMPLICADAS Y COMPLETAS

### 1. **`AlbumModelo::obtenerTodasImagenesLikeadas()`**
**Ubicación:** `app/models/albumModelo.php`

Esta función es **muy compleja** porque:
- Hace DOS consultas SQL diferentes (una para portadas, otra para imágenes)
- Combina ambos resultados en un solo array
- Maneja el caso especial de `imagen.png` (portada por defecto)
- Normaliza las URLs de las imágenes con `basename()`
- Marca cada elemento como "portada" o "imagen" para que el frontend sepa cómo tratarlo

**Por qué es complicada:** Tiene que unificar dos tipos diferentes de contenido (portadas de álbumes e imágenes individuales) en una sola estructura de datos coherente.

### 2. **`cargarDetalleLikesUsuario(idUsuario)`**
**Ubicación:** `public/assets/js/perfil.js`

Esta función es **compleja** porque:
- Es una función global (está fuera de `DOMContentLoaded`)
- Abre un modal y lo llena dinámicamente con datos de AJAX
- Construye el HTML del carrusel desde cero
- Distingue entre portadas (sin interacción) e imágenes (con likes/comentarios)
- Inicializa el carrusel de Bootstrap
- Maneja los event listeners para likes y comentarios
- Prevenir conflictos con otros modales

**Por qué es complicada:** Tiene que coordinar múltiples sistemas (modal, carrusel, likes, comentarios) y manejar estados dinámicos.

### 3. **Sistema de Edición de Álbumes**
**Ubicación:** `app/views/perfil.php` + `public/assets/js/perfil.js`

Este sistema es **muy completo** porque:
- Permite editar múltiples aspectos del álbum (título, portada, privacidad)
- Gestiona imágenes individuales (agregar, eliminar, editar)
- Tiene previews en tiempo real
- Maneja formularios inline para editar cada imagen
- Valida que solo el dueño pueda editar
- Actualiza la vista sin recargar la página

**Por qué es complicado:** Tiene que manejar múltiples estados, formularios dinámicos, y actualizaciones en tiempo real.

### 4. **Sistema de Seguimiento con Polling**
**Ubicación:** `public/assets/js/home.js` y `perfil.js`

Este sistema es **completo** porque:
- Verifica el estado de seguimiento automáticamente
- Actualiza el botón cada 5 segundos (polling)
- Maneja tres estados diferentes (Seguir/Pendiente/Siguiendo)
- Funciona tanto en la página principal como en modales
- Oculta el botón si eres el dueño del contenido

**Por qué es complicado:** Tiene que mantener sincronizado el estado del botón con la base de datos en tiempo real.

### 5. **Gestión de Historial de Fotos de Perfil**
**Ubicación:** `app/views/editarPerfil.php`

Este sistema es **completo** porque:
- Guarda un historial de todas las fotos usadas
- Muestra preview inmediato antes de guardar
- Permite seleccionar una foto del historial para usar como actual
- Permite eliminar fotos del historial
- Permite eliminar la foto actual
- Usa SweetAlert2 para confirmaciones bonitas

**Por qué es complicado:** Tiene que manejar múltiples estados (foto actual, historial, preview), y coordinar entre frontend y backend.

---

## 🎨 MEJORAS DE UI/UX

### **SweetAlert2:**
Usamos SweetAlert2 para las alertas de edición de perfil y recuperación de contraseña. Es mucho más bonito que los `alert()` nativos.

### **Botones con Estilos Personalizados:**
- `btn-orange-full` - Botones naranjas (Seguir, Guardar cambios, etc.)
- `btn-success-full` - Botones verdes (Siguiendo, Agregar imágenes)
- `btn-secondary` - Botones grises (Cancelar, Pendiente)

### **Tiempo Relativo:**
Las fechas se muestran como "hace 2 horas", "hace 3 días", etc., calculadas con la hora local del dispositivo.

### **Accesibilidad:**
- Corregimos los atributos `aria-hidden` y `aria-modal` en los modales
- Los modales son accesibles para lectores de pantalla

---

## 📝 ENDPOINTS API COMPLETOS

### **Álbumes:**
- `POST /api/guardar-album` - Crear álbum
- `POST /api/editarAlbum` - Editar álbum
- `POST /api/eliminarAlbum` - Eliminar álbum
- `GET /api/detalleAlbum` - Obtener detalle de álbum
- `GET /api/obtenerDatosAlbum` - Obtener datos para edición
- `GET /api/imagenesDeAlbum` - Obtener imágenes de álbum
- `GET /api/detalleLikesUsuario` - Obtener álbum virtual "Me gusta"
- `POST /api/eliminarPortadaAlbum` - Eliminar portada

### **Imágenes:**
- `POST /api/agregarImagenesAlbum` - Agregar imágenes a álbum
- `POST /api/eliminarImagen` - Eliminar imagen
- `POST /api/actualizarImagen` - Editar imagen

### **Interacción Social:**
- `POST /api/seguir` - Seguir usuario
- `POST /api/dejarSeguir` - Dejar de seguir
- `POST /api/checkFollowStatus` - Verificar estado de seguimiento
- `POST /api/megusta` - Like/Unlike álbum
- `GET /api/obtenerLikes` - Obtener likes de imagen
- `POST /api/agregarComentario` - Agregar comentario
- `POST /api/responderSolicitud` - Responder solicitud de seguimiento

### **Perfil:**
- `POST /api/editarPerfil` - Editar perfil
- `GET /api/historialFotosPerfil` - Obtener historial de fotos
- `GET /api/detalleFotoHistorial` - Obtener detalles de foto del historial
- `POST /api/eliminarFotoPerfil` - Eliminar foto actual
- `POST /api/eliminarFotoHistorial` - Eliminar foto del historial

### **Notificaciones:**
- `GET /api/listarNotificaciones` - Listar notificaciones
- `POST /api/notificaciones` - Gestionar notificaciones

### **Autenticación:**
- `POST /api/recuperar` - Recuperar contraseña (simulado)
- `POST /login` - Iniciar sesión
- `POST /registro` - Registrar usuario

---

## 🔄 FLUJOS DE TRABAJO PRINCIPALES

### **1. Crear un Álbum:**
```
Usuario → Selecciona portada → Agrega imágenes una por una
→ Completa título y descripción de cada imagen
→ Define privacidad → Guarda
→ Backend crea álbum en BD → Guarda imágenes
→ Envía notificaciones a seguidores → Muestra mensaje de éxito
```

### **2. Editar un Álbum:**
```
Usuario → Clic en "Editar álbum" → Modal se abre
→ Carga datos actuales del álbum
→ Usuario modifica título/portada/privacidad
→ Usuario agrega/elimina/edita imágenes
→ Guarda cambios → Backend actualiza BD
→ Actualiza vista sin recargar página
```

### **3. Ver Álbum Virtual "Me gusta":**
```
Usuario → Va a su perfil → Tab "Me gusta"
→ Ve cards de usuarios seguidos a los que les dio like
→ Clic en una card → Verifica que el seguimiento esté activo
→ Backend obtiene todas las imágenes likeadas (portadas + imágenes)
→ Frontend construye carrusel dinámicamente
→ Muestra portadas e imágenes en el mismo modal
→ Permite navegar, dar likes y comentar
```

### **4. Seguir a un Usuario:**
```
Usuario A → Clic en "Seguir" → Backend crea registro 'pendiente'
→ Notificación a Usuario B
→ Usuario B acepta/rechaza
→ Estado cambia a 'activo' o 'rechazado'
→ Polling actualiza botón automáticamente cada 5 segundos
```

---

## 🎯 PUNTOS FUERTES DEL CÓDIGO

1. ✅ **Arquitectura MVC clara** - Fácil de entender y mantener
2. ✅ **Separación de responsabilidades** - Cada archivo tiene su función específica
3. ✅ **Seguridad** - Prepared statements, validación de propiedad, validación de archivos
4. ✅ **Interfaz moderna** - Bootstrap 5, diseño responsive, modales bonitos
5. ✅ **Funcionalidades completas** - Edición completa de álbumes, sistema de seguimiento, álbum virtual
6. ✅ **Actualización en tiempo real** - Polling, actualización sin recargar
7. ✅ **Manejo de errores** - Try-catch, validaciones, mensajes de error claros
8. ✅ **Código reutilizable** - Funciones globales, helpers, utilidades
9. ✅ **Accesibilidad** - Atributos ARIA, modales accesibles
10. ✅ **UX mejorada** - SweetAlert2, previews, tiempo relativo

---

## 🔮 FUNCIONALIDADES FUTURAS (Pendientes)

1. **Sistema de Denuncias:** Ya tenemos la UI, falta implementar la lógica
2. **Búsqueda Avanzada:** Filtros por categorías, etiquetas, etc.
3. **Compartir en Redes Sociales:** Botones para compartir álbumes
4. **Optimización de Imágenes:** Compresión, thumbnails, lazy loading
5. **Notificaciones Push:** WebSockets para notificaciones en tiempo real
6. **Drag & Drop:** Para subir imágenes más fácilmente

---

## 📞 CONCLUSIÓN

**Artesanos** es una aplicación web completa y funcional que implementa las características principales de una red social para artesanos. El código está bien organizado, sigue buenas prácticas, y es fácil de entender y mantener.

Las funcionalidades más complejas son:
- El sistema de "Me gusta" con álbum virtual
- La edición completa de álbumes
- El sistema de seguimiento con polling
- La gestión de historial de fotos de perfil

Todas estas funcionalidades están completamente implementadas y funcionando.

**Estado actual:** ✅ **FUNCIONAL Y COMPLETO**

---

*Informe actualizado: Diciembre 2024*
*Versión del proyecto: 2.2*
