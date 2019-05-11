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
 * @version 1.0.0
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL 2.1
 */
class Runner
{
	/**
	 * The list of processes to be run
	 * @var array
	 */
	private $processList = array();
	/**
	 * Maximum number of parallel processes
	 * @var int
	 */
	private $maxProcesses = 1;
	/**
	 * Maximum runtime in seconds before a process is forcefully killed. 0 means unlimited
	 * @var float
	 */
	private $maxRunTime = 0;

	/**
	 * Get the current or total runtime of a specific process
	 * @param int $processIndex
	 * @return float
	 * @throws \OutOfBoundsException
	 */
	private function getProcessRunTime(int $processIndex): float
	{
		if (isset($this->processList[$processIndex]))
		{
			if ($this->processList[$processIndex]['started'] == 0)
			{
				return 0;
			}

			if ($this->processList[$processIndex]['ended'] == 0)
			{
				$endTime = microtime(true);
			}
			else
			{
				$endTime = $this->processList[$processIndex]['ended'];
			}

			$runTime = $endTime - $this->processList[$processIndex]['started'];
			return $runTime;
		}
		else
		{
			throw new \OutOfBoundsException('Invalid index: '.$processIndex);
		}
	}

	/**
	 * Log output read from the processes
	 * @param int $processIndex
	 * @param string $channel
	 * @param string $output
	 */
	private function logOutput(int $processIndex, string $channel, string $output)
	{
		if ((strlen($output) > 0) && (is_array($this->processList[$processIndex][$channel])))
		{
			$this->processList[$processIndex][$channel][] = array(
				'time'		=> microtime(true),
				'output'	=> $output
			);
		}
	}

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
	 * Add a process to the process list
	 * @param Process $process
	 */
	public function addProcess(Process $process): void
	{
		$this->procesList[] = array(
			'process'	=> $process,
			'started'	=> 0,
			'ended'		=> 0,
			'exitCode'	=> null,
			'stdOut'	=> array(),
			'stdErr'	=> array()
		);
	}

	/**
	 * Get information from the process list
	 * @param int $processIndex
	 * @return array
	 * @throws \OutOfBoundsException
	 */
	public function getProcessInfo(int $processIndex = null): array
	{
		if ($processIndex === null)
		{
			return $this->processList;
		}
		else
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
	}

	/**
	 * Find information in the process list
	 * @param string $filter
	 * @param bool $regEx
	 * @return array
	 */
	public function findProcessInfo(string $filter, bool $regEx = false): array
	{
		$foundProcesses = array();

		foreach ($this->processList as $process)
		{
			$command = $process->getCommand();

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
	 */
	public function setMaxProcesses(int $maxProcesses)
	{
		$this->maxProcesses = $maxProcesses;
	}

	/**
	 * Set the maximum runtime in seconds before a process is forcefully killed. 0 means unlimited
	 * @param float $maxRunTime
	 */
	public function setMaxRunTime(float $maxRunTime)
	{
		$this->maxRunTime = $maxRunTime;
	}

	/**
	 * Process the process list
	 */
	public function run()
	{
		$index = 0;
		$runningProcs = array();

		while (($index < count($this->processList)) || (count($runningProcs) > 0))
		{
			if (($index < count($this->processList)) && (count($runningProcs) < $this->maxProcesses))
			{
				$runningProcs[$index] = $this->processList[$index]['process'];
				$this->processList[$index]['started'] = microtime(true);
				$runningProcs[$index]->start();
				$index++;
			}

			foreach ($runningProcs as $key => $proc)
			{
				$this->logOutput($key, 'stdOut', $proc->readSTDOUT());
				$this->logOutput($key ,'stdErr', $proc->readSTDERR());

				if ($proc->isRunning() == false)
				{
					if (($this->processList[$key]['ended'] == 0) && ($this->processList[$key]['exitCode'] == null))
					{
						$this->processList[$key]['ended'] = microtime(true);
						$this->processList[$key]['exitCode'] = $proc->getExitCode();
					}
					else
					{
						unset($runningProcs[$key]);
					}
				}
				elseif (($this->maxRunTime > 0) && ($this->getProcessRunTime($key) > $this->maxRunTime))
				{
					$this->logOutput($key, 'stdErr', 'Killing process'.PHP_EOL);
					$proc->kill(9);
				}
			}
		}
	}

	/**
	 * Run the given command
	 * @param string $command
	 * @param float $maxRunTime
	 * @return array
	 */
	public static function runCommand(string $command, float $maxRunTime = 0): array
	{
		$process = new Process($command, false);
		$runner = new self(1, $maxRunTime);
		$runner->addProcess($process);
		$runner->run();

		$processInfo = $runner->getProcessInfo(0);
		$output = array(
				'stdOut'	=> '',
				'stdErr'	=> '',
				'exitCode'	=> $processInfo['exitCode'],
				'runTime'	=> round($processInfo['ended'] - $processInfo['started'], 2)
				);

		foreach ($processInfo['stdOut'] as $entry)
		{
			$output['stdOut'] .= $entry['output'];
		}

		foreach ($processInfo['stdErr'] as $entry)
		{
			$output['stdErr'] .= $entry['output'];
		}

		return $output;
	}
}
?>