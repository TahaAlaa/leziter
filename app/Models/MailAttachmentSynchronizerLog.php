<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailAttachmentSynchronizerLog extends Model
{
    use HasFactory;

    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_ERROR = 'error';
    const STATUS_PROCESSED = 'processed';
    const PROCESS_STATUS_ATTACHMENTS_DOWNLOADED = 'attachments_downloaded';
    const PROCESS_STATUS_ATTACHMENTS_PROCESSED = 'attachments_processed';
    const PROCESS_STATUS_ATTACHMENTS_UPLOADED = 'attachments_uploaded';
    const PROCESS_STATUS_ATTACHMENTS_ATTACHED_TO_ORDER = 'attachments_connected';
    const PROCESS_STATUS_WARRANTY_INFO_UPLOADED = 'warranty_info_uploaded';
    const PROCESS_STATUS_NOT_STARTED_YET = 'not_started_yet';

    protected $table = 'mail_attachment_synchronizer_log';
    protected $guarded = ['id'];
}
