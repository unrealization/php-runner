<?php
declare(strict_types=1);
/**
 * @package PHPClassCollection
 * @subpackage Runner
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 */
namespace unrealization;

use \unrealization\Runner\ProcessInfo;
/**
 * @package PHPClassCollection
 * @subpackage Runner
 * @link http://php-classes.sourceforge.net/ PHP Class Collection
 * @author Dennis Wronka <reptiler@users.sourceforge.net>
 * @version 3.0.0
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL 2.1
 */
class Runner
{
	/**
	 * The list of processes to be run
	 * @var array
	 */
	private array $processList = array();
	/**
	 * Maximum number of parallel processes
	 * @var int
	 */
	private int $maxProcesses = 1;
	/**
	 * Maximum runtime in seconds before a process is forcefully killed. 0 means unlimited
	 * @var float
	 */
	private float $maxRunTime = 0;

	/**
	 * Constructor
	 * @param int $maxProcesses
	 * @param float $maxRunTime
	 */
	public function __construct(int $maxProcesses = 1, float $maxRunTime = 0)
	{
		$this->setMaxProcesses($maxProcesses);
		$this->setMaxRunTime($maxRunTime);
	}

	/**
	 * Add a command to the process list
	 * @param string $command
	 * @return Runner
	 */
	public function addCommand(string $command): Runner
	{
		$process = new Process($command, false);
		$this->processList[] = new ProcessInfo($process);
		return $this;
	}

	/**
	 * Add a process to the process list
	 * @param Process $process
	 * @return Runner
	 */
	public function addProcess(Process $process): Runner
	{
		$this->processList[] = new ProcessInfo($process);
		return $this;
	}

	/**
	 * Get information from the process list
	 * @param int $processIndex
	 * @return ProcessInfo
	 * @throws \OutOfBoundsException
	 */
	public function getProcessInfo(int $processIndex): ProcessInfo
	{
		if (isset($this->processList[$processIndex]))
		{
			return $this->processList[$processIndex];
		}
		else
		{
			throw new \OutOfBoundsException('Invalid index: '.$processIndex);
		}
	}

	/**
	 * Find information in the process list
	 * @param string $filter
	 * @param bool $regEx
	 * @return ProcessInfo[]
	 */
	public function findProcessInfo(string $filter, bool $regEx = false): array
	{
		$foundProcesses = array();

		foreach ($this->processList as $process)
		{
			$command = $process->getProcess()->getCommand();

			if ((($regEx === true) && (preg_match($filter, $command))) || (($regEx === false) && ($command === $filter)))
			{
				$foundProcesses[] = $process;
			}
		}

		return $foundProcesses;
	}

	/**
	 * Set the maximum number of parallel processes
	 * @param int $maxProcesses
	 * @return Runner
	 */
	public function setMaxProcesses(int $maxProcesses): Runner
	{
		$this->maxProcesses = $maxProcesses;
		return $this;
	}

	/**
	 * Set the maximum runtime in seconds before a process is forcefully killed. 0 means unlimited
	 * @param float $maxRunTime
	 * @return Runner
	 */
	public function setMaxRunTime(float $maxRunTime): Runner
	{
		$this->maxRunTime = $maxRunTime;
		return $this;
	}

	/**
	 * Process the process list
	 */
	public function run(): void
	{
		$index = 0;
		$runningProcs = array();

		while (($index < count($this->processList)) || (count($runningProcs) > 0))
		{
			if (($index < count($this->processList)) && (count($runningProcs) < $this->maxProcesses))
			{
				$runningProcs[$index] = $this->processList[$index];
				$runningProcs[$index]->hasStarted()->getProcess()->start();
				$index++;
			}

			foreach ($runningProcs as $key => $proc)
			{
				$output = $proc->getProcess()->readSTDOUT();

				if (!empty($output))
				{
					$proc->logStdOut($output);
				}

				$output = $proc->getProcess()->readSTDERR();

				if (!empty($output))
				{
					$proc->logStdErr($output);
				}

				if ($proc->getProcess()->isRunning() === false)
				{
					if ($proc->getEndTime() === 0.0)
					{
						$proc->hasEnded();
					}
					else
					{
						unset($runningProcs[$key]);
					}
				}
				elseif (($this->maxRunTime > 0) && ($proc->getRunTime() > $this->maxRunTime))
				{
					$proc->logStdErr('Killing process'.PHP_EOL);
					$proc->getProcess()->kill(SIGKILL);
				}
			}
		}
	}

	/**
	 * Run the given command
	 * @param string $command
	 * @param float $maxRunTime
	 * @return ProcessInfo
	 */
	public static function runCommand(string $command, float $maxRunTime = 0): ProcessInfo
	{
		$runner = new self(1, $maxRunTime);
		$runner->addCommand($command);
		$runner->run();
		$processInfo = $runner->getProcessInfo(0);
		return $processInfo;
	}
}