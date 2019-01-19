@extends('layouts.app')

@section('content')
<section class="h-100">
  <div class="container h-100">
    <div class="row justify-content-center h-100">
      <div class="page-title">
        <h1 class="card-title">{{ __('Login') }}</h1>
        <h3 class="card-title">{{ __('Enter the code from your authenticator app') }}</h3>
      </div>
      <div class="form-wrapper">
        <form method="POST" action="{{ route('2fa-login') }}">
          @csrf

          <div class="form-group">
            <input id="one_time_password" type="text" class="form-control form-field{{ $errors->all() ? ' is-invalid' : '' }}" name="one_time_password" required autofocus>
            <label for="one_time_password">Authenticator code</label>
          </div>

          <div class="form-group form-options">
            <p class="status-message bad">{{ $errors->first() }}</p>
          </div>

          <div class="form-group no-margin">
            <button type="submit" class="btn btn-primary btn-block">{{ __('Log in') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
@endsection
