<?php
// app/views/editarPerfil.php
if (session_status() === PHP_SESSION_NONE) session_start();

// incluir conexión (ruta robusta)
require_once dirname(__DIR__, 2) . '/config/conexion.php';

// helper
function e($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Verificar sesión
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: login.php');
    exit;
}

$conexion = abrirConexion();
if ($conexion === false || $conexion->connect_error) {
    die("Error de conexión a la base de datos.");
}

$usuarioId = (int)$_SESSION['usuario']['id'];

// Obtener datos actuales del usuario
$sql = "
    SELECT u.idUsuario, u.nombreUsuario, u.apellidoUsuario, u.apodoUsuario, u.arrobaUsuario,
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
    $avatarUrl = '../../public/uploads/avatars/' . e($userData['avatarActual']);
} else {
    $avatarUrl = '../../public/assets/images/imagen.png';
}

// si hay errores guardados en sesión, recupéralos
$errors = $_SESSION['errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['errors'], $_SESSION['form_data']);
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Artesanos</title>
    <link rel="icon" href="../../public/assets/images/logo.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/nav.css">
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
    </style>
</head>

<body>
    <?php include 'nav.php'; ?>

    <div class="container py-5">
        <h2>Editar perfil</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="procesarEdicion.php" method="post" enctype="multipart/form-data" novalidate>
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <img src="<?= e($avatarUrl) ?>" alt="Avatar" class="avatar-preview mb-3">
                    <div class="mb-3">
                        <label class="form-label d-block">Cambiar foto</label>
                        <input type="file" name="new_avatar" accept="image/*" class="form-control">
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
                        <textarea name="descripcionUsuario" class="form-control"><?= htmlspecialchars(str_replace(["\\r\\n", "\\n", "\\r"], "\n", $userData['descripcionUsuario'])) ?></textarea>

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
                        <a href="perfil.php?id=<?= $usuarioId ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-custom-orange">Guardar cambios</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>