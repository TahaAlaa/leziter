<?php

namespace App\Console\Commands;

use App\Leziter\MailboxSynchronizer\AdminNotificationEmail;
use App\Leziter\MailboxSynchronizer\MailboxSynchronizer;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;

class SyncMailbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-mailbox';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the mailbox synchronizer.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {

            $synchronizer = new MailboxSynchronizer();
            $synchronizer->synchronize();

        } catch (\Exception $e) {
            AdminNotificationEmail::send($e->getMessage());
        } catch (GuzzleException $e) {
            AdminNotificationEmail::send($e->getMessage());
        }
    }
}
