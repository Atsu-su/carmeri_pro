<div class="c-search">
  <h2 class="title">{{ $title ?? '商品検索'}}</h2>
  <form class="form" action="{{ $route }}" method="POST">
    @csrf
    <fieldset>
      <p class="label">キーワード</p>
      <input id="search-keyword" class="input" type="text" name="keyword" value="{{ $searchArray['keyword'] ?? '' }}" placeholder="商品名">
      <p class="label">カテゴリ</p>
      <select id="search-category" class="select" name="category_id">
        <option value="">選択してください</option>
        @foreach ($categories as $category)
          <option value="{{ $category->id }}" {{ (isset($searchArray['category_id']) && $searchArray['category_id'] == $category->id) ? 'selected' : '' }}>{{ $category->category }}</option>
        @endforeach
      </select>
      <p class="label">ブランド名</p>
      <input  id="search-brand" class="input" type="text" name="brand" value="{{ $searchArray['brand'] ?? '' }}" placeholder="ブランド名">
      <p class="label">商品の状態</p>
      <select  id="search-condition" class="select" name="condition_id">
        <option class="option-instruction" value="">選択してください</option>
        @foreach ($conditions as $condition)
          <option value="{{ $condition->id }}" {{ (isset($searchArray['condition_id']) && $searchArray['condition_id'] == $condition->id) ? 'selected' : '' }}>{{ $condition->condition }}</option>
        @endforeach
      </select>
      <p class="label">価格</p>
      <input id="search-min-price" class="input input-price" type="number" name="min_price" value="{{ $searchArray['min_price'] ?? '' }}" placeholder="最小価格"><span class="currency">円</span><input id="search-max-price" class="input input-price" type="number" name="max_price" class="max-price" value="{{ $searchArray['max_price'] ?? '' }}" placeholder="最大価格"><span class="currency">円</span>
      <label class="label-checkbox" for="search-on-sale">
        <input id="search-on-sale" class="checkbox" id="search-on-sale" type="checkbox" name="on_sale" value="1" {{ (isset($searchArray['on_sale']) && $searchArray['on_sale'] == 1) ? 'checked' : '' }}>販売中の商品のみ検索
      </label>
      <div class="buttons">
        <button class="button c-btn c-btn--advanced-search" type="submit">検&nbsp;索</button>
        <a id="reset-search" class="reset-search c-btn c-btn--advanced-search-reset">リセット</a>
      </div>
    </fieldset>
  </form>
</div>
<script src="{{ asset('js/resetsearch.js') }}" defer></script>