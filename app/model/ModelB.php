<?php
class ModelB extends WaxModel{
  public function setup(){
    $this->define("model_a", "ManyToManyField", array("target_model"=>"ModelA"));
    $this->define("title", "CharField");
  }
}?>