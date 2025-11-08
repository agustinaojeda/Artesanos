<?php

function abrirConexion(){
    // Intentar crear la conexión
    $conexion = new mysqli("localhost", "root", "", "artesanos");
    
    // Verificar si hay un error de conexión
    if($conexion->connect_error){
        // **IMPORTANTE:** No usamos die() aquí.
        // Solo mostramos el error si es necesario y retornamos 'false'
        error_log("Fallo de Conexión a MySQL: " . $conexion->connect_error);
        return false; // Retorna false en caso de error
    }
    
    return $conexion; // Retorna el objeto mysqli si es exitoso
}
?>