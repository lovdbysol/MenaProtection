<?php

/**
 * MenaProtection - Plugin de protección de áreas para PocketMine-MP
 * 
 * Este plugin permite a los jugadores proteger sus casas usando un bloque especial llamado "Mena"
 * La protección cubre un área de 31x31x31 bloques (configurable)
 * 
 * @author TuNombre
 * @version 1.0.0
 */

declare(strict_types=1);

namespace MenaProtection;

use MenaProtection\Commands\MenaCommand;
use MenaProtection\Events\ProtectionListener;
use MenaProtection\Managers\ProtectionManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase {
    
    /** @var ProtectionManager */
    private ProtectionManager $protectionManager;
    
    /** @var Config */
    private Config $config;
    
    /**
     * Se ejecuta cuando el plugin se habilita
     * Inicializa el gestor de protecciones y registra eventos
     */
    public function onEnable(): void {
        // Crear directorio de datos si no existe
        if (!is_dir($this->getDataFolder())) {
            mkdir($this->getDataFolder());
        }
        
        // Cargar configuración
        $this->saveDefaultConfig();
        $this->config = $this->getConfig();
        
        // Inicializar el gestor de protecciones
        $this->protectionManager = new ProtectionManager($this);
        
        // Registrar eventos
        $this->getServer()->getPluginManager()->registerEvents(
            new ProtectionListener($this), 
            $this
        );
        
        // Registrar comandos
        $this->getServer()->getCommandMap()->register(
            'menaprotection', 
            new MenaCommand($this)
        );
        
        $this->getLogger()->info("§aMenaProtection ha sido habilitado correctamente!");
    }
    
    /**
     * Se ejecuta cuando el plugin se deshabilita
     * Guarda todas las protecciones
     */
    public function onDisable(): void {
        if (isset($this->protectionManager)) {
            $this->protectionManager->saveAllProtections();
        }
        $this->getLogger()->info("§cMenaProtection ha sido deshabilitado.");
    }
    
    /**
     * Obtiene el gestor de protecciones
     * 
     * @return ProtectionManager
     */
    public function getProtectionManager(): ProtectionManager {
        return $this->protectionManager;
    }
    
    /**
     * Obtiene la configuración del plugin
     * 
     * @return Config
     */
    public function getPluginConfig(): Config {
        return $this->config;
    }
    
    /**
     * Obtiene un mensaje de la configuración
     * 
     * @param string $key
     * @param array $replacements
     * @return string
     */
    public function getMessage(string $key, array $replacements = []): string {
        $message = $this->config->getNested("messages.$key", "Mensaje no encontrado");
        
        foreach ($replacements as $placeholder => $value) {
            $message = str_replace("{{$placeholder}}", $value, $message);
        }
        
        return $message;
    }
} 