<?php

namespace App\Messages;

class Message
{
    // 定数としてメッセージタイプを定義
    public const SUCCESS = 'success';
    public const ERROR = 'error';
    public const INFO = 'info';

    // メッセージ定義
    private static array $messages = [
        'purchase' => [
            'success' => [
                'status' => self::SUCCESS,
                'title' => 'お買上げありがとうございます',
                'contents' => [
                    '購入手続きが正常に完了しました',
                    '詳細は購入履歴からご確認頂けます'
                ],
            ],

            'failed' => [
                'status' => self::ERROR,
                'title' => '購入処理に失敗しました',
                'contents' => [
                    '申し訳ございません',
                    'お手数ですが、しばらく時間をおいて再度お試しください'
                ],
            ],

            'already' => [
                'status' => self::ERROR,
                'title' => '購入手続きを継続できません',
                'contents' => ['直前に他のお客様にて購入手続きが開始されました'],
            ],

            'cancel' => [
                'status' => self::INFO,
                'title' => '購入処理をキャンセルしました',
                'contents' => ['引き続き、お買い物をお楽しみください'],
            ],
            'own' => [
                'status' => self::INFO,
                'title' => 'この商品は購入できません',
                'contents' => [
                    '本商品はご自身にて出品された商品です',
                    '出品を取り下げる場合はマイページよりお手続きください'
                ],
            ],
        ],

        'address' => [
            'success' => [
                'status' => self::SUCCESS,
                'title' => '住所変更完了',
                'contents' => ['住所を変更しました'],
            ],
            'failed' => [
                'status' => self::ERROR,
                'title' => '住所変更に失敗しました',
                'contents' => [
                    '申し訳ございません',
                    'お手数ですが、しばらく時間をおいて再度お試しください'
                ],
            ],
        ],

        'comment' => [
            'success' => [
                'create' => [
                    'status' => self::SUCCESS,
                    'title' => 'コメントを投稿しました',
                    'contents' => ['引き続き、お買い物をお楽しみください'],
                ],
                'update' => [
                    'status' => self::SUCCESS,
                    'title' => 'コメントを更新しました',
                    'contents' => ['引き続き、お買い物をお楽しみください'],
                ],
                'delete' => [
                    'status' => self::SUCCESS,
                    'title' => 'コメントを削除しました',
                    'contents' => ['引き続き、お買い物をお楽しみください'],
                ],
            ],
            'failed' => [
                'create' => [
                    'status' => self::ERROR,
                    'title' => 'コメントの登録に失敗しました',
                    'contents' => [
                        '申し訳ございません',
                        'お手数ですが、しばらく時間をおいて再度お試しください'
                    ],
                ],
                'update' => [
                    'status' => self::ERROR,
                    'title' => 'コメントの更新に失敗しました',
                    'contents' => [
                        '申し訳ございません',
                        'お手数ですが、しばらく時間をおいて再度お試しください'
                    ],
                ],
            ],
        ],

        'profile' => [
            'success' => [
                'status' => self::SUCCESS,
                'title' => 'プロフィール更新完了',
                'contents' => ['プロフィールを更新しました'],
            ],
            'failed' => [
                'status' => self::ERROR,
                'title' => 'プロフィールの更新に失敗しました',
                'contents' => [
                    '申し訳ございません',
                    'お手数ですが、しばらく時間をおいて再度お試しください'
                ],
            ],
            'password' => [
                'updated' => [
                    'success' => [
                        'status' => self::SUCCESS,
                        'title' => 'パスワード変更完了',
                        'contents' => ['パスワードを変更しました'],
                    ],
                    'failed' => [
                        'status' => self::ERROR,
                        'title' => 'パスワードの変更に失敗しました',
                        'contents' => [
                            '申し訳ございません',
                            'お手数ですが、しばらく時間をおいて再度お試しください'
                        ],
                    ]
                ]
            ]
        ],

        'list' => [
            'create' => [
                'success' => [
                    'status' => self::SUCCESS,
                    'title' => '出品完了',
                    'contents' => [
                        '商品が出品されました',
                        '出品した商品はマイページから確認できます'
                    ],
                ],
                'failed' => [
                    'status' => self::ERROR,
                    'title' => '出品に失敗しました',
                    'contents' => [
                        '申し訳ございません',
                        'お手数ですが、しばらく時間をおいて再度お試しください'
                    ],
                ],
            ],
            'update' => [
                'success' => [
                    'status' => self::SUCCESS,
                    'title' => '商品情報の更新完了',
                    'contents' => [
                        '商品情報を更新しました',
                        '引き続き、お買い物をお楽しみください'
                    ],
                ],
                'failed' => [
                    'status' => self::ERROR,
                    'title' => '商品情報の更新に失敗しました',
                    'contents' => [
                        '申し訳ございません',
                        'お手数ですが、しばらく時間をおいて再度お試しください'
                    ],
                ],
            ],
            'delete' => [
                'success' => [
                    'status' => self::SUCCESS,
                    'title' => '商品の出品を取り下げました',
                    'contents' => ['引き続き、お買い物をお楽しみください'],
                ],
                'failed' => [
                    'status' => self::ERROR,
                    'title' => '商品の出品の取り下げに失敗しました',
                    'contents' => [
                        '申し訳ございません',
                        'お手数ですが、しばらく時間をおいて再度お試しください'
                    ],
                ],
            ],
        ],
        'user' => [
            'deactivate' => [
                'success' => [
                    'status' => self::SUCCESS,
                    'title' => 'ユーザ無効化完了',
                    'contents' => ['ユーザを無効化しました'],
                ],
                'failed' => [
                    'status' => self::ERROR,
                    'title' => 'ユーザ無効化に失敗しました',
                    'contents' => [
                        '申し訳ございません',
                        'お手数ですが、しばらく時間をおいて再度お試しください'
                    ],
                ],
                'invalid' => [
                    'status' => self::ERROR,
                    'title' => '退会処理を実行できません',
                    'contents' => [
                        '申し訳ございません',
                        '取引中の商品があるため、退会はできません'
                    ],
                ],
            ],
        ],
    ];

    // メッセージを取得するメソッド
    public static function get(string $key): ?array
    {
        $keys = explode('.', $key);
        $message = self::$messages;

        foreach ($keys as $k) {
            if (!isset($message[$k])) {
                return null;
            }
            $message = $message[$k];
        }

        return $message;
    }
}
