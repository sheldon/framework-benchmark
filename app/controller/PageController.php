<?php

/**
 * Page Controller
 *
 * This is a default controller installed by PHP-WAX
 * Feel free to use this one, or just create your own using 'script/new_controller my_name'
 *
 * All you need to do in this controller is make one public method for each url.
 * Then make html templates in the 'view/page' directory.
 * The default 'index' is setup already.
 **/

class PageController extends ApplicationController{
  
  public function create(){
    $model_a = new ModelA;
    $model_b = new ModelB;
    foreach(range(1,200) as $i){
      $model_a->id = false;
      $model_a->title = "a$i";
      $model_a->save();

      $model_b->id = false;
      $model_b->title = "b$i";
      $model_b->save();

      if(rand(0,1) > 0.5) $model_a->model_b = $model_b;
    }
    echo "created"; exit;
  }
  
}
?>