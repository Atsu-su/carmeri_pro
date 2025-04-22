@extends('layouts.base')
@section('title', 'Carmeri')
@push('javascripts')
  @php
    $publicPath = asset('js');
    $javascripts = ['imageloader.js']
  @endphp
  @foreach ($javascripts as $javascript)
    <script src="{{ "{$publicPath}/{$javascript}" }}" defer></script>
  @endforeach
@endpush
@section('modal')
  @include('components.modal')
@endsection
@section('header')
  @include('components.header')
@endsection
@section('content')
  <div id="values"
  data-itemroute="{{route('item.show', ['item_id' => '0'])}}"
  data-imagepath="{{Storage::url('item_images/')}}"
  data-noimagepath="{{asset('img/no_image.jpg')}}"
  data-url="{{route('index')}}"
  ></div>
  <div class="c-items" id="index">
    <div class="titles">
      <h2 class="title title-recommend js-active-title" data-tab="first-tab">おすすめ</h2>
      <h2 class="title title-mylist" data-tab="second-tab">マイリスト</h2>
    </div>

    {{-- おすすめ --}}
    <div id="first-tab" class="tab first-tab"></div>

    {{-- マイリスト --}}
    <div class="tab second-tab js-hidden">
      @if (auth()->check() && $likedItems->isEmpty())
          <p class="second-tab-no-item">お気に入りの商品はありません</p>
      @elseif (auth()->check() && !$likedItems->isEmpty())
        @foreach ($likedItems as $like)
          <a class="c-item" href="{{ route('item.show', $like->item_id) }}">
            @if ($like->item->image && Storage::exists('item_images/'.$like->item->image))
              <img src="{{ Storage::url('item_images/').$like->item->image }}" width="290" height="281" alt="【商品名】の画像">
            @else
              <img class="c-no-image" src="{{ asset('img/'.'no_image.jpg') }}" width="290" height="281" alt="商品の画像がありません">
            @endif
            @if ($like->item->isOnSale())
              <p>{{ $like->item->name }}</p>
            @else
              <p class="sold">{{ $like->item->name }}</p>
            @endif
          </a>
        @endforeach
      @else
        <p class="second-tab-login"><a href="{{ route('login') }}">ログイン</a>後に表示されます</p>
      @endif
    </div>
  </div>
@endsection