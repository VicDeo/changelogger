<?php

namespace Owncloud\Changelogger;

use Milo\Github;

class Main {
	const PAGESIZE = 100;
	protected $repo;
	protected $api;

	public function __construct($config){
		$this->api = new Github\Api;
		$token = $config->get('token');
		if (!$this->isEmptyToken($token)){
			$authToken = new Github\OAuth\Token($token);
			$this->api->setToken($authToken);
		}
	}

	public function generate($repo, $milestone){
		$this->repo = $repo;
		$processed = 0;
		$page = 1;
		do {
			$query  = sprintf(
				"/search/issues?q=repo:%s+milestone:%s+type:pr+is:merged&per_page=%s&page=%s",
				$this->repo,
				$milestone,
				self::PAGESIZE,
				$page
			);
	
			$response = $this->api->get($query);
			$prs = $this->api->decode($response);

			foreach ($prs->items as $pr){
				$this->echoNl($pr->title . ' ' . $this->getSlug($pr->number));
				$links = $this->printLinkedIssues($pr);
				$this->echoNl(''); 
			}

			$processed += count($prs->items);
			$page += 1;
		} while ($prs->total_count>0 && $processed<$prs->total_count);
	}
	
	protected function printLinkedIssues($pr){
		$linksCache = [];
		foreach ($this->collectLinkedIssues($pr) as $link){
			// strip #commentId
			$link = trim(preg_replace('!#.*$!', '', $link));
			// do not duplicate issues
			if (isset($cache[$link])){
				continue;
			}
			$cache[$link] = true;
			try {
				$this->echoNl($this->getIssueInfoByLink($link));
			} catch (\Milo\Github\NotFoundException $e){
				$this->echoNl('Access to ' . $link . ' is restricted');
			}
		}
	}
	
	protected function collectLinkedIssues($pr){
		$links = $this->extractLinks($pr->body);
		foreach ($this->getAllComments($pr->number) as $comment){
			$links = array_merge($links, $this->extractLinks($comment->body));
		}
		return $links;
	}
	
	protected function getAllComments($issueId){
		$query = sprintf(
			"/repos/%s/issues/%d/comments",
			$this->repo,
			$issueId
		);
		$response = $this->api->get($query);
		$comments = $this->api->decode($response);
		return $comments;
	}
	
	protected function getIssueInfoByLink($link){
		$info = '';
		$relLink = str_replace('https://github.com/', '', $link);
		preg_match_all('!https://github\.com/([^/]+/[^/]+)/(pull|issues)/(\d+)!', $link, $matches);
		if (isset($matches[1][0]) && isset($matches[3][0])){
			$query = sprintf(
				"/repos/%s/issues/%d",
				$matches[1][0],
				$matches[3][0]
			);
			$response = $this->api->get($query);
			$issue = $this->api->decode($response);
			$info = sprintf(
				" - %s %s#%d",
				$issue->title,
				$matches[1][0],
				$matches[3][0]
			);
		}
		return $info;
	}
	
	protected function getSlug($issueId){
		$prefix = preg_replace('!.*/!', '' , $this->repo);
		return $prefix . '#' . $issueId;
	}
	
	protected function extractLinks($comment){
		$links = [];
		preg_match_all('!https://github.com/[^\s]*!', $comment, $matches);
		if (isset($matches[0]) && count($matches[0])){
			$links = $matches[0];
		}
		return $links;
	}
	
	protected function isEmptyToken($token){
		return in_array($token, [null, '']);
	}
	
	protected function echoNl($message){
		echo "$message\n";
	}
}
