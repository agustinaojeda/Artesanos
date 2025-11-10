<?php
// incluir conexión (ruta robusta)
require_once CONFIG_PATH . '/conexion.php';

// helper
function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Verificar sesión
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: ' . $basePath . '/login');
    exit;
}

$conexion = abrirConexion();
if ($conexion === false || $conexion->connect_error) {
    die("Error de conexión a la base de datos.");
}

$usuarioId = (int)$_SESSION['usuario']['id'];

// Obtener datos actuales del usuario
$sql = "
    SELECT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.apodoUsuario, u.arrobaUsuario, u.descripcionUsuario, u.privacidadUsuario,
    u.correoUsuario, u.idFotoPerfilUsuario, fp.imagenPerfil AS avatarActual 
    FROM usuario u
    LEFT JOIN fotosdeperfil fp ON fp.idFotoPerfil = u.idFotoPerfilUsuario
    WHERE u.idUsuario = ? LIMIT 1
";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

if (!$userData) {
    die("Usuario no encontrado.");
}

// Fallbacks si la base aún no tiene estas columnas
$userData['descripcionUsuario'] = $userData['descripcionUsuario'] ?? '';
$userData['privacidadUsuario'] = $userData['privacidadUsuario'] ?? 'publico';

// determinar url del avatar (para mostrar)
if (!empty($userData['avatarActual'])) {
    $avatarUrl = "$basePath/uploads/avatars/" . e($userData['avatarActual']);
} else {
    $avatarUrl = "$basePath/assets/images/imagen.png";
}

// si hay errores guardados en sesión, recupéralos
$errors = $_SESSION['errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['errors'], $_SESSION['form_data']);

include VIEW_PATH . '/header.php'; 
include VIEW_PATH . '/nav.php'; 
?>

<style>
.container {
    max-width: 1000px;
}

.avatar-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #f7931e;
}

@media (max-width:768px) {
    .form-row {
        flex-direction: column;
    }
}

.btn-custom-orange {
    background-color: #f7931e;
    border-color: #f7931e;
    color: #fff;
}

.btn-custom-orange:hover {
    background-color: #e58514;
    border-color: #e58514;
    color: #fff;
}

.historial-foto-mini {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #f7931e;
    cursor: pointer;
    transition: transform 0.2s, border-color 0.2s;
}

.historial-foto-mini:hover {
    transform: scale(1.1);
    border-color: #e58514;
    box-shadow: 0 2px 8px rgba(247, 147, 30, 0.4);
}

.historial-foto-mini.actual {
    border-width: 3px;
    border-color: #28a745;
}

.btn-orange-full {
    background-color: #f7931e;
    border-color: #f7931e;
    color: #fff;
}

.btn-orange-full:hover {
    background-color: #e58514;
    border-color: #e58514;
    color: #fff;
}
</style>

    <div class="container py-5">
        <h2 class="text-center mb-4">Editar perfil</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="procesarEdicion" method="post" enctype="multipart/form-data" novalidate>
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <!-- Foto de perfil principal -->
                    <div class="position-relative d-inline-block mb-3">
                        <img id="avatarPreview" src="<?= e($avatarUrl) ?>" alt="Avatar" class="avatar-preview">
                        <?php if (!empty($userData['avatarActual'])): ?>
                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 rounded-circle shadow-sm" 
                                style="width: 32px; height: 32px; padding: 0; line-height: 32px; font-size: 16px; border: 2px solid white;"
                                id="btnEliminarFotoPerfil" 
                                title="Eliminar foto de perfil">
                            <i class="bi bi-x"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Historial de fotos (círculos pequeños) -->
                    <div class="mb-3">
                        <label class="form-label d-block mb-2">Utilizadas anteriormente</label>
                        <div id="historialFotosContainer" class="d-flex flex-wrap justify-content-center gap-2">
                            <!-- Las fotos se cargarán aquí dinámicamente como círculos pequeños -->
                            <div class="text-center w-100">
                                <small class="text-muted">Cargando historial...</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Input para cambiar foto -->
                    <div class="mb-3">
                        <label class="form-label d-block">Cambiar foto</label>
                        <input type="file" name="new_avatar" id="inputAvatar" accept="image/*" class="form-control">
                    </div>
                    <small class="text-muted">jpg, png, webp, gif &lt; 2MB</small>
                </div>

                <div class="col-md-8">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombreUsuario" class="form-control" required
                            value="<?= e($form_data['nombreUsuario'] ?? $userData['nombreUsuario']) ?>">
                    </div>

                    <div class="mb-3 row">
                        <div class="col-md-6">
                            <label class="form-label">Apellido</label>
                            <input type="text" name="apellidoUsuario" class="form-control" required
                                value="<?= e($form_data['apellidoUsuario'] ?? $userData['apellidoUsuario']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">@Usuario (arroba)</label>
                            <input type="text" name="arrobaUsuario" class="form-control" required
                                value="<?= e($form_data['arrobaUsuario'] ?? $userData['arrobaUsuario']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Apodo</label>
                        <input type="text" name="apodoUsuario" class="form-control"
                            value="<?= e($form_data['apodoUsuario'] ?? $userData['apodoUsuario']) ?>">
                    </div>

                    <div class="mb-3 row">
                        <div class="col-md-6">
                            <label class="form-label">Correo (público/Contacto)</label>
                            <input type="email" name="correoUsuario" class="form-control" required
                                value="<?= e($form_data['correoUsuario'] ?? $userData['correoUsuario']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Privacidad</label>
                            <select name="privacidadUsuario" class="form-select" required>
                                <option value="publico" <?= (($form_data['privacidadUsuario'] ?? $userData['privacidadUsuario']) === 'publico' ? 'selected' : '') ?>>Público</option>
                                <option value="privado" <?= (($form_data['privacidadUsuario'] ?? $userData['privacidadUsuario']) === 'privado' ? 'selected' : '') ?>>Privado</option>
                            </select>
                        </div>
                    </div>


                    <div class="mb-3">
                        <label class="form-label">Descripción / Biografía</label>
                        <textarea name="descripcionUsuario" class="form-control"><?= e($form_data['descripcionUsuario'] ?? $userData['descripcionUsuario']) ?></textarea>
                    </div>

                    <div class="mb-3 row">
                        <div class="col-md-6">
                            <label class="form-label">Nueva contraseña (opcional)</label>
                            <input type="password" name="new_password" class="form-control" minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirmar nueva contraseña</label>
                            <input type="password" name="confirm_new_password" class="form-control" minlength="6">
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="perfil?id=<?= $usuarioId ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-custom-orange">Guardar cambios</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal para ver detalles de foto del historial -->
    <div class="modal fade" id="modalDetalleFotoHistorial" tabindex="-1" aria-labelledby="modalDetalleFotoHistorialLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetalleFotoHistorialLabel">Foto de perfil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalFotoHistorial" src="" alt="Foto del historial" class="img-fluid rounded-circle mb-3" style="max-width: 300px; width: 100%; aspect-ratio: 1/1; object-fit: cover;">
                    <p id="modalFechaHistorial" class="text-muted mb-3"></p>
                    <button type="button" class="btn btn-orange-full" id="btnUsarFotoHistorial">
                        <i class="bi bi-check-circle me-2"></i> Usar como foto de perfil
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Preview inmediato de foto al seleccionar archivo
    document.getElementById('inputAvatar')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('avatarPreview');
                if (preview) {
                    preview.src = e.target.result;
                }
            };
            reader.readAsDataURL(file);
        }
    });

    // Eliminar foto de perfil actual
    document.getElementById('btnEliminarFotoPerfil')?.addEventListener('click', function() {
        Swal.fire({
            title: '¿Eliminar foto de perfil?',
            text: 'Se usará la imagen por defecto',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f7931e',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('<?= $basePath ?>/api/eliminarFotoPerfil', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar preview a imagen por defecto
                        const preview = document.getElementById('avatarPreview');
                        if (preview) {
                            preview.src = '<?= $basePath ?>/assets/images/imagen.png';
                        }
                        // Ocultar botón de eliminar
                        const btnEliminar = document.getElementById('btnEliminarFotoPerfil');
                        if (btnEliminar) {
                            btnEliminar.style.display = 'none';
                        }
                        // Recargar historial
                        cargarHistorialFotos();
                        Swal.fire({
                            title: '¡Eliminada!',
                            text: 'Tu foto de perfil ha sido eliminada correctamente',
                            icon: 'success',
                            confirmButtonColor: '#f7931e',
                            timer: 2000,
                            showConfirmButton: true
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'No se pudo eliminar la foto',
                            icon: 'error',
                            confirmButtonColor: '#f7931e'
                        });
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    Swal.fire({
                        title: 'Error',
                        text: 'Error al eliminar la foto de perfil',
                        icon: 'error',
                        confirmButtonColor: '#f7931e'
                    });
                });
            }
        });
    });

    // Eliminar foto del historial (función global)
    window.eliminarFotoHistorial = function(idFoto, btnElement) {
        Swal.fire({
            title: '¿Eliminar del historial?',
            text: 'Esta foto se eliminará permanentemente',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f7931e',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('idFotoPerfil', idFoto);

                fetch('<?= $basePath ?>/api/eliminarFotoHistorial', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Eliminar el elemento del DOM
                        if (btnElement && btnElement.parentElement) {
                            btnElement.parentElement.remove();
                        }
                        // Si no quedan fotos, mostrar mensaje
                        const container = document.getElementById('historialFotosContainer');
                        if (container && container.children.length === 0) {
                            container.innerHTML = '<small class="text-muted w-100 text-center">No hay fotos anteriores</small>';
                        }
                        Swal.fire({
                            title: '¡Eliminada!',
                            text: 'La foto ha sido eliminada del historial',
                            icon: 'success',
                            confirmButtonColor: '#f7931e',
                            timer: 2000,
                            showConfirmButton: true
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'No se pudo eliminar la foto',
                            icon: 'error',
                            confirmButtonColor: '#f7931e'
                        });
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    Swal.fire({
                        title: 'Error',
                        text: 'Error al eliminar la foto del historial',
                        icon: 'error',
                        confirmButtonColor: '#f7931e'
                    });
                });
            }
        });
    };

    // Cargar historial de fotos al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        cargarHistorialFotos();
    });

    function cargarHistorialFotos() {
        fetch('<?= $basePath ?>/api/historialFotosPerfil', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('historialFotosContainer');
            if (!container) return;

            if (data.error) {
                container.innerHTML = `<small class="text-danger w-100 text-center">${data.error}</small>`;
                return;
            }

            if (!data.fotos || data.fotos.length === 0) {
                container.innerHTML = '<small class="text-muted w-100 text-center">No hay fotos anteriores</small>';
                return;
            }

            container.innerHTML = data.fotos.map(foto => {
                // Escapar correctamente para HTML
                const imagenEscapada = foto.imagenPerfil.replace(/&/g, '&amp;').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
                const fechaEscapada = (foto.fechaUltimaVez || foto.fechaCreacion).replace(/&/g, '&amp;').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
                const claseActual = foto.esActual ? 'actual' : '';
                return `
                <div class="position-relative d-inline-block">
                    <img src="<?= $basePath ?>/uploads/avatars/${imagenEscapada}" 
                         class="historial-foto-mini ${claseActual}"
                         data-foto-id="${foto.idFotoPerfil}"
                         data-foto-imagen="${imagenEscapada}"
                         data-foto-fecha="${fechaEscapada}"
                         onclick="abrirModalFotoHistorialDesdeCard(this)"
                         alt="Foto anterior"
                         title="${foto.esActual ? 'Foto actual' : 'Foto anterior'}">
                    ${!foto.esActual ? `
                    <button type="button" 
                            class="btn btn-sm btn-danger position-absolute top-0 end-0 rounded-circle p-0" 
                            style="width: 18px; height: 18px; font-size: 10px; line-height: 1;"
                            onclick="event.stopPropagation(); eliminarFotoHistorial(${foto.idFotoPerfil}, this)"
                            title="Eliminar del historial">
                        <i class="bi bi-x"></i>
                    </button>
                    ` : ''}
                </div>
            `;
            }).join('');
        })
        .catch(err => {
            console.error('Error al cargar historial:', err);
            const container = document.getElementById('historialFotosContainer');
            if (container) {
                container.innerHTML = '<small class="text-danger w-100 text-center">Error al cargar el historial</small>';
            }
        });
    }

    function abrirModalFotoHistorialDesdeCard(imgElement) {
        const idFoto = parseInt(imgElement.dataset.fotoId);
        const imagen = imgElement.dataset.fotoImagen;
        const fecha = imgElement.dataset.fotoFecha;
        abrirModalFotoHistorial(idFoto, imagen, fecha);
    }

    function abrirModalFotoHistorial(idFoto, imagen, fecha) {
        const modal = new bootstrap.Modal(document.getElementById('modalDetalleFotoHistorial'));
        const img = document.getElementById('modalFotoHistorial');
        const fechaEl = document.getElementById('modalFechaHistorial');
        const btnUsar = document.getElementById('btnUsarFotoHistorial');

        if (img) img.src = '<?= $basePath ?>/uploads/avatars/' + imagen;
        if (fechaEl) {
            try {
                const fechaObj = new Date(fecha);
                if (!isNaN(fechaObj.getTime())) {
                    const fechaFormateada = fechaObj.toLocaleDateString('es-ES', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    fechaEl.textContent = 'Última vez usada: ' + fechaFormateada;
                } else {
                    fechaEl.textContent = 'Fecha no disponible';
                }
            } catch (e) {
                fechaEl.textContent = 'Fecha no disponible';
            }
        }
        if (btnUsar) {
            btnUsar.onclick = function() {
                usarFotoHistorial(idFoto);
            };
        }
        modal.show();
    }

    function usarFotoHistorial(idFoto) {
        const form = document.querySelector('form[action="procesarEdicion"]');
        if (!form) return;

        // Crear input hidden para la foto seleccionada
        let inputHidden = document.getElementById('selected_history_avatar_id');
        if (!inputHidden) {
            inputHidden = document.createElement('input');
            inputHidden.type = 'hidden';
            inputHidden.name = 'selected_history_avatar_id';
            inputHidden.id = 'selected_history_avatar_id';
            form.appendChild(inputHidden);
        }
        inputHidden.value = idFoto;

        // Actualizar preview
        fetch('<?= $basePath ?>/api/detalleFotoHistorial?idFoto=' + idFoto)
            .then(res => res.json())
            .then(data => {
                if (data.imagenPerfil) {
                    const preview = document.getElementById('avatarPreview');
                    if (preview) {
                        preview.src = '<?= $basePath ?>/uploads/avatars/' + data.imagenPerfil;
                    }
                }
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalDetalleFotoHistorial'));
                if (modal) modal.hide();
            })
                    .catch(err => {
                        console.error('Error:', err);
                        Swal.fire({
                            title: 'Error',
                            text: 'Error al cargar la foto. Intenta guardar los cambios.',
                            icon: 'error',
                            confirmButtonColor: '#f7931e'
                        });
                    });
    }
    </script>

<?php include VIEW_PATH . '/footer.php'; ?>