<?php

namespace App\Http\Controllers;

use App\Enums\LegalDocumentType;
use App\Models\LegalDocument;
use Illuminate\View\View;

class LegalDocumentController extends Controller
{
    /**
     * The latest published version of a legal document, by type. Guest
     * accessible — a Terms of Service nobody outside staff can read isn't
     * functional as a legal document.
     */
    public function show(string $type): View
    {
        $documentType = LegalDocumentType::tryFrom($type);

        abort_if($documentType === null, 404);

        $document = LegalDocument::where('type', $documentType)
            ->published()
            ->latest('published_at')
            ->firstOrFail();

        return view('legal.show', ['document' => $document]);
    }
}
