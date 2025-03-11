<?php

namespace App\Library;

class Message
{
    public $chatId;
    public $receiverId;    // 受信者のユーザID
    public $purchaseId;    // 購入ID
    public $username;       // 送信者の名前
    public $message;        // 送信メッセージ
    public $datetime;       // 送信時間（サーバ）
    public $image;          // プロフィール画像のファイル名
    public $isText;         // boolean（true: テキスト, false: 画像）
    public $method;         // CRUDメソッド（create, update, delete）
}