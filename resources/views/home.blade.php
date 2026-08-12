@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <section class="hero-section text-white text-center py-5">
        <div class="container py-5">
            <h1 class="display-4 fw-bold mb-3">Teach, sell, and manage content &mdash; all in one place</h1>
            <p class="lead col-lg-8 mx-auto mb-4">
                {{ config('app.name') }} is a marketplace for courses and scripts: creators publish and sell,
                learners subscribe and buy, and your team keeps everything approved, audited, and accounted for.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="{{ route('register') }}" class="btn btn-light btn-lg px-4">Get started free</a>
                <a href="#features" class="btn btn-outline-light btn-lg px-4">See what's included</a>
            </div>
        </div>
    </section>

    <section id="features" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Everything you need to run a content marketplace</h2>
                <p class="text-body-secondary col-lg-7 mx-auto">
                    From course creation to payouts, every step is covered &mdash; with approvals and audit trails
                    built in from day one.
                </p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5 fw-semibold">Courses &amp; scripts</h3>
                            <p class="card-text text-body-secondary">
                                Creators publish courses and scripts that go through a clear approval workflow
                                before they go live.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5 fw-semibold">Subscriptions &amp; purchases</h3>
                            <p class="card-text text-body-secondary">
                                Flexible subscription plans and one-off purchases, with a demo mode for
                                users who want to try before they buy.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5 fw-semibold">Approvals built in</h3>
                            <p class="card-text text-body-secondary">
                                Nothing reaches learners without sign-off &mdash; admins review and approve
                                every course, script, and ad.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5 fw-semibold">Audit &amp; compliance</h3>
                            <p class="card-text text-body-secondary">
                                Full activity logs and legal document tracking give your monitoring team
                                independent oversight.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5 fw-semibold">Finance &amp; payouts</h3>
                            <p class="card-text text-body-secondary">
                                Track revenue, reconcile transactions, and manage creator payouts from a
                                dedicated finance view.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <h3 class="h5 fw-semibold">Advertising</h3>
                            <p class="card-text text-body-secondary">
                                Sellers can submit ads for approval and promote their content across the
                                platform.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="roles" class="py-5 bg-body-tertiary">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Built for every role in your organization</h2>
                <p class="text-body-secondary col-lg-7 mx-auto">
                    Each account type sees exactly what it needs &mdash; nothing more.
                </p>
            </div>
            <div class="row g-4">
                @foreach ([
                    ['title' => 'Super Admin', 'desc' => 'Full control over the entire platform and every account.'],
                    ['title' => 'Admin', 'desc' => 'Manages courses, scripts, approvals, subscriptions, purchases, and users.'],
                    ['title' => 'Monitoring', 'desc' => 'Audit, legal, and advertising oversight with admin-level visibility.'],
                    ['title' => 'Creator', 'desc' => 'Publishes and sells courses and scripts, tracks earnings.'],
                    ['title' => 'User', 'desc' => 'Subscribes, purchases, and explores demo content.'],
                    ['title' => 'Accounts', 'desc' => 'Handles invoices, payouts, and revenue reconciliation.'],
                ] as $role)
                    <div class="col-sm-6 col-lg-4">
                        <div class="p-4 h-100 bg-white rounded-3 shadow-sm">
                            <h3 class="h6 fw-bold text-uppercase text-primary mb-2">{{ $role['title'] }}</h3>
                            <p class="mb-0 text-body-secondary">{{ $role['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container text-center py-4">
            <h2 class="fw-bold mb-3">Ready to get started?</h2>
            <p class="text-body-secondary mb-4">Create your account in less than a minute.</p>
            <a href="{{ route('register') }}" class="btn btn-primary btn-lg px-5">Sign up now</a>
        </div>
    </section>
@endsection
