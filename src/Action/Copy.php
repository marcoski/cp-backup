<?php
namespace Backup\Action;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Finder\SplFileInfo;

class Copy extends BaseAction{
	
	/**
	 * @var Finder
	 */
	protected $finder;
	
	public function __construct(InputInterface $input, OutputInterface $output){
		parent::__construct($input, $output);
		$this->finder = new Finder();
	}
	
	public function run(){
		$this->finder->files()->in(sys_get_temp_dir())->name($this->getBackupFilePattern());
		foreach($this->finder as $file){
			$this->copy($file);
			$this->remove($file);
		}
	}
	
	public function getMessage(){
		return '---==== COPY STEP ===---';
	}
	
	public function getKey(){
		return 'copy';
	}
	
	protected function copy(SplFileInfo $file){
		$this->output->writeln(sprintf('Copying %s to %s', $file->getRealPath(), $this->input->getArgument('destination')));
		$copyBuilder = new ProcessBuilder();
		$copyBuilder->setPrefix('cp');
		$copy = $copyBuilder->setArguments(array($file->getRealPath(), $this->input->getArgument('destination')))->getProcess();
		$copy->start();
		$this->progress->start();
		while($copy->isRunning()){
			$this->progress->advance();
		}
		$this->progress->finish();
		$this->output->writeln("\n");
	}
	
	protected function remove(SplFileInfo $file){
		$this->output->writeln(sprintf('Remove %s to %s', $file->getRealPath(), sys_get_temp_dir()));
		$removeBuilder = new ProcessBuilder();
		$removeBuilder->setPrefix('rm');
		$remove = $removeBuilder->setArguments(array($file->getRealPath()))->getProcess();
		$remove->run();
	}
}