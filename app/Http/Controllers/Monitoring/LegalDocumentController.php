<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Http\Requests\LegalDocument\StoreLegalDocumentRequest;
use App\Http\Requests\LegalDocument\UpdateLegalDocumentRequest;
use App\Models\LegalDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LegalDocumentController extends Controller
{
    public function index(): View
    {
        $documents = LegalDocument::with('author')->orderBy('type')->latest('version')->get();

        return view('monitoring.legal-documents.index', ['documents' => $documents]);
    }

    public function create(): View
    {
        return view('monitoring.legal-documents.create');
    }

    public function store(StoreLegalDocumentRequest $request): RedirectResponse
    {
        LegalDocument::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('monitoring.legal-documents.index')->with('status', 'Document created as a draft.');
    }

    public function edit(LegalDocument $legalDocument): View
    {
        return view('monitoring.legal-documents.edit', ['document' => $legalDocument]);
    }

    public function update(UpdateLegalDocumentRequest $request, LegalDocument $legalDocument): RedirectResponse
    {
        $legalDocument->update($request->validated());

        return redirect()->route('monitoring.legal-documents.index')->with('status', 'Document updated.');
    }

    public function destroy(LegalDocument $legalDocument): RedirectResponse
    {
        abort_if($legalDocument->published_at !== null, 422, 'Unpublish this document before deleting it.');

        $legalDocument->delete();

        return redirect()->route('monitoring.legal-documents.index')->with('status', 'Document deleted.');
    }

    public function publish(LegalDocument $legalDocument): RedirectResponse
    {
        abort_if($legalDocument->published_at !== null, 422, 'This document is already published.');

        $legalDocument->update(['published_at' => now()]);

        return back()->with('status', "\"{$legalDocument->title}\" published.");
    }

    public function unpublish(LegalDocument $legalDocument): RedirectResponse
    {
        abort_if($legalDocument->published_at === null, 422, 'This document is not published.');

        $legalDocument->update(['published_at' => null]);

        return back()->with('status', "\"{$legalDocument->title}\" unpublished.");
    }
}
