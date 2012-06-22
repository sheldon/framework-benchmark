<?php
class ModelA extends WaxModel{
  public function setup(){
    $this->define("model_b", "ManyToManyField", array("target_model"=>"ModelB"));
    $this->define("title", "CharField");
  }
}?>