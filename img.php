<?php
    function createResizedImage(
        string $imagePath = '',
        string $newPath = '',
        int $newWidth = 0,
        int $newHeight = 0,
        int $width = 0,
        int $height = 0,
        ?string $outExt = null
    ) : ?string
    {
        if (!$newPath or !$imagePath) {
            echo 'pas bon path';
            return null;
        }

        $types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP, IMAGETYPE_WEBP];
        $type = exif_imagetype ($imagePath);

        if (!in_array ($type, $types)) {
            return null;
        }

        $outBool = in_array ($outExt, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg ($imagePath);
                if (!$outBool) $outExt = 'jpg';
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng ($imagePath);
                if (!$outBool) $outExt = 'png';
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif ($imagePath);
                if (!$outBool) $outExt = 'gif';
                break;
            case IMAGETYPE_BMP:
                $image = imagecreatefrombmp ($imagePath);
                if (!$outBool) $outExt = 'bmp';
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp ($imagePath);
                if (!$outBool) $outExt = 'webp';
        }

        $newImage = imagecreatetruecolor ($newWidth, $newHeight);

        //TRANSPARENT BACKGROUND
        $color = imagecolorallocatealpha ($newImage, 0, 0, 0, 127); //fill transparent back
        imagefill ($newImage, 0, 0, $color);
        imagesavealpha ($newImage, true);

        //ROUTINE
        imagecopyresampled ($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Rotate image on iOS
        if(function_exists('exif_read_data') && $exif = exif_read_data($imagePath, 'IFD0'))
        {
            if(isset($exif['Orientation']) && isset($exif['Make']) && !empty($exif['Orientation']) && preg_match('/(apple|ios|iphone)/i', $exif['Make'])) {
                switch($exif['Orientation']) {
                    case 8:
                        if ($width > $height) $newImage = imagerotate($newImage,90,0);
                        break;
                    case 3:
                        $newImage = imagerotate($newImage,180,0);
                        break;
                    case 6:
                        $newImage = imagerotate($newImage,-90,0);
                        break;
                }
            }
        }

        switch (true) {
            case in_array ($outExt, ['jpg', 'jpeg']): $success = imagejpeg ($newImage, $newPath);
                break;
            case $outExt === 'png': $success = imagepng ($newImage, $newPath);
                break;
            case $outExt === 'gif': $success = imagegif ($newImage, $newPath);
                break;
            case  $outExt === 'bmp': $success = imagebmp ($newImage, $newPath);
                break;
            case  $outExt === 'webp': $success = imagewebp ($newImage, $newPath);
        }

        if (!$success) {
            return null;
        }

        return $newPath;
    }

    function createArticleImg($imgPropertiesList, $articleName, $response) {
        // Set the different size

        $artcilesImgWidthList = array(
            'xs' => 260,
            's' => 394,
            'm' => 127,
            'xm' => 186,
            'l' => 267,
            'xl' => 326);

        $artcileImgWidthList = array(
            'xs' => 330,
            'm' => 510,
            'xm' => 690,
            'l' => 930,
            'xl' => 1110);

        if (!is_dir ("img/$articleName")) {
            mkdir("img/$articleName");
        }

        for ($i = 0; $i < count($imgPropertiesList); $i++) {
            try {
                $img = $imgPropertiesList[$i];
                //throw new Exception("test fail img");
                if (property_exists($img, 'uploaded') && $img->uploaded) {
                    return;
                }
                list ($width, $height) = getimagesize ($img->src);
                $imgFolderPath = "img/$articleName/img". ($i+1);

                if (!is_dir($imgFolderPath)) {
                    mkdir($imgFolderPath);
                }

                createImgByFolder($artcileImgWidthList, 'article', $imgFolderPath, $img->src, $width, $height);
                // Create only articles img for the first img
                if ($i === 0) {
                    createImgByFolder($artcilesImgWidthList, 'articles', $imgFolderPath, $img->src, $width, $height);
                    createImgByFolderCropped('structuredData', $imgFolderPath, $img->src, $width, $height);
                }
                $img->uploaded = true;
            } catch (Exception $e) {
                $img->uploaded = false;
                $response->allImgUploaded = false;
            }
        }
    }

    function createImgByFolder($sizeList, $folder, $imgFolderPath, $imgSrc, $width, $height) {

        foreach ($sizeList as $key => $newWidth) {
            $folderPath = $imgFolderPath.'/' . $folder;
            if (!is_dir($folderPath)) {
                mkdir($folderPath);
            }

            $newHeight = getNewHeight($width, $newWidth, $height);
            $newImgPath = "$folderPath/$key.jpg";

            createResizedImage($imgSrc, $newImgPath, $newWidth, $newHeight, $width, $height);
        }
    }

    function createImgByFolderCropped($folder, $imgFolderPath, $imgSrc, $width, $height) {

        $folderPath = $imgFolderPath.'/' . $folder;
        if (!is_dir($folderPath)) {
            mkdir($folderPath);
        }
        $newImgPath11 = "$folderPath/11.jpg";
        $newImgPath43 = "$folderPath/43.jpg";
        $newImgPath169 = "$folderPath/169.jpg";

        resize_image_crop($imgSrc, $newImgPath11, 550, 550, $width, $height);
        resize_image_crop($imgSrc, $newImgPath43, 731.5, 550, $width, $height);
        resize_image_crop($imgSrc, $newImgPath169, 1152, 648, $width, $height);
    }

    function getNewHeight($width, $newWidth, $height) {
        return floor($height * $newWidth / $width);
    }

    function resize_image_crop(
        string $imagePath = '',
        string $newPath = '',
        int $newWidth = 0,
        int $newHeight = 0,
        int $width = 0,
        int $height = 0,
        string $outExt = 'DEFAULT')
    {
        if (!$newPath or !$imagePath) {
            echo 'pas bon path';
            return null;
        }

        $types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP, IMAGETYPE_WEBP];
        $type = exif_imagetype ($imagePath);

        if (!in_array ($type, $types)) {
            return null;
        }

        $outBool = in_array ($outExt, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);

        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg ($imagePath);
                if (!$outBool) $outExt = 'jpg';
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng ($imagePath);
                if (!$outBool) $outExt = 'png';
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif ($imagePath);
                if (!$outBool) $outExt = 'gif';
                break;
            case IMAGETYPE_BMP:
                $image = imagecreatefrombmp ($imagePath);
                if (!$outBool) $outExt = 'bmp';
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp ($imagePath);
                if (!$outBool) $outExt = 'webp';
        }
        $newImage = imagecreatetruecolor ($newWidth, $newHeight);

        //TRANSPARENT BACKGROUND
        $color = imagecolorallocatealpha ($newImage, 0, 0, 0, 127); //fill transparent back
        imagefill ($newImage, 0, 0, $color);
        imagesavealpha ($newImage, true);

        // ROUTINE
        $original_aspect = $width / $height;
        $newImg_aspect = $newWidth / $newHeight;

        if ( $original_aspect >= $newImg_aspect )
        {
        // If image is wider than thumbnail (in aspect ratio sense)
        $new_height = $newHeight;
        $new_width = $width / ($height / $newHeight);
        }
        else
        {
        // If the thumbnail is wider than the image
        $new_width = $newWidth;
        $new_height = $height / ($width / $newWidth);
        }

        // Resize and crop
        imagecopyresampled($newImage,
                        $image,
                        0 - ($new_width - $newWidth) / 2, // Center the image horizontally
                        0 - ($new_height - $newHeight) / 2, // Center the image vertically
                        0, 0,
                        $new_width, $new_height,
                        $width, $height);


        // Rotate image on iOS
        if(function_exists('exif_read_data') && $exif = exif_read_data($imagePath, 'IFD0'))
        {
            if(isset($exif['Orientation']) && isset($exif['Make']) && !empty($exif['Orientation']) && preg_match('/(apple|ios|iphone)/i', $exif['Make'])) {
                switch($exif['Orientation']) {
                    case 8:
                        if ($width > $height) $newImage = imagerotate($newImage,90,0);
                        break;
                    case 3:
                        $newImage = imagerotate($newImage,180,0);
                        break;
                    case 6:
                        $newImage = imagerotate($newImage,-90,0);
                        break;
                }
            }
        }

        switch (true) {
            case in_array ($outExt, ['jpg', 'jpeg']): $success = imagejpeg ($newImage, $newPath);
                break;
            case $outExt === 'png': $success = imagepng ($newImage, $newPath);
                break;
            case $outExt === 'gif': $success = imagegif ($newImage, $newPath);
                break;
            case  $outExt === 'bmp': $success = imagebmp ($newImage, $newPath);
                break;
            case  $outExt === 'webp': $success = imagewebp ($newImage, $newPath);
        }

        if (!$success) {
            return null;
        }

        return $newPath;
    }
?>