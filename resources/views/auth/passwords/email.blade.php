@extends('layouts.app')
@section('content')
<div class="my-2">
  <h1 class="my-2">Forgot password?</h1>
  <h4>Request to reset your password below</h4>
</div>
<form method="POST" action="{{ route('password.email') }}">
  @csrf
  <at-alert message="{{ $errors->first() }}" class="{{ $errors->all() ? '' : 'd-none' }}" type="error"></at-alert>
  <at-input name="email" placeholder="Email" status="{{ $errors->has('email') ? 'error' : '' }}" type="email" size="large" class="my-2" required autofocus></at-input>
  <div class="my-2 mx-0 row">
    <input type="submit" style="display: none;"></input>
    <at-button type="primary" onclick="this.form.submit();">Send password reset link</at-button>
  </div>
  <div class="my-2"><a href="/">Go back</a></div>
</form>
@endsection

@section('illustration')
<img height="250px" src="{{ asset('icons/forgot_password.svg') }}">
@endsection
