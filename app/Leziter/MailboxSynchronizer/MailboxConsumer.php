<?php

namespace App\Leziter\MailboxSynchronizer;

use Ddeboer\Imap\Server;

class MailboxConsumer
{

    private \Ddeboer\Imap\Mailbox $mailbox;
    private \Ddeboer\Imap\ConnectionInterface $connection;

    public function __construct()
    {
        $this->connect();
    }

    private function connect(): void
    {
        $host = config('leziter.mail_sync.mailbox.imap_host');
        $port = config('leziter.mail_sync.mailbox.imap_port');
        $folder = config('leziter.mail_sync.mailbox.folder');
        $username = config('leziter.mail_sync.mailbox.username');
        $password = config('leziter.mail_sync.mailbox.password');

        $server = new Server($host, $port);
        $this->connection = $server->authenticate($username, $password);
        $this->mailbox = $this->connection->getMailbox($folder);
    }

    public function noEmailFound(): bool
    {
        return $this->mailbox->count() === 0;
    }

    public function getMailboxEmails(): \Ddeboer\Imap\MessageIteratorInterface
    {
        return $this->mailbox->getMessages();
    }

    private function moveMailToAnotherMailbox(\Ddeboer\Imap\Message $message, string $newFolder): void
    {
        $mailbox = $this->connection->getMailbox($newFolder);
        $message->move($mailbox);
        $this->mailbox->getMessage($message->getNumber())->delete();
        $this->connection->expunge();
    }

    public function moveToProcessedFolder(\Ddeboer\Imap\Message $message): void
    {
        $processedFolder = config('leziter.mail_sync.mailbox.processed_folder');
        $this->moveMailToAnotherMailbox($message, $processedFolder);
    }

    public function moveToFailedFolder(\Ddeboer\Imap\Message $message): void
    {
        $failedFolder = config('leziter.mail_sync.mailbox.failed_folder');
        $this->moveMailToAnotherMailbox($message, $failedFolder);
    }
}
