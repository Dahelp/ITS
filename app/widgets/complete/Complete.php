<?php

namespace app\widgets\complete;

use ishop\App;

class Complete{
	
	public $complete;
	public $tpl;
	public $curr;
	
    public function __construct($complete, $curr, $tpl = ''){

		$this->tpl = $tpl ?: __DIR__ . '/complete_tpl.php';
        $this->run($complete, $curr);
		
    }
	
	protected function run($complete, $curr){

        require $this->tpl;

    }

}