<?php

namespace App\Leziter\MailboxSynchronizer;

use Ddeboer\Imap\Message\Attachment;
use Ddeboer\Imap\Message\AttachmentInterface;
use Illuminate\Support\Facades\File;

class ProcessableEmail
{
    private int $id;
    private ?string $content;
    /**
     * @var array|Attachment[]|AttachmentInterface[]
     */
    private array $attachments;
    private ?string $subject;
    private ?string $orderId;
    private array $warrantyInformation;
    private string $invoiceFile;
    private string $warrantyFile;

    public function __construct(\Ddeboer\Imap\Message $message)
    {
        $this->id = $message->getNumber();
        $this->content = $message->getBodyHtml() ?? $message->getBodyText();
        $this->attachments = $message->getAttachments() ?? [];
        $this->subject = $message->getSubject();
        $this->orderId = (new MailContentParser())->getOrderID($this->content);
        $this->warrantyInformation = [];
        $this->warrantyFile = '';
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function doesNotContainOrderId(): bool
    {
        return is_null($this->orderId);
    }

    public function getWarrantyInformation(int $documentId): array
    {
        return array_map(function($item) use ($documentId) {
            $item['document_id'] = $documentId;
            return $item;
        }, $this->warrantyInformation);
    }

    public function getInvoiceFile(): string
    {
        return $this->invoiceFile;
    }

    public function getWarrantyFile(): string
    {
        return $this->warrantyFile;
    }

    public function downloadAttachments(): void
    {
        $targetDir = $this->createAttachmentDirectory();
        foreach ($this->attachments as $attachment) {
            file_put_contents($targetDir . '/' . $attachment->getFilename(), $attachment->getDecodedContent());
        }
    }

    /**
     * @throws InsufficientAttachmentsCountException
     */
    public function processAttachments(): void
    {
        if (count($this->attachments) < 2) {
            throw new InsufficientAttachmentsCountException($this, 'Mail has insufficient attachments');
        }

        $pdfCount = 0;
        $downloadedAttachments = File::files(storage_path("app/attachments/{$this->id}"));
        foreach ($downloadedAttachments as $attachment) {
            if ($attachment->getExtension() === 'pdf') $pdfCount++;
        }

        if ($pdfCount < 2) {
            throw new InsufficientAttachmentsCountException($this, 'Mail has insufficient PDF attachments');
        }

        foreach ($downloadedAttachments as $attachment) {
            if ($attachment->getExtension() !== 'pdf') continue;

            $attachmentParser = new MailAttachmentParser($attachment->getRealPath());
            $attachmentParser->parse();

            if ($attachmentParser->isInvoice()) {
                File::move($attachment->getRealPath(), $attachment->getPath() . "/{$this->orderId}_invoice.pdf");
                $this->invoiceFile = $attachment->getPath() . "/{$this->orderId}_invoice.pdf";
            } else {
                $this->warrantyFile = $attachment->getPath() . "/{$this->orderId}_warranty.pdf";
                $this->warrantyInformation = $attachmentParser->parseWarrantyInformation();
                File::move($attachment->getRealPath(), $attachment->getPath() . "/{$this->orderId}_warranty.pdf");
            }
        }
    }

    private function createAttachmentDirectory(): string
    {
        $targetDir = config('leziter.mail_sync.local_attachments_path') . '/' . $this->id;
        if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
        return $targetDir;
    }

    public function deleteDownloadedAttachments(): void
    {
        $dir = config('leziter.mail_sync.local_attachments_path') . '/' . $this->id;
        File::deleteDirectory($dir);
    }

}
