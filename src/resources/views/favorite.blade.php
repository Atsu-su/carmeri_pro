@extends('layouts.base')
@section('title', 'Carmeri')
@push('javascripts')
  @php
    $publicPath = asset('js');
    $javascripts = [];
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
  {{-- サイドバーを追加するので --}}
  <div id="index">
    {{-- サイドバー（検索） --}}
    <aside class="sidebar c-search">
      @include('components.search', [
        'title' => 'お気に入りから検索',
        'route' => route('favorite.search.advanced')
      ])
    </aside>
    {{-- タイトル --}}
    <div class="list c-items">
      <div class="titles">
        <h2 class="title title-item-list"><a href="{{ route('index') }}">商品一覧</a></h2>
        <h2 class="title title-favorite-list current-page">お気に入り</h2>
      </div>
      {{-- お気に入り --}}
      <div class="tab">
        @if ($likedItems->isEmpty() && !isset($advancedSearchFlag))
          <p class="tab-message">お気に入りの商品はありません</p>
        @elseif ($likedItems->isEmpty() && isset($advancedSearchFlag))
          <p class="tab-message">検索条件に一致するお気に入りの商品はありません</p>
        @else
          @foreach ($likedItems as $like)
            <a class="c-item" href="{{ route('item.show', $like->item_id) }}">
              <p class="price">{{ $like->item->price }}円</p>
              @if ($like->item->image && Storage::exists('item_images/'.$like->item->image))
                <img src="{{ Storage::url('item_images/').$like->item->image }}" width="290" height="281" alt="【商品名】の画像">
              @else
                <img class="c-no-image" src="{{ asset('img/'.'no_image.jpg') }}" width="290" height="281" alt="商品の画像がありません">
              @endif
              @if ($like->item->isOnSale())
                <p>{{$like->item->id}}{{ $like->item->name }}</p>
              @else
                <p class="sold">{{$like->item->id}}{{ $like->item->name }}</p>
              @endif
            </a>
          @endforeach
        @endif
      </div>
      @if (isset($advancedSearchFlag) && $advancedSearchFlag)
        {{ ($likedItems->appends($searchArray)->links('vendor.pagination.default')) }}
      @else
        {{ ($likedItems->links('vendor.pagination.default')) }}
      @endif
    </div>
  </div>
@endsection