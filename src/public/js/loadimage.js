"use strict";

// 画像の無限スクロール読込処理
const itemRoute = document.getElementById('values').dataset.itemroute;
const imagePath = document.getElementById('values').dataset.imagepath;
const noImagePath = document.getElementById('values').dataset.noimagepath;
const url = document.getElementById('values').dataset.url;

class InfiniteImageLoader {
  constructor(options = {}) {
    this.container = options.container || document.getElementById('load-image');
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

    // window.innerHeight: 表示領域の高さ（画面の大きさ）
    // window.scrollY: スクロールした量
    const scrollPosition = window.innerHeight + window.scrollY;
    const bodyHeight = document.documentElement.scrollHeight;

    if (bodyHeight - scrollPosition < this.threshold) {
        await this.loadImages();
    }
  }

  async loadImages() {
    // ローディング中にスクロールしても処理を行わない
    this.loading = true;

    const elementArray = [];
    const count = await fetch(`${url}/api/count?page=${this.currentPage}&limit=${this.pageSize}`);
    const countJson = await count.json();

    for (let i = 0; i < countJson; i++) {
      // const divTag = document.createElement('div');
      const aTag = document.createElement('a');
      const pTag = document.createElement('p'); // 価格表示用
      const imgTag = document.createElement('img');
      const pTag2 = document.createElement('p');  // 商品名表示用
      const spinnerTag = document.createElement('div');

      aTag.classList.add('c-item');
      imgTag.setAttribute('width', '290');
      imgTag.setAttribute('height', '281');
      spinnerTag.classList.add('c-spinner');
      pTag.classList.add('price');
      pTag.style.display = 'none'; // 初期状態では非表示
      pTag2.style.display = 'none'; // 初期状態では非表示

      this.container.appendChild(aTag);
      aTag.appendChild(spinnerTag);
      aTag.appendChild(pTag);
      aTag.appendChild(imgTag);
      aTag.appendChild(pTag2);

      elementArray.push({
        aTag,
        imgTag,
        pTag,
        pTag2,
        spinnerTag
      });
    }

    try {
      const response = await fetch(`${url}/api/images?page=${this.currentPage}&limit=${this.pageSize}`);
      const json = await response.json();

      if (!json.data || json.data.length < this.pageSize) {
          this.hasMore = false;
      }

      if (json.data && json.data.length > 0) {
        await this.displayImages(json.data, elementArray);
        this.currentPage++;
      }
    } catch (error) {
        console.error('画像の読み込みに失敗しました:', error);
    } finally {
        this.loading = false;
    }
  }

  async displayImages(dataArray, elementArray) {
    // ここの設定から開始
    for (const [index, data] of dataArray.entries()) {
      const element = elementArray[index];

      // aタグ
      let segments = itemRoute.split('/');
      segments[segments.length - 1] = data.id;
      element.aTag.href = segments.join('/');

      // imgタグ
      if (data.image) {
        element.imgTag.src = imagePath + data.image;
        element.imgTag.alt = data.name + 'の画像' || '';
      } else {
        element.imgTag.classList.add('c-no-image');
        element.imgTag.src = noImagePath;
        element.imgTag.alt = '商品の画像がありません';
      }

      // pタグ
      element.pTag.textContent = data.price + '円';
      element.pTag2.textContent = data.name;

      // スピナーを削除
      element.spinnerTag.classList.remove('c-spinner');

      // スピナーの位置が変わらないように最後に表示
      // 価格・商品名を表示
      element.pTag.style.display = 'block';
      element.pTag2.style.display = 'block';
      // soldマークを表示
      if (!data.on_sale) {
        element.pTag2.classList.add('sold');
      }
    }
  }
}

const urlBasedLoader = new InfiniteImageLoader({
    // デフォルトが document.getElementById('load-imageなので
    // container: document.getElementById('load-image'),
    pageSize: 10,
    threshold: 300,
});