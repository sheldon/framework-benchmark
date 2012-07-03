<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Default_Controller extends CI_Controller {

	public function index(){
    $this->page = IgnitedRecord::factory('posts');
    $this->load->view('shared/apptop.html');
		$this->load->view('home.html');
    $this->load->view('shared/appbottom.html');
	}

}

