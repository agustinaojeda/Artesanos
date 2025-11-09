<?php
$basePath = $GLOBALS['basePath'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Artesanos' ?></title>
  <link rel="icon" href="<?= $basePath ?>/assets/images/logo.png" type="image/x-icon">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <!-- Bootstrap Icons (necesario para las clases .bi ...) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Estilos Propios -->
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/nav.css">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/re.css">
  <link rel="stylesheet" href="<?= $basePath ?>/assets/css/home.css">

  <script>
    window.BASE_URL = "<?= htmlspecialchars($basePath, ENT_QUOTES) ?>"; 
    
  </script>

</head>
<body>
