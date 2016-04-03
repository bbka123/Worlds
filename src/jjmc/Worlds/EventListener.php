<?php
/**
 * Created by PhpStorm.
 * User: Jarne
 * Date: 03.04.16
 * Time: 18:28
 */

namespace jjmc\Worlds;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\player\PlayerHungerChangeEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;

class EventListener extends PluginBase implements Listener {
    public function __construct(Worlds $plugin) {
        $this->plugin = $plugin;
    }

    public function onLevelLoad(LevelLoadEvent $event) {
        $foldername = $event->getLevel()->getFolderName();
        $this->plugin->loadWorld($foldername);
    }

    public function onPlayerJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        $foldername = $player->getLevel()->getFolderName();

        if($player instanceof Player) {
            if(isset($this->plugin->worlds[$foldername]["gamemode"])) {
                $player->setGamemode($this->plugin->worlds[$foldername]["gamemode"]);
            }
        }
    }

    public function onEntityLevelChange(EntityLevelChangeEvent $event) {
        $player = $event->getEntity();
        $foldername = $event->getTarget()->getFolderName();

        if($player instanceof Player) {
            if(isset($this->plugin->worlds[$foldername]["gamemode"])) {
                $player->setGamemode($this->plugin->worlds[$foldername]["gamemode"]);
            }
        }
    }

    public function onBlockBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();

        if($player instanceof Player) {
            $foldername = $player->getLevel()->getFolderName();

            if(isset($this->plugin->worlds[$foldername]["build"])) {
                $event->setCancelled($this->plugin->worlds[$foldername]["build"]);
            }
        }
    }

    public function onBlockPlace(BlockPlaceEvent $event) {
        $player = $event->getPlayer();

        if($player instanceof Player) {
            $foldername = $player->getLevel()->getFolderName();

            if(isset($this->plugin->worlds[$foldername]["build"])) {
                $event->setCancelled($this->plugin->worlds[$foldername]["build"]);
            }
        }
    }

    public function onEntityDamage(EntityDamageEvent $event) {
        $player = $event->getEntity();
        $cause = $event->getCause();

        if($player instanceof Player) {
            $foldername = $player->getLevel()->getFolderName();

            switch($cause) {
                case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                    if(isset($this->plugin->worlds[$foldername]["pvp"])) {
                        $event->setCancelled($this->plugin->worlds[$foldername]["pvp"]);
                    }
                    break;
                default:
                    if(isset($this->plugin->worlds[$foldername]["damage"])) {
                        $event->setCancelled($this->plugin->worlds[$foldername]["damage"]);
                    }
                    break;
            }
        }
    }

    public function onPlayerHungerChange(PlayerHungerChangeEvent $event) {
        $player = $event->getPlayer();

        if($player instanceof Player) {
            $foldername = $player->getLevel()->getFolderName();

            if(isset($this->plugin->worlds[$foldername]["hunger"])) {
                if($this->plugin->worlds[$foldername]["hunger"]) {
                    $player->setFood(20); // TODO: Does not work
                }
            }
        }
    }

    public function onPlayerDropItem(PlayerDropItemEvent $event) {
        $player = $event->getPlayer();

        if($player instanceof Player) {
            $foldername = $player->getLevel()->getFolderName();

            if(isset($this->plugin->worlds[$foldername]["drop"])) {
                $event->setCancelled($this->plugin->worlds[$foldername]["drop"]);
            }
        }
    }
}