@extends('layouts.dashboard')

@section('title', 'User Management')

@section('content')
    <h1 class="h3 fw-bold mb-4">User Management <span class="badge text-bg-light text-body-secondary">View only</span></h1>

    <div class="table-responsive">
        <table class="table align-middle bg-white">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td><span class="badge text-bg-light text-body-secondary">{{ $user->role->label() }}</span></td>
                        <td>{{ $user->created_at->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
