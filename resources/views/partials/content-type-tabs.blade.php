<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link {{ $active === 'courses' ? 'active' : '' }}" href="{{ route("{$prefix}.courses.index") }}">
            Courses
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $active === 'scripts' ? 'active' : '' }}" href="{{ route("{$prefix}.scripts.index") }}">
            Scripts
        </a>
    </li>
</ul>
