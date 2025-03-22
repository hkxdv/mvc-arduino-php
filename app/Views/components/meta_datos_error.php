<?php

/**
 * Meta_datos_error.php
 * Metadatos para las páginas de error
 */

// Obtener el título y código de error
$titulo = $titulo ?? 'Error del Sistema';
$codigo = $codigo ?? 'Error';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Página de error <?php echo $codigo; ?> - <?php echo $titulo; ?>">
<meta name="robots" content="noindex, nofollow">
<title><?php echo $codigo; ?> - <?php echo $titulo; ?></title>

<!-- CDN Tailwind -->
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

<!-- Favicon -->
<link rel="shortcut icon" href="assets/img/favicon.180x180.png" type="image/png">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">