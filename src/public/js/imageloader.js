"use strict";

// タブの切り替え処理
const titles = document.querySelectorAll('.title');
const tabs = document.querySelectorAll('.tab');

titles.forEach(title => {
  title.addEventListener('click', (e) => {
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

// 画像の無限スクロール読込処理
const itemRoute = document.getElementById('values').dataset.itemroute;
const imagePath = document.getElementById('values').dataset.imagepath;
const noImagePath = document.getElementById('values').dataset.noimagepath;
const url = document.getElementById('values').dataset.url;

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

    // window.innerHeight: 表示領域の高さ（画面の大きさ）
    // window.scrollY: スクロールした量
    const scrollPosition = window.innerHeight + window.scrollY;
    const bodyHeight = document.documentElement.scrollHeight;

    if (bodyHeight - scrollPosition < this.threshold) {
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
      pTagArray[index].textContent = data.id + data.name;
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