<?php
namespace Backup\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Backup\Action\Compress;
use Backup\Action\CopyRemote;
use Backup\Action\ControlBackupRemote;
use Backup\Ssh\SftpProxy;


class RemoteBackupCommand extends BackupCommand{
	
	private $hostname;
	private $user;
	
	/**
	 * @var SftpProxy
	 */
	private $sftp;
	
	public function getHostname(){
		return $this->hostname;
	}
	
	public function getUser(){
		return $this->user;
	}
	
	protected function configure(){
		parent::configure();
		$this->setName('remote')
			->setDescription('Remote backup');
		
		$this->addArgument('host', InputArgument::REQUIRED, 'Destination remote host <comment>[user@hostname]</comment>');
		$sshPubKey = null;
		if(isset($this->configs['remote']) && isset($this->configs['remote']['ssh_pubkey'])){
			$sshPubKey = $this->configs['remote']['ssh_pubkey'];
		}
		$this->addOption('ssh_pubkey', '-K', InputOption::VALUE_REQUIRED, 'Ssh public key file path', $sshPubKey);
		$sshPrivKey = null;
		if(isset($this->configs['remote']) && isset($this->configs['remote']['ssh_privkey'])){
			$sshPrivKey = $this->configs['remote']['ssh_privkey'];
		}
		$this->addOption('ssh_privkey', '-k', InputOption::VALUE_REQUIRED, 'Ssh private key file path', $sshPrivKey);
	}
	
	protected function initialize(InputInterface $input, OutputInterface $output){
		if(!$this->isValidHost($input)){
			throw new \RuntimeException('Host name is invalid follow the form user@hostname');
		}
		$this->sftp = new \Backup\Ssh\SftpProxy($this, $input, $output);
		parent::initialize($input, $output);
		$this->actions = array(
			new Compress($input, $output),
			new CopyRemote($input, $output, $this->sftp),
			new ControlBackupRemote($input, $output, $this->sftp)
		);
	}
	
	protected function isDestinationPathValid(InputInterface $input){
		return $this->sftp->exists($input->getArgument('destination'));
	}

	private function isValidHost(InputInterface $input){
		$host = 'ssh://'.$input->getArgument('host');
		$hostParts = parse_url(filter_var($host, FILTER_SANITIZE_URL));
		
		if(!isset($hostParts['user'])){
			return false;
		}
		
		$this->hostname = $hostParts['host'];
		$this->user = $hostParts['user'];
		
		return true;
	}
	
}