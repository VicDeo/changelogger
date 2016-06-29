<?php

require __DIR__ . '/vendor/autoload.php';

use \Owncloud\Changelogger\Config;
use \Owncloud\Changelogger\Main;

$milestone = isset($argv[1]) ? $argv[1] : null;

if (!is_null($milestone)){
	$config = new Config();
	$main = new Main($config);
	try {
		foreach ($config->get('repos') as $repo){
			echo "Processing repository $repo\n";
			$main->generate($repo, $milestone);
		}
	} catch (\Milo\Github\RateLimitExceedException $e){
		echo "\n" . 'You have reached a limit set by GitHub for unauthorized users.'
			. "\n" . 'Please consider specifying Oauth token in ' . __DIR__ . '/config/config.php to increase it.' 
			. "\n" . 'See https://github.com/milo/github-api/wiki/OAuth-token-obtaining for more details.'
			. "\n"
		;
	}
} else {
	echo "Usage: php $argv[0] milestone\n";
}
