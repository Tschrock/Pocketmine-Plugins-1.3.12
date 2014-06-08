<?php

/*
  __PocketMine Plugin__
  name=Template
  description=Template
  version=1.0
  author=tschrock
  class=Template
  apiversion=12
 */

class Template implements Plugin {

    private $api, $config;

    public function __construct(ServerAPI $api, $server = false) {
        $this->api = $api;
    }

    public function init() {

        $this->config = new Config($this->api->plugin->configPath($this) . "config.yml", CONFIG_YAML, array(
            "option1" => "stuffs",
        ));

        $this->api->console->register("simplecommand", "A test command", array($this, "commandHandler"));
        $this->api->console->register("simplecommandplus", "A test command with sub commands", array($this, "commandHandler"));
        $this->api->addhandler("event.name", array($this, "eventHandler"));
    }

    public function eventHandler($data, $event) {
        switch (strtolower($event)) {
            case "event.name":
                break;
            default:
                return true;
        }
    }

    public function commandHandler($cmd, $params, $issuer) {
        switch (strtolower($cmd)) {
            
            case "simplecommand":
                return "This is the output of /simplecommand.";
                
            case "simplecommandplus":
                switch (strtolower(array_shift($params))) {
                
                    case "subcommand1":
                        return "This is the output of /simplecommandplus subcommand1.";

                    case "sc2":
                    case "subcommand2":
                        return "This is the output of /simplecommandplus subcommand1 or /simplecommandplus sc1.";
                    
                    case "subcommand3":
                        $oldVlaue = $this->config->get("option1");
                        $newValue = implode(" ", $params);
                        $this->config->set("option1", $newValue);
                        return "Option1 changed from $oldValue to $newValue!";
                        
                    default:
                        return "This is the help text for simplecommandplus.";
                }
                
            default:
        }
    }

    public function __destruct() {
        
    }

}
