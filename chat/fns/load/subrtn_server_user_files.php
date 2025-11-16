<?php
$user_files = [];
$location = 'assets/files/storage/'.$user_id.'/files/';

if (!empty($data["search"])) {
    $data['search'] = sanitize_filename($data['search']);
}

$extensionPattern = '';

if (file_exists($location)) {
    $iterator = new DirectoryIterator($location);

    if ($data["filter"] === 'images') {
        $extensions = ['jpg', 'png', 'gif', 'jpeg', 'bmp', 'webp'];
        $extensionPattern = '/\.(?:' . implode('|', $extensions) . ')$/i';
    } else if ($data["filter"] === 'videos') {
        $extensions = ['mp4', 'mpeg', 'ogg', 'webm', 'mov'];
        $extensionPattern = '/\.(?:' . implode('|', $extensions) . ')$/i';
    } else if ($data["filter"] === 'audio') {
        $extensions = ['oga', 'mp3', 'wav'];
        $extensionPattern = '/\.(?:' . implode('|', $extensions) . ')$/i';
    } else if ($data["filter"] === 'others') {
        $extensions = ['mp4', 'mpeg', 'ogg', 'webm', 'mov', 'jpg', 'png', 'gif', 'jpeg', 'bmp', 'oga', 'mp3', 'wav', 'webp'];
    }

    foreach ($iterator as $fileinfo) {

        $skip_file = false;

        if ($fileinfo->isDot() || !$fileinfo->isFile()) {
            continue;
        }

        $filename = $fileinfo->getFilename();

        if (!empty($data['search']) && stripos($filename, $data['search']) === false) {
            continue;
        }

        if ($data["filter"] === 'others') {

            $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($fileExtension, $extensions)) {
                $skip_file = true;
            }
        } else if ($extensionPattern && !preg_match($extensionPattern, $filename)) {
            $skip_file = true;
        }

        if (!$skip_file) {
            $user_files[] = $fileinfo->getPathname();
        }
    }

    usort($user_files, function($file1, $file2) {
        return filemtime($file2) <=> filemtime($file1);
    });

    $user_files = array_slice($user_files, $data["offset"], Registry::load('settings')->records_per_call);
}

$files = array();
$array_index = 1;

foreach ($user_files as $file) {

    $file_name = $files[$array_index]['file_basename'] = basename($file);
    $file_name = explode('-gr-', $file_name, 2);

    if (isset($file_name[1])) {
        $files[$array_index]['file_name'] = $file_name[1];
    } else {
        $files[$array_index]['file_name'] = basename($file);
    }

    $files[$array_index]['file_size'] = files('getsize', ['getsize_of' => $file, 'real_path' => true]);


    $files[$array_index]['file_type'] = $file_type = mime_content_type($file);
    $files[$array_index]['file_path'] = Registry::load('config')->site_url.$file;

    $file_extension_img = "assets/files/file_extensions/".pathinfo($file, PATHINFO_EXTENSION).".png";
    $thumbnail = null;
    $files[$array_index]['file_format'] = 'others';

    if (in_array($file_type, $video_file_formats)) {
        $thumbnail = 'assets/files/storage/'.$user_id.'/thumbnails/'.pathinfo($file, PATHINFO_FILENAME).'.jpg';
        $files[$array_index]['file_format'] = 'video';
    } else if (in_array($file_type, $image_file_formats)) {
        $thumbnail = 'assets/files/storage/'.$user_id.'/thumbnails/'.basename($file);
        $files[$array_index]['file_format'] = 'image';
    } else if (in_array($file_type, $audio_file_formats)) {
        $files[$array_index]['file_format'] = 'audio';
    } else if (in_array($file_type, $pdf_file_formats)) {
        $files[$array_index]['file_format'] = 'pdf_file';
    }

    if (!empty($thumbnail) && file_exists($thumbnail)) {
        $files[$array_index]['thumbnail'] = Registry::load('config')->site_url.$thumbnail;
    } else if (file_exists($file_extension_img)) {
        $files[$array_index]['thumbnail'] = Registry::load('config')->site_url.$file_extension_img;
    } else {
        $files[$array_index]['thumbnail'] = $unknown_file_extension;
    }


    $array_index++;
}
?>