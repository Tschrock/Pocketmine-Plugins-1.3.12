<?php

/*
  __PocketMine Plugin__
  name=BouncyBlocks
  description=Bounce on Blocks!
  version=1.0
  author=tschrock
  class=BouncyBlocks
  apiversion=12
 */

class BouncyBlocks implements Plugin {

    private $api;

    public function __construct(ServerAPI $api, $server = false) {
        $this->api = $api;
    }

    public function init() {
        $this->api->event("entity.move", array($this, "eventHandler"));
        $this->config = new Config($this->api->plugin->configPath($this) . "config.yml", CONFIG_YAML, array(
            "opsOnly" => false,
            "Blocks" => array(
                "35:2" => "10",
                "35:3" => "20",
            ),
        ));
    }

    public function eventHandler($data, $event) {
        if (!$data->fallY && !is_null($data->player) && ($this->config->get("opsOnly") ? $this->api->ban->isOp($data->player) : true)) {
            
            $playerX = (int) round($data->x - 0.5);
            $playerY = (int) round($data->y - 1);
            $playerZ = (int) round($data->z - 0.5);
            
            $block = $data->level->getBlock(new Vector3($playerX, $playerY, $playerZ));
            
            if ($this->isBouncy($block)) {
                
                $data->speedY = $this->getBouncyness($block);

                $pk = new SetEntityMotionPacket;
                $pk->eid = 0;
                $pk->speedX = $data->speedX;
                $pk->speedY = (int) ($data->speedY * 32000);
                $pk->speedZ = $data->speedZ;

                $data->player->dataPacket($pk);
            }
            return true;
        }
    }
    
    public function isBouncy($block){
        return isset($this->config->get("Blocks")[$block->getID() . ":" . $block->getMetadata()]);
    }
    
    public function getBouncyness($block){
        return $this->config->get("Blocks")[$block->getID() . ":" . $block->getMetadata()];
    }

    public function __destruct() {
        
    }

}
