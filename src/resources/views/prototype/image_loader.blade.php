@extends('layouts.base')
@section('content')
  <div id="image-container">
    <div id="loading" style="display: none;">
      <p>画像を読込中</p>
    </div>
  </div>
  <script>
    const imagePath = @json(asset('storage/item_images/').'/');

    class InfiniteImageLoader {
        constructor(options = {}) {
            this.container = options.container || document.getElementById('image-container');
            this.loadingElement = options.loadingElement || document.getElementById('loading');
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

        async handleScroll() {
            // ローディング中または画像データが無い場合は処理終了
            if (this.loading || !this.hasMore) return;

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
            try {
                this.loading = true;
                this.loadingElement.style.display = 'block';

                const response = await fetch(`/api/images?page=${this.currentPage}&limit=${this.pageSize}`);
                const json = await response.json();

                if (!json.data || json.data.length < this.pageSize) {
                    this.hasMore = false;
                }

                if (json.data && json.data.length > 0) {
                  await this.displayImages(json.data);
                  this.currentPage++;
                }

            } catch (error) {
                console.error('画像の読み込みに失敗しました:', error);
            } finally {
                this.loading = false;
                this.loadingElement.style.display = 'none';
            }
        }

        async displayImages(dataArray) {
            // URLとBase64の両方に対応
            for (const data of dataArray) {
                const imgElement = document.createElement('img');

                imgElement.src = imagePath + data.image;
                imgElement.alt = data.image.description || '';
                imgElement.classList.add('lazy-image');
                imgElement.style.cssText = `
                    width: 300px;
                    height: auto;
                    margin: 10px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                `;

                this.container.appendChild(imgElement);
            }
        }
    }

    // 使用例1: URLベースの画像読み込み
    const urlBasedLoader = new InfiniteImageLoader({
        container: document.getElementById('image-container'),
        loadingElement: document.getElementById('loading'),
        pageSize: 5,
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