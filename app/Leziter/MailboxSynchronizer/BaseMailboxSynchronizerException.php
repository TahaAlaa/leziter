<?php

namespace App\Leziter\MailboxSynchronizer;

use Throwable;

class BaseMailboxSynchronizerException extends \Exception
{
    private ProcessableEmail $processableEmail;

    public function __construct(ProcessableEmail $email, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->processableEmail = $email;
    }

    public function getProcessableEmail(): ProcessableEmail
    {
        return $this->processableEmail;
    }
}
