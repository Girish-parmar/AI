<footer class="bg-dark text-light-emphasis py-4 mt-auto">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <span class="text-white-50">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
        <ul class="nav">
            <li class="nav-item"><a class="nav-link px-2 text-white-50" href="{{ route('home') }}#features">Features</a></li>
            <li class="nav-item"><a class="nav-link px-2 text-white-50" href="{{ route('login') }}">Log in</a></li>
            <li class="nav-item"><a class="nav-link px-2 text-white-50" href="{{ route('register') }}">Sign up</a></li>
        </ul>
    </div>
</footer>
