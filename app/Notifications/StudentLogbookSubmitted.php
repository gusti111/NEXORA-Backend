<?php

namespace App\Notifications;

use App\Models\Logbook;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentLogbookSubmitted extends Notification
{
    use Queueable;

    public function __construct(public readonly Logbook $logbook)
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
            'title' => 'Logbook baru menunggu pemeriksaan',
            'message' => ($this->logbook->pkl?->mahasiswa?->name ?? 'Mahasiswa')
                .' mengirim logbook: '.$this->logbook->judul_kegiatan,
            'logbook_id' => $this->logbook->id,
            'status' => $this->logbook->status,
            'action_url' => '/dosen/logbooks/'.$this->logbook->id,
        ];
    }
}
