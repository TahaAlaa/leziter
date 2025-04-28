<?php

return [
    'mail_sync' => [
        'limit_per_run' => env('MAIL_SYNC_LIMIT_PER_RUN', 10),
        'mail_content_order_id_pattern' => '/(?P<identifier>[0-9]{5,}-[0-9]{6,})/',
        'local_attachments_path' => storage_path('app/attachments'),
        'mailbox' => [
            'folder' => 'INBOX',
            'processed_folder' => 'INBOX.LeziterApiProcessed',
            'failed_folder' => 'INBOX.LeziterApiFailed',
            'username' => env('MAIL_SYNC_IMAP_USER'),
            'password' => env('MAIL_SYNC_IMAP_PASS'),
            'imap_host' => env('MAIL_SYNC_IMAP_HOST'),
            'imap_port' => env('MAIL_SYNC_IMAP_PORT'),
        ]
    ]
];
