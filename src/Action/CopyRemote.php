<?php
namespace Backup\Action;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Backup\Ssh\SftpProxy;
use Symfony\Component\Finder\SplFileInfo;

class CopyRemote extends Copy{
	
	/**
	 * @var SftpProxy
	 */
	private $sftp;
	
	public function __construct(InputInterface $input, OutputInterface $output, SftpProxy $sftp){
		parent::__construct($input, $output);
		$this->sftp = $sftp;
	}
	
	public function getMessage(){
		return '---==== REMOTE COPY STEP ===---';
	}
	
	public function getKey(){
		return 'remote-copy';
	}
	
	protected function copy(SplFileInfo $file){
		$destinationFile = $this->input->getArgument('destination') . DIRECTORY_SEPARATOR . $file->getFilename();
		$this->output->writeln(sprintf(
				'Copying %s to %s', $file->getRealPath(), $this->input->getArgument('host').':'.$this->input->getArgument('destination')));
		$this->sftp->send($file, $destinationFile);
	}
	
}