@extends('layouts.base')
@section('title', 'Carmeri')
@push('javascripts')
  @php
    $javascripts = [];
    $publicPath = asset('js');
    if (isset($searchFlag) || isset($advancedSearchFlag)) {
      $javascripts = [];
    } else {
      $javascripts = ['loadimage.js'];
    }
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
  <div id="index">
    {{-- サイドバー（検索） --}}
    <aside class="sidebar c-search">
      @include('components.search', [
        'title' => '商品一覧から検索',
        'route' => route('search.advanced')
      ])
    </aside>
    {{-- 商品一覧 --}}
    <div class="list c-items">
      <div class="titles">
        <h2 class="title title-item-list current-page">商品一覧</h2>
        <h2 class="title title-favorite-list"><a href="{{ route('favorite') }}">お気に入り</a></h2>
      </div>
      {{-- HomeController内に$searchFlag, advancedSearchFlagは存在しない --}}
      {{-- 一覧表示（スクロールでロード） --}}
      @if (!isset($searchFlag) && !isset($advancedSearchFlag))
        <div id="load-image" class="list-items"></div>
      @else
        {{-- 検索結果表示 --}}
        <div class="list-items">
          @if ($items->isEmpty())
            <p class="list-items-no-item">検索条件に一致する商品はありません</p>
          @else
            @foreach ($items as $item)
              <a class="c-item" href="{{ route('item.show', $item->id) }}">
                <p class="price">{{ $item->price }}円</p>
                @if ($item->image && Storage::exists('item_images/'.$item->image))
                  <img src="{{ Storage::url('item_images/').$item->image }}" width="250" height="242" alt="【商品名】の画像">
                @else
                  <img class="c-no-image" src="{{ asset('img/'.'no_image.jpg') }}" width="250" height="242" alt="商品の画像がありません">
                @endif
                @if ($item->isOnSale())
                  <p>{{ $item->name }}</p>
                @else
                  <p class="sold">{{ $item->name }}</p>
                @endif
              </a>
            @endforeach
          @endif
        </div>
        {{-- ページネーション --}}
        @if (isset($searchFlag) && $searchFlag)
          {{ ($items->appends(['keyword' => $keyword])->links('vendor.pagination.default')) }}
        @elseif (isset($advancedSearchFlag) && $advancedSearchFlag)
          {{ ($items->appends($searchArray)->links('vendor.pagination.default')) }}
        @endif
      @endif
    </div>
  </div>
@endsection