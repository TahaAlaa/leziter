<?php

namespace App\Jobs;

use App\Leziter\MailboxSynchronizer\AdminNotificationEmail;
use App\Leziter\MailboxSynchronizer\MailboxSynchronizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Exception\GuzzleException;

class SyncMailboxJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $failOnTimeout = true;
    public $tries = 1;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
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
