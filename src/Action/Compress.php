<?php
namespace Backup\Action;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Helper\ProgressBar;

class Compress extends BaseAction{
	
	private $compress;
	
	/**
	 * @var ProgressBar
	 */
	protected $progress;
	
	public function __construct(InputInterface $input, OutputInterface $output){
		parent::__construct($input, $output);
		$this->compress = $input->getOption('archive');
		$this->progress = new ProgressBar($output);
		$this->progress->setFormat('verbose');
	}
	
	public function run(){
		$compressBuilder = new ProcessBuilder();
		$compress = null;
		switch($this->compress){
			case 'zip':
				$compressBuilder->setPrefix('zip');
				$compress = $compressBuilder->setArguments(array('-r', $this->getFileName(), $this->input->getArgument('source')))
					->getProcess();
			break;
			case 'tar.gz':
				$compressBuilder->setPrefix('tar');
				$compress = $compressBuilder->setArguments(
					array('cfz', $this->getFileName(), '-C' . $this->input->getArgument('source'), '.')
				)->getProcess();
			break;
		}
		$compress->start();
		$this->output->writeln(sprintf('Compress %s', $this->getFileName()));
		$this->progress->start();
		while($compress->isRunning()){
			$this->progress->advance();
		}
		$this->progress->finish();
		$this->output->writeln(sprintf("\n%s compressed", $this->getFileName()));
	}
	
	public function getMessage(){
		return '---==== COMPRESSION STEP ===---';
	}
	
	public function getKey(){
		return 'compress';
	}
	
	private function getFileName(){
		$prefix = $this->input->getOption('filename');
		return sys_get_temp_dir() . DIRECTORY_SEPARATOR .$prefix . '_' . time() . '.' . $this->compress;
	}
	
}