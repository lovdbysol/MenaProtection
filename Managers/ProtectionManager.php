<?php

/**
 * ProtectionManager - Gestor de protecciones para MenaProtection
 * 
 * Maneja la creación, eliminación y verificación de áreas protegidas
 * 
 * @author TuNombre
 * @version 1.0.0
 */

declare(strict_types=1);

namespace MenaProtection\Managers;

use MenaProtection\Main;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\utils\Config;

class ProtectionManager {
    
    /** @var Main */
    private Main $plugin;
    
    /** @var array */
    private array $protections = [];
    
    /** @var Config */
    private Config $protectionsConfig;
    
    /** @var Config */
    private Config $logsConfig;
    
    /**
     * Constructor del gestor de protecciones
     * 
     * @param Main $plugin
     */
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->loadProtections();
        $this->loadLogs();
    }
    
    /**
     * Carga las protecciones desde el archivo
     */
    private function loadProtections(): void {
        $this->protectionsConfig = new Config(
            $this->plugin->getDataFolder() . "protections.yml", 
            Config::YAML, 
            ["protections" => []]
        );
        
        $this->protections = $this->protectionsConfig->get("protections", []);
    }
    
    /**
     * Carga los logs desde el archivo
     */
    private function loadLogs(): void {
        $this->logsConfig = new Config(
            $this->plugin->getDataFolder() . "logs.yml", 
            Config::YAML, 
            ["logs" => []]
        );
    }
    
    /**
     * Guarda todas las protecciones al archivo
     */
    public function saveAllProtections(): void {
        $this->protectionsConfig->set("protections", $this->protections);
        $this->protectionsConfig->save();
    }
    
    /**
     * Crea una nueva protección para un jugador
     * 
     * @param Player $player
     * @param Position $position
     * @return bool
     */
    public function createProtection(Player $player, Position $position): bool {
        $playerName = $player->getName();
        
        // Verificar si el jugador ya tiene una protección
        if ($this->hasProtection($playerName)) {
            $player->sendMessage($this->plugin->getMessage("already_protected"));
            return false;
        }
        
        // Verificar distancia mínima con otras protecciones
        if (!$this->isValidLocation($position)) {
            $player->sendMessage($this->plugin->getMessage("protection_too_close"));
            return false;
        }
        
        // Crear la protección
        $protection = [
            "owner" => $playerName,
            "world" => $position->getWorld()->getFolderName(),
            "x" => $position->getX(),
            "y" => $position->getY(),
            "z" => $position->getZ(),
            "created" => time(),
            "radius" => $this->plugin->getPluginConfig()->getNested("protection.radius", 15)
        ];
        
        $this->protections[$playerName] = $protection;
        $this->saveAllProtections();
        $this->addLog("protection_created", $playerName, $position);
        
        $player->sendMessage($this->plugin->getMessage("protection_created"));
        return true;
    }
    
    /**
     * Elimina la protección de un jugador
     * 
     * @param string $playerName
     * @return bool
     */
    public function removeProtection(string $playerName): bool {
        if (!isset($this->protections[$playerName])) {
            return false;
        }
        
        $protection = $this->protections[$playerName];
        $position = new Position(
            $protection["x"],
            $protection["y"],
            $protection["z"],
            $this->plugin->getServer()->getWorldManager()->getWorldByName($protection["world"])
        );
        
        unset($this->protections[$playerName]);
        $this->saveAllProtections();
        $this->addLog("protection_removed", $playerName, $position);
        
        return true;
    }
    
    /**
     * Verifica si un jugador tiene una protección
     * 
     * @param string $playerName
     * @return bool
     */
    public function hasProtection(string $playerName): bool {
        return isset($this->protections[$playerName]);
    }
    
    /**
     * Obtiene la protección de un jugador
     * 
     * @param string $playerName
     * @return array|null
     */
    public function getProtection(string $playerName): ?array {
        return $this->protections[$playerName] ?? null;
    }
    
    /**
     * Verifica si una posición está dentro de una protección
     * 
     * @param Position $position
     * @return array|null
     */
    public function getProtectionAt(Position $position): ?array {
        foreach ($this->protections as $protection) {
            if ($protection["world"] !== $position->getWorld()->getFolderName()) {
                continue;
            }
            
            $distance = sqrt(
                pow($position->getX() - $protection["x"], 2) +
                pow($position->getZ() - $protection["z"], 2)
            );
            
            if ($distance <= $protection["radius"]) {
                return $protection;
            }
        }
        
        return null;
    }
    
    /**
     * Verifica si una ubicación es válida para crear una protección
     * 
     * @param Position $position
     * @return bool
     */
    private function isValidLocation(Position $position): bool {
        $minDistance = $this->plugin->getPluginConfig()->getNested("protection.min_distance", 50);
        
        foreach ($this->protections as $protection) {
            if ($protection["world"] !== $position->getWorld()->getFolderName()) {
                continue;
            }
            
            $distance = sqrt(
                pow($position->getX() - $protection["x"], 2) +
                pow($position->getZ() - $protection["z"], 2)
            );
            
            if ($distance < $minDistance) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obtiene todas las protecciones
     * 
     * @return array
     */
    public function getAllProtections(): array {
        return $this->protections;
    }
    
    /**
     * Agrega un log
     * 
     * @param string $action
     * @param string $playerName
     * @param Position $position
     */
    private function addLog(string $action, string $playerName, Position $position): void {
        if (!$this->plugin->getPluginConfig()->getNested("logs.enabled", true)) {
            return;
        }
        
        $logs = $this->logsConfig->get("logs", []);
        $logs[time()] = [
            "action" => $action,
            "player" => $playerName,
            "world" => $position->getWorld()->getFolderName(),
            "x" => $position->getX(),
            "y" => $position->getY(),
            "z" => $position->getZ()
        ];
        
        // Limitar el número de logs
        $maxLogs = $this->plugin->getPluginConfig()->getNested("logs.max_logs", 1000);
        if (count($logs) > $maxLogs) {
            $logs = array_slice($logs, -$maxLogs, null, true);
        }
        
        $this->logsConfig->set("logs", $logs);
        $this->logsConfig->save();
    }
    
    /**
     * Obtiene los logs
     * 
     * @return array
     */
    public function getLogs(): array {
        return $this->logsConfig->get("logs", []);
    }
}
