<nav class="navbar navbar-expand-md navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ route('home') }}">{{ config('app.name') }}</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-md-center gap-md-2">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('home') }}#features">Features</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('home') }}#roles">Who it's for</a>
                </li>

                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Log in</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-md-2" href="{{ route('register') }}">Sign up</a>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}" class="ms-md-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-light">Log out</button>
                        </form>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>
