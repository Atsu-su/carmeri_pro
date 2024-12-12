#!/bin/bash
profile_path="storage/app/public/profile_images"
test_path="tests/test_images/profile_images"

sudo cp -pf "$test_path"/* "$profile_path" > /dev/null 2>&1
if [ $? -ne 0 ]; then
  cp -pf "$test_path"/* "$profile_path"
  if [ $? -ne 0 ]; then
    echo "=================================================="
    echo "プロフィール画像の復元処理（copy）に失敗しました"
    echo "処理を終了します"
    echo "=================================================="
    exit 1
  fi
fi
echo "初期化処理が完了しました"