@php
    $document ??= null;
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    <div class="mb-3">
        <label for="type" class="form-label">Type</label>
        @if ($document)
            <input type="text" class="form-control" value="{{ $document->type->label() }}" disabled>
            <div class="form-text">Type can't be changed after a document is created — make a new document instead.</div>
        @else
            <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                <option value="" disabled selected>Choose a type&hellip;</option>
                @foreach (\App\Enums\LegalDocumentType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            @error('type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        @endif
    </div>

    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input
            id="title"
            type="text"
            name="title"
            class="form-control @error('title') is-invalid @enderror"
            value="{{ old('title', $document->title ?? '') }}"
            required
        >
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="version" class="form-label">Version</label>
        <input
            id="version"
            type="text"
            name="version"
            class="form-control @error('version') is-invalid @enderror"
            value="{{ old('version', $document->version ?? '') }}"
            placeholder="e.g. 1.0"
            style="max-width: 10rem;"
            required
        >
        @error('version')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="content" class="form-label">Content</label>
        <textarea
            id="content"
            name="content"
            rows="14"
            class="form-control @error('content') is-invalid @enderror"
            required
        >{{ old('content', $document->content ?? '') }}</textarea>
        @error('content')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">Cancel</a>
</form>
