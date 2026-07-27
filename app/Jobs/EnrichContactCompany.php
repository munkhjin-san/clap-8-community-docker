<?php

namespace App\Jobs;

use App\Models\ContactRecord;
use App\Services\ContactEnrichmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EnrichContactCompany implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public function __construct(public int $contactId) {}

    public function handle(ContactEnrichmentService $service): void
    {
        $contact = ContactRecord::find($this->contactId);
        if (!$contact) {
            return;
        }

        // Never overwrite company info a user has already added/edited.
        if (!empty($contact->data)) {
            $contact->update(['enrichment_status' => 'completed']);
            return;
        }

        try {
            $service->enrich($contact);
            $contact->refresh();
            // enrich() saves company data onto the record when it finds anything.
            $contact->update([
                'enrichment_status' => empty($contact->data) ? 'failed' : 'completed',
            ]);
        } catch (\Throwable $e) {
            report($e);
            $contact->update(['enrichment_status' => 'failed']);
        }
    }
}
