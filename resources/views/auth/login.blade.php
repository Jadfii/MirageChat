@extends('layouts.app')

@section('content')
<section class="h-100">
  <div class="container h-100">
    <div class="row justify-content-center h-100">
      <div class="page-title">
        <h1 class="card-title">{{ __('Login') }}</h1>
        <h3 class="card-title">{{ __('Sign in by entering your information below') }}</h3>
      </div>
      <div class="form-wrapper">
        <form method="POST" id="login-form" action="{{ route('login') }}">
          @csrf

          <div class="form-group">
            <input id="email" type="email" class="form-control form-field{{ $errors->all() ? ' is-invalid' : '' }}" name="email" required autofocus>
            <label for="email">{{ __('Email') }}</label>
          </div>

          <div class="form-group">
            <input id="password" type="password" class="form-control form-field{{ $errors->all() ? ' is-invalid' : '' }}" name="password" required>
            <label for="password">{{ __('Password') }}</label>
          </div>

          <div class="form-group form-options">
            <p class="status-message bad">{{ $errors->first() }}</p>
            <a href="{{ route('password.request') }}" class="ml-auto">{{ __('Forgot Your Password?') }}</a>
          </div>

          <div class="form-group no-margin">
            <button type="submit" class="btn btn-primary btn-block">{{ __('Log in to your account') }}</button>
          </div>
          <div class="margin-top20 text-center">{{ __("Don't have an account?") }} <a href="/register">{{ __('Sign up') }}</a></div>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection
