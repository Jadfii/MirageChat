@extends('layouts.app')
@section('content')
<div class="my-2">
  <at-alert message="{{ session('resent') ? '' : 'A fresh verification link has been sent to your email address.' }}" class="{{ session('resent') ? '' : 'd-none' }}" type="error"></at-alert>
  <h1 class="my-2">Verify Your Email Address</h1>
  <h4 class="my-1">Before proceeding, please check your email for a verification link.</h4>
  <h4 class="my-1">If you did not receive the email, <a href="{{ route('verification.resend') }}">click here to request another</a></h4>
</div>
@endsection

@section('illustration')
<img height="250px" src="{{ asset('icons/verify_email.svg') }}">
@endsection
