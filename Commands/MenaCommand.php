<?php

/**
 * MenaCommand - Comandos de administración para MenaProtection
 * 
 * Maneja todos los comandos de administrador del plugin
 * 
 * @author TuNombre
 * @version 1.0.0
 */

declare(strict_types=1);

namespace MenaProtection\Commands;

use MenaProtection\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;

class MenaCommand extends Command {
    
    /** @var Main */
    private Main $plugin;
    
    /**
     * Constructor del comando
     * 
     * @param Main $plugin
     */
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        parent::__construct("mena", "Comandos de administración para MenaProtection");
        $this->setPermission("menaprotection.admin");
    }
    
    /**
     * Ejecuta el comando
     * 
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) {
            $sender->sendMessage($this->plugin->getMessage("no_permission"));
            return false;
        }
        
        if (empty($args)) {
            $this->sendHelp($sender);
            return false;
        }
        
        $subCommand = strtolower($args[0]);
        
        switch ($subCommand) {
            case "list":
                $this->handleList($sender);
                break;
            case "remove":
                $this->handleRemove($sender, $args);
                break;
            case "info":
                $this->handleInfo($sender, $args);
                break;
            case "give":
                $this->handleGive($sender, $args);
                break;
            case "logs":
                $this->handleLogs($sender, $args);
                break;
            case "reload":
                $this->handleReload($sender);
                break;
            default:
                $this->sendHelp($sender);
                break;
        }
        
        return true;
    }
    
    /**
     * Maneja el comando /mena list
     * 
     * @param CommandSender $sender
     */
    private function handleList(CommandSender $sender): void {
        $protections = $this->plugin->getProtectionManager()->getAllProtections();
        
        if (empty($protections)) {
            $sender->sendMessage(TF::YELLOW . "No hay protecciones activas.");
            return;
        }
        
        $sender->sendMessage(TF::GREEN . "=== Protecciones Activas ===");
        foreach ($protections as $playerName => $protection) {
            $sender->sendMessage(TF::WHITE . "• " . TF::GREEN . $playerName . TF::WHITE . 
                " - Mundo: " . TF::BLUE . $protection["world"] . 
                TF::WHITE . " - Posición: " . TF::GOLD . 
                $protection["x"] . ", " . $protection["y"] . ", " . $protection["z"]);
        }
    }
    
    /**
     * Maneja el comando /mena remove
     * 
     * @param CommandSender $sender
     * @param array $args
     */
    private function handleRemove(CommandSender $sender, array $args): void {
        if (count($args) < 2) {
            $sender->sendMessage(TF::RED . "Uso: /mena remove <jugador>");
            return;
        }
        
        $playerName = $args[1];
        
        if (!$this->plugin->getProtectionManager()->hasProtection($playerName)) {
            $sender->sendMessage($this->plugin->getMessage("protection_not_found"));
            return;
        }
        
        if ($this->plugin->getProtectionManager()->removeProtection($playerName)) {
            $sender->sendMessage(TF::GREEN . "Protección de " . $playerName . " eliminada.");
            
            // Notificar al jugador si está en línea
            $player = $this->plugin->getServer()->getPlayerByPrefix($playerName);
            if ($player !== null) {
                $player->sendMessage($this->plugin->getMessage("protection_removed_by_admin"));
            }
        } else {
            $sender->sendMessage(TF::RED . "Error al eliminar la protección.");
        }
    }
    
    /**
     * Maneja el comando /mena info
     * 
     * @param CommandSender $sender
     * @param array $args
     */
    private function handleInfo(CommandSender $sender, array $args): void {
        if (count($args) < 2) {
            $sender->sendMessage(TF::RED . "Uso: /mena info <jugador>");
            return;
        }
        
        $playerName = $args[1];
        $protection = $this->plugin->getProtectionManager()->getProtection($playerName);
        
        if ($protection === null) {
            $sender->sendMessage($this->plugin->getMessage("protection_not_found"));
            return;
        }
        
        $sender->sendMessage(TF::GREEN . "=== Información de Protección ===");
        $sender->sendMessage(TF::WHITE . "Propietario: " . TF::GREEN . $protection["owner"]);
        $sender->sendMessage(TF::WHITE . "Mundo: " . TF::BLUE . $protection["world"]);
        $sender->sendMessage(TF::WHITE . "Posición: " . TF::GOLD . 
            $protection["x"] . ", " . $protection["y"] . ", " . $protection["z"]);
        $sender->sendMessage(TF::WHITE . "Radio: " . TF::YELLOW . $protection["radius"] . " bloques");
        $sender->sendMessage(TF::WHITE . "Creada: " . TF::AQUA . date("d/m/Y H:i:s", $protection["created"]));
    }
    
    /**
     * Maneja el comando /mena give
     * 
     * @param CommandSender $sender
     * @param array $args
     */
    private function handleGive(CommandSender $sender, array $args): void {
        if (!$sender->hasPermission("menaprotection.give")) {
            $sender->sendMessage($this->plugin->getMessage("no_permission"));
            return;
        }
        
        if (count($args) < 2) {
            $sender->sendMessage(TF::RED . "Uso: /mena give <jugador>");
            return;
        }
        
        $playerName = $args[1];
        $player = $this->plugin->getServer()->getPlayerByPrefix($playerName);
        
        if ($player === null) {
            $sender->sendMessage($this->plugin->getMessage("player_not_found"));
            return;
        }
        
        $this->giveMena($player);
        $sender->sendMessage($this->plugin->getMessage("protection_given_to", ["player" => $playerName]));
    }
    
    /**
     * Maneja el comando /mena logs
     * 
     * @param CommandSender $sender
     * @param array $args
     */
    private function handleLogs(CommandSender $sender, array $args): void {
        $logs = $this->plugin->getProtectionManager()->getLogs();
        
        if (empty($logs)) {
            $sender->sendMessage(TF::YELLOW . "No hay logs disponibles.");
            return;
        }
        
        $page = isset($args[1]) ? (int)$args[1] : 1;
        $logsPerPage = 10;
        $totalPages = ceil(count($logs) / $logsPerPage);
        
        if ($page < 1 || $page > $totalPages) {
            $page = 1;
        }
        
        $offset = ($page - 1) * $logsPerPage;
        $pageLogs = array_slice($logs, $offset, $logsPerPage, true);
        
        $sender->sendMessage(TF::GREEN . "=== Logs de Protección (Página $page/$totalPages) ===");
        foreach ($pageLogs as $timestamp => $log) {
            $action = $log["action"] === "protection_created" ? "CREADA" : "ELIMINADA";
            $sender->sendMessage(TF::WHITE . "• " . TF::GREEN . $log["player"] . 
                TF::WHITE . " - " . TF::YELLOW . $action . 
                TF::WHITE . " - " . TF::GOLD . date("d/m/Y H:i:s", $timestamp));
        }
    }
    
    /**
     * Maneja el comando /mena reload
     * 
     * @param CommandSender $sender
     */
    private function handleReload(CommandSender $sender): void {
        $this->plugin->reloadConfig();
        $sender->sendMessage(TF::GREEN . "Configuración recargada correctamente.");
    }
    
    /**
     * Envía la ayuda del comando
     * 
     * @param CommandSender $sender
     */
    private function sendHelp(CommandSender $sender): void {
        $sender->sendMessage(TF::GREEN . "=== Comandos de MenaProtection ===");
        $sender->sendMessage(TF::WHITE . "• " . TF::GREEN . "/mena list" . TF::WHITE . " - Ver todas las protecciones");
        $sender->sendMessage(TF::WHITE . "• " . TF::GREEN . "/mena remove <jugador>" . TF::WHITE . " - Eliminar protección");
        $sender->sendMessage(TF::WHITE . "• " . TF::GREEN . "/mena info <jugador>" . TF::WHITE . " - Ver información de protección");
        $sender->sendMessage(TF::WHITE . "• " . TF::GREEN . "/mena give <jugador>" . TF::WHITE . " - Dar una Mena");
        $sender->sendMessage(TF::WHITE . "• " . TF::GREEN . "/mena logs [página]" . TF::WHITE . " - Ver historial de logs");
        $sender->sendMessage(TF::WHITE . "• " . TF::GREEN . "/mena reload" . TF::WHITE . " - Recargar configuración");
    }
    
    /**
     * Da una Mena a un jugador
     * 
     * @param Player $player
     */
    public function giveMena(Player $player): void {
        $config = $this->plugin->getPluginConfig();
        $blockId = $config->getNested("mena.block_id", 153); // Amatista
        $itemName = $config->getNested("mena.item_name", "§6§lMena");
        $itemLore = $config->getNested("mena.item_lore", []);
        
        $item = \pocketmine\item\ItemFactory::getInstance()->get($blockId);
        $item->setCustomName($itemName);
        $item->setLore($itemLore);
        
        $player->getInventory()->addItem($item);
        $player->sendMessage($this->plugin->getMessage("protection_given"));
    }
} 