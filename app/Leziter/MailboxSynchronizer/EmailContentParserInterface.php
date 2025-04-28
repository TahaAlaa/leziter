<?php

namespace App\Leziter\MailboxSynchronizer;

interface EmailContentParserInterface
{
    public function getOrderId(string $mailContent): ?string;
}
