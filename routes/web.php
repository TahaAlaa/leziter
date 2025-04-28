<?php

use App\Leziter\MailboxSynchronizer\MailAttachmentParser;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Route::get('/test', function (\Illuminate\Http\Request $request) {

    if (!App::environment('local')) exit;

    // Read files from attachment storage directory with laravel Storage helper
    $files = Storage::files('attachments');
    foreach ($files as $file) {
        if (is_dir($file)) continue;
        if (!Str::endsWith($file, '.pdf')) continue;
        $path = Storage::path($file); // Read file contents

        $attachmentParser = new MailAttachmentParser($path);
        $attachmentParser->parse();

        echo $file . ' is ' . ($attachmentParser->isInvoice() ? 'invoice' : 'warranty') . '<br>';

        if (!$attachmentParser->isInvoice()) {
            $warrantyInformation = $attachmentParser->parseWarrantyInformation();
            dump($warrantyInformation);
        }
    }

    return 'ok';
});
