<?php
namespace GO\Job\Tests;

use GO\Job;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;

class MailerTest extends TestCase
{
    public function testShouldHaveDefaultConfigToSendAnEmail()
    {
        $job = new Job('ls');
        $config = $job->getEmailConfig();

        $this->assertTrue(isset($config['subject']));
        $this->assertTrue(isset($config['from']));
        $this->assertTrue(isset($config['body']));
        $this->assertInstanceOf(MailerInterface::class, $config['transport']);
    }

    public function testShouldAllowCustomTransportWhenSendingEmails()
    {
        $job = new Job(function () {
            return 'hi'; });

        $nullTransport = new Mailer(Transport::fromDsn('null://null'));

        $job->configure([
            'email' => [
                'transport' => $nullTransport,
            ],
        ]);

        $this->assertInstanceOf(MailerInterface::class, $job->getEmailConfig()['transport']);
    }

    public function testEmailTransportShouldAlwaysBeInstanceOfMailerInterface()
    {
        $job = new Job(function () {
            return 'hi'; });

        $job->configure([
            'email' => [
                'transport' => 'Something not allowed',
            ],
        ]);

        $this->assertInstanceOf(
            MailerInterface::class,
            $job->getEmailConfig()['transport']
        );
    }

    public function testShouldSendJobOutputToEmail()
    {
        $emailAddress = 'local@localhost.com';
        $command = PHP_BINARY . ' ' . __DIR__ . '/../test_job.php';

        $job1 = new Job($command);
        $job2 = new Job(fn() => 'Hello World!');

        $nullTransportConfig = [
            'email' => [
                'transport' => new Mailer(Transport::fromDsn('null://null')),
            ],
        ];

        $job1->configure($nullTransportConfig);
        $job2->configure($nullTransportConfig);

        $outputFile1 = __DIR__ . '/../tmp/output001.log';
        $this->assertTrue($job1->output($outputFile1)->email($emailAddress)->run());

        $outputFile2 = __DIR__ . '/../tmp/output002.log';
        $this->assertTrue($job2->output($outputFile2)->email($emailAddress)->run());

        // Only unlink if file exists
        if (file_exists($outputFile1)) {
            unlink($outputFile1);
        }

        // Only unlink if file exists
        if (file_exists($outputFile2)) {
            unlink($outputFile2);
        }
    }

    public function testShouldSendMultipleFilesToEmail()
    {
        $emailAddress = 'local@localhost.com';
        $command = PHP_BINARY . ' ' . __DIR__ . '/../async_job.php';
        $job = new Job($command);

        $outputFile1 = __DIR__ . '/../tmp/output003.log';
        $outputFile2 = __DIR__ . '/../tmp/output004.log';

        $nullTransportConfig = [
            'email' => [
                'transport' => new Mailer(Transport::fromDsn('null://null')),
            ],
        ];

        $job->configure($nullTransportConfig);

        $this->assertTrue(
            $job->output([$outputFile1, $outputFile2])
                ->email([$emailAddress])
                ->run()
        );

        // Only unlink if file exists
        if (file_exists($outputFile1)) {
            unlink($outputFile1);
        }

        // Only unlink if file exists
        if (file_exists($outputFile2)) {
            unlink($outputFile2);
        }
    }

    public function testShouldSendToMultipleEmails()
    {
        $command = PHP_BINARY . ' ' . __DIR__ . '/../async_job.php';
        $job = new Job($command);

        $outputFile = __DIR__ . '/../tmp/output005.log';

        $nullTransportConfig = [
            'email' => [
                'transport' => new Mailer(Transport::fromDsn('null://null')),
            ],
        ];

        $job->configure($nullTransportConfig);

        $this->assertTrue(
            $job->output($outputFile)
                ->email(['local@localhost.com', 'local1@localhost.com'])
                ->run()
        );

        // Only unlink if file exists
        if (file_exists($outputFile)) {
            unlink($outputFile);
        }
    }

    public function testShouldAcceptCustomEmailConfig()
    {
        $command = PHP_BINARY . ' ' . __DIR__ . '/../async_job.php';
        $job = new Job($command);

        $outputFile = __DIR__ . '/../tmp/output6.log';

        $nullTransport = new Mailer(Transport::fromDsn('null://null'));

        $this->assertTrue(
            $job->output($outputFile)
                ->email('local@localhost.com')
                ->configure([
                    'email' => [
                        'subject' => 'My custom subject',
                        'from' => 'my@custom.from',
                        'body' => 'My custom body',
                        'transport' => $nullTransport,
                    ],
                ])->run()
        );

        // Only unlink if file exists
        if (file_exists($outputFile)) {
            unlink($outputFile);
        }
    }

    public function testShouldIgnoreEmailIfSpecifiedInConfig()
    {
        $job = new Job(function () {
            $x = 1 + 2; });

        $nullTransportConfig = [
            'email' => [
                'transport' => new Mailer(Transport::fromDsn('null://null')),
                'ignore_empty_output' => true,
            ],
        ];
        $job->configure($nullTransportConfig);

        $outputFile = __DIR__ . '/../tmp/output.log';
        $this->assertTrue($job->output($outputFile)->email('local@localhost.com')->run());

        // Only unlink if file exists
        if (file_exists($outputFile)) {
            unlink($outputFile);
        }
    }
}
