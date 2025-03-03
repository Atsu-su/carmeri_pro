@extends('layouts.base')
@section('title', 'アカウント有効化')
@section('header')
  @include('components.header')
@endsection
@section('content')
  <div class="c-default-form" id="register">
    <h1 class="title">パスワード変更</h1>
    <form class="form" action="{{ route('activate.profile.update') }}" method="POST">
      @csrf
      @method('PUT')
      <label class="form-title">パスワード</label>
      <input class="form-input" type="password" name="password">
      @error('password')
        <p class="c-error-message">{{ $message }}</p>
      @enderror
      <label class="form-title">確認用パスワード</label>
      <input class="form-input" type="password" name="confirm_password">
      @error('confirm_password')
        <p class="c-error-message">{{ $message }}</p>
      @enderror
      <button class="form-btn c-btn c-btn--red" type="submit">送信</button>
    </form>
  </div>
@endsection