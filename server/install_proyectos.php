<?php
/**
 * Script de instalación para el módulo de Proyectos
 * Ejecuta el archivo schema_proyectos.sql
 */

header('Content-Type: text/plain');

require_once 'db.php';

try {
    $sql = file_get_contents('schema_proyectos.sql');
    
    if (!$sql) {
        die("Error: No se pudo leer el archivo schema_proyectos.sql");
    }
    
    // Ejecutar múltiples consultas
    $pdo->exec($sql);
    
    echo "===============================================\n";
    echo " INSTALACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "===============================================\n\n";
    echo "Se han creado las tablas:\n";
    echo "- proyectos\n";
    echo "- proyecto_categorias\n";
    echo "- proyecto_items\n\n";
    echo "Se han insertado las categorías por defecto.\n";
    
} catch (PDOException $e) {
    echo "ERROR DE INSTALACIÓN:\n";
    echo $e->getMessage();
}
