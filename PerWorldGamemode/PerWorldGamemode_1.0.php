<?php

/*
  __PocketMine Plugin__
  name=PerWorldGamemode
  description=Control Gamemode on differant worlds
  version=1.0
  author=tschrock
  class=PerWorldGamemode
  apiversion=12
 */

class PerWorldGamemode implements Plugin {

    private $api, $list, $config, $server;

    public function __construct(ServerAPI $api, $server = false) {
        $this->api = $api;
        $this->server = ServerAPI::request();
        $this->list = array();
    }

    public function init() {
        $this->api->console->register("perworldgamemode", "/pwgm set <survival|creative> (world) or /pwgm exclude <player>", array($this, "commandHandler"));
        $this->api->console->alias("pwgm", "perworldgamemode");

        $this->api->schedule(20, array($this, "timeHandler"), array(), true);

        $this->api->addHandler("player.quit", array($this, "eventHandler"));
        $this->api->addHandler("player.spawn", array($this, "eventHandler"));
        $this->api->addHandler("player.teleport.level", array($this, "eventHandler"));

        $this->config = new Config($this->api->plugin->configPath($this) . "config.yml", CONFIG_YAML, array(
            "countdownTime" => 5,
            "creativeWorlds" => array(),
            "survivalWorlds" => array(),
            "excludedPlayers" => array(),
        ));
    }

    public function eventHandler($data, $event) {
        switch ($event) {
            case "player.quit":
                $this->checkPlayer($data, true);
                break;
            case "player.spawn":
                //$data->sendChat("[Debug] Player Spawned");
                break;
            case "player.teleport.level":
                //$data->sendChat("[Debug] Player Teleported");
                $this->checkPlayer($data);
                break;
        }
    }

    public function commandHandler($cmd, $params, $issuer) {
        if ($cmd == "perworldgamemode") {

            switch (array_shift($params)) {
                case "set":
                    $mode = array_shift($params);
                    $world = array_shift($params);
                    if (is_null($world)) {
                        if (!$issuer instanceof Player) {
                            return 'Please specify a world!';
                        } else {
                            $world = $issuer->world->getname();
                        }
                    }

                    switch ($mode) {
                        case "s":
                        case "survival":
                            $this->removeprop("creativeWorlds", $world);
                            $this->addprop("survivalWorlds", $world);
                            return "Set " . $world . " to survival.";
                        case "c":
                        case "creative":
                            $this->removeprop("survivalWorlds", $world);
                            $this->addprop("creativeWorlds", $world);
                            return "Set " . $world . " to creative.";
                        default:
                            return "You need a gamemode!";
                    }
                    break;
                case "exclude":
                    $player = array_shift($params);
                    if (!is_null($player)) {
                        $this->addprop("excludedPlayers", $player);
                    }
                    return "Added " . $player . " to excluded list.";
                default:
                    return "/pwgm set <survival|creative> (world) or /pwgm exclude <player>";
            }
        }
    }

    public function timeHandler() {

        $removekeys = array();

        foreach ($this->list as $key => &$countdown) {
            $countdown[0]->sendChat("Gamemode change in $countdown[2]!");
            if ($countdown[2] <= 0) {
                $countdown[0]->setGamemode($countdown[1]);
                $removekeys[] = $key;
            } else {
                $countdown[2] = $countdown[2] - 1;
            }
        }

        foreach ($removekeys as $value) {
            unset($this->list[$value]);
        }
    }

    public function startCountdown($player, $gamemode) {
        $player->sendChat("Gamemode Change! You will need to log back in!!!!");
        $this->list[$player->iusername] = array(
            $player,
            $gamemode,
            5
        );
    }

    public function checkPlayer($data, $immediate = false) {

        if ($data instanceof Player) {
            $player = $data;
            $target = $player->level->getName();
        } elseif (is_array($data) && isset($data["player"]) && isset($data["target"])) {
            $player = $data["player"];
            $target = $data["target"]->getName();
        } else {
            return false;
        }

        $this->cancelCountdown($player);

        if ($immediate) {
            if (in_array($target, $this->config->get("creativeWorlds")) and !in_array($player->iusername, $this->config->get("excludedPlayers"))) {
                $player->setGamemode(CREATIVE);
            }
            if (in_array($target, $this->config->get("survivalWorlds")) and !in_array($player->iusername, $this->config->get("excludedPlayers"))) {
                $player->SetGamemode(SURVIVAL);
            }
        } else {
            if (in_array($target, $this->config->get("creativeWorlds")) and $player->gamemode != CREATIVE and !in_array($player->iusername, $this->config->get("excludedPlayers"))) {
                $this->startCountdown($player, CREATIVE);
            }
            if (in_array($target, $this->config->get("survivalWorlds")) and $player->gamemode != SURVIVAL and !in_array($player->iusername, $this->config->get("excludedPlayers"))) {
                $this->startCountdown($player, SURVIVAL);
            }
        }
    }

    public function cancelCountdown($player) {
        if (isset($this->list[$player->iusername])) {
            $player->sendChat("Gamemode change canceled!");
            unset($this->list[$player->iusername]);
        }
    }

    public function removeprop($arrname, $value) {
        $arr = $this->config->get($arrname);
        $arr = array_diff($arr, array($value));
        $this->config->set($arrname, $arr);
        $this->config->save();
    }

    public function addprop($arrname, $value) {
        $arr = $this->config->get($arrname);
        $arr[] = $value;
        $this->config->set($arrname, $arr);
        $this->config->save();
    }

    public function __destruct() {
    }

}
