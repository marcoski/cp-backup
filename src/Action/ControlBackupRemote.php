<?php
namespace Backup\Action;

use Backup\Ssh\SftpProxy;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\SplFileInfo;

class ControlBackupRemote extends ControlBackup{
	
	/**
	 * @var SftpProxy
	 */
	private $sftp;
	
	public function __construct(InputInterface $input, OutputInterface $output, SftpProxy $sftp){
		parent::__construct($input, $output);
		$this->sftp = $sftp;
	}
	
	protected function finder(){
		$this->finder->files()
		->in($this->sftp->getUrl($this->input->getArgument('destination')))
		->name($this->getBackupFilePattern());
	}
	
	protected function remove(SplFileInfo $file){
		$this->output->writeln(sprintf('Remove %s to %s', $file->getRealPath(), $this->input->getArgument('destination')));
		$this->sftp->remove($file->getRealPath());
	}
	
}