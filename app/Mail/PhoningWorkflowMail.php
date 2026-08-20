<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhoningWorkflowMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $mailSubject,
        public string $mailBody,
        public ?string $pdfPath = null,
        public ?string $audioPath = null,
        public ?string $signatureName = null,
        public ?string $signaturePhone = null,
        public ?string $signatureEmail = null,
        public array $externalPaths = [],
    ) {
        $this->subject($mailSubject);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.phoning-workflow',
            with: [
                'corps' => $this->mailBody,
                'sujet' => $this->mailSubject,
                'signature_name' => $this->signatureName,
                'signature_phone' => $this->signaturePhone,
                'signature_email' => $this->signatureEmail,
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];
        if ($this->pdfPath && is_file($this->pdfPath)) {
            $attachments[] = Attachment::fromPath($this->pdfPath)->withMime('application/pdf');
        }
        if ($this->audioPath) {
            try {
                if (is_file($this->audioPath)) {
                    $attachments[] = Attachment::fromPath($this->audioPath);
                } elseif (filter_var($this->audioPath, FILTER_VALIDATE_URL)) {
                    $response = Http::timeout(20)->get($this->audioPath);
                    if ($response->successful()) {
                        $extension = pathinfo(parse_url($this->audioPath, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) ?: 'mp3';
                        $mime = $response->header('Content-Type') ?: 'audio/mpeg';
                        $attachments[] = Attachment::fromData(fn () => $response->body(), "enregistrement-appel.{$extension}")->withMime($mime);
                    } else {
                        Log::warning('PhoningWorkflowMail: audio Ringover indisponible', ['status' => $response->status()]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('PhoningWorkflowMail: échec téléchargement audio', ['message' => $e->getMessage()]);
            }
        }
        foreach ($this->externalPaths as $externalPath) {
            if (is_string($externalPath) && is_file($externalPath)) {
                $attachments[] = Attachment::fromPath($externalPath);
            }
        }
        return $attachments;
    }
}
