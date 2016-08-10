<?php
namespace Backup\Action;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseAction implements ActionInterface{
	
	const BACKUP_FILE_PATTERN = '/^%s_([0-9]*).%s/';
	
	/**
	 * @var InputInterface
	 */
	protected $input;
	
	/**
	 * @var OutputInterface
	 */
	protected $output;
	
	public function __construct(InputInterface $input, OutputInterface $output){
		$this->input = $input;
		$this->output = $output;
	}
	
	protected function getBackupFilePattern(){
		return sprintf(self::BACKUP_FILE_PATTERN, $this->input->getOption('filename'), $this->input->getOption('archive'));
	}
	
}