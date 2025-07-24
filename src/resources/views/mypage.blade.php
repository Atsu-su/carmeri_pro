@extends('layouts.base')
@section('title', 'プロフィール')
@push('javascripts')
  @php
    $javascripts = ['tab.js', 'pagination.js'];
    $publicPath = asset('js');
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
  {{-- jsで取得する定数を定義 --}}
  <div id="values"
  data-item-show-url="{{ route('item.show', ['item_id' => 'id']) }}"
  data-sell-edit-url="{{ route('sell.edit', ['item_id' => 'id']) }}"
  data-chat-url="{{ route('chat', ['purchase_id' => 'id']) }}"
  data-seller-info-url="{{ route('seller.show', ['purchase_id' => 'id']) }}"
  data-buyer-info-url="{{ route('buyer.show', ['purchase_id' => 'id']) }}"
  data-status-seller-url="{{ route('status.seller.show', ['purchase_id' => 'id']) }}"
  data-status-buyer-url="{{ route('status.buyer.show', ['purchase_id' => 'id']) }}"
  ></div>
  <div id="mypage">
    <div class="user">
      <div class="user-info">
        <div class="c-profile-outer-frame user-info-icon">
          @if ($user->image && Storage::exists('profile_images/'.$user->image))
            <img class="c-profile-inner-frame" src="{{ Storage::url('profile_images/').$user->image }}" alt="プロフィールの画像">
          @else
            <div class="c-profile-no-image">
              <p>NO</p>
              <p>IMAGE</p>
            </div>
          @endif
        </div>
        <div>
          <p class="user-info-name">{{ $user->name }}</p>
          <div class="user-info-stars">
            @if ($user->evaluations > 0)
              @for ($i = 1; $i <= 5; $i++)
                <span class="user-info-stars-star{{$i}}{{ $i <= $user->rating ? ' filled' : '' }}"></span>
              @endfor
            @else
              <p class="user-info-stars-zero">評価はまだありません</p>
            @endif
          </div>
        </div>
      </div>
      <a class="c-btn c-btn--profile-edit" href="{{ route('profile.edit')}}">プロフィールを編集</a>
    </div>
    <div class="c-items">
      <div class="titles">
        <h2 class="title title-listed js-active-title" data-tab="js-first-tab">出品した商品</h2>
        <h2 class="title title-purchased" data-tab="js-second-tab">購入した商品</h2>
        @php $result = $sellingItems->sum(function ($purchase) {return $purchase->chats->count();}) @endphp
        <h2 class="title title-processing" data-tab="js-third-tab">取引中の出品商品
          @if ($result > 0)
            <span class="new-message-icon"><span>{{ $result < 100 ? $result : '99+' }}</span></span>
          @endif
        </h2>
        @php $result = $purchasingItems->sum(function ($purchase) {return $purchase->chats->count();}) @endphp
        <h2 class="title title-processing" data-tab="js-forth-tab">取引中の購入商品
          @if ($result > 0)
            <span class="new-message-icon"><span>{{ $result < 100 ? $result : '99+' }}</span></span>
          @endif
        </h2>
      </div>
      <div class="tab table js-first-tab js-active">
        {{-- 出品した商品 --}}
        @if ($listedItems->isEmpty())
          <p class="no-listed-item">出品された商品はありません</p>
        @else
          <div class="table-container">
            <table class="c-table c-table--listed-items">
              <thead>
                <tr class="header">
                  <th>画像</th>
                  <th class="name c-message-show-detail">商品名</th>
                  <th>価格</th>
                  <th>出品日／<br>取引完了日</th>
                  <th>ステータス</th>
                  <th>アクション</th>
                </tr>
              </thead>
              <tbody id="listed-items-tbody">
                @foreach ($listedItems as $item)
                  <tr class="data">
                    <td class="img">
                      <a href="{{ route('item.show', $item->id) }}">
                        @if ($item->image && Storage::exists('item_images/'.$item->image))
                          <img src="{{ Storage::url('item_images/').$item->image }}" width="80" height="80" alt="{{ $item->name }}の画像">
                        @else
                          <img class="c-no-image" src="{{ asset('img/'.'no_image.jpg') }}" width="80" height="80" alt="商品の画像がありません">
                        @endif
                      </a>
                    </td>
                    <td class="name"><a href="{{ route('item.show', $item->id) }}">{{ $item->name }}</a></td>
                    <td class="price">{{ $item->price }}円</td>
                    <td class="date"><span>{{ $item->created_at->format('Y/m/d') }}</span><span>{{ isset($item->transaction_completed_at) ? $item->transaction_completed_at : '-'}}</span></td>
                    <td class="status">{{ $item->status_text }}</td>
                    <td class="edit-chat">
                      @if (!isset($item->purchase))
                        <a href="{{ route('sell.edit', ['item_id' => $item->id]) }}">編集</a>
                      @else
                        <a href="{{ route('chat', ['purchase_id' => $item->purchase->id]) }}">チャット</a>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div id="listed-items-pagination">
            {{ ($listedItems->links('vendor.pagination.default')) }}
          </div>
        @endif
      </div>
      <div class="tab table js-second-tab js-hidden">
        @if ($purchasedItems->isEmpty())
          <p class="no-purchased-item">購入された商品はありません</p>
        @else
          <div class="table-container">
            <table class="c-table c-table--purchased-items">
              <thead>
                <tr class="header">
                  <th>画像</th>
                  <th class="name c-message-show-detail">商品名</th>
                  <th>価格</th>
                  <th>購入日</th>
                  <th>出品者情報</th>
                  <th>チャット</th>
                </tr>
              </thead>
              <tbody  id="purchased-items-tbody">
                @foreach ($purchasedItems as $item)
                  <tr class="data">
                    <td class="img">
                      <a href="{{ route('item.show', $item->id) }}">
                        @if ($item->item->image && Storage::exists('item_images/'.$item->item->image))
                          <img src="{{ Storage::url('item_images/').$item->item->image }}" width="80" height="80" alt="{{ $item->name }}の画像">
                        @else
                          <img class="c-no-image" src="{{ asset('img/'.'no_image.jpg') }}" width="80" height="80" alt="商品の画像がありません">
                        @endif
                      </a>
                    </td>
                    <td class="name"><a href="{{ route('item.show', $item->item->id) }}">{{ $item->item->name }}</a></td>
                    <td class="price">{{ $item->item->price }}円</td>
                    <td class="date">{{ $item->created_at->format('Y/m/d') }}</td>
                    <td class="seller"><a href="{{ route('seller.show', ['purchase_id'=> $item->id]) }}">{{ $item->item->user->name }}</a></td>
                    <td class="chat">
                      <a href="{{ route('chat', ['purchase_id' => $item->id]) }}">表示</a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div id="purchased-items-pagination">
            {{ ($purchasedItems->links('vendor.pagination.default')) }}
          </div>
        @endif
      </div>
      <div class="tab table js-third-tab js-hidden">
        @if ($sellingItems->isEmpty())
          <p class="no-purchased-item">出品している商品のうち、取引中の商品はありません</p>
        @else
          <div class="table-container">
            <table class="c-table c-table--selling-items">
              <thead>
                <tr class="header">
                  <th>画像</th>
                  <th class="name c-message-show-detail">商品名</th>
                  <th>チャット<br>（未読件数）</th>
                  <th>発送ステータス</th>
                  <th>販売日</th>
                  <th>購入者情報</th>
                </tr>
              </thead>
              <tbody id="selling-items-tbody">
                @foreach ($sellingItems as $purchase)
                  <tr class="data">
                    <td class="img">
                      <a href="{{ route('item.show', $purchase->item->id) }}">
                        @if ($purchase->item->image && Storage::exists('item_images/'.$purchase->item->image))
                          <img src="{{ Storage::url('item_images/').$purchase->item->image }}" width="80" height="80" alt="{{ $purchase->name }}の画像">
                        @else
                          <img class="c-no-image" src="{{ asset('img/'.'no_image.jpg') }}" width="80" height="80" alt="商品の画像がありません">
                        @endif
                      </a>
                    </td>
                    <td class="name"><a href="{{ route('item.show', $purchase->item->id) }}">{{ $purchase->item->name }}</a></td>
                    <td class="chat">
                      <a href="{{ route('chat', ['purchase_id' => $purchase->id]) }}">表示（
                        @php $result = $purchase->chats->chats_count @endphp
                        @if ($result > 0)
                          <span>{{ $result < 100 ? $result : '99+' }}</span>
                        @elseif ($result == 0)
                          <span>{{ $result }}</span>
                        @endif
                      件）</a>
                    </td>
                    <td class="item-status"><a href="{{ route('status.seller.show', ['purchase_id' => $purchase->id]) }}">{{ $purchase->status_text}}</a></td>
                    <td class="date">{{ $purchase->created_at->format('Y/m/d') }}</td>
                    <td class="buyer"><a href="{{ route('buyer.show', ['purchase_id' => $purchase->id]) }}">{{ $purchase->user->name }}</a></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div id="selling-items-pagination">
            {{ ($sellingItems->links('vendor.pagination.default')) }}
          </div>
        @endif
      </div>
      <div class="tab table js-forth-tab js-hidden">
        @if ($purchasingItems->isEmpty())
          <p class="no-purchased-item">購入した商品のうち、取引中の商品はありません</p>
        @else
          <div class="table-container">
            <table class="c-table c-table--purchasing-items">
              <thead>
                <tr class="header">
                  <th>画像</th>
                  <th class="name c-message-show-detail">商品名</th>
                  <th>チャット<br>（未読件数）</th>
                  <th>発送ステータス</th>
                  <th>購入日</th>
                  <th>出品者者情報</th>
                </tr>
              </thead>
              <tbody id="purchasing-items-tbody">
                @foreach ($purchasingItems as $purchase)
                  <tr class="data">
                    <td class="img">
                      <a href="{{ route('item.show', $purchase->item->id) }}">
                        @if ($purchase->item->image && Storage::exists('item_images/'.$purchase->item->image))
                          <img src="{{ Storage::url('item_images/').$purchase->item->image }}" width="80" height="80" alt="{{ $purchase->name }}の画像">
                        @else
                          <img class="c-no-image" src="{{ asset('img/'.'no_image.jpg') }}" width="80" height="80" alt="商品の画像がありません">
                        @endif
                      </a>
                    </td>
                    <td class="name"><a href="{{ route('item.show', $purchase->item->id) }}">{{ $purchase->item->name }}</a></td>
                    <td class="chat">
                      <a href="{{ route('chat', ['purchase_id' => $purchase->id]) }}">表示（
                        @php $result = $purchase->chats->chats_count @endphp
                        @if ($result > 0)
                          <span>{{ $result < 100 ? $result : '99+' }}</span>
                        @elseif ($result == 0)
                          <span>{{ $result }}</span>
                        @endif
                      件）</a>
                    </td>
                    <td class="item-status"><a href="{{ route('status.buyer.show', ['purchase_id' => $purchase->id]) }}">{{ $purchase->status_text}}</a></td>
                    <td class="date">{{ $purchase->created_at->format('Y/m/d') }}</td>
                    <td class="seller"><a href="{{ route('seller.show', ['purchase_id' => $purchase->id]) }}">{{ $purchase->item->user->name }}</a></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div id="purchasing-items-pagination">
            {{ ($purchasingItems->links('vendor.pagination.default')) }}
          </div>
        @endif
      </div>
    </div>
  </div>
@endsection