<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use Illuminate\View\View;

class LegalDocumentController extends Controller
{
    public function index(): View
    {
        $documents = LegalDocument::with('author')->orderBy('type')->latest('version')->get();

        return view('admin.legal-documents.index', ['documents' => $documents]);
    }
}
