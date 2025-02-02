<?php

namespace App\Traits;

use Image;

trait CompressImage
{
    /**
     * Compress image
     *
     * @param string $imagePath
     * @param int $width
     * @param int $height
     * @param string $mime
     * @param array $quality // jpeg, png
     * @return Image $resizedImage
     */

    public function compressImage(
        $imagePath,
        $width,
        $height,
        $mime,
        $quality = ['jpeg' => 75, 'png' => 75]
    )
    {
        $resizedImage = Image::make($imagePath)
        ->resize($width, $height, function ($constraint) {
          $constraint->aspectRatio();
          $constraint->upsize();
        });

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $resizedImage->encode('jpg', $quality['jpeg']);
                break;
            case 'image/png':
                $resizedImage->encode('png', $quality['png']);
                break;
        }

        return $resizedImage;
    }
}