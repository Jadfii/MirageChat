@extends('layouts.app')

@section('content')
<section class="h-100">
  <div class="container h-100">
    <div class="row justify-content-center h-100">
      <div class="page-title">
        <h1 class="card-title">{{ __('Verify Your Email Address') }}</h1>
      </div>
      <div class="page-text">
        @if (session('resent'))
          <div class="margin-top20 alert alert-success status-alert fade show" role="alert">
             <span class="status-message">{{ __('A fresh verification link has been sent to your email address.') }}</span>
          </div>
        @endif
        {{ __('Before proceeding, please check your email for a verification link.') }}
        {{ __('If you did not receive the email') }}, <a href="{{ route('verification.resend') }}">{{ __('click here to request another') }}</a>.
      </div>
    </div>
  </div>
</section>
@endsection
