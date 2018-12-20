@extends('layouts.app')

@section('content')
<section class="h-100">
  <div class="container h-100">
    <div class="row justify-content-center h-100">
      <div class="page-title">
        <h1 class="card-title">{{ __('Reset password') }}</h1>
        <h3 class="card-title">{{ __('Enter your new password') }}</h3>
      </div>
      <div class="form-wrapper">
        <form method="POST" id="reset_password-form" action="{{ route('password.update') }}">
          @csrf

          <input type="hidden" name="token" value="{{ $token }}">

          <div class="form-group">
            <input id="email" type="text" class="form-control form-field{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ $email ?? old('email') }}" readonly>
            <label for="email">{{ __('Email') }}</label>
          </div>

          <div class="form-group">
             <input id="password" type="password" class="form-control form-field{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password" required>
             <label for="password">{{ __('Enter new password') }}</label>
          </div>

          <div class="form-group">
             <input id="password_confirmation" type="password" class="form-control form-field{{ $errors->has('password') ? ' is-invalid' : '' }}" name="password_confirmation" required>
             <label for="password_confirmation">{{ __('Confirm new password') }}</label>
          </div>

          <div class="form-group form-options">
            <p class="status-message bad">{{ $errors->first() }}</p>
          </div>

          <div class="form-group no-margin">
             <button type="submit" class="btn btn-primary btn-block">{{ __('Change password') }}</button>
          </div>

          <div class="margin-top20 text-center"><a href="/">{{ __('Go back') }}</a></div>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection
