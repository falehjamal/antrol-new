@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="antrol-auth-page">
    <div class="antrol-auth-card">
        <div class="antrol-auth-brand">
            <h1>Antrol MJKN</h1>
            <p>Webservice RS BPJS — Admin Panel</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success mb-3">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" placeholder="Username admin" autofocus required />
                @error('username')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="••••••••••••" required />
                @error('password')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember" />
                    <label class="form-check-label" for="remember_me">Ingat saya</label>
                </div>
            </div>
            <button type="submit" class="antrol-btn-primary">Masuk</button>
        </form>
    </div>
</div>
@endsection
