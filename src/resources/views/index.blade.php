@extends('layouts.base')
@section('title', 'Carmeri')
@section('header')
  @include('components.header')
@endsection
@section('content')
  <div class="c-items" id="index">
    <div class="titles">
      <h2 class="title title-recommend js-active-title" data-tab="first-tab">おすすめ</h2>
      <h2 class="title title-mylist" data-tab="second-tab">マイリスト</h2>
    </div>

    {{-- おすすめ --}}
    <div id="first-tab" class="tab first-tab"></div>

    {{-- マイリスト --}}
    <div class="tab second-tab js-hidden">
      @if (auth()->check())
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
  <script>
    const titles = document.querySelectorAll('.title');
    const tabs = document.querySelectorAll('.tab');

    titles.forEach(title => {
      title.addEventListener('click', (e) => {
        /* ・クリックされたタイトルにacitiveクラスが付いていない場合
             1. クリックされたタイトルにactiveクラスを付与
             2. クリックされなかったタイトルからactiveクラスを削除
             3. クリックされたタイトルに紐づくタブを表示
             4. クリックされなかったタイトルに紐づくタブを非表示

           ・クリックされたタイトルにactiveクラスが付いている場合、
             何もしない
        */
        if (! e.target.classList.contains('js-active-title')) {
          e.target.classList.add('js-active-title');

          titles.forEach(title => {
            if (e.target !== title) {
              title.classList.remove('js-active-title');
            }
          })

          tabs.forEach(tab => {
            if (tab.classList.contains(e.target.dataset.tab)) {
              tab.classList.remove('js-hidden');
            } else {
              tab.classList.add('js-hidden');
            }
          });
        }
      })
    })
  </script>
  <script>
    // 一旦item_idを0に設定し後程修正する
    const itemRoute = {{ Js::from(route('item.show', ['item_id' => '0'])) }};
    const imagePath = {{ Js::from(Storage::url('item_images/')) }};
    const noImagePath = {{Js::from(asset('img/').'/'.'no_image.jpg') }};
    const url = {{ Js::from(route('index')) }};

    class InfiniteImageLoader {
      constructor(options = {}) {
        this.container = options.container || document.getElementById('first-tab');
        this.pageSize = options.pageSize || 10;
        this.currentPage = 1;
        this.threshold = options.threshold || 200;
        this.loading = false;
        this.hasMore = true;
        this.handleScroll = this.handleScroll.bind(this);
        this.init();
      }

      init() {
        window.addEventListener('scroll', this.handleScroll);
        this.loadImages();
      }

      async handleScroll() {
        // ローディング中または画像データが無い場合は処理終了
        if (this.loading || !this.hasMore) {
          return;
        }

        const threshold = 200;
        // window.innerHeight: 表示領域の高さ（画面の大きさ）
        // window.scrollY: スクロールした量
        const scrollPosition = window.innerHeight + window.scrollY;
        const bodyHeight = document.documentElement.scrollHeight;

        if (bodyHeight - scrollPosition < threshold) {
            await this.loadImages();
        }
      }

      async loadImages() {
        const aTagArray = [];
        const imgTagArray = [];
        const pTagArray = [];

        // ローディング中にスクロールしても処理を行わない
        this.loading = true;

        const count = await fetch(`${url}/api/count?page=${this.currentPage}&limit=${this.pageSize}`);
        const countJson = await count.json();

        for (let i = 0; i < countJson; i++) {
          const divTag = document.createElement('div');
          const aTag = document.createElement('a');
          const imgTag = document.createElement('img');
          const pTag = document.createElement('p');
          const spinnerTag = document.createElement('div');

          aTag.classList.add('c-item');
          divTag.classList.add('first-tab-img-container');
          imgTag.setAttribute('width', '290');
          imgTag.setAttribute('height', '281');
          spinnerTag.classList.add('c-spinner');
          this.container.appendChild(aTag);
          aTag.appendChild(divTag);
          divTag.appendChild(imgTag);
          divTag.appendChild(spinnerTag);
          aTag.appendChild(pTag);

          aTagArray.push(aTag);
          imgTagArray.push(imgTag);
          pTagArray.push(pTag);
        }

        try {
          const response = await fetch(`${url}/api/images?page=${this.currentPage}&limit=${this.pageSize}`);
          const json = await response.json();

          if (!json.data || json.data.length < this.pageSize) {
              this.hasMore = false;
          }

          if (json.data && json.data.length > 0) {
            await this.displayImages(json.data, aTagArray, imgTagArray, pTagArray);
            this.currentPage++;
          }
        } catch (error) {
            console.error('画像の読み込みに失敗しました:', error);
        } finally {
            this.loading = false;
        }
      }

      async displayImages(dataArray, aTagArray, imgTagArray, pTagArray) {

        // ここの設定から開始
        for (const [index, data] of dataArray.entries()) {
          // aタグ
          let segments = itemRoute.split('/');
          segments[segments.length - 1] = data.id;
          aTagArray[index].href = segments.join('/');

          // imgタグ
          if (data.image) {
            imgTagArray[index].src = imagePath + data.image;
            imgTagArray[index].alt = data.name + 'の画像' || '';
          } else {
            imgTagArray[index].classList.add('c-no-image');
            imgTagArray[index].src = noImagePath;
            imgTagArray[index].alt = '商品の画像がありません';
          }

          // pタグ
          pTagArray[index].textContent = data.name;
          if (!data.on_sale) {
            pTagArray[index].classList.add('sold');
          }

          // スピナーを削除
          imgTagArray[index].parentElement.querySelector('.c-spinner').classList.remove('c-spinner');
        }
      }
    }

    const urlBasedLoader = new InfiniteImageLoader({
        container: document.getElementById('first-tab'),
        pageSize: 10,
        threshold: 300,
    });
  </script>
@endsection