@php
    $advertisement ??= null;
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input
            id="title"
            type="text"
            name="title"
            class="form-control @error('title') is-invalid @enderror"
            value="{{ old('title', $advertisement->title ?? '') }}"
            required
        >
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="target_url" class="form-label">Target URL</label>
        <input
            id="target_url"
            type="url"
            name="target_url"
            class="form-control @error('target_url') is-invalid @enderror"
            value="{{ old('target_url', $advertisement->target_url ?? '') }}"
            placeholder="https://example.com"
            required
        >
        @error('target_url')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="banner_path" class="form-label">Banner image URL <span class="text-body-secondary">(optional)</span></label>
        <input
            id="banner_path"
            type="url"
            name="banner_path"
            class="form-control @error('banner_path') is-invalid @enderror"
            value="{{ old('banner_path', $advertisement->banner_path ?? '') }}"
            placeholder="https://example.com/banner.jpg"
        >
        @error('banner_path')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="row mb-4">
        <div class="col">
            <label for="starts_at" class="form-label">Starts <span class="text-body-secondary">(optional)</span></label>
            <input
                id="starts_at"
                type="datetime-local"
                name="starts_at"
                class="form-control @error('starts_at') is-invalid @enderror"
                value="{{ old('starts_at', optional($advertisement->starts_at ?? null)->format('Y-m-d\TH:i')) }}"
            >
            @error('starts_at')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col">
            <label for="ends_at" class="form-label">Ends <span class="text-body-secondary">(optional)</span></label>
            <input
                id="ends_at"
                type="datetime-local"
                name="ends_at"
                class="form-control @error('ends_at') is-invalid @enderror"
                value="{{ old('ends_at', optional($advertisement->ends_at ?? null)->format('Y-m-d\TH:i')) }}"
            >
            @error('ends_at')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-text">Leave both blank to run indefinitely once approved.</div>
    </div>

    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">Cancel</a>
</form>
