@extends('layouts.base')
@section('title', 'アカウント有効化')
@section('header')
  @include('components.header')
@endsection
@section('content')
  <div class="c-default-form" id="register">
    <h1 class="title">アカウントの有効化</h1>
    <form class="form" action="{{ route('activate') }}" method="POST">
      @csrf
      <label class="form-title">メールアドレスを入力してください</label>
      <input class="form-input" type="text" name="email" value="{{ old('email') }}">
      @error('email')
        <p class="c-error-message">{{ $message }}</p>
      @enderror
      <button class="form-btn c-btn c-btn--red" type="submit">送信</button>
    </form>
  </div>
@endsection