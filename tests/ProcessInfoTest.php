<?php
use PHPUnit\Framework\TestCase;
use unrealization\PHPClassCollection\Process;
use unrealization\PHPClassCollection\ProcessInfo;

/**
 * ProcessInfo test case.
 * @covers unrealization\PHPClassCollection\ProcessInfo
 */
class ProcessInfoTest extends TestCase
{
	public function test__construct()
	{
		$process = new Process('ls', false);
		$info = new ProcessInfo($process);
		$this->assertInstanceOf(ProcessInfo::class, $info);
	}

	public function testGetProcess()
	{
		$process = new Process('ls', false);
		$info = new ProcessInfo($process);
		$this->assertSame($process, $info->getProcess());
	}

	public function testHasStarted()
	{
		$process = new Process('ls', false);
		$info = new ProcessInfo($process);
		$this->assertSame(0.0, $info->getStartTime());
		$info = $info->hasStarted();
		$this->assertInstanceOf(ProcessInfo::class, $info);
		$this->assertGreaterThan(0, $info->getStartTime());
	}

	public function testHasEnded()
	{
		$process = new Process('ls', false);
		$info = new ProcessInfo($process);
		$this->assertSame(0.0, $info->getEndTime());
		$info = $info->hasEnded();
		$this->assertInstanceOf(ProcessInfo::class, $info);
		$this->assertGreaterThan(0, $info->getEndTime());
	}

	public function testLogStdOut()
	{
		$process = new Process('ls', false);
		$info = new ProcessInfo($process);
		$info = $info->logStdOut('Test');
		$this->assertInstanceOf(ProcessInfo::class, $info);
		$output = $info->getStdOut();
		$this->assertIsArray($output);
		$this->assertSame(1, count($output));
	}

	public function testLogStdErr()
	{
		$process = new Process('ls', false);
		$info = new ProcessInfo($process);
		$info = $info->logStdErr('Test');
		$this->assertInstanceOf(ProcessInfo::class, $info);
		$output = $info->getStdErr();
		$this->assertIsArray($output);
		$this->assertSame(1, count($output));
	}

	public function testGetStartTime()
	{
		$process = new Process('ls', false);
		$info = new ProcessInfo($process);
		$startTime = $info->getStartTime();
		$this->assertIsFloat($startTime);
		$this->assertSame(0.0, $startTime);
		$info->hasStarted();
		$startTime = $info->getStartTime();
		$this->assertIsFloat($startTime);
		$this->assertGreaterThan(0, $startTime);
	}

	public function testGetEndTime()
	{
		$process = new Process('ls', false);
		$info = new ProcessInfo($process);
		$endTime = $info->getEndTime();
		$this->assertIsFloat($endTime);
		$this->assertSame(0.0, $endTime);
		$info->hasEnded();
		$endTime = $info->getEndTime();
		$this->assertIsFloat($endTime);
		$this->assertGreaterThan(0, $endTime);
	}

	public function testGetExitCode()
	{
		$process = $this->createMock(Process::class);
		$process->method('isRunning')->willReturn(true, false);
		$process->method('getExitCode')->willReturn(null, 1);
		$info = new ProcessInfo($process);
		$code = $info->getExitCode();
		$this->assertNull($code);
		$info->hasStarted()->getProcess()->start();

		while ($info->getProcess()->isRunning())
		{
			usleep(1000);
		}

		$info->hasEnded();
		$code = $info->getExitCode();
		$this->assertIsInt($code);
		$this->assertSame(1, $code);
	}

	public function testGetStdOut()
	{
		$process = new Process('ls', false);
		$info = new ProcessInfo($process);
		$output = $info->getStdOut();
		$this->assertIsArray($output);
		$this->assertSame(0, count($output));
		$info->logStdOut('Test');
		$output = $info->getStdOut();
		$this->assertIsArray($output);
		$this->assertSame(1, count($output));
	}

	public function testGetStdErr()
	{
		$process = new Process('ls', false);
		$info = new ProcessInfo($process);
		$output = $info->getStdErr();
		$this->assertIsArray($output);
		$this->assertSame(0, count($output));
		$info->logStdErr('Test');
		$output = $info->getStdErr();
		$this->assertIsArray($output);
		$this->assertSame(1, count($output));
	}

	public function testGetRunTime()
	{
		$process = new Process('ls', false);
		$info = new ProcessInfo($process);
		$info->hasStarted();
		usleep(50000);
		$info->hasEnded();
		$runTime = $info->getRunTime();
		$this->assertIsFloat($runTime);
		$this->assertGreaterThan(0, $runTime);
		$this->assertLessThan(1, $runTime);
	}

	public function testGetRunTimeNotStarted()
	{
		$process = new Process('ls', false);
		$info = new ProcessInfo($process);
		$runTime = $info->getRunTime();
		$this->assertIsFloat($runTime);
		$this->assertSame(0.0, $runTime);
	}

	public function testGetRunTimeNotEnded()
	{
		$process = new Process('ls', false);
		$info = new ProcessInfo($process);
		$info->hasStarted();
		usleep(50000);
		$runTime = $info->getRunTime();
		$this->assertIsFloat($runTime);
		$this->assertGreaterThan(0, $runTime);
		$this->assertLessThan(1, $runTime);
	}
}
