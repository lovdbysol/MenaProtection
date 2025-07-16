<?php

/**
 * Script para generar el archivo .phar de MenaProtection
 * 
 * Uso: php build.php
 */

// Verificar que Phar esté habilitado
if (!class_exists('Phar')) {
    die("Error: La extensión Phar no está habilitada.\n");
}

// Configuración
$pluginName = "MenaProtection";
$version = "1.0.0";
$pharFile = $pluginName . ".phar";

// Eliminar archivo .phar existente si existe
if (file_exists($pharFile)) {
    unlink($pharFile);
    echo "Archivo .phar existente eliminado.\n";
}

// Crear el archivo .phar
$phar = new Phar($pharFile);

// Agregar archivos al .phar
$files = [
    "Main.php",
    "plugin.yml",
    "config.yml",
    "Commands/MenaCommand.php",
    "Events/ProtectionListener.php",
    "Managers/ProtectionManager.php",
    "MyPlotIntegration.php"
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $phar->addFile($file);
        echo "Agregado: $file\n";
    } else {
        echo "Advertencia: $file no encontrado\n";
    }
}

// Configurar el stub (punto de entrada)
$stub = "<?php __HALT_COMPILER(); ?>";
$phar->setStub($stub);

echo "\n✅ Archivo .phar generado exitosamente: $pharFile\n";
echo "📦 Tamaño: " . number_format(filesize($pharFile) / 1024, 2) . " KB\n";
echo "📁 Ubicación: " . realpath($pharFile) . "\n\n";

echo "📋 Instrucciones para Aternos:\n";
echo "1. Descarga el archivo $pharFile\n";
echo "2. Ve a tu panel de Aternos\n";
echo "3. Sube el archivo a la carpeta 'plugins'\n";
echo "4. Reinicia tu servidor\n";
echo "5. El plugin se habilitará automáticamente\n\n";

echo "🔧 Integración con MyPlot:\n";
echo "- Si tienes MyPlot instalado, MenaProtection se integrará automáticamente\n";
echo "- Si no tienes MyPlot, funcionará de forma independiente\n";
echo "- Los plots de MyPlot tendrán prioridad sobre las protecciones de Mena\n\n";

echo "📖 Comandos disponibles:\n";
echo "- /mena list - Ver todas las protecciones\n";
echo "- /mena remove <jugador> - Eliminar protección\n";
echo "- /mena info <jugador> - Información de protección\n";
echo "- /mena give <jugador> - Dar Mena a un jugador\n";
echo "- /mena logs - Ver logs de actividad\n";
echo "- /mena reload - Recargar configuración\n"; 