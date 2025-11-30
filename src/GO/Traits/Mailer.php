<?php
namespace GO\Traits;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

trait Mailer
{
    /**
     * Get email configuration.
     *
     * @return array
     */
    public function getEmailConfig(): array
    {
        if (!isset($this->emailConfig['subject']) || !is_string($this->emailConfig['subject'])) {
            $this->emailConfig['subject'] = 'Cronjob execution';
        }

        if (!isset($this->emailConfig['from'])) {
            $this->emailConfig['from'] = 'cronjob@server.my';
        }

        if (!isset($this->emailConfig['body']) || !is_string($this->emailConfig['body'])) {
            $this->emailConfig['body'] = 'Cronjob output attached';
        }

        if (!isset($this->emailConfig['transport']) || !($this->emailConfig['transport'] instanceof MailerInterface)) {
            // Default transport (sendmail)
            $transport = Transport::fromDsn('sendmail://default');
            $this->emailConfig['transport'] = new \Symfony\Component\Mailer\Mailer($transport);
        }

        return $this->emailConfig;
    }

    /**
     * Send files to emails.
     *
     * @param array $files
     * @return void
     */
    private function sendToEmails(array $files): void
    {
        $config = $this->getEmailConfig();

        $email = (new Email())
            ->from($config['from'])
            ->to(...$this->emailTo) // assume $this->emailTo is an array
            ->subject($config['subject'])
            ->text($config['body'])
            ->html('<q>' . $config['body'] . '</q>');

        foreach ($files as $filename) {
            $email->attachFromPath($filename);
        }

        /** @var \Symfony\Component\Mailer\MailerInterface $mailer */
        $mailer = $config['transport'];
        $mailer->send($email);
    }
}
