<?php

namespace App\Leziter\MailboxSynchronizer;

use App\Models\MailAttachmentSynchronizerLog;

class ProcessLog
{
    private array $failedMailIds;
    private ProcessableEmail $email;

    public function __construct()
    {
        $this->failedMailIds = MailAttachmentSynchronizerLog::where('status', MailAttachmentSynchronizerLog::STATUS_ERROR)
            ->pluck('mail_id')->toArray();
    }

    public function setUpFor(ProcessableEmail $email): void
    {
        $this->email = $email;
    }

    public function isMailInFailedList(): bool
    {
        return in_array($this->email->getId(), $this->failedMailIds);
    }

    private function upsertProcessEntry(
        string $status,
        ProcessableEmail $email,
        string $details = '',
        ?string $processStatus = null,
        ?int $remoteOrderId = 0
    ): void
    {
        $updateData = [
            'mail_id' => $email->getId(),
            'subject' => $email->getSubject(),
            'attachments_count' => count($email->getAttachments()),
            'order_id' => $email->getOrderId(),
            'status' => $status,
            'details' => $details,
        ];
        if ($processStatus) $updateData['process_status'] = $processStatus;
        if ($remoteOrderId) $updateData['remote_order_id'] = $remoteOrderId;
        MailAttachmentSynchronizerLog::upsert($updateData, ['mail_id']);
    }

    public function markProcessFailed(string $details = ''): void
    {
        $status = MailAttachmentSynchronizerLog::STATUS_ERROR;
        $this->upsertProcessEntry(status: $status, email: $this->email, details: $details);
    }

    public function markProcessInProgress(int $remoteOrderId): void
    {
        $this->upsertProcessEntry(
            status: MailAttachmentSynchronizerLog::STATUS_IN_PROGRESS,
            email: $this->email,
            processStatus: MailAttachmentSynchronizerLog::PROCESS_STATUS_ATTACHMENTS_DOWNLOADED,
            remoteOrderId: $remoteOrderId
        );
    }

    public function markProcessFinished(): void
    {
        $status = MailAttachmentSynchronizerLog::STATUS_PROCESSED;
        $this->upsertProcessEntry(
            status: $status,
            email: $this->email,
            processStatus: MailAttachmentSynchronizerLog::PROCESS_STATUS_WARRANTY_INFO_UPLOADED
        );
    }

    public function updateProcessStatus(string $status): void
    {
        MailAttachmentSynchronizerLog::where('mail_id', $this->email->getId())->update([
            'process_status' => $status
        ]);
    }

    public function updateDocumentIds(int $invoiceId, int $warrantyId): void
    {
        MailAttachmentSynchronizerLog::where('mail_id', $this->email->getId())->update([
            'remote_invoice_id' => $invoiceId,
            'remote_warranty_id' => $warrantyId,
            'process_status' => MailAttachmentSynchronizerLog::PROCESS_STATUS_ATTACHMENTS_UPLOADED
        ]);
    }
}
