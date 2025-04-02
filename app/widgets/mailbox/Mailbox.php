<?php

namespace app\widgets\mailbox;

use ishop\App;

class Mailbox{

    public $tpl;
	
    public function __construct($tpl){

		$this->tpl = $tpl;
        $this->run();
    } 
	
	protected function run(){

        require $this->tpl;

    }
}