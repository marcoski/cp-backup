<?php
namespace Backup\Action;

use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Backup\Command\BackupCommand;
use Symfony\Component\Process\ProcessBuilder;

class ControlBackup extends BaseAction{
	
	/**
	 * @var Finder
	 */
	protected $finder;
	
	public function __construct(InputInterface $input, OutputInterface $output){
		parent::__construct($input, $output);
		$this->finder = new Finder();
	}
	
	public function run(){
		$this->finder();
		foreach($this->finder as $file){
			if($this->isRemovable($file)){
				$this->remove($file);
				$this->output->writeln(sprintf('<info>%s backup file removed older then a %s</info>', $file->getRealPath(), $this->getInfoTimeMessage()));
			}else{
				$this->output->writeln(sprintf('<info>%s backup file <comment>not</comment> removed yunger then a %s</info>', $file->getRealPath(), $this->getInfoTimeMessage()));
			}
		}
	}
	
	public function getMessage(){
		return '---==== CONTROL BACKUP REPOSITORY STEP ===---';
	}
	
	public function getKey(){
		return 'control-backup';
	}
	
	protected function finder(){
		$this->finder->files()
			->in($this->input->getArgument('destination'))->name($this->getBackupFilePattern());
	}
	
	protected function isRemovable(SplFileInfo $file){
		$today = new \DateTime(null, new \DateTimeZone('GMT'));
		$fileCTime = \DateTime::createFromFormat('U', $file->getCTime(), new \DateTimeZone('GMT'));
		$dateDiff = $fileCTime->diff($today);
		
		switch($this->input->getOption('delete')){
			case BackupCommand::WEEKELY:
				if($dateDiff->days > 7){
					return true;
				}
				return false;
			case BackupCommand::MONTHLY:
				if($dateDiff->days > 30){
					return true;
				}
				return false;
			default: return false;
		}
	}
	
	protected function remove(SplFileInfo $file){
		$this->output->writeln(sprintf('Remove %s to %s', $file->getRealPath(), $this->input->getArgument('destination')));
		$removeBuilder = new ProcessBuilder();
		$removeBuilder->setPrefix('rm');
		$remove = $removeBuilder->setArguments(array($file->getRealPath()))->getProcess();
		$remove->run();
	}
	
	private function getInfoTimeMessage(){
		switch($this->input->getOption('delete')){
			case BackupCommand::WEEKELY:
				return 'week';
			case BackupCommand::MONTHLY:
				return 'month';
		}
	}
	
}