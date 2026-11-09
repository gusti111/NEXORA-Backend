<?php

namespace App\Notifications;

use App\Models\Logbook;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentLogbookSubmitted extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Logbook $logbook,
        public readonly bool $isResubmission = false,
    )
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event' => 'logbook_submitted',
            'title' => $this->isResubmission
                ? 'Logbook revisi menunggu pemeriksaan'
                : 'Logbook baru menunggu pemeriksaan',
            'message' => ($this->logbook->pkl?->mahasiswa?->name ?? 'Mahasiswa')
                .($this->isResubmission ? ' mengirim ulang logbook: ' : ' mengirim logbook: ')
                .$this->logbook->judul_kegiatan,
            'submission_type' => $this->isResubmission ? 'resubmission' : 'initial',
            'logbook_id' => $this->logbook->id,
            'status' => $this->logbook->status,
            'action_url' => '/dosen/logbooks/'.$this->logbook->id,
        ];
    }
}
