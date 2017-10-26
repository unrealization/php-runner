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
 * @version 0.9.0
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL 2.1
 */
class Runner
{
	/**
	 * The list of commands
	 * @var array
	 */
	private $commandList = array();
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
	 * Constructor
	 * @param array $commandList
	 * @param int $maxProcesses
	 * @param float $maxRunTime
	 */
	public function __construct(array $commandList = array(), int $maxProcesses = 1, float $maxRunTime = 0)
	{
		$this->loadCommandList($commandList);
		$this->setMaxProcesses($maxProcesses);
		$this->setMaxRunTime($maxRunTime);
	}

	/**
	 * Add a command to the command list
	 * @param string $command
	 */
	public function addCommand(string $command)
	{
		$this->commandList[] = array(
				'command'	=> $command,
				'started'	=> 0,
				'ended'		=> 0,
				'exitCode'	=> null,
				'stdOut'	=> array(),
				'stdErr'	=> array()
				);
	}

	/**
	 * Load an array of commands into the command list
	 * @param array $commandList
	 */
	public function loadCommandList(array $commandList)
	{
		foreach ($commandList as $command)
		{
			$this->addCommand($command);
		}
	}

	/**
	 * Get information from the command list
	 * @param int $commandIndex
	 * @return array
	 * @throws \OutOfBoundsException
	 */
	public function getCommandInfo(int $commandIndex = null): array
	{
		if ($commandIndex === null)
		{
			return $this->commandList;
		}
		else
		{
			if (isset($this->commandList[$commandIndex]))
			{
				return $this->commandList[$commandIndex];
			}
			else
			{
				throw new \OutOfBoundsException('Invalid index: '.$commandIndex);
			}
		}
	}

	/**
	 * Get the current or total runtime of a specific command
	 * @param int $commandIndex
	 * @return float
	 * @throws \OutOfBoundsException
	 */
	private function getCommandRunTime(int $commandIndex): float
	{
		if (isset($this->commandList[$commandIndex]))
		{
			if ($this->commandList[$commandIndex]['started'] == 0)
			{
				return 0;
			}

			if ($this->commandList[$commandIndex]['ended'] == 0)
			{
				$endTime = microtime(true);
			}
			else
			{
				$endTime = $this->commandList[$commandIndex]['ended'];
			}

			$runTime = $endTime - $this->commandList[$commandIndex]['started'];
			return $runTime;
		}
		else
		{
			throw new \OutOfBoundsException('Invalid index: '.$commandIndex);
		}
	}

	/**
	 * Find information in the command list
	 * @param string $filter
	 * @param bool $regEx
	 * @return array
	 */
	public function findCommandInfo(string $filter, bool $regEx = false): array
	{
		$foundCommands = array();

		foreach ($this->commandList as $command)
		{
			if ((($regEx === true) && (preg_match($filter, $command['command']))) || (($regEx === false) && ($command['command'] === $filter)))
			{
				$foundCommands[] = $command;
			}
		}

		return $foundCommands;
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
	 * Log output read from the processes
	 * @param int $commandIndex
	 * @param string $channel
	 * @param string $output
	 */
	private function logOutput(int $commandIndex, string $channel, string $output)
	{
		if ((strlen($output) > 0) && (is_array($this->commandList[$commandIndex][$channel])))
		{
			$this->commandList[$commandIndex][$channel][] = array(
					'time'		=> microtime(true),
					'output'	=> $output
					);
		}
	}

	/**
	 * Process the command list
	 */
	public function run()
	{
		$index = 0;
		$runningProcs = array();

		while (($index < count($this->commandList)) || (count($runningProcs) > 0))
		{
			if (($index < count($this->commandList)) && (count($runningProcs) < $this->maxProcesses))
			{
				$this->commandList[$index]['started'] = microtime(true);
				$runningProcs[$index] = new Process($this->commandList[$index]['command']);
				$index++;
			}

			foreach ($runningProcs as $key => $proc)
			{
				$this->logOutput($key, 'stdOut', $proc->readSTDOUT());
				$this->logOutput($key ,'stdErr', $proc->readSTDERR());

				if ($proc->isRunning() == false)
				{
					if (($this->commandList[$key]['ended'] == 0) && ($this->commandList[$key]['exitCode'] == null))
					{
						$this->commandList[$key]['ended'] = microtime(true);
						$this->commandList[$key]['exitCode'] = $proc->getExitCode();
					}
					else
					{
						unset($runningProcs[$key]);
					}
				}
				elseif (($this->maxRunTime > 0) && ($this->getCommandRunTime($key) > $this->maxRunTime))
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
		$runner = new self(array($command), 1, $maxRunTime);
		$runner->run();

		$commandInfo = $runner->getCommandInfo(0);
		$output = array(
				'stdOut'	=> '',
				'stdErr'	=> '',
				'exitCode'	=> $commandInfo['exitCode'],
				'runTime'	=> round($commandInfo['ended'] - $commandInfo['started'], 2)
				);

		foreach ($commandInfo['stdOut'] as $entry)
		{
			$output['stdOut'] .= $entry['output'];
		}

		foreach ($commandInfo['stdErr'] as $entry)
		{
			$output['stdErr'] .= $entry['output'];
		}

		return $output;
	}
}
?>