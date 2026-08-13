<?php

namespace App\Http\Requests\LegalDocument;

class UpdateLegalDocumentRequest extends StoreLegalDocumentRequest
{
    /**
     * Same fields as creation, minus `type`: it's set once at creation and
     * not editable afterward — a document's legal category shouldn't be
     * silently reassignable from an edit form.
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['type']);

        return $rules;
    }
}
