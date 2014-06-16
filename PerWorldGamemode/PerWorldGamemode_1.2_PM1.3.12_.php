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
        $this->api->console->register("perworldgamemode", "/pwgm set <survival|creative> (world) or /pwgm <exclude|include> <player>", array($this, "commandHandler"));
        $this->api->console->alias("pwgm", "perworldgamemode");

        $this->api->schedule(20, array($this, "timeHandler"), array(), true);

        $this->api->addHandler("player.quit", array($this, "eventHandler"));
        $this->api->addHandler("player.spawn", array($this, "eventHandler"));
        $this->api->addHandler("player.teleport.level", array($this, "eventHandler"));

        $this->config = new Config($this->api->plugin->configPath($this) . "config.yml", CONFIG_YAML, array(
            "countdownTime" => 5,
            "excludedPlayers" => array(),
            "worlds" => array(),
        ));
    }

    public function eventHandler($data, $event) {
        switch ($event) {
            case "player.quit":
                $this->checkPlayer($data, true);
                break;
            case "player.spawn":
                break;
            case "player.teleport.level":
                $this->checkPlayer($data);
                break;
        }
    }

    public function commandHandler($cmd, $params, $issuer) {
        if (strtolower($cmd) == "perworldgamemode") {

            switch (strtolower(array_shift($params))) {
                case "set":
                    if (is_null($world = array_shift($params))) {
                        if (!$issuer instanceof Player) {
                            return 'Please specify a world!';
                        } else {
                            $world = $issuer->level->getname();
                        }
                    } elseif ($this->api->level->get($world) === false) {
                        return 'That world doesn\'t exist! (World names are case-sensitive).';
                    }
                    if (!$mode = $this->checkGamemode(array_shift($params))) {
                        return 'Please specify a correct gamemode!';
                    }

                    $this->setWorldGamemode($world, $mode);
                    return "Set $world to $mode.";

                case "exclude":
                    if (false !== $player = $this->api->player->get(array_shift($params))) {
                        $this->addprop("excludedPlayers", $player->iusername);
                        return "Added " . $player->iusername . " to excluded list.";
                    }
                case "include":
                    if (false !== $player = $this->api->player->get(array_shift($params))) {
                        $this->removeprop("excludedPlayers", $player->iusername);
                        return "Removed " . $player->iusername . " from excluded list.";
                    }
                default:
                    return "/pwgm set <survival|creative> (world) or /pwgm <exclude|include> <player>";
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
            $world = $player->level->getName();
        } elseif (is_array($data) && isset($data["player"]) && isset($data["target"])) {
            $player = $data["player"];
            $world = $data["target"]->getName();
        } else {
            return false;
        }

        $this->cancelCountdown($player);

        if (!in_array(strtolower($player->iusername), array_map('strtolower', $this->config->get("excludedPlayers"))) && ($gm = $this->checkGamemode($this->getWorldGamemode($world))) !== false && $player->gamemode !== ($gm = $this->getGamemodeNumber($gm))) {

            if ($immediate) {
                $player->setGamemode($gm);
            } else {
                $this->startCountdown($player, $gm);
            }
        } else {
            return false;
        }
    }

    public function cancelCountdown($player) {
        if (isset($this->list[$player->iusername])) {
            $player->sendChat("Gamemode change canceled!");
            unset($this->list[$player->iusername]);
        }
    }

    public function getWorldGamemode($world) {
        return (isset($this->config->get("worlds")[$world])) ? $this->config->get("Worlds")[$world] : $this->api->getProperty("gamemode", "survival");
    }

    public function setWorldGamemode($world, $gamemode) {
        $worlds = $this->config->get("worlds");
        $worlds[$world] = $gamemode;
        $this->config->set("worlds", $worlds);
        $this->config->save();
    }

    public function unsetWorldGamemode($world) {
        $worlds = $this->config->get("worlds");
        unset($worlds[$world]);
        $this->config->set("worlds", $worlds);
        $this->config->save();
    }

    public function removeprop($arrname, $value) {
        $this->config->set($arrname, array_diff($this->config->get($arrname), array($value)));
        $this->config->save();
    }

    public function addprop($arrname, $value) {
        $arr = $this->config->get($arrname);
        $arr[] = $value;
        $this->config->set($arrname, $arr);
        $this->config->save();
    }

    public function checkGamemode($gamemode) {
        switch (strtolower($gamemode)) {
            case "survival":
            case "s":
                return "survival";
            case "creative":
            case "c":
                return "creative";
            default:
                return ($gamemode === SURVIVAL) ? "survival" : (($gamemode === CREATIVE) ? "creative" : false);
        }
    }

    public function getGamemodeNumber($gamemode) {
        return ("survival" === $gm = $this->checkGamemode($gamemode)) ? SURVIVAL : (("creative" === $gm) ? CREATIVE : $gm);
    }

    public function __destruct() {
        
    }

}
