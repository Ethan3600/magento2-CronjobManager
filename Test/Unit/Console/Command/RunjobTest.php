<?php

namespace EthanYehuda\CronjobManager\Test\Unit\Console\Command;

use EthanYehuda\CronjobManager\Console\Command\Runjob;
use EthanYehuda\CronjobManager\Model\Cron\Runner;
use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\Console\Cli;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;

class RunjobTest extends TestCase
{
    protected $runner;
    protected $command;

    protected function setUp(): void
    {
        $this->runner = $this->createMock(Runner::class);

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->expects($this->any())
            ->method('create')
            ->willReturn($this->runner);

        $objectManagerFactory = $this->createMock(ObjectManagerFactory::class);
        $objectManagerFactory->expects($this->any())
            ->method('create')
            ->willReturn($objectManager);

        $command = new Runjob($objectManagerFactory);
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteThrowsWithoutRequiredArgument()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "job_code").');

        $this->commandTester->execute([]);
    }

    public function testExecute()
    {
        $jobCode = 'good_job';
        $resultCode = Cli::RETURN_SUCCESS;
        $resultMessage = "$jobCode successfully ran";

        $this->runner->expects($this->once())
            ->method('runCron')
            ->with($jobCode)
            ->willReturn([$resultCode, $resultMessage]);

        $commandResult = $this->commandTester->execute([
            'job_code' => $jobCode,
        ]);
        $this->assertSame($resultCode, $commandResult);
        $this->assertSame($resultMessage . PHP_EOL, $this->commandTester->getDisplay());
    }
}
