<?php

namespace Uploader;

class Image
{
    public static function upload($file, $targetDir)
    {
        $filename = uniqid(session_id() . "_", true) . ".jpg";
        $filepath = PATH($targetDir . $filename);
        $filetype = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

        $check = getimagesize($file["tmp_name"]);
        if ($check === false) {
            return [
                "status" => "error",
                "message" => "Type: Not Image."
            ];
        };

        if ($file["size"] > 5 * 1024 * 1024) {
            return [
                "status" => "error",
                "message" => "Size: Over 5MB."
            ];
        };

        if (!in_array($filetype, ["jpg", "jpeg", "png", "gif", "webp"])) {
            return [
                "status" => "error",
                "message" => "Type: Only JPG, JPEG, PNG, GIF, Webp."
            ];
        };

        switch ($filetype) {
            case "jpeg":
            case "jpg":
                $image = imagecreatefromjpeg($file["tmp_name"]);
                break;
            case "png":
                $image = imagecreatefrompng($file["tmp_name"]);
                break;
            case "gif":
                $image = imagecreatefromgif($file["tmp_name"]);
                break;
            case "webp":
                $image = imagecreatefromwebp($file["tmp_name"]);
                break;
            default:
                return [
                    "status" => "error",
                    "message" => "Type: Only JPG, JPEG, PNG, GIF, Webp."
                ];
        };

        if (!$image) {
            return [
                "status" => "error",
                "message" => "Error: Loading error."
            ];
        };

        $width = imagesx($image);
        $height = imagesy($image);

        $new_image = imagecreatetruecolor($width, $height);
        imagefill($new_image, 0, 0, imagecolorallocate($new_image, 255, 255, 255));
        imagecopy($new_image, $image, 0, 0, 0, 0, $width, $height);

        if (imagejpeg($new_image, $filepath)) {
            imagedestroy($image);
            imagedestroy($new_image);
            return [
                "filename" => $filename,
                "filepath" => $targetDir . $filename
            ];
        } else {
            imagedestroy($image);
            imagedestroy($new_image);
            return;
        };
    }
}
