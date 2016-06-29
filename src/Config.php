<?php

namespace Owncloud\Changelogger;

class Config {
	public function __construct(){
		if (!file_exists($this->getConfigBasePath() . '/config.php')){
			copy($this->getConfigBasePath() . '/config.sample.php', $this->getConfigBasePath() . '/config.php');
		}
	}
	
	public function get($key){
		@include $this->getConfigBasePath() . '/config.php';
		if (isset($config[$key])){
			return $config[$key];
		}
		return null;
	}
	
	private function getConfigBasePath(){
		return dirname(__DIR__) . '/config';
	}
}
