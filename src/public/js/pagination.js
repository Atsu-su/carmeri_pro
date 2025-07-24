"use strict";

// ページネーションの全てのリンク先にイベントリスナーを追加
const links = document.querySelectorAll('.c-pagination a');
links.forEach(link => {
  link.addEventListener('click', paginationClickEvent);
});

async function paginationClickEvent(e) {
    e.preventDefault();

    let paginationTarget;
    const url = e.target.href;
    const data = await load(url);

    switch (data.type) {
      case 'listed_items':
        paginationTarget = document.getElementById('listed-items-pagination');
        renderListedItems(data.items.data);
        break;
      case 'purchased_items':
        paginationTarget = document.getElementById('purchased-items-pagination');
        renderPurchasedItems(data.items.data);
        break;
      case 'selling_items':
        paginationTarget = document.getElementById('selling-items-pagination');
        renderSellingItems(data.items.data);
        break;
      case 'purchasing_items':
        paginationTarget = document.getElementById('purchasing-items-pagination');
        renderPurchasingItems(data.items.data);
        break;
      default:
        console.error('Unknown data type:', data.type);
    }

    renderPagination(data.pagination, paginationTarget);
}

async function load(url) {
  try {
    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    if (!response.ok) {
      throw new Error('Fetch error: ' + response.statusText);
    }

    const data = await response.json()
    return data
  } catch (error) {
    return null;
  }
}

function renderListedItems(items) {
  const tbody = document.getElementById('listed-items-tbody');

  // 現在表示されているtbodyを空にする
  tbody.innerHTML = '';

  items.forEach(item => {
    // tr要素を作成
    const tr = document.createElement('tr');
    tr.className = 'data';

    // 画像セル
    const imgTd = document.createElement('td');
    imgTd.className = 'img';
    const imgLink = document.createElement('a');
    const itemShowUrl = formatUrl(document.getElementById('values').dataset.itemShowUrl, item.id, 4);
    imgLink.href = itemShowUrl;
    const img = document.createElement('img');
    img.width = 80;
    img.height = 80;
    img.src = item.image_url;
    img.alt = `${item.name}の画像`;

    imgLink.appendChild(img);
    imgTd.appendChild(imgLink);

    // 商品名セル
    const nameTd = document.createElement('td');
    nameTd.className = 'name';
    const nameLink = document.createElement('a');
    nameLink.href = itemShowUrl;
    nameLink.textContent = item.name;
    nameTd.appendChild(nameLink);

    // 価格セル
    const priceTd = document.createElement('td');
    priceTd.className = 'price';
    priceTd.textContent = `${item.price}円`;

    // 出品日セル
    const dateTd = document.createElement('td');
    dateTd.className = 'date';
    dateTd.textContent = formatDate(item.created_at); // 日付フォーマット関数が必要

    // ステータスセル
    const statusTd = document.createElement('td');
    statusTd.className = 'status';
    statusTd.textContent = item.on_sale_text;

    // 編集セル
    const editTd = document.createElement('td');
    editTd.className = 'edit';
    const editLink = document.createElement('a');
    const sellEditUrl = formatUrl(document.getElementById('values').dataset.sellEditUrl, item.id, 5);
    editLink.href = sellEditUrl;
    editLink.textContent = '編集';
    editTd.appendChild(editLink);

    // 全てのセルをtrに追加
    tr.appendChild(imgTd);
    tr.appendChild(nameTd);
    tr.appendChild(priceTd);
    tr.appendChild(dateTd);
    tr.appendChild(statusTd);
    tr.appendChild(editTd);

    // trをtbodyに追加
    tbody.appendChild(tr);
  });
}

function renderPurchasedItems(purchases) {
  const tbody = document.getElementById('purchased-items-tbody');

  // 現在表示されているtbodyを空にする
  tbody.innerHTML = '';

  purchases.forEach(purchase => {
    // tr要素を作成
    const tr = document.createElement('tr');
    tr.className = 'data';

    // 画像セル
    const imgTd = document.createElement('td');
    imgTd.className = 'img';
    const imgLink = document.createElement('a');
    const itemShowUrl = document.getElementById('values').dataset.itemShowUrl;
    imgLink.href = formatUrl(itemShowUrl, purchase.item.id, 4); // route('item.show', $item->item->id)相当
    const img = document.createElement('img');
    img.width = 80;
    img.height = 80;
    img.src = purchase.item.image_url;
    img.alt = `${purchase.item.name}の画像`;

    imgLink.appendChild(img);
    imgTd.appendChild(imgLink);

    // 商品名セル
    const nameTd = document.createElement('td');
    nameTd.className = 'name';
    const nameLink = document.createElement('a');
    nameLink.href = itemShowUrl;
    nameLink.textContent = purchase.item.name;
    nameTd.appendChild(nameLink);

    // 価格セル
    const priceTd = document.createElement('td');
    priceTd.className = 'price';
    priceTd.textContent = `${purchase.item.price}円`;

    // 購入日セル
    const dateTd = document.createElement('td');
    dateTd.className = 'date';
    dateTd.textContent = formatDate(purchase.created_at); // 日付フォーマット関数が必要

    // 出品者情報セル
    const sellerTd = document.createElement('td');
    sellerTd.className = 'seller';
    const sellerLink = document.createElement('a');
    const sellerInfoUrl = document.getElementById('values').dataset.sellerInfoUrl
    sellerLink.href = formatUrl(sellerInfoUrl, purchase.id, 4);
    sellerLink.textContent = purchase.item.user.name;
    sellerTd.appendChild(sellerLink);

    // チャットセル
    const chatTd = document.createElement('td');
    chatTd.className = 'chat';
    const chatLink = document.createElement('a');
    const chatUrl = document.getElementById('values').dataset.chatUrl;
    chatLink.href = formatUrl(chatUrl, purchase.id, 4); // route('chat', ['purchase_id' => $item->id])相当
    chatLink.textContent = '表示';
    chatTd.appendChild(chatLink);

    // 全てのセルをtrに追加
    tr.appendChild(imgTd);
    tr.appendChild(nameTd);
    tr.appendChild(priceTd);
    tr.appendChild(dateTd);
    tr.appendChild(sellerTd);
    tr.appendChild(chatTd);

    // trをtbodyに追加
    tbody.appendChild(tr);
  });
}

function renderSellingItems(purchases) {
  const tbody = document.getElementById('selling-items-tbody');

  // 現在表示されているtbodyを空にする
  tbody.innerHTML = '';

  purchases.forEach(purchase => {
    // tr要素を作成
    const tr = document.createElement('tr');
    tr.className = 'data';

    // 画像セル
    const imgTd = document.createElement('td');
    imgTd.className = 'img';
    const imgLink = document.createElement('a');
    const itemShowUrl = document.getElementById('values').dataset.itemShowUrl;
    imgLink.href = formatUrl(itemShowUrl, purchase.item.id, 4); // route('item.show', $purchase->item->id)相当
    const img = document.createElement('img');
    img.width = 290;
    img.height = 281;
    img.src = purchase.item.image_url;
    img.alt = `${purchase.item.name}の画像`;

    imgLink.appendChild(img);
    imgTd.appendChild(imgLink);

    // 商品名セル
    const nameTd = document.createElement('td');
    nameTd.className = 'name';
    const nameLink = document.createElement('a');
    nameLink.href = formatUrl(itemShowUrl, purchase.item.id, 4);
    nameLink.textContent = purchase.item.name;
    nameTd.appendChild(nameLink);

    // チャットセル（未読件数）
    const chatTd = document.createElement('td');
    chatTd.className = 'chat';
    const chatLink = document.createElement('a');
    const chatUrl = document.getElementById('values').dataset.chatUrl;
    chatLink.href = formatUrl(chatUrl, purchase.id, 4); // route('chat', ['purchase_id' => $purchase->id])相当

    // チャット件数の表示
    const chatCount = purchase.chats.chats_count || 0;
    let displayCount;
    if (chatCount > 0) {
      displayCount = chatCount < 100 ? chatCount : '99+';
    } else {
      displayCount = '0';
    }

    chatLink.innerHTML = `表示（<span>${displayCount}</span>件）`;
    chatTd.appendChild(chatLink);

    // 発送ステータスセル
    const deliveryStatusTd = document.createElement('td');
    deliveryStatusTd.className = 'item-status';
    const deliveryLink = document.createElement('a');
    const statusSellerUrl = document.getElementById('values').dataset.statusSellerUrl;
    deliveryLink.href = formatUrl(statusSellerUrl, purchase.id, 5); // route('delivery.show', ['purchase_id' => $purchase->id])相当
    deliveryLink.textContent = purchase.status_text;
    deliveryStatusTd.appendChild(deliveryLink);

    // 購入日セル
    const dateTd = document.createElement('td');
    dateTd.className = 'date';
    dateTd.textContent = formatDate(purchase.created_at); // Y/m/d形式

    // 購入者情報セル
    const buyerTd = document.createElement('td');
    buyerTd.className = 'buyer'; // 元のクラス名を維持
    const buyerLink = document.createElement('a');
    const buyerInfoUrl = document.getElementById('values').dataset.buyerInfoUrl;
    buyerLink.href = formatUrl(buyerInfoUrl, purchase.id, 4); // 元のBladeテンプレートでは空のhref
    buyerLink.textContent = purchase.user.name;
    buyerTd.appendChild(buyerLink);

    // 全てのセルをtrに追加
    tr.appendChild(imgTd);
    tr.appendChild(nameTd);
    tr.appendChild(chatTd);
    tr.appendChild(deliveryStatusTd);
    tr.appendChild(dateTd);
    tr.appendChild(buyerTd);

    // trをtbodyに追加
    tbody.appendChild(tr);
  });
}

function renderPurchasingItems(purchases) {
  const tbody = document.getElementById('purchasing-items-tbody');

  // 現在表示されているtbodyを空にする
  tbody.innerHTML = '';

  purchases.forEach(purchase => {
    // tr要素を作成
    const tr = document.createElement('tr');
    tr.className = 'data';

    // 画像セル
    const imgTd = document.createElement('td');
    imgTd.className = 'img';
    const imgLink = document.createElement('a');
    const itemShowUrl = document.getElementById('values').dataset.itemShowUrl;
    imgLink.href = formatUrl(itemShowUrl, purchase.item.id, 4); // route('item.show', $purchase->item->id)相当
    const img = document.createElement('img');
    img.width = 290;
    img.height = 281;
    img.src = purchase.item.image_url;
    img.alt = `${purchase.item.name}の画像`;

    imgLink.appendChild(img);
    imgTd.appendChild(imgLink);

    // 商品名セル
    const nameTd = document.createElement('td');
    nameTd.className = 'name';
    const nameLink = document.createElement('a');
    nameLink.href = formatUrl(itemShowUrl, purchase.item.id, 4);
    nameLink.textContent = purchase.item.name;
    nameTd.appendChild(nameLink);

    // チャットセル（未読件数）
    const chatTd = document.createElement('td');
    chatTd.className = 'chat';
    const chatLink = document.createElement('a');
    const chatUrl = document.getElementById('values').dataset.chatUrl;
    chatLink.href = formatUrl(chatUrl, purchase.id, 4); // route('chat', ['purchase_id' => $purchase->id])相当

    // チャット件数の表示
    const chatCount = purchase.chats.chats_count || 0;
    let displayCount;
    if (chatCount > 0) {
      displayCount = chatCount < 100 ? chatCount : '99+';
    } else {
      displayCount = '0';
    }

    chatLink.innerHTML = `表示（<span>${displayCount}</span>件）`;
    chatTd.appendChild(chatLink);

    // 発送ステータスセル
    const deliveryStatusTd = document.createElement('td');
    deliveryStatusTd.className = 'item-status';
    const deliveryLink = document.createElement('a');
    const statusBuyerUrl = document.getElementById('values').dataset.statusBuyerUrl;
    deliveryLink.href = formatUrl(statusBuyerUrl, purchase.id, 5); // route('delivery.show', ['purchase_id' => $purchase->id])相当

    console.log(statusBuyerUrl)
    console.log(deliveryLink.href)

    deliveryLink.textContent = purchase.status_text;
    deliveryStatusTd.appendChild(deliveryLink);

    // 購入日セル
    const dateTd = document.createElement('td');
    dateTd.className = 'date';
    dateTd.textContent = formatDate(purchase.created_at); // Y/m/d形式

    // 出品者情報セル
    const sellerTd = document.createElement('td');
    sellerTd.className = 'seller'; // 元のクラス名を維持
    const sellerLink = document.createElement('a');
    const sellerInfoUrl = document.getElementById('values').dataset.sellerInfoUrl;
    sellerLink.href = formatUrl(sellerInfoUrl, purchase.id, 4); // 元のBladeテンプレートでは空のhref
    sellerLink.textContent = purchase.item.user.name;
    sellerTd.appendChild(sellerLink);

    // 全てのセルをtrに追加
    tr.appendChild(imgTd);
    tr.appendChild(nameTd);
    tr.appendChild(chatTd);
    tr.appendChild(deliveryStatusTd);
    tr.appendChild(dateTd);
    tr.appendChild(sellerTd);

    // trをtbodyに追加
    tbody.appendChild(tr);
  });
}

// 日付フォーマット用のヘルパー関数
function formatDate(dateString) {
  const date = new Date(dateString);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}/${month}/${day}`;
}

// URLフォーマット用のヘルパー関数
function formatUrl(url, value, index, string = 'id') {
    const urlArray = url.split('/');
    if (urlArray[index] === string) {
      urlArray[index] = value; // item_idを設定
    }
    const newUrl = urlArray.join('/'); // URLを再構築

   return newUrl;
}

function renderPagination(html, target) {
  // HTML文字列をDOMParserでパースしてDOMの.c-paginationを取得
  const parse = new DOMParser();
  const tempPagination = parse.parseFromString(html, 'text/html');
  const newPagination = tempPagination.querySelector('.c-pagination');  // parseでは全体（<html>...</html>）を取得する
  const oldPagination = target.querySelector('.c-pagination');

  // 既存のページネーションを新しいものに置き換える
  target.replaceChild(newPagination, oldPagination);

  // イベントリスナーで新しいページネーションにイベントを付与
  const links = newPagination.querySelectorAll('a');
  links.forEach(link => {
    link.addEventListener('click', paginationClickEvent);
  });
}