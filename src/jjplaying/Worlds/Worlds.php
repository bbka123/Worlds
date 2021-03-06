<?php
/**
 * Created by PhpStorm.
 * User: Jarne
 * Date: 19.03.16
 * Time: 16:01
 */

namespace jjplaying\Worlds;

use jjplaying\Worlds\Types\World;
use jjplaying\Worlds\Utils\StaticArrayList;
use pocketmine\level\generator\Flat;
use pocketmine\level\generator\hell\Nether;
use pocketmine\level\generator\normal\Normal;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;

class Worlds extends PluginBase {
    private $worlds;
    private $messages;

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->saveDefaultConfig();

        $this->worlds = new StaticArrayList();

        foreach($this->getServer()->getLevels() as $level) {
            $this->loadWorld($level->getFolderName());
        }

        $messagesfile = $this->getServer()->getPluginPath() . "Worlds/messages.yml";

        if(!file_exists($messagesfile)) {
            file_put_contents($messagesfile, $this->getResource("messages.yml"));
        }

        $this->messages = new Config($messagesfile, Config::YAML, []);
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        switch(strtolower($command->getName())) {
            case "worlds":
                if(count($args) >= 1) {
                    switch(strtolower($args[0])) {
                        case "info":
                            $sender->sendMessage("§7This server is using §l§9Worlds §r§fversion 1.0 §7(C) 2016 by §ejjplaying §7(https://github.com/jjplaying)");
                            return true;
                        case "list":
                        case "ls":
                            if($sender->hasPermission("worlds.list")) {
                                $levels = array();

                                foreach($this->getServer()->getLevels() as $level) {
                                    $levels[] = $level->getName();
                                }

                                $sender->sendMessage($this->getMessage("allworlds", array("worlds" => implode(", ", $levels))));
                            } else {
                                $sender->sendMessage($this->getMessage("permission"));
                            }
                            return true;
                        case "create":
                        case "cr":
                            if($sender->hasPermission("worlds.admin.create")) {
                                switch(count($args)) {
                                    case 2:
                                        $this->getServer()->generateLevel($args[1]);
                                        $sender->sendMessage($this->getMessage("created"));
                                        return true;
                                    case 3:
                                        switch($args[2]) {
                                            case "normal":
                                                $generator = Normal::class;
                                                break;
                                            case "flat":
                                                $generator = Flat::class;
                                                break;
                                            case "nether":
                                                $generator = Nether::class;
                                                break;
                                            default:
                                                $generator = Normal::class;
                                        }

                                        $this->getServer()->generateLevel($args[1], null, $generator);
                                        $sender->sendMessage($this->getMessage("created"));
                                        return true;
                                    default:
                                        return false;
                                }
                            } else {
                                $sender->sendMessage($this->getMessage("permission"));
                            }
                            return true;
                        case "remove":
                        case "rm":
                            if($sender->hasPermission("worlds.admin.create")) {
                                if(count($args) == 2) {
                                    if($this->getServer()->isLevelLoaded($args[1])) {
                                        $this->getServer()->unloadLevel($this->getServer()->getLevelByName($args[1]));
                                    }

                                    $this->delete($this->getServer()->getFilePath() . "worlds/" . $args[1]);

                                    $sender->sendMessage($this->getMessage("removed"));
                                } else {
                                    return false;
                                }
                            } else {
                                $sender->sendMessage($this->getMessage("permission"));
                            }
                            return true;
                        case "load":
                        case "ld":
                            if($sender->hasPermission("worlds.admin.load")) {
                                if(count($args) == 2) {
                                    if(!$this->getServer()->isLevelLoaded($args[1])) {
                                        $level = $this->getServer()->loadLevel($args[1]);

                                        if($level instanceof Level) {
                                            $sender->sendMessage($this->getMessage("loadworld", array("world" => $args[1])));
                                        } else {
                                            $sender->sendMessage($this->getMessage("noworld"));
                                        }
                                    } else {
                                        $sender->sendMessage($this->getMessage("alreadyloaded"));
                                    }
                                } else {
                                    return false;
                                }
                            } else {
                                $sender->sendMessage($this->getMessage("permission"));
                            }
                            return true;
                        case "unload":
                        case "unld":
                            if($sender->hasPermission("worlds.admin.load")) {
                                if(count($args) == 2) {
                                    if($this->getServer()->isLevelLoaded($args[1])) {
                                        $this->getServer()->unloadLevel($this->getServer()->getLevelByName($args[1]));
                                        $sender->sendMessage($this->getMessage("unloadworld", array("world" => $args[1])));

                                        $this->getWorlds()->remove($args[1]);
                                    } else {
                                        $sender->sendMessage($this->getMessage("notloaded"));
                                    }
                                } else {
                                    return false;
                                }
                            } else {
                                $sender->sendMessage($this->getMessage("permission"));
                            }
                            return true;
                        case "teleport":
                        case "tp":
                            if($sender->hasPermission("worlds.admin.teleport")) {
                                if($sender instanceof Player) {
                                    if(count($args) == 2) {
                                        if(!$this->getServer()->isLevelLoaded($args[1])) {
                                            $level = $this->getServer()->loadLevel($args[1]);

                                            if($level instanceof Level) {
                                                $sender->sendMessage($this->getMessage("loadworld", array("world" => $args[1])));
                                            } else {

                                                $sender->sendMessage($this->getMessage("noworld"));
                                                return true;
                                            }
                                        }

                                        $world = $this->getServer()->getLevelByName($args[1]);
                                        $sender->teleport($world->getSafeSpawn());
                                        $sender->sendMessage($this->getMessage("teleported", array("world" => $args[1])));
                                    } else {
                                        return false;
                                    }
                                } else {
                                    $sender->sendMessage($this->getMessage("ingame"));
                                }
                            } else {
                                $sender->sendMessage($this->getMessage("permission"));
                            }
                            return true;
                        case "set":
                            if($sender->hasPermission("worlds.admin.set")) {
                                if(count($args) == 3) {
                                    if(in_array($args[1], array("gamemode", "build", "pvp", "damage", "explode", "drop"))) {
                                        if($args[1] == "gamemode") {
                                            if(in_array($args[2], array("0", "1", "2", "3"))) {
                                                if($sender instanceof Player) {
                                                    if($world = $this->getWorldByName($sender->getLevel()->getFolderName())) {
                                                        $world->updateValue($args[1], $args[2]);

                                                        $sender->sendMessage($this->getMessage("set", array("world" => $sender->getLevel()->getFolderName(), "key" => $args[1], "value" => $args[2])));
                                                    } else {
                                                        $sender->sendMessage($this->getMessage("noworld"));
                                                    }
                                                } else {
                                                    $sender->sendMessage($this->getMessage("ingame"));
                                                }

                                                return true;
                                            }
                                        } else {
                                            if(in_array($args[2], array("true", "false"))) {
                                                if($sender instanceof Player) {
                                                    if($world = $this->getWorldByName($sender->getLevel()->getFolderName())) {
                                                        $world->updateValue($args[1], $args[2]);

                                                        $sender->sendMessage($this->getMessage("set", array("world" => $sender->getLevel()->getFolderName(), "key" => $args[1], "value" => $args[2])));
                                                    } else {
                                                        $sender->sendMessage($this->getMessage("noworld"));
                                                    }
                                                } else {
                                                    $sender->sendMessage($this->getMessage("ingame"));
                                                }

                                                return true;
                                            }
                                        }
                                    }
                                }
                            } else {
                                $sender->sendMessage($this->getMessage("permission"));
                                return true;
                            }
                            return false;
                    }
                }

                return false;
        }

        return false;
    }

    /**
     * @param string $name
     * @return World|bool
     */
    public function getWorldByName(string $name) {
        if($this->getWorlds()->containsKey($name)) {
            return $this->getWorlds()->get($name);
        }

        return false;
    }

    /**
     * @param string $foldername
     */
    public function loadWorld(string $foldername) {
        $file = $this->getWorldFile($foldername);
        $config = $this->getCustomConfig($file);

        $this->getWorlds()->add(new World($this, $config), $foldername);
    }

    /**
     * @param string $file
     * @return Config
     */
    public function getCustomConfig(string $file) {
        $config = new Config($file, Config::YAML, []);

        if(!file_exists($file)) {
            $config->save();
        }

        return $config;
    }

    /**
     * @param string $foldername
     * @return string
     */
    public function getWorldFile(string $foldername) {
        return $this->getServer()->getDataPath() . "worlds/" . $foldername . "/worlds.yml";
    }

    /**
     * @param string $directory
     */
    public function delete(string $directory) {
         if(is_dir($directory)) {
             $objects = scandir($directory);

             foreach($objects as $object) {
                 if($object != "." AND $object != "..") {
                     if(is_dir($directory . "/" . $object)) {
                         $this->delete($directory . "/" . $object);
                     } else {
                         unlink($directory . "/" . $object);
                     }
                 }
             }

             rmdir($directory);
         }
    }

    /**
     * @param string $key
     * @param array|null $replaces
     * @return string
     */
    public function getMessage(string $key, array $replaces = null) {
        $messages = $this->getMessages();

        if($messages->exists($key)) {
            if(isset($replaces)) {
                $get = $messages->get($key);

                foreach($replaces as $replace => $value) {
                    $get = str_replace("{" . $replace . "}", $value, $get);
                }

                return $get;
            } else {
                return $messages->get($key);
            }
        }

        return $key;
    }

    /**
     * @return Config
     */
    public function getMessages() {
        return $this->messages;
    }

    /**
     * @return StaticArrayList
     */
    public function getWorlds() {
        return $this->worlds;
    }
}