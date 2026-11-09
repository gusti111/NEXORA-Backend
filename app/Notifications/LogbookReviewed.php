<?php

namespace App\Notifications;

use App\Models\Logbook;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LogbookReviewed extends Notification
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
        $statusLabels = [
            'approved' => 'disetujui',
            'revision_requested' => 'memerlukan revisi',
            'rejected' => 'ditolak',
        ];

        $status = $statusLabels[$this->logbook->status] ?? $this->logbook->status;

        return [
            'event' => 'logbook_reviewed',
            'title' => 'Logbook telah diperiksa',
            'message' => 'Logbook "'.$this->logbook->judul_kegiatan.'" '.$status.'.',
            'logbook_id' => $this->logbook->id,
            'status' => $this->logbook->status,
            'comment' => $this->logbook->catatan_dosen,
            'action_url' => '/logbooks/'.$this->logbook->id,
        ];
    }
}
