<?php

namespace App\Leziter\MailboxSynchronizer;

use App\Leziter\Facades\RestApiClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class AppBackendApi
{
    private int $remoteOrderId;
    private mixed $remoteInvoiceId;
    private mixed $remoteWarrantyId;
    private ProcessableEmail $email;

    public function __construct(private readonly RestApiClient $restApi){}

    public function setupFor(ProcessableEmail $email): void
    {
        $this->email = $email;
        $this->remoteOrderId = 0;
        $this->remoteInvoiceId = null;
        $this->remoteWarrantyId = null;
    }

    /**
     * @throws GuzzleException
     * @throws MailProcessFailedException
     */
    public function findOrderForMail(): void
    {
        $response = $this->restApi->post('/webshop/order/find', ['webshop_id' => $this->email->getOrderId()]);
        if ($response['status'] === 404) {
            throw new MailProcessFailedException(
                $this->email,
                "Order not found in Api {$this->email->getOrderId()} ({$this->email->getSubject()})"
            );
        }

        $this->remoteOrderId = (int)$response['data']['data']['id'];
    }

    public function getRemoteOrderId(): int
    {
        return $this->remoteOrderId;
    }

    /**
     * @throws GuzzleException
     * @throws MailProcessFailedException
     */
    public function uploadInvoice(): void
    {
        $response = $this->restApi->postFile('/documents/upload', $this->email->getInvoiceFile(), [
            'title' => basename($this->email->getInvoiceFile()),
            'type' => 'invoice',
            'public' => 0
        ]);
        if ($response['status'] !== 201) {
            throw new MailProcessFailedException(
                $this->email,
                "Failed to upload invoice file to remote ({$this->email->getSubject()})"
            );
        }

        $this->remoteInvoiceId = $response['data']['data']['id'];
    }

    public function getRemoteInvoiceId(): mixed
    {
        return $this->remoteInvoiceId;
    }

    /**
     * @throws GuzzleException
     * @throws MailProcessFailedException
     */
    public function uploadWarranty(): void
    {
        $response = $this->restApi->postFile('/documents/upload', $this->email->getWarrantyFile(), [
            'title' => basename($this->email->getWarrantyFile()),
            'type' => 'invoice',
            'public' => 0
        ]);
        if ($response['status'] !== 201) {
            throw new MailProcessFailedException(
                $this->email,
                "Failed to upload warranty file to remote ({$this->email->getSubject()})"
            );
        }

        $this->remoteWarrantyId = $response['data']['data']['id'];
    }

    public function getRemoteWarrantyId(): mixed
    {
        return $this->remoteWarrantyId;
    }

    /**
     * @throws GuzzleException
     * @throws MailProcessFailedException
     */
    public function attachInvoiceToRemoteOrder(): void
    {
        $response = $this->restApi->post(
            "/webshop/order/document/{$this->remoteOrderId}",
            ['document_id' => $this->remoteInvoiceId]
        );
        if ($response['status'] !== 200) {
            throw new MailProcessFailedException(
                $this->email,
                "Invoice document could not be attached to the order ({$this->email->getSubject()})"
            );
        }
    }

    /**
     * @throws GuzzleException
     * @throws MailProcessFailedException
     */
    public function attachWarrantyToRemoteOrder(): void
    {
        $response = $this->restApi->post(
            "/webshop/order/document/{$this->remoteOrderId}",
            ['document_id' => $this->remoteWarrantyId]
        );
        if ($response['status'] !== 200) {
            throw new MailProcessFailedException($this->email, "Warranty document could not be attached to the order");
        }
    }

    /**
     * @throws GuzzleException
     * @throws MailProcessFailedException
     */
    public function uploadWarrantyInformation(): void
    {
        $items = $this->email->getWarrantyInformation($this->remoteWarrantyId);
        $response = $this->restApi->post(
            "/webshop/order/warranty/{$this->remoteOrderId}",
            ['warranty_items' => $items]
        );

        if ($response['status'] !== 200) {
            Log::error("Failed to upload warranty information", [
                'email' => $this->email->getOrderId(),
                'response' => $response,
            ]);
            throw new MailProcessFailedException(
                $this->email,
                "Failed to upload warranty information ({$this->email->getOrderId()}): {$response['status']}"
            );
        }
    }
}
