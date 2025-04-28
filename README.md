# Leziter Api

## Basic Information:
The framework used in this project is **Laravel 11**.  
The package can be found in **\App\Leziter\MailSync namespace**.  
The following packages are required to run the service:
- ```poppler-utils``` (pdftotext library) command line tool.
- ```ddeboer/imap```

## For now, it is an e-mail attachment processor microservice between Appsy and Leziter.
### What this service does is:
- Fetching e-mails from the given e-mail account.
- Iterating over the fetched e-mails and parse the mail content then extract the order id from it.
- Fetching the order id from the Appsy API at the ```/webshop/order/find``` endpoint.
- If the order is not found, the service sends an e-mail to the given e-mail address with the error message.
- Store the order id in the log database.
- Check that the email contains at least two PDF attachment or not.
- If the email does not contain at least two PDF attachments, the service sends an e-mail to the given admin e-mail.
- Parse the attachments and determine which one is the invoice and which one is the warranty.
- Parse the warranty attachment and extract the warranty information.
- Upload both attachments to the App Backend via Appsy Api at the ```/documents/upload``` endpoint.
- Store the responded document ids in the log database.
- Attach the documents to the remote order via the Appsy Api at the ```/webshop/order/document/{order-id}``` endpoint.
- Extracting the start date and the duration of the warranty for every sku from the warranty attachment.
- Upload the warranty information to the Appsy Api at the ```/webshop/order/warranty/{order-id}``` endpoint.
- If any error occurs during the process, the service sends an e-mail to the given e-mail address with some details about the error.
- If a mail process fails, the service moves the mail to the failed mail folder.
- if a mail process is successful, the service moves the mail to the processed mail folder.
- The process is repeated at the given limit.
- The process update the mail and attachment processing status in the database quite detailed.

## Important Notes:
- You need to install the pdftotext library on your system. You can install it by running the following command: ```apt-get install poppler-utils``` or the equivalent command for your system package manager.
- The ```install.sh``` file contains a sample to install it for laravel sail. You can use it as a reference.

## Configuration and Need to Knows:
- You can configure the service by editing the ```.env``` file.
- You need to configure the standard Laravel mail configuration to send the notification e-mails.
- You can modify the service behavior by editing the following variables in the .env file:
```bash
APPSY_API_URL=
APPSY_API_TOKEN=
MAIL_SYNC_LIMIT_PER_RUN=10
MAIL_SYNC_ADMIN_EMAIL=
MAIL_SYNC_ADMIN_EMAIL_SUBJECT=
MAIL_SYNC_IMAP_HOST=
MAIL_SYNC_IMAP_PORT=
MAIL_SYNC_IMAP_USER=
MAIL_SYNC_IMAP_PASS=
```
- You can also modify the service behaviour by editing the ```config/leziter.php``` file:
```php
'mail_sync' => [
    'limit_per_run' => env('MAIL_SYNC_LIMIT_PER_RUN', 10),
    'mail_content_order_id_pattern' => '/(?P<identifier>[0-9]{5,}-[0-9]{6,})/', // IMPORTANT!!!: keep the named group as 'identifier' or change the code accordingly.
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
```

- A ```test_warranty_content.txt``` file can be found in the project root.  
Well because of the missing unit tests, I used this file content to test the warranty information parsing functionality.

## How to Run:
- There is an artisan command ```app:sync-mailbox``` to run the service.
- This artisan command is scheduled to run in every 5 minutes in the ```routes/console.php``` file (Laravel 11 new approach).
