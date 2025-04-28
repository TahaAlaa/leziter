<?php

namespace App\Leziter\MailboxSynchronizer;

use App\Leziter\Facades\RestApiClient;
use App\Models\MailAttachmentSynchronizerLog;
use GuzzleHttp\Exception\GuzzleException;

class MailboxSynchronizer
{
    /**
     * @throws GuzzleException
     */
    public function synchronize(): void
    {
        $syncLimit  = (int)config('leziter.mail_sync.limit_per_run');
        $appBackend = new AppBackendApi(new RestApiClient(env('APPSY_API_URL'), env('APPSY_API_TOKEN')));
        $processLog = new ProcessLog();
        $consumer   = new MailboxConsumer();

        if ($consumer->noEmailFound()) return;

        foreach ($consumer->getMailboxEmails() as $loopIndex => $mailBoxItem)
        {
            if ($loopIndex + 1 >= $syncLimit) break;

            try {
                $email = new ProcessableEmail($mailBoxItem);
                $appBackend->setupFor($email);
                $processLog->setUpFor($email);

                if ($processLog->isMailInFailedList()) continue;

                if ($email->doesNotContainOrderId()) {
                    throw new MailProcessFailedException($email, "Missing order id in mail content ({$email->getSubject()})");
                }

                $appBackend->findOrderForMail();

                $email->downloadAttachments();
                $processLog->markProcessInProgress($appBackend->getRemoteOrderId());

                $email->processAttachments();
                $processLog->updateProcessStatus(MailAttachmentSynchronizerLog::PROCESS_STATUS_ATTACHMENTS_PROCESSED);

                $appBackend->uploadInvoice();
                $appBackend->uploadWarranty();
                $processLog->updateDocumentIds($appBackend->getRemoteInvoiceId(), $appBackend->getRemoteWarrantyId());

                $appBackend->attachInvoiceToRemoteOrder();
                $appBackend->attachWarrantyToRemoteOrder();
                $processLog->updateProcessStatus(MailAttachmentSynchronizerLog::PROCESS_STATUS_ATTACHMENTS_ATTACHED_TO_ORDER);

                $appBackend->uploadWarrantyInformation();

                $consumer->moveToProcessedFolder($mailBoxItem);
                $email->deleteDownloadedAttachments();

                $processLog->markProcessFinished();

            }
            catch (BaseMailboxSynchronizerException $e) {
                $processLog->markProcessFailed($e->getMessage());
                $consumer->moveToFailedFolder($mailBoxItem);
                AdminNotificationEmail::send($e->getMessage());
            }
            catch (\Exception $e) {
                if (isset($email)) {
                    $processLog->markProcessFailed($e->getMessage());
                }
                AdminNotificationEmail::send($e->getMessage());
            }
        }
    }

}
