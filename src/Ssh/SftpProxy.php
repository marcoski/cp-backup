<?php
namespace Backup\Ssh;

use Commonhelp\Resource\Auth;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Console\Helper\ProgressBar;
use Backup\Command\RemoteBackupCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SftpProxy extends SshProxy{
	
	/**
	 * @var ProgressBar
	 */
	protected $progressBar;

	public function __construct(RemoteBackupCommand $command, InputInterface $input, OutputInterface $output){
		parent::__construct($command, $input, $output);
		ProgressBar::setFormatDefinition('send', '%current%/%max% [%bar%] %percent:3s%% Time to finish: %estimated:-6s%');
	}
	
	/**
	 * 
	 * @return \Commonhelp\Ssh\System\Sftp;
	 */
	public function getSftp(){
		return $this->subSystem;
	}
	
	public function send(SplFileInfo $source, $destination){
		$stream = @fopen($this->getUrl($destination), 'wb');
		
		if(!$stream){
			throw new \RuntimeException(sprintf('Could not open remote file %s', $destination));
		}
		
		$this->progressBar = new ProgressBar($this->output, $source->getSize());
		$this->progressBar->setFormat('send');
		$dataToSendFp = @fopen($source->getRealPath(), 'rb');
		if(!$dataToSendFp){
			throw new \RuntimeException(sprintf('Could not open local file %s.', $source->getRealPath()));
		}
		
		while(!feof($dataToSendFp)){
			if(false === @fwrite($stream, fread($dataToSendFp, 1024))){
				throw new \RuntimeException(sprintf('Could not send data from file: %s', $source->getRealPath()));
			}
			$this->progressBar->advance(1024);
		}
		$this->progressBar->finish();
		@fclose($stream);
		@fclose($dataToSendFp);
	}
	
	public function receive($remote, $local){
		$stream = @fopen($this->getUrl($remote), 'r', null, $this->createProgressContext());
		
		if(!$stream){
			throw new \RuntimeException(sprintf('Could not open file: %s', $remote));
		}
		
		$contents = fread($stream, filesize($this->getUrl($remote)));
		file_put_contents($local, $remote);
		@fclose($stream);
	}
	
	public function getUrl($filename){
		return $this->getSftp()->getUrl($filename);
	}
	
	public function remove($filename){
		return $this->getSftp()->unlink($filename);
	}
	
	public function exists($filename){
		return $this->getSftp()->exists($filename);
	}
	
	protected function connect(Auth $auth){
		parent::connect($auth);
		$this->subSystem = $this->sshSession->getSftp();
		$this->subSystem->getResource();
	}
}