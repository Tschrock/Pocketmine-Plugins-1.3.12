<?php

/*
  __PocketMine Plugin__
  name=ServerMail
  description=Send mail to players
  version=1.0
  author=tschrock
  class=ServerMail
  apiversion=12
 */

class ServerMail implements Plugin {

    private $api, $config;

    public function __construct(ServerAPI $api, $server = false) {
        $this->api = $api;
    }

    public function init() {

        $this->config = new Config($this->api->plugin->configPath($this) . "config.yml", CONFIG_YAML, array(
            "newPlayerMessage" => "Welcome to the Server!",
        ));

        @mkdir($this->api->plugin->configPath($this) . "players/");


        $this->api->addHandler("player.connect", array($this, "eventHandler"));

        if (class_exists("SimpleAuth")) {
            $this->api->addHandler("simpleauth.login", array($this, "eventHandler"));
        } else {
            $this->api->addHandler("player.spawn", array($this, "eventHandler"));
        }

        $this->api->console->register("mail", "<read|clear> or /mail send <player> <message>", array($this, "commandHandler"));
    }

    public function commandHandler($cmd, $params, $issuer, $alias) {
        if ($cmd == "mail") {
            switch (strtolower(array_shift($params))) {
                case "read":  // fallthrough
                case "view":
                    if ($issuer instanceof Player) {
                        $messages = $this->getMessages($issuer->iusername);
                        $issuer->sendChat("[ServerMail] You have " . count($messages) . " messages:");
                        foreach ($messages as $message) {
                            $issuer->sendChat("    " . $message["sender"] . ": " . $message["message"]);
                        }
                    }
                    break;
                case "clear":
                    if ($issuer instanceof Player) {
                        $this->clearMessages($issuer->iusername);
                        $issuer->sendChat("[ServerMail] All messages cleared");
                    }
                    break;
                case "send":
                    $sender = $issuer->iusername;
                    $recipiant = strtolower(array_shift($params));
                    $message = implode(" ", $params);

                    if ($recipiant != NULL && $message != NULL) {
                        if ($this->checkUser($recipiant)) {
                            $this->addMessage($recipiant, $sender, $message);
                            $issuer->sendChat("[ServerMail] Message sent!");
                        } else {
                            $issuer->sendChat("[ServerMail] $recipiant has no mailbox!");
                        }
                    } else {
                        $issuer->sendChat("Usage: /mail send <player> <message>");
                    }

                    break;
                case "broadcast":
                    
                    //break;
                default:
                    $issuer->sendChat("Usage: /mail <view|read|clear|send>");
            }
        }
    }

    public function eventHandler($data, $event) {
        switch ($event) {
            case "player.connect":

                break;
            case "simpleauth.login":
            case "player.spawn":

                $messagecount = $this->getMessageCount($data->iusername);

                if ($messagecount == 0) {
                    $data->sendChat("[ServerMail] You have no messages.");
                } else {
                    $data->sendChat("[ServerMail] You have " . $messagecount . " messages.");
                    $data->sendChat("Use '/mail read' to see them. ");
                }
                break;
        }
    }

    public function getMessageCount($player) {
        $d = $this->getData($player);
        $c = count($d->get("messages"));
        if ($d->get("firstrun")) {
            $c = $c + 1;
        }
        return $c;
    }

    public function getMessages($player) {
        $d = $this->getData($player);
        $m = $this->getData($player)->get("messages");
        if ($d->get("firstrun")) {
            $m[] = array(
                "sender" => "Server",
                "message" => $this->config->get("newPlayerMessage"),
            );
        }
        return $m;
    }

    public function addMessage($player, $sender, $message) {
        $d = $this->getData($player);
        $e = $d->get("messages");
        $e[] = array(
            "sender" => "$sender",
            "message" => "$message",
        );
        $d->set("messages", $e);
        $d->save();
    }

    public function clearMessages($player) {
        $d = $this->getData($player);
        $d->remove("messages");
        $d->set("firstrun", false);
        $d->save();
    }

    public function getData($iusername) {
        $iusername = strtolower($iusername);
        if (!file_exists($this->api->plugin->configPath($this) . "players/" . $iusername{0} . "/$iusername.yml")) {
            @mkdir($this->api->plugin->configPath($this) . "players/" . $iusername{0} . "/");
            $d = new Config($this->api->plugin->configPath($this) . "players/" . $iusername{0} . "/" . $iusername . ".yml", CONFIG_YAML, array(
                "firstrun" => true,
                "messages" => array(),
            ));

            $d->save();
            return $d;
        }
        return new Config($this->api->plugin->configPath($this) . "players/" . $iusername{0} . "/" . $iusername . ".yml", CONFIG_YAML, array(
            "firstrun" => true,
            "messages" => array(),
        ));
    }

    public function checkUser($name) {
        $name = strtolower($name);
        return file_exists(dirname(dirname($this->api->plugin->configPath($this))) . "/players/$name.yml");
    }

    public function __destruct() {
        
    }

}
