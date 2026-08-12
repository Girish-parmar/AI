@extends('layouts.app')

@section('title', 'Sign up')

@section('content')
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 auth-card">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h3 fw-bold mb-1 text-center">Create your account</h1>
                        <p class="text-body-secondary text-center mb-4">Start browsing and purchasing content</p>

                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('register') }}" novalidate>
                            @csrf

                            <div class="mb-3">
                                <label for="name" class="form-label">Full name</label>
                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}"
                                    required
                                    autofocus
                                    autocomplete="name"
                                >
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}"
                                    required
                                    autocomplete="username"
                                >
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    required
                                    autocomplete="new-password"
                                >
                                <div class="form-text">At least 8 characters.</div>
                            </div>

                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">Confirm password</label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    class="form-control"
                                    required
                                    autocomplete="new-password"
                                >
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3">Create account</button>

                            <p class="text-center text-body-secondary mb-0">
                                Already have an account?
                                <a href="{{ route('login') }}">Log in</a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
