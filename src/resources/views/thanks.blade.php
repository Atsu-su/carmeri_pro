@extends('layouts.base')
@section('title', '退会処理完了')
@section('modal')
  @include('components.modal')
@endsection
@section('header')
  @include('components.header')
@endsection
@section('content')
  <div id="thanks">
    <div>
      <p>ご利用ありがとうございました</p>
      <p>またのご利用をお待ちしております</p>
    </div>
    <a href="{{ route('index')}}">一覧へ戻る</a>
  </div>
@endsection