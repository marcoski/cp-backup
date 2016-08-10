<?php
namespace Backup\Action;

interface ActionInterface{
	
	public function run();
	
	public function getMessage();
	
	public function getKey();
	
}