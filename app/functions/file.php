<?php

function get_file($filepath)
{
    if (!file_exists($filepath)) {
        return;
    };

    $content = file_get_contents($filepath);

    return $content ?? "";
}

function get_json_from($filepath)
{
    $content = get_file($filepath);
    $data = json_decode($content, true);

    if (!is_array($data)) {
        return;
    };

    return $data;
}

function save_json_to($filepath, $data)
{
    file_put_contents($filepath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function get_file_extension($file)
{
    return strtolower(pathinfo($file, PATHINFO_EXTENSION));
};

function check_file($fits, $file)
{
    if (in_array(get_file_extension($file), $fits)) {
        return true;
    };
    return false;
}

function get_file_type($file)
{
    if (is_file_document($file)) {
        return "document";
    } else if (is_file_media($file)) {
        return "media";
    } else if (is_file_image($file)) {
        return "image";
    } else {
        return "undefined";
    };
}

function is_file_document($file)
{
    return check_file(["pdf", "doc", "docx", "xls", "xlsx", "ppt", "pptx", "txt", "csv", "md", "json"], $file);
}

function is_file_media($file)
{
    return check_file(["mp3", "mp4", "avi", "mov", "wmv", "flv"], $file);
}

function is_file_image($file)
{
    return check_file(["jpg", "jpeg", "png", "gif", "svg", "webp"], $file);
}
