@extends('layouts.app')
@section('content')
<div class="my-2">
  <h1 class="my-2">Login</h1>
  <h4>Enter the code from your authenticator app</h4>
</div>
<form class="w-100 flex flex-column justify-content-center align-self-center" method="POST" action="{{ route('2fa-login') }}">
  @csrf
  <at-alert message="{{ $errors->first() }}" class="{{ $errors->all() ? '' : 'd-none' }}" type="error"></at-alert>
  <at-input name="one_time_password" placeholder="Authenticator code" status="{{ $errors->all() ? 'error' : '' }}" type="text" size="large" class="my-2" required autofocus></at-input>
  <div class="my-2 mx-0 row">
    <input type="submit" style="display: none;"></input>
    <at-button type="primary" onclick="this.form.submit();">Log in</at-button>
  </div>
</form>
@endsection

@section('illustration')
<img height="250px" src="{{ asset('icons/auth.svg') }}">
@endsection
