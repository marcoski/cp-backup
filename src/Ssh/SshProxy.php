<?php
namespace Backup\Ssh;

use Symfony\Component\Console\Input\InputInterface;
use Backup\Command\RemoteBackupCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Commonhelp\Resource\Auth;
use Commonhelp\Resource\SubSystem;
use Symfony\Component\Console\Question\Question;
use Commonhelp\Ssh\Auth\None;
use Commonhelp\Ssh\Auth\PublicKeyFile;
use Commonhelp\Ssh\Auth\Password;
use Commonhelp\Ssh\SshSession;
use Symfony\Component\Console\Helper\ProgressBar;

abstract class SshProxy{
	
	protected $sshSession;
	
	/**
	 * @var Application
	 */
	protected $application;
	
	/**
	 * @var InputInterface
	 */
	protected $input;
	
	/**
	 * @var OutputInterface
	 */
	protected $output;
	
	/**
	 * @var QuestionHelper
	 */
	protected $questionHelper;
	
	/**
	 * @var SubSystem
	 */
	protected $subSystem;
	
	/**
	 * @var ProgressBar
	 */
	protected $progressBar;
	
	protected $user;
	protected $hostname;
	
	public function __construct(RemoteBackupCommand $command, InputInterface $input, OutputInterface $output){
		$this->application = $command->getApplication();
		$this->user = $command->getUser();
		$this->hostname = $command->getHostname();
		$this->input = $input;
		$this->output = $output;
		$this->questionHelper = $command->getHelper('question');
		$this->doConnection();
	}
	
	protected function connect(Auth $auth){
		$this->sshSession = new SshSession(array('host' => $this->hostname), $auth);
	}
	
	protected function question($message){
		$question = new Question($message);
		$question->setHidden(true);
		$question->setHiddenFallback(false);
		return $this->questionHelper->ask($this->input, $this->output, $question);
	}
	
	protected function createProgressContext(){
		return stream_context_create(
			[],
			['notification' => [$this, 'streamProgress']]
		);
	}
	
	protected function streamProgress($notificationCode, $severity, $message, $messageCode, $bytesTransferred, $bytesMax){
		if(STREAM_NOTIFY_REDIRECTED === $notificationCode){
			$this->progressBar->clear();
			$this->progressBar = null;
			return;
		}
		
		if(STREAM_NOTIFY_FILE_SIZE_IS === $notificationCode){
			if(null !== $this->progressBar){
				$this->progressBar->clear();
			}
			$this->progressBar = new ProgressBar($this->output, $bytesMax);
		}
		
		if(STREAM_NOTIFY_PROGRESS === $notificationCode){
			if(null === $this->progressBar){
				$this->progressBar = new ProgressBar($this->output);
			}
			$this->progressBar->setProgress($bytesTransferred);
		}
		
		if(STREAM_NOTIFY_COMPLETED){
			$this->progressBar->finish($bytesTransferred);
		}
	}
	
	private function doConnection(){
		try{
			$this->connect(new None($this->user));
		}catch (\RuntimeException $e){
			if(file_exists($this->getPublicKeyFile()) && file_exists($this->getPrivateKeyFile())){
				try{
					$pubKeyAuth = new PublicKeyFile($this->user, $this->getPublicKeyFile(), $this->getPrivateKeyFile());
					$this->connect($pubKeyAuth);
				}catch (\RuntimeException $e){
					//Want a passphrase
					$passPhrase = $this->question(sprintf('Please insert passphrase for %s@%s: ', $this->user, $this->hostname));
					$pubKey = new PublicKeyFile($username, $this->getPublicKeyFile(), $this->getPrivateKeyFile(), $passPhrase);
					$this->connect($pubKey);
				}
			}else{
				//Try ask password
				$password = $this->question(sprintf('Please insert password for %s@%s: ', $this->user, $this->hostname));
				$pwdAuth = new Password($this->user, $password);
				$this->connect($pwdAuth);
			}
		}
	}
	
	private function getPublicKeyFile(){
		$publicKeyFile = '/home/' . $this->user . '/.ssh/id_rsa.pub';
		if(null !== $this->input->getOption('ssh_pubkey')){
			$publicKeyFile = $this->input->getOption('ssh_pubkey');
		}
	
		return $publicKeyFile;
	}
	
	private function getPrivateKeyFile(){
		$privateKeyFile = '/home/' . $this->user .'/.ssh/id_rsa';
		if(null !== $this->input->getOption('ssh_privkey')){
			$privateKeyFile = $this->input->getOption('ssh_privkey');
		}
	
		return $privateKeyFile;
	}
	
}