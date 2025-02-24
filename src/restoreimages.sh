if ! cp -p resources/test_img/item/* storage/app/public/item_images/ > /dev/null 2>&1; then
    echo 'failed to copy, trying with sudo'
    sudo cp -p resources/test_img/item/* storage/app/public/item_images/ > /dev/null 2>&1 || echo 'copy failed even with sudo'
fi