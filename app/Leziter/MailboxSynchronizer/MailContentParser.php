<?php

namespace App\Leziter\MailboxSynchronizer;

readonly class MailContentParser implements EmailContentParserInterface
{
    public function getOrderID(string $mailContent): ?string
    {
        $pattern = config('leziter.mail_sync.mail_content_order_id_pattern', '/(?P<identifier>[0-9]{5,}-[0-9]{6,})/');
        preg_match($pattern, $mailContent, $matches);
        return $matches['identifier'] ?? null;
    }
}
