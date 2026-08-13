<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use Illuminate\View\View;

class LegalDocumentController extends Controller
{
    public function index(): View
    {
        $documents = LegalDocument::with('author')->orderBy('type')->latest('version')->get();

        return view('accounts.legal-documents.index', ['documents' => $documents]);
    }
}
