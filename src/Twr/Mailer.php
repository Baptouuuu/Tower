<?php

namespace Twr;

class Mailer
{
    protected $mailer;
    protected $to;
    protected $from;
    protected $logPath;

    public function __construct($config, $logPath) {
        if ($config !== null) {
            switch ($config['transport']) {
                case 'smtp':
                    $transport = \Swift_SmtpTransport::newInstance(
                            $config['host'],
                            $config['port']
                        )
                        ->setUsername($config['username'])
                        ->setPassword($config['password']);
                    break;
                case 'sendmail':
                    $transport = \Swift_SendmailTransport::newInstance(
                        '/usr/sbin/sendmail -bs'
                    );
                    break;
                case 'mail':
                    $transport = \Swift_MailTransport::newInstance();
                    break;
            }

            $this->mailer = \Swift_Mailer::newInstance($transport);
            $this->to = $config['to'];
            $this->from = $config['from'];
            $this->logPath = $logPath;
        }
    }

    /**
     * Send a message
     *
     * @param string $subject
     * @param string $body
     */

    public function send($subject, $body)
    {
        if ($this->mailer) {
            $message = \Swift_Message::newInstance($subject)
                ->setBody($body)
                ->addTo($this->to)
                ->setFrom($this->from)
                ->attach(
                    \Swift_Attachment::fromPath($this->logPath)
                        ->setDisposition('inline')
                );

            $this->mailer->send($message);
        }
    }
}