@extends('layouts.app')
@section('content')
<div class="container-fluid h-100">
  <div class="row justify-content-center h-100 w-100">
    <div class="col col-md-12">
      <div class="w-100 px-8">
        <div class="page-title">
          <h1 class="card-title">{{ __('Login') }}</h1>
          <h3 class="card-title">{{ __('Sign in by entering your information below') }}</h3>
        </div>
        <form method="POST" id="login-form" action="{{ route('login') }}">
          @csrf

          <at-input name="email" type="email" placeholder="Email" class="{{ $errors->all() ? ' is-invalid' : '' }}" required autofocus></at-input>

          <at-input name="password" type="password" placeholder="Password" class="{{ $errors->all() ? ' is-invalid' : '' }}" required></at-input>

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
    <div class="col col-md-12">
      Login
    </div>
  </div>
</div>
@endsection
