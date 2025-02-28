@extends('layouts.base')
@section('content')
  <style>
    .container {
      width: 100%;
      display: flex;
      flex-wrap: wrap;
    }

    .img-container {
      width: 300px;
      height: 300px;
      display: grid;
      place-content: center;
    }

    img {
      width: 100%;
      height: auto;
    }

    .spinner {
      width: 40px;
      height: 40px;
      border: 4px solid #fff;
      border-top: 4px solid #3498db;
      border-radius: 50%;
      box-sizing: border-box;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
    <div id="image-container" class="container">
      {{-- loadingElement --}}
    </div>
  <script>
    const imagePath = {{ Js::from(Storage::url('item_images/')) }};

    class InfiniteImageLoader {
      constructor(options = {}) {
        this.container = options.container || document.getElementById('image-container');
        // this.loadingElement = options.loadingElement || document.getElementById('loading');
        this.pageSize = options.pageSize || 10;
        this.currentPage = 1;
        this.loading = false;
        this.hasMore = true;
        this.imageFormat = options.imageFormat || 'url'; // 'url' または 'base64'
        this.cache = new Map(); // URL形式の場合のキャッシュ

        // window.addEventListener('scroll', this.handleScroll)がbindなしの場合、
        // this = windowとして処理される。それを防ぐためにbindを使用
        this.handleScroll = this.handleScroll.bind(this);
        this.init();
      }

      init() {
        window.addEventListener('scroll', this.handleScroll);
        this.loadImages();
      }

      // sleep関数の定義（ミリ秒単位）
      sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
      }

      async handleScroll() {
        // ローディング中または画像データが無い場合は処理終了
        if (this.loading || !this.hasMore) {
          console.log('ローディング中');
          return;
        }

        const threshold = 380;
        // window.innerHeight: 表示領域の高さ（画面の大きさ）
        // window.scrollY: スクロールした量
        const scrollPosition = window.innerHeight + window.scrollY;
        const bodyHeight = document.documentElement.scrollHeight;

        if (bodyHeight - scrollPosition < threshold) {
            console.log('ローディング開始');
            await this.loadImages();
        }
      }

      async loadImages() {
        const imgArray = [];
        this.loading = true;

        console.log('this.currentPage::' + this.currentPage);
        console.log('this.pageSize::' + this.pageSize);

        try {
          // this.loadingElement.style.display = 'block';

          const response = await fetch(`/api/images?page=${this.currentPage}&limit=${this.pageSize}`);
          const {items, count} = await response.json();

          console.log(response);
          console.log('items.data::' + items.data);
          console.log('count::' + count);

          for (let i = 0; i < count; i++) {
            const divTag = document.createElement('div');
            const imgTag = document.createElement('img');
            const spinnerTag = document.createElement('div');
            divTag.classList.add('img-container');
            spinnerTag.classList.add('spinner');
            this.container.appendChild(divTag);
            divTag.appendChild(imgTag);
            divTag.appendChild(spinnerTag);
            imgArray.push(imgTag);
          }

          console.log('sleep開始');
          await this.sleep(800);
          console.log('sleep終了');

          if (!items.data || items.data.length < this.pageSize) {
              this.hasMore = false;
          }

          if (items.data && items.data.length > 0) {
            await this.displayImages(items.data, imgArray);
            this.currentPage++;
          }
        } catch (error) {
          console.error('画像の読み込みに失敗しました:', error);
        } finally {
          this.loading = false;
          console.log('ローディング完了');
        }
      }

      async displayImages(dataArray, imgArray) {
        for (const [index, data] of dataArray.entries()) {
          imgArray[index].src = imagePath + data.image;
          imgArray[index].alt = data.image.description || '';
          imgArray[index].classList.add('lazy-image');
          imgArray[index].style.cssText = `
            width: 300px;
            height: auto;
            margin: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);`;

          imgArray[index].parentElement.querySelector('div').classList.remove('spinner');
        }
      }
    }

    // 使用例1: URLベースの画像読み込み
    const urlBasedLoader = new InfiniteImageLoader({
      container: document.getElementById('image-container'),
      // loadingElement: document.getElementById('loading'),
      pageSize: 10,
      imageFormat: 'url'
    });

    // // 使用例2: Base64ベースの画像読み込み
    // const base64BasedLoader = new InfiniteImageLoader({
    //     container: document.getElementById('image-container'),
    //     loadingElement: document.getElementById('loading'),
    //     pageSize: 12,
    //     imageFormat: 'base64'
    // });
  </script>
@endsection