@extends('layouts.app')
@section('title', $register ? 'Create your account' : 'Welcome back')
@section('content')
<div class="auth-grid">
    <section class="auth-intro"><span class="eyebrow">YOUR NEXT CHAPTER STARTS HERE</span><h1>A clearer path to<br>your education.</h1><p>Discover scholarships, understand the requirements, and find funding for the programme you have in mind.</p><div class="trust-note">✓ Clear criteria &nbsp; ✓ Explained decisions &nbsp; ✓ Official sources</div></section>
    <section class="panel auth-panel"><span class="eyebrow">SCHOLARMATCH</span><h2>{{ $register ? 'Create your student account' : 'Welcome back' }}</h2><p class="muted">{{ $register ? 'Keep your qualifications and opportunities in one place.' : 'Sign in to explore your scholarship opportunities.' }}</p>
        <form method="POST" action="{{ route($register ? 'register' : 'login') }}">@csrf
            @if($register)<x-input name="name" label="Full name" :value="old('name')" required autocomplete="name" />@endif
            <x-input name="email" label="Email address" type="email" :value="old('email')" required autocomplete="email" />
            <x-input name="password" label="Password" type="password" required :autocomplete="$register ? 'new-password' : 'current-password'" />
            @if($register)<p class="help">At least 10 characters.</p><x-input name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />@endif
            <button class="btn btn-primary w-100 mt-3">{{ $register ? 'Create account' : 'Sign in' }} →</button>
        </form><p class="mt-4 muted">{{ $register ? 'Already have an account?' : 'New to ScholarMatch?' }} <a href="{{ route($register ? 'login' : 'register') }}">{{ $register ? 'Sign in' : 'Create an account' }}</a></p>
    </section>
</div>
@endsection
