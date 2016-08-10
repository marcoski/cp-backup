<?php
namespace Backup\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Backup\Action\Compress;
use Backup\Action\Copy;
use Backup\Action\ControlBackup;


class BackupCommand extends Command{
	
	const WEEKELY = 0;
	const MONTHLY = 1;
	
	const TARGZ = 'tar.gz';
	const ZIP = 'zip';
	
	protected $destination;
	protected $source;
	protected $options;
	protected $actions;
	
	protected $configs = null;
	
	protected function configure(){
		$this->makeConfigs();
		$this->setName('simple')
			->setDescription('Simple backup');
		$this->addArgument('source', InputArgument::REQUIRED, 'The source path to backup');
		$this->addArgument('destination', InputArgument::REQUIRED, 'The destination path');
		$this->addOption('delete', 'd', InputOption::VALUE_REQUIRED,
			'How long the backuped data should be mantained set <comment>[' . self::WEEKELY . ': weekely, ' . self::MONTHLY . ': monthly]</comment>',
			isset($this->configs['delete']) ? $this->configs['delete'] : self::WEEKELY
		);
		$this->addOption('archive', 'a', InputOption::VALUE_REQUIRED,
			'What kind of archive? set <comment>[' . self::TARGZ . ', ' . self::ZIP . ']</comment>',
			isset($this->configs['archive']) ? $this->configs['archive'] : self::TARGZ		
		);
		$this->addOption('filename', 'F', InputOption::VALUE_REQUIRED,
			'Set filename prefix',
			isset($this->configs['filename']) ? $this->configs['filename'] : 'cp_backup'
		);
	}
	
	protected function initialize(InputInterface $input, OutputInterface $output){
		if(!$this->isSourcePathValid($input)){
			throw new \RuntimeException(sprintf('Invalid source path (%s)', $input->getArgument('source')));
		}
		if(!$this->isDestinationPathValid($input)){
			throw new \RuntimeException(sprintf('Invalid destination path (%s)', $input->getArgument('destination')));
		}
		$this->actions = array(
			new Compress($input, $output),
			new Copy($input, $output),
			new ControlBackup($input, $output)
		);
	}
	
	protected function execute(InputInterface $input, OutputInterface $output){
		foreach($this->actions as $action){
			$output->writeln($action->getMessage());
			$action->run();
			$output->writeln('----------===========---------');
		}
	}
	
	protected function isSourcePathValid(InputInterface $input){
		return file_exists($input->getArgument('source'));
	}
	
	protected function isDestinationPathValid(InputInterface $input){
		return file_exists($input->getArgument('destination'));
	}
	
	private function makeConfigs(){
		$configFile = $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.cp-backup' . DIRECTORY_SEPARATOR . 'config.json';
		if(file_exists($configFile)){
			$this->configs = json_decode(file_get_contents($configFile), true);
			if(json_last_error() !== JSON_ERROR_NONE){
				$error_message  = 'Syntax error';
				if(function_exists('json_last_error_msg')){
					$error_message = json_last_error_msg();
				}
				$error = array(
						'message' => $error_message,
						'type'    => json_last_error(),
						'file'    => $configFile,
				);
				throw new \RuntimeException($error['message'] . ' -> ' .$error['file']);
			}
		}
	}
	
}