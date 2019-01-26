@extends('layouts.app')

@section('content')
<section class="h-100">
    <div class="container h-100">
      <div class="row justify-content-center h-100">
        <div class="page-title">
          <h1 class="card-title">{{ __('Register') }}</h1>
          <h3 class="card-title">{{ __('Enter your information below to create an account') }}</h3>
        </div>
        <div class="form-wrapper">
          <form method="POST" id="register-form" action="{{ route('register') }}">
            @csrf

            <div class="form-group">
              <input id="username" type="text" class="form-control form-field{{ $errors->has('name') ? ' is-invalid' : '' }}" name="username" value="{{ old('username') }}" required>
              <label class="{{ old('username') ? 'active' : '' }}" for="username">{{ __('Username') }}</label>
            </div>

            <div class="form-group">
              <input id="email" type="email" class="form-control form-field{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required>
              <label class="{{ old('email') ? 'active' : '' }}" for="email">{{ __('Email') }}</label>
            </div>

            <div class="form-group">
              <input id="password" type="password" class="form-control form-field{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
              <label for="password">{{ __('Password') }}</label>
            </div>

            <div class="form-group">
              <input id="password_confirmation" type="password" class="form-control form-field{{ $errors->has('password_confirmation') ? ' is-invalid' : '' }}" name="password_confirmation" required>
              <label for="password_confirmation">{{ __('Repeat password') }}</label>
            </div>

            <div class="form-group form-options">
              <p class="status-message bad">{{ $errors->first() }}</p>
            </div>

            <div class="form-group">
              {!! app('captcha')->display() !!}
            </div>

            <div class="form-group no-margin">
              <button type="submit" class="btn btn-primary btn-block">{{ __('Register account') }}</button>
            </div>
            <div class="margin-top20 text-center">{{ __('Already have an account?') }} <a href="/login">{{ __('Sign in') }}</a></div>
          </form>
        </div>
      </div>
    </div>
</section>
@endsection
