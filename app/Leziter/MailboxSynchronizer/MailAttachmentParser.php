<?php

namespace App\Leziter\MailboxSynchronizer;

use App\Leziter\PdfParsing\PdfParser;
use App\Leziter\PdfParsing\WarrantyPdfParser;
use Exception;

class MailAttachmentParser
{
    const TYPE_INVOICE = 'invoice';
    const TYPE_WARRANTY = 'warranty';
    private string $type;
    private string $content;

    public function __construct(readonly mixed $attachment){}

    /**
     * @throws Exception
     */
    public function parse(): void
    {
        $parser = new PdfParser();
        $this->content = $parser->getText($this->attachment);
        $this->type = str_contains($this->content, 'Számla') ? self::TYPE_INVOICE : self::TYPE_WARRANTY;
    }

    public function isInvoice(): bool
    {
        return $this->type === self::TYPE_INVOICE;
    }

    /**
     * @throws Exception
     */
    public function parseWarrantyInformation(): array
    {
        $parser = new WarrantyPdfParser();
        return $parser->parse($this->attachment);
    }
}
