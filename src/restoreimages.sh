if ! cp -p resources/test_img/item/* storage/app/public/item_images/ > /dev/null 2>&1; then
    echo 'failed to copy item images, trying with sudo'
    sudo cp -p resources/test_img/item/* storage/app/public/item_images/ > /dev/null 2>&1 || echo 'copy item images failed even with sudo'
fi

if ! cp -p resources/test_img/profile/* storage/app/public/profile_images/ > /dev/null 2>&1; then
    echo 'failed to copy profile images, trying with sudo'
    sudo cp -p resources/test_img/profile/* storage/app/public/profile_images/ > /dev/null 2>&1 || echo 'copy profile images failed even with sudo'
fi
