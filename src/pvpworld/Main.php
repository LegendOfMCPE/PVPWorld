<?php

namespace pvpworld;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener {
	private $config;

	public function onEnable() {		
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
          "message" => "You're not allowed to hurt players here.",
          "pvp-worlds" => []
        ]);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->setConfigBool("pvp", true);
    }

	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		if(strtolower($command->getName()) == "pvpworld") {
			if($sender instanceof Player) {
				$world = $sender->getLevel()->getName();
			}else{
				$world = $this->getServer()->getDefaultLevel()->getName();
			}
			if(isset($args[1])) {
				if($this->getServer()->isLevelLoaded($args[1])) {
					$world = $args[1];
				}else{
					$sender->sendMessage("That world doesn't exist.\n");
					return true;
				}
			}
			$mode = in_array($world, $this->config->get("pvp-worlds"));
			if(isset($args[0])) {
				$mode = $args[0] == "on";
			}
			if($mode) {
				$sender->sendMessage("PVP is now enabled in \"$world\".");
				if(($key = array_search($world, $this->config->get("pvp-worlds"))) !== false) {
					$arr = $this->config->get("pvp-worlds");
					unset($arr[$key]);
					$this->config->set("pvp-worlds", $arr);
				}
			}else{
				$sender->sendMessage("PVP is now disabled in \"$world\".");
				$arr = $this->config->get("pvp-worlds");
				array_push($arr, $world);
				$this->config->set("pvp-worlds", $arr);
			}
			$this->config->save();
		}
		return true;
	}
	
    public function onEntityDamageByEntity(EntityDamageEvent $event){
    	if($event instanceof EntityDamageByEntityEvent) {
        	$victim = $event->getEntity();
       		$attacker = $event->getDamager();
        	if($victim instanceof Player && $attacker instanceof Player){
        		if(in_array($attacker->getLevel()->getName(), $this->config->get("pvp-worlds")) && !$attacker->hasPermission("pvpworld.bypass")) {
        			$attacker->sendMessage($this->config->get("message"));
        			$event->setCancelled();
        		}
        	}
    	}
    }
    
}