<?php

namespace app\widgets\cabinet;

use ishop\Cache;

class Cabinet{

    public $tpl;
	
    public function __construct($tpl){
		require_once ''.$tpl.'';
    }   
}