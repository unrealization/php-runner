<?php
use PHPUnit\Framework\TestCase;
use unrealization\Process;
use unrealization\Runner\ProcessInfo;
use unrealization\Runner;

/**
 * ProcessInfo test case.
 * @covers unrealization\Runner
 * @uses unrealization\Runner\ProcessInfo
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

	public function testRunCommand()
	{
		$info = Runner::runCommand('php --version');
		$this->assertInstanceOf(ProcessInfo::class, $info);
		$this->assertSame(0, $info->getExitCode());
		$this->assertStringContainsString('PHP '.PHP_VERSION, $info->getStdOut()[0]['output']);

		$info = Runner::runCommand('php --version > /dev/stderr');
		$this->assertInstanceOf(ProcessInfo::class, $info);
		$this->assertSame(0, $info->getExitCode());
		$this->assertStringContainsString('PHP '.PHP_VERSION, $info->getStdErr()[0]['output']);
	}

	public function testMaxRunTime()
	{
		$info = Runner::runCommand('sleep 2', 1);
		$this->assertSame(-1, $info->getExitCode());
	}
}
