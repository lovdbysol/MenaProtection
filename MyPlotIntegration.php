<?php

/**
 * MyPlotIntegration - Integración con MyPlot para MenaProtection
 * 
 * Permite que MenaProtection funcione con el sistema de plots de MyPlot
 * 
 * @author TuNombre
 * @version 1.0.0
 */

declare(strict_types=1);

namespace MenaProtection;

use MenaProtection\Main;
use pocketmine\player\Player;
use pocketmine\world\Position;

class MyPlotIntegration {
    
    /** @var Main */
    private Main $plugin;
    
    /** @var bool */
    private bool $myPlotEnabled = false;
    
    /**
     * Constructor de la integración
     * 
     * @param Main $plugin
     */
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->checkMyPlot();
    }
    
    /**
     * Verifica si MyPlot está disponible
     */
    private function checkMyPlot(): void {
        $myPlot = $this->plugin->getServer()->getPluginManager()->getPlugin("MyPlot");
        if ($myPlot !== null) {
            $this->myPlotEnabled = true;
            $this->plugin->getLogger()->info("§aMyPlot detectado - Integración habilitada");
        } else {
            $this->plugin->getLogger()->info("§eMyPlot no detectado - MenaProtection funcionará de forma independiente");
        }
    }
    
    /**
     * Verifica si MyPlot está habilitado
     * 
     * @return bool
     */
    public function isMyPlotEnabled(): bool {
        return $this->myPlotEnabled;
    }
    
    /**
     * Obtiene el plot en una posición específica
     * 
     * @param Position $position
     * @return mixed|null
     */
    public function getPlotAt(Position $position) {
        if (!$this->myPlotEnabled) {
            return null;
        }
        
        $myPlot = $this->plugin->getServer()->getPluginManager()->getPlugin("MyPlot");
        if ($myPlot === null) {
            return null;
        }
        
        // Intentar obtener el plot usando la API de MyPlot
        try {
            $plot = $myPlot->getPlotByPosition($position);
            return $plot;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Verifica si un jugador es propietario del plot en una posición
     * 
     * @param Player $player
     * @param Position $position
     * @return bool
     */
    public function isPlotOwner(Player $player, Position $position): bool {
        $plot = $this->getPlotAt($position);
        if ($plot === null) {
            return false;
        }
        
        return $plot->owner === $player->getName();
    }
    
    /**
     * Verifica si un jugador puede construir en un plot
     * 
     * @param Player $player
     * @param Position $position
     * @return bool
     */
    public function canBuildInPlot(Player $player, Position $position): bool {
        if (!$this->myPlotEnabled) {
            return true; // Si no hay MyPlot, permitir construcción
        }
        
        $plot = $this->getPlotAt($position);
        if ($plot === null) {
            return true; // Si no hay plot, permitir construcción
        }
        
        // Verificar si el jugador es propietario o tiene permisos
        if ($plot->owner === $player->getName()) {
            return true;
        }
        
        // Verificar si el plot está en venta o es público
        if (isset($plot->helpers) && in_array($player->getName(), $plot->helpers)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Obtiene el propietario del plot en una posición
     * 
     * @param Position $position
     * @return string|null
     */
    public function getPlotOwner(Position $position): ?string {
        $plot = $this->getPlotAt($position);
        return $plot !== null ? $plot->owner : null;
    }
    
    /**
     * Verifica si una posición está dentro de un plot
     * 
     * @param Position $position
     * @return bool
     */
    public function isInPlot(Position $position): bool {
        return $this->getPlotAt($position) !== null;
    }
    
    /**
     * Obtiene información del plot para mostrar al jugador
     * 
     * @param Position $position
     * @return array
     */
    public function getPlotInfo(Position $position): array {
        $plot = $this->getPlotAt($position);
        if ($plot === null) {
            return [
                "owner" => null,
                "plot_id" => null,
                "world" => null
            ];
        }
        
        return [
            "owner" => $plot->owner,
            "plot_id" => $plot->id,
            "world" => $plot->levelName
        ];
    }
} 