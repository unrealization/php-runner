<?php
declare(strict_types=1);
/**
 * @package PHPClassCollection
 * @subpackage Runner
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 */
namespace unrealization\PHPClassCollection;
/**
 * @package PHPClassCollection
 * @subpackage Runner
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 * @version 0.0.2
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL 2.1
 */
class ProcessInfo
{
	private $process	= null;
	private $startTime	= 0;
	private $endTime	= 0;
	private $stdOut		= array();
	private $stdErr		= array();

	public function __construct(Process $process)
	{
		$this->process = $process;
	}

	public function getProcess(): Process
	{
		return $this->process;
	}

	public function hasStarted(): ProcessInfo
	{
		$this->startTime = microtime(true);
		return $this;
	}

	public function hasEnded(): ProcessInfo
	{
		$this->endTime = microtime(true);
		return $this;
	}

	public function logStdOut(string $output): ProcessInfo
	{
		$this->stdOut[] = array(
			'time'		=> microtime(true),
			'output'	=> $output
		);
		return $this;
	}

	public function logStdErr(string $output): ProcessInfo
	{
		$this->stdErr[] = array(
			'time'		=> microtime(true),
			'output'	=> $output
		);
		return $this;
	}

	public function getStartTime(): float
	{
		return $this->startTime;
	}

	public function getEndTime(): float
	{
		return $this->endTime;
	}

	/**
	 * Get the process's exit code.
	 * @return int|NULL
	 * @obsolete Use getProcess()->getExitCode() instead.
	 */
	public function getExitCode(): ?int
	{
		return $this->process->getExitCode();
	}

	public function getStdOut(): array
	{
		return $this->stdOut;
	}

	public function getStdErr(): array
	{
		return $this->stdErr;
	}

	public function getRunTime(): float
	{
		if ($this->startTime === 0)
		{
			return 0;
		}

		if ($this->endTime === 0)
		{
			$endTime = microtime(true);
		}
		else
		{
			$endTime = $this->endTime;
		}

		$runTime = $endTime - $this->startTime;
		return $runTime;
	}
}
?>