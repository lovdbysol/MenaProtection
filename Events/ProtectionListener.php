<?php

/**
 * ProtectionListener - Eventos de protección para MenaProtection
 * 
 * Maneja todos los eventos relacionados con la protección de áreas
 * 
 * @author TuNombre
 * @version 1.0.0
 */

declare(strict_types=1);

namespace MenaProtection\Events;

use MenaProtection\Main;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\block\Block;
use pocketmine\player\Player;
use pocketmine\world\Position;

class ProtectionListener implements Listener {
    
    /** @var Main */
    private Main $plugin;
    
    /**
     * Constructor del listener
     * 
     * @param Main $plugin
     */
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }
    
    /**
     * Se ejecuta cuando un jugador entra al servidor
     * Le da una Mena automáticamente
     * 
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        
        // Verificar si el jugador ya tiene una Mena en su inventario
        $hasMena = false;
        foreach ($player->getInventory()->getContents() as $item) {
            if ($this->isMenaItem($item)) {
                $hasMena = true;
                break;
            }
        }
        
        // Si no tiene una Mena, darle una
        if (!$hasMena) {
            $this->giveMena($player);
        }
    }
    
    /**
     * Se ejecuta cuando un jugador coloca un bloque
     * Detecta si es una Mena y crea la protección
     * 
     * @param BlockPlaceEvent $event
     */
    public function onBlockPlace(BlockPlaceEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $position = $block->getPosition();
        
        // Verificar si el bloque colocado es una Mena
        if ($this->isMenaBlock($block)) {
            $event->cancel();
            
            // Crear la protección
            if ($this->plugin->getProtectionManager()->createProtection($player, $position)) {
                // Colocar el bloque de amatista en el mundo
                $position->getWorld()->setBlock($position, $block);
            }
        }
    }
    
    /**
     * Se ejecuta cuando un jugador rompe un bloque
     * Detecta si es una Mena y elimina la protección
     * 
     * @param BlockBreakEvent $event
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $position = $block->getPosition();
        
        // Verificar si el bloque roto es una Mena
        if ($this->isMenaBlock($block)) {
            // Verificar si el jugador es el propietario de la protección
            $protection = $this->plugin->getProtectionManager()->getProtectionAt($position);
            
            if ($protection !== null && $protection["owner"] === $player->getName()) {
                // Eliminar la protección
                $this->plugin->getProtectionManager()->removeProtection($player->getName());
                $player->sendMessage($this->plugin->getMessage("protection_removed"));
            } else {
                // No es el propietario, cancelar el evento
                $event->cancel();
                $player->sendMessage($this->plugin->getMessage("no_permission"));
            }
        }
        
        // Verificar si el jugador está rompiendo en un área protegida
        $protection = $this->plugin->getProtectionManager()->getProtectionAt($position);
        
        if ($protection !== null && $protection["owner"] !== $player->getName()) {
            // Verificar si el jugador tiene permisos de bypass
            if (!$player->hasPermission("menaprotection.bypass")) {
                $event->cancel();
                $player->sendMessage($this->plugin->getMessage("cannot_build", ["owner" => $protection["owner"]]));
            }
        }
    }
    
    /**
     * Se ejecuta cuando un jugador interactúa con un bloque
     * Previene la interacción con cofres en áreas protegidas
     * 
     * @param PlayerInteractEvent $event
     */
    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $position = $block->getPosition();
        
        // Verificar si el jugador está interactuando en un área protegida
        $protection = $this->plugin->getProtectionManager()->getProtectionAt($position);
        
        if ($protection !== null && $protection["owner"] !== $player->getName()) {
            // Verificar si el jugador tiene permisos de bypass
            if (!$player->hasPermission("menaprotection.bypass")) {
                $event->cancel();
                $player->sendMessage($this->plugin->getMessage("cannot_build", ["owner" => $protection["owner"]]));
            }
        }
    }
    
    /**
     * Se ejecuta cuando un jugador abre un inventario
     * Previene abrir cofres en áreas protegidas
     * 
     * @param InventoryOpenEvent $event
     */
    public function onInventoryOpen(InventoryOpenEvent $event): void {
        $player = $event->getPlayer();
        $inventory = $event->getInventory();
        
        // Solo verificar cofres
        if ($inventory instanceof \pocketmine\inventory\ChestInventory) {
            $position = $inventory->getHolder()->getPosition();
            
            // Verificar si el cofre está en un área protegida
            $protection = $this->plugin->getProtectionManager()->getProtectionAt($position);
            
            if ($protection !== null && $protection["owner"] !== $player->getName()) {
                // Verificar si el jugador tiene permisos de bypass
                if (!$player->hasPermission("menaprotection.bypass")) {
                    $event->cancel();
                    $player->sendMessage($this->plugin->getMessage("cannot_build", ["owner" => $protection["owner"]]));
                }
            }
        }
    }
    
    /**
     * Verifica si un bloque es una Mena
     * 
     * @param Block $block
     * @return bool
     */
    private function isMenaBlock(Block $block): bool {
        $config = $this->plugin->getPluginConfig();
        $menaBlockId = $config->getNested("mena.block_id", 153); // Amatista
        
        return $block->getId() === $menaBlockId;
    }
    
    /**
     * Verifica si un ítem es una Mena
     * 
     * @param \pocketmine\item\Item $item
     * @return bool
     */
    private function isMenaItem(\pocketmine\item\Item $item): bool {
        $config = $this->plugin->getPluginConfig();
        $menaBlockId = $config->getNested("mena.block_id", 153); // Amatista
        $menaItemName = $config->getNested("mena.item_name", "§6§lMena");
        
        return $item->getId() === $menaBlockId && $item->getCustomName() === $menaItemName;
    }
    
    /**
     * Da una Mena a un jugador
     * 
     * @param Player $player
     */
    private function giveMena(Player $player): void {
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