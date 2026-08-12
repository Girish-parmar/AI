@php
    $content ??= null;
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
            value="{{ old('title', $content->title ?? '') }}"
            required
        >
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="category" class="form-label">Category</label>
        <input
            id="category"
            type="text"
            name="category"
            class="form-control @error('category') is-invalid @enderror"
            value="{{ old('category', $content->category ?? '') }}"
            required
        >
        @error('category')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="price" class="form-label">Price (USD)</label>
        <input
            id="price"
            type="number"
            step="0.01"
            min="0"
            name="price"
            class="form-control @error('price') is-invalid @enderror"
            value="{{ old('price', $content->price ?? '0.00') }}"
            required
        >
        @error('price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="description" class="form-label">Description</label>
        <textarea
            id="description"
            name="description"
            rows="6"
            class="form-control @error('description') is-invalid @enderror"
            required
        >{{ old('description', $content->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">Cancel</a>
</form>
