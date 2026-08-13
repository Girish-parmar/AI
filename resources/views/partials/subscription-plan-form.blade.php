@php
    $plan ??= null;
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method === 'PUT')
        @method('PUT')
    @endif

    <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input
            id="name"
            type="text"
            name="name"
            class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $plan->name ?? '') }}"
            required
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="slug" class="form-label">Slug</label>
        <input
            id="slug"
            type="text"
            name="slug"
            class="form-control @error('slug') is-invalid @enderror"
            value="{{ old('slug', $plan->slug ?? '') }}"
            placeholder="e.g. pro-monthly"
            required
        >
        @error('slug')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Description <span class="text-body-secondary">(optional)</span></label>
        <textarea
            id="description"
            name="description"
            rows="3"
            class="form-control @error('description') is-invalid @enderror"
        >{{ old('description', $plan->description ?? '') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="row mb-3">
        <div class="col">
            <label for="price" class="form-label">Price</label>
            <input
                id="price"
                type="number"
                step="0.01"
                min="0"
                name="price"
                class="form-control @error('price') is-invalid @enderror"
                value="{{ old('price', $plan->price ?? '') }}"
                required
            >
            @error('price')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col">
            <label for="billing_interval" class="form-label">Billing interval</label>
            <select id="billing_interval" name="billing_interval" class="form-select @error('billing_interval') is-invalid @enderror" required>
                @foreach (\App\Enums\BillingInterval::cases() as $interval)
                    <option value="{{ $interval->value }}" @selected(old('billing_interval', $plan->billing_interval?->value ?? '') === $interval->value)>{{ $interval->label() }}</option>
                @endforeach
            </select>
            @error('billing_interval')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="mb-3" style="max-width: 12rem;">
        <label for="trial_days" class="form-label">Free trial <span class="text-body-secondary">(days)</span></label>
        <input
            id="trial_days"
            type="number"
            step="1"
            min="0"
            max="365"
            name="trial_days"
            class="form-control @error('trial_days') is-invalid @enderror"
            value="{{ old('trial_days', $plan->trial_days ?? 0) }}"
            required
        >
        <div class="form-text">0 for no trial. Subscribers get full access for this many days before any charge is created.</div>
        @error('trial_days')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-check mb-4">
        <input
            id="is_active"
            type="checkbox"
            name="is_active"
            value="1"
            class="form-check-input"
            @checked(old('is_active', $plan->is_active ?? true))
        >
        <label for="is_active" class="form-check-label">Active (visible to users and subscribable)</label>
    </div>

    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">Cancel</a>
</form>
