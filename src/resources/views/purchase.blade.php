@extends('layouts.base')
@section('title', '商品購入')
@section('modal')
  @include('components.modal')
@endsection
@section('header')
  @include('components.header')
@endsection
@section('content')
<div id="purchase">
  <form action="{{ route('purchase.store', $item->id) }}" method="post">
    @csrf
    <div class="info">
      <div class="info-item">
        @if ($item->image && Storage::disk('public')->exists('item_images/'.$item->image))
          <img class="info-item-img" src="{{ asset('storage/item_images/'.$item->image) }}" width="600" height="600" alt="{{ $item->name }}の画像">
        @else
          <img class="c-no-image info-item-img" src="{{ asset('img/'.'no_image.jpg') }}" width="600" height="600" alt="商品の画像がありません">
        @endif
        <div class="info-item-value">
          <h1 class="info-item-value-name">{{ $item->name }}</h1>
          <p class="info-item-value-price">¥ {{ number_format($item->price) }}</p>
        </div>
      </div>
      <div class="info-delivery">
        <div class="info-delivery-header">
          <h2 class="info-delivery-header-title">配送先</h2>
          <a class="info-delivery-header-link" href="{{ route('address.edit', $item->id) }}">変更する</a>
        </div>
        <div class="info-delivery-main">
          <p>〒{{ $user->postal_code }}</p>
          <p>{{ $user->address }}</p>
          <p>{{ $user->building_name }}</p>
        </div>
      </div>
    </div>
    <div class="summary">
      <table>
        <tr class="summary-price">
          <th>商品代金</th>
          <td>¥ {{ number_format($item->price) }}</td>
        </tr>
      </table>
      @if (is_null($item->purchase))
        <button class="c-btn c-btn--red" type="submit">購入する</button>
      @else
        <p class="c-btn c-btn--disabled">
          {{ $item->purchase->isPurchased() ? '購入済み' : '購入手続き中' }}
        </p>
        <a class="summary-link" href="{{ route('index') }}">商品一覧に戻る</a>
      @endif
    </div>
  </form>
</div>
@endsection