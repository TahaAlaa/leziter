<?php

namespace App\Leziter\MailboxSynchronizer;

use Illuminate\Support\Facades\Mail;

class AdminNotificationEmail
{
    public static function send(string $content): void
    {
        Mail::raw($content, function ($message) {
            $message->to(env('MAIL_SYNC_ADMIN_EMAIL'))->subject(env('MAIL_SYNC_ADMIN_EMAIL_SUBJECT'));
        });
    }
}
