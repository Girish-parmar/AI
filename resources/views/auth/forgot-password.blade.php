@extends('layouts.app')

@section('title', 'Forgot password')

@section('content')
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 auth-card">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h3 fw-bold mb-1 text-center">Forgot your password?</h1>
                        <p class="text-body-secondary text-center mb-4">
                            Enter your email and we'll send you a link to reset it.
                        </p>

                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}" novalidate>
                            @csrf

                            <div class="mb-4">
                                <label for="email" class="form-label">Email address</label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}"
                                    required
                                    autofocus
                                    autocomplete="username"
                                >
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">Send reset link</button>

                            <p class="text-center text-body-secondary mb-0">
                                <a href="{{ route('login') }}">Back to log in</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
