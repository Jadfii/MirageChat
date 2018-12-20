@extends('layouts.app')

@section('content')
<section class="h-100">
  <div class="container h-100">
    <div class="row justify-content-center h-100">
      <div class="page-title">
        <h1 class="card-title">{{ __('Forgot password?') }}</h1>
        <h3 class="card-title">{{ __('Request to reset your password below') }}</h3>
      </div>
      <div class="form-wrapper">
        @if (session('status'))
          <div class="margin-top20 alert alert-success status-alert fade show" role="alert">
             <span class="status-message">{{ session('status') }}</span>
          </div>
        @endif

        <form method="POST" id="email_password-form" action="{{ route('password.email') }}">
          @csrf

          <div class="form-group">
            <input id="email" type="text" class="form-control form-field{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="" required autofocus>
            <label for="email">{{ __('Email') }}</label>
          </div>

          <div class="form-group form-options">
            <p class="status-message bad">{{ $errors->first() }}</p>
          </div>

          <div class="form-group no-margin">
             <button type="submit" class="btn btn-primary btn-block">{{ __('Send password reset link') }}</button>
          </div>

          <div class="margin-top20 text-center"><a href="/">{{ __('Go back') }}</a></div>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection
