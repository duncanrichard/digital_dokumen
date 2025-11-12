@extends('layouts/blankLayout')

@section('title', 'Login')

@section('page-style')
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">
@endsection

@section('content')
<div class="position-relative">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-4">

      <div class="card p-2">
        <div class="app-brand justify-content-center mt-5">
          <a href="{{ route('login') }}" class="app-brand-link gap-2">
            <span class="app-brand-logo demo">@include('_partials.macros',["height"=>20,"withbg"=>'fill: #fff;'])</span>
            <span class="app-brand-text demo text-heading fw-semibold">{{ config('variables.templateName') }}</span>
          </a>
        </div>

        <div class="card-body mt-2">
          <h4 class="mb-2">Welcome! 👋</h4>
          <p class="mb-4">Please sign in to continue.</p>

          @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
          @endif

          <form id="formAuthentication" class="mb-3" action="{{ route('login.perform') }}" method="POST" novalidate>
            @csrf

            <div class="form-floating form-floating-outline mb-3">
              <input type="text" class="form-control @error('username') is-invalid @enderror"
                     id="username" name="username" value="{{ old('username') }}"
                     placeholder="Enter your username" autofocus required>
              <label for="username">Username</label>
              @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
              <div class="form-password-toggle">
                <div class="input-group input-group-merge">
                  <div class="form-floating form-floating-outline">
                    <input type="password" id="password" class="form-control @error('password') is-invalid @enderror"
                           name="password" placeholder="••••••••" required />
                    <label for="password">Password</label>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                  </div>
                  <span class="input-group-text cursor-pointer"><i class="mdi mdi-eye-off-outline"></i></span>
                </div>
              </div>
            </div>

            <div class="mb-3 d-flex justify-content-between align-items-center">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                <label class="form-check-label" for="remember">Remember Me</label>
              </div>
              {{-- optional link forgot password --}}
              {{-- <a href="{{ url('auth/forgot-password-basic') }}"><span>Forgot Password?</span></a> --}}
            </div>

            <div class="mb-3">
              <button class="btn btn-primary d-grid w-100" type="submit">Sign in</button>
            </div>
          </form>

          {{-- Optional register link --}}
          {{-- <p class="text-center">
            <span>New here?</span>
            <a href="{{ url('auth/register-basic') }}"><span>Create an account</span></a>
          </p> --}}
        </div>
      </div>

      <img src="{{ asset('assets/img/illustrations/tree-3.png') }}" alt="auth-tree" class="authentication-image-object-left d-none d-lg-block">
      <img src="{{ asset('assets/img/illustrations/auth-basic-mask-light.png') }}" class="authentication-image d-none d-lg-block" alt="triangle-bg">
      <img src="{{ asset('assets/img/illustrations/tree.png') }}" alt="auth-tree" class="authentication-image-object-right d-none d-lg-block">
    </div>
  </div>
</div>
@endsection
