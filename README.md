# Artesanos – Álbumes Sociales para Artesanos

![Status](https://img.shields.io/badge/Status-Active-success?style=flat)
![Project Type](https://img.shields.io/badge/Type-Social%20Albums-blue?style=flat)
![Made With](https://img.shields.io/badge/Made%20With-PHP%20%2B%20JS%20%2B%20Bootstrap-9cf?style=flat)

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?logo=javascript&logoColor=black)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-Dev%20Server-FB7A24?logo=xampp&logoColor=white)

## Descripción

Artesanos es una aplicación web sencilla y directa para compartir **álbumes de imágenes**, pensada para artesanos y creadores. Permite crear álbumes, subir imágenes con sus detalles, recibir “me gusta” y comentarios, y seguir a otros usuarios.

## Características Clave

- Creación de álbumes con portada e información básica.
- Subida de imágenes por etapas: añade una imagen, completa sus detalles y luego “Agregar otra”.
- Likes de álbum e imagen, con contadores visibles en galería y modal.
- Comentarios por imagen dentro del modal de detalle.
- Seguimiento entre usuarios con contador de seguidores (solo estado “activo”).
- Tiempo relativo del álbum mostrado con la hora local del dispositivo.

## Tech Stack

- Backend: `PHP 8.2`, `MySQL 8.0` (XAMPP)
- Frontend: `JavaScript (ES6)`, `Bootstrap 5`
- Servidor embebido: `php -S` para desarrollo local

## Estructura del Proyecto (resumen)

```
app/
  controllers/   # Lógica de rutas (PHP)
  models/        # Acceso a datos (PHP)
  views/         # Vistas y plantillas (PHP + HTML)
public/
  assets/js/     # JS del frontend
  assets/css/    # Estilos
  uploads/       # Archivos subidos (portadas, imágenes, avatars)
```

## Puesta en Marcha (local)

1. Requisitos: `XAMPP`, `PHP 8.2+`, `MySQL 8+`.
2. Arranca el servidor embebido:
   - `php -S 127.0.0.1:8000 -t c:\xampp\htdocs\Social_Artesanos\Artesanos`
3. Abre `http://127.0.0.1:8000/app/views/home.php`.

> [!NOTE]
> El cálculo del tiempo relativo usa `Date.now()` del dispositivo, evitando desfases por zona horaria.
