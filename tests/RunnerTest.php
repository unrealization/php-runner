<?php
use PHPUnit\Framework\TestCase;
use unrealization\PHPClassCollection\Process;
use unrealization\PHPClassCollection\ProcessInfo;
use unrealization\PHPClassCollection\Runner;

/**
 * ProcessInfo test case.
 * @covers unrealization\PHPClassCollection\Runner
 * @uses unrealization\PHPClassCollection\ProcessInfo
 */
class RunnerTest extends TestCase
{
	public function test__construct()
	{
		$runner = new Runner();
		$this->assertInstanceOf(Runner::class, $runner);
	}

	public function testAddProcess()
	{
		$runner = new Runner();
		$process = new Process('ls', false);
		$runner = $runner->addProcess($process);
		$this->assertInstanceOf(Runner::class, $runner);
		$info = $runner->getProcessInfo(0);
		$this->assertInstanceOf(ProcessInfo::class, $info);
	}

	public function testGetProcessInfo()
	{
		$runner = new Runner();
		$process = new Process('ls', false);
		$runner = $runner->addProcess($process);
		$info = $runner->getProcessInfo(0);
		$this->assertInstanceOf(ProcessInfo::class, $info);
	}

	public function testGetProcessInfoInvalidIndex()
	{
		$runner = new Runner();
		$this->expectException(\OutOfBoundsException::class);
		$runner->getProcessInfo(0);
	}

	public function testFindProcessInfo()
	{
		$runner = new Runner();
		$process = new Process('ls', false);
		$runner = $runner->addProcess($process);
		$processes = $runner->findProcessInfo('ls');
		$this->assertIsArray($processes);
		$this->assertSame(1, count($processes));
	}

	public function testRun()
	{
		$this->markTestIncomplete();
	}

	public function testRunCommand()
	{
		$info = Runner::runCommand('');
		$this->assertInstanceOf(ProcessInfo::class, $info);
	}
}
