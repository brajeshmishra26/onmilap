<?php
include 'fns/filters/load.php';
include 'fns/files/load.php';
include_once 'fns/cloud_storage/load.php';

$noerror = true;
$disabled = 0;
$user_id = Registry::load('current_user')->id;
$all_files = array();
$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';
$result['highlight'] = [];
$generate_pdf_preview = false;

if (extension_loaded('imagick')) {
    $generate_pdf_preview = true;
}

$attach_files_request = false;

if (isset($private_data["attach_files_request"]) && $private_data["attach_files_request"]) {
    $attach_files_request = true;
}

if ($force_request || $attach_files_request || role(['permissions' => ['storage' => 'upload_files']])) {
    $pdf_file_formats = ['application/pdf', 'application/x-pdf', 'application/acrobat'];
    $audio_file_formats = ['audio/wav', 'audio/mpeg', 'audio/mp4', 'audio/webm', 'audio/ogg', 'audio/x-wav'];
    $image_file_formats = [
        'image/jpeg', 'image/png', 'image/x-png', 'image/gif', 'image/bmp', 'image/x-ms-bmp', 'image/webp',
        'image/svg+xml', 'image/avif'
    ];
    $video_file_formats = ['video/mp4', 'video/mpeg', 'video/ogg', 'video/webm', 'video/x-matroska'];
    $doc_file_formats = ['text/comma-separated-values', 'application/msword', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        'application/pdf', 'application/mspowerpoint', 'application/vnd.ms-powerpoint', 'application/mspowerpoint',
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/mspowerpoint', 'application/mspowerpoint', 'text/rtf', 'text/plain', 'application/msexcel', 'application/vnd.ms-excel',
        'application/msexcel', 'application/vnd.ms-excel', 'application/msexcel', 'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template', 'application/vnd.ms-excel',
        'application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.oasis.opendocument.spreadsheet-template',
        'application/vnd.oasis.opendocument.presentation-template'
    ];

    if (isset(Registry::load('settings')->ffmpeg) && Registry::load('settings')->ffmpeg === 'enable') {
        $video_file_formats = ['video/3gpp', 'video/mp4', 'video/mpeg', 'video/ogg', 'video/quicktime', 'video/webm', 'video/x-m4v',
            'video/ms-asf', 'video/x-ms-wmv', 'video/x-msvideo', 'video/x-matroska'];
    }

    $disallowed_file_formats = role(['find' => 'disallowed_file_formats']);

    if (!empty($disallowed_file_formats)) {
        $disallowed_file_formats = preg_split("/[\r\n,]+/", $disallowed_file_formats, -1, PREG_SPLIT_NO_EMPTY);
        $disallowed_file_formats = array_map('trim', $disallowed_file_formats);
    }

    $max_file_upload_size = role(['find' => 'max_file_upload_size']);
    $maximum_storage_space = role(['find' => 'maximum_storage_space']);

    if (empty($max_file_upload_size)) {
        $max_file_upload_size = 0;
    }

    if (empty($maximum_storage_space)) {
        $maximum_storage_space = 0;
    }

    if (!$force_request) {
        if (isset($_FILES['file_attachments']['name']) && !empty($_FILES['file_attachments']['name'])) {
            if (isset($_FILES['file_attachments']['size']) && is_array($_FILES['file_attachments']['size'])) {
                $fileSizes = array_filter($_FILES['file_attachments']['size'], function ($size) {
                    return is_numeric($size);
                });

                $totalFileSize = array_sum($fileSizes);
                $totalFileSize = number_format($totalFileSize / 1048576, 2);
            } else {
                $totalFileSize = 0;
            }

            if ($totalFileSize > $max_file_upload_size) {
                $noerror = false;
                $result = array();
                $result['success'] = false;
                $result['error_message'] = Registry::load('strings')->exceeded_max_file_upload_size;
                $result['error_key'] = 'exceeded_max_file_upload_size';
            } else {
                $location = 'assets/files/storage/'.$user_id.'/files/';

                if (Registry::load('settings')->cloud_storage !== 'disable') {
                    $storage_space = cloud_storage_module(['get_size' => $location, 'load_from_cache' => true]);
                } else {
                    $storage_space = files('getsize', ['getsize_of' => $location, 'real_path' => true, 'original_value' => true]);
                }

                $storage_space = (int)$storage_space;
                if (empty($storage_space)) {
                    $storage_space = 0;
                }

                $storage_space = is_numeric($storage_space) ? $storage_space : 0;
                $storage_space = number_format(floatval($storage_space) / 1048576, 2);
                $totalFileSize = is_numeric($totalFileSize) ? $totalFileSize : 0;
                $storage_space = (float)$storage_space + (float)$totalFileSize;

                if ($storage_space > $maximum_storage_space) {
                    $noerror = false;
                    $result = array();
                    $result['success'] = false;
                    $result['error_message'] = Registry::load('strings')->storage_limit_exceeded;
                    $result['error_key'] = 'storage_limit_exceeded';
                }
            }

            if (!empty($disallowed_file_formats)) {
                foreach ($_FILES['file_attachments']['type'] as $index => $mime_type) {
                    if (in_array($mime_type, $disallowed_file_formats)) {
                        $noerror = false;
                        $result = array();
                        $result['success'] = false;
                        $result['error_message'] = Registry::load('strings')->contains_disallowed_file_types;
                        $result['error_key'] = 'contains_disallowed_file_types';
                        break;
                    }
                }
            }
        }
    }


    if ($noerror) {
        if (isset($data['user_id'])) {
            if ($force_request || role(['permissions' => ['storage' => 'super_privileges']])) {
                $data['user_id'] = filter_var($data['user_id'], FILTER_SANITIZE_NUMBER_INT);
                if (!empty($data['user_id'])) {
                    $user_id = $data['user_id'];
                }
            }
        }

        $location = 'assets/files/storage/'.$user_id.'/files/';
        $thumbnails_folder = 'assets/files/storage/'.$user_id.'/thumbnails/';

        $user_filePath = 'assets/cache/user_storage/'.$user_id.'.cache';

        if (file_exists($user_filePath)) {
            unlink($user_filePath);
        }

        if (!file_exists($location)) {
            mkdir($location, 0755, true);
        }

        if (!file_exists($thumbnails_folder)) {
            mkdir($thumbnails_folder, 0755, true);
        }

        if (isset($_FILES['file_attachments']['name']) && !empty($_FILES['file_attachments']['name'])) {
            $upload_info = [
                'upload' => 'file_attachments',
                'folder' => $location,
                'prepend_random_string' => true,
                'real_path' => true,
                'sanitize_filename' => true,
                'multi_upload' => true,
            ];

            if (!$force_request && !role(['permissions' => ['allowed_file_formats' => 'all_file_formats']])) {
                $upload_info['only_allow'] = array();

                if (role(['permissions' => ['allowed_file_formats' => 'image_files']])) {
                    $upload_info['only_allow'] = array_merge($upload_info['only_allow'], $image_file_formats);
                }

                if (role(['permissions' => ['allowed_file_formats' => 'video_files']])) {
                    $upload_info['only_allow'] = array_merge($upload_info['only_allow'], $video_file_formats);
                }

                if (role(['permissions' => ['allowed_file_formats' => 'audio_files']])) {
                    $upload_info['only_allow'] = array_merge($upload_info['only_allow'], $audio_file_formats);
                }

                if (role(['permissions' => ['allowed_file_formats' => 'documents']])) {
                    $upload_info['only_allow'] = array_merge($upload_info['only_allow'], $doc_file_formats);
                }

                if (empty($upload_info['only_allow'])) {
                    $upload_info['only_allow'] = ['disallow/disallow'];
                }
            }

            $files = files('upload', $upload_info);

            if ($files['result']) {
                if (isset($files['files'])) {
                    foreach ($files['files'] as $index => $file) {
                        $file_type = $file['file_type'];
                        $attachment_type = 'other_files';

                        $file_name = basename($file['file']);
                        $file_name = explode(Registry::load('config')->file_seperator, $file_name, 2);

                        if ($file_type === 'video/x-matroska') {
                            $file_type = 'video/webm';
                        }

                        if (isset($file_name[1])) {
                            $file_name = $file_name[1];
                        } else {
                            $file_name = $file_name[0];
                        }

                        if (isset(Registry::load('settings')->image_moderation) && Registry::load('settings')->image_moderation !== 'disable') {
                            if (in_array($file_type, $image_file_formats)) {

                                include_once('fns/image_moderation/load.php');

                                $image_location = $file['file'];
                                $image_moderation = moderate_image_content($image_location);

                                if (!$image_moderation['success']) {
                                    if (file_exists($image_location)) {
                                        unlink($image_location);
                                    }
                                    continue;
                                }
                            }
                        }


                        if (in_array($file_type, $image_file_formats)) {

                            if ($file_type !== 'image/svg+xml') {
                                list($orginal_width, $orginal_height) = getimagesize($file['file']);

                                if ((int)$orginal_height < 20 || (int)$orginal_width < 20) {
                                    if (file_exists($file['file'])) {
                                        unlink($file['file']);
                                    }
                                    continue;
                                }
                            }

                            $attachment_type = 'image_files';
                            $resize = [
                                'resize' => $file['file'],
                                'width' => 300,
                                'crop' => false,
                                'real_path' => true,
                                'saveas' => 'assets/files/storage/'.$user_id.'/thumbnails/'.basename($file['file'])
                            ];

                            if (files('resize_img', $resize)) {
                                $all_files[$attachment_type][$index]['thumbnail'] = $resize['saveas'];

                                if (isset(Registry::load('settings')->compress_image_files) && Registry::load('settings')->compress_image_files === 'enable') {

                                    $image_type = $file_type;
                                    if ($image_type === 'image/jpeg' || $image_type === 'image/png' || $image_type === 'image/bmp' || $image_type === 'image/x-ms-bmp') {

                                        $webp_file_name = pathinfo($file['file'], PATHINFO_FILENAME);
                                        $webp_file_name = 'assets/files/storage/'.$user_id.'/files/'.$webp_file_name.'.webp';

                                        $create_webp = [
                                            'file' => $file['file'],
                                            'real_path' => true,
                                            'saveas' => $webp_file_name
                                        ];


                                        if (files('create_webp', $create_webp)) {

                                            if (file_exists($file['file'])) {
                                                unlink($file['file']);
                                            }

                                            $file['file'] = $webp_file_name;

                                            $thumb_webp_file_name = pathinfo($resize['saveas'], PATHINFO_FILENAME);
                                            $thumb_webp_file_copy = 'assets/files/storage/'.$user_id.'/thumbnails/'.$thumb_webp_file_name.'_copy.webp';
                                            $thumb_webp_file_name = 'assets/files/storage/'.$user_id.'/thumbnails/'.$thumb_webp_file_name.'.webp';

                                            $create_webp = [
                                                'file' => $resize['saveas'],
                                                'real_path' => true,
                                                'saveas' => $thumb_webp_file_name
                                            ];
                                            if (files('create_webp', $create_webp)) {

                                                if (file_exists($resize['saveas'])) {
                                                    unlink($resize['saveas']);
                                                }

                                                $all_files[$attachment_type][$index]['thumbnail'] = $thumb_webp_file_name;
                                            }
                                        }
                                    }
                                }
                            }
                        } else if ($generate_pdf_preview && in_array($file_type, $pdf_file_formats)) {

                            $pdfFilePath = $file['file'];
                            $pdf_thumnail = pathinfo($pdfFilePath, PATHINFO_FILENAME);
                            $pdf_thumnail = 'assets/files/storage/'.$user_id.'/thumbnails/'.$pdf_thumnail.'.png';
                            file_put_contents($pdf_thumnail, 'empty_file');

                            try {

                                $pdfFilePath = str_replace('\\', '/', realpath($pdfFilePath));
                                $pdfFileThumbDesc = str_replace('\\', '/', realpath($pdf_thumnail));

                                if (file_exists($pdfFilePath)) {
                                    $imagick = new Imagick();
                                    $imagick->setResolution(72, 72);
                                    $imagick->readImage($pdfFilePath . '[0]');
                                    $imagick->setImageFormat('png');
                                    $imagick->cropThumbnailImage(200, 300);
                                    $imagick->writeImage($pdfFileThumbDesc);
                                    $imagick->clear();
                                    $imagick->destroy();

                                    $attachment_type = 'pdf_files';
                                    $all_files[$attachment_type][$index]['thumbnail'] = $pdf_thumnail;

                                }
                            } catch (Exception $e) {
                                continue;
                            }
                        } elseif (in_array($file_type, $audio_file_formats)) {
                            $attachment_type = 'audio_files';

                            if (isset(Registry::load('settings')->compress_audio_files) && Registry::load('settings')->compress_audio_files === 'enable') {
                                if (isset(Registry::load('settings')->ffmpeg) && Registry::load('settings')->ffmpeg === 'enable') {
                                    $save_in = $location.pathinfo($file['file'], PATHINFO_FILENAME).'_compressed.mp3';
                                    $final_name = $location.pathinfo($file['file'], PATHINFO_FILENAME).'.mp3';


                                    $audio_file_path = $file['file'];
                                    $bitrate = '192k';
                                    $ffmpegCommand = "ffmpeg -i \"$audio_file_path\" -b:a $bitrate \"$save_in\"";
                                    $exe_ffmpeg = exec($ffmpegCommand, $ff_output, $returnCode);


                                    if (file_exists($save_in)) {
                                        unlink($file['file']);
                                        rename($save_in, $final_name);
                                        $file['file'] = $final_name;
                                    }
                                }
                            }

                        } elseif (in_array($file_type, $video_file_formats)) {
                            $attachment_type = 'video_files';

                            $all_files[$attachment_type][$index]['thumbnail'] = 'assets/files/defaults/video_thumb.jpg';

                            if (isset(Registry::load('settings')->ffmpeg) && Registry::load('settings')->ffmpeg !== 'enable') {
                                if (isset($file['file_thumbnail'])) {
                                    $thumbnail = 'assets/files/storage/'.$user_id.'/thumbnails/'.pathinfo($file['file'], PATHINFO_FILENAME).'.jpg';
                                    file_put_contents($thumbnail, $file['file_thumbnail']);

                                    $thumbnail_resize = [
                                        'resize' => $thumbnail,
                                        'width' => 300,
                                        'crop' => false,
                                        'real_path' => true
                                    ];

                                    files('resize_img', $thumbnail_resize);

                                    if (file_exists($thumbnail)) {
                                        $all_files[$attachment_type][$index]['thumbnail'] = $thumbnail;
                                    }
                                }
                            } else {
                                $save_in = $location.pathinfo($file['file'], PATHINFO_FILENAME).'.mp4';
                                $thumbnail = 'assets/files/storage/'.$user_id.'/thumbnails/'.pathinfo($file['file'], PATHINFO_FILENAME).'.jpg';

                                $inputFile = $file['file'];
                                $file_converted = true;

                                $ffmpegCommand = "ffmpeg -i $inputFile -ss 00:00:03 -vframes 1 $thumbnail";
                                exec($ffmpegCommand, $output_thumb, $returnCode);

                                if ($file_type !== 'video/mp4') {

                                    $fileExtension = pathinfo($inputFile, PATHINFO_EXTENSION);

                                    if (strtolower($fileExtension) === 'mp4') {
                                        $newFile = preg_replace('/\.mp4$/i', '.mov', $inputFile);
                                        rename($inputFile, $newFile);
                                        $inputFile = $newFile;
                                    }

                                    $ffmpegCommand = "ffmpeg -i $inputFile -c:v libx264 -c:a aac -strict experimental $save_in";
                                    exec($ffmpegCommand, $output_video_convert, $returnCode);

                                    if ((int)$returnCode !== 0) {
                                        $ffmpegCommand = "ffmpeg -i $inputFile -c copy $save_in";
                                        exec($ffmpegCommand, $output_video_convert, $returnCode);

                                        if (file_exists($save_in) && filesize($save_in) === 0) {
                                            $file_converted = false;
                                            unlink($save_in);
                                        }
                                    }

                                }

                                $thumbnail_resize = [
                                    'resize' => $thumbnail,
                                    'width' => 300,
                                    'crop' => false,
                                    'real_path' => true
                                ];

                                files('resize_img', $thumbnail_resize);

                                if (isset($inputFile) && $file_converted && $file_type !== 'video/mp4') {
                                    unlink($inputFile);
                                }

                                if ($file_converted && $file_type !== 'video/mp4') {
                                    $file['file'] = $save_in;
                                }

                                if ($file_converted && $file_type !== 'video/mp4') {
                                    $file_name = pathinfo($file_name, PATHINFO_FILENAME).'.mp4';
                                }

                                if ($file_converted) {
                                    $file_type = 'video/mp4';
                                }

                                $all_files[$attachment_type][$index]['thumbnail'] = $thumbnail;

                                if ($file_converted && isset(Registry::load('settings')->compress_video_files) && Registry::load('settings')->compress_video_files === 'enable') {

                                    $save_in = $location.pathinfo($file['file'], PATHINFO_FILENAME).'_compressed.mp4';

                                    $inputFile = $file['file'];
                                    $ffmpeg_path = Registry::load('settings')->ffmpeg_binaries_path;
                                    $ffmpegCommand = "ffmpeg -i $inputFile -c:v libx264 -crf 23 -c:a aac -b:a 128k $save_in";
                                    exec($ffmpegCommand, $ff_output, $returnCode);


                                    if (file_exists($save_in)) {
                                        unlink($file['file']);
                                        rename($save_in, $file['file']);
                                    }
                                }

                            }
                        }


                        $all_files[$attachment_type][$index]['name'] = $file_name;

                        if (strlen($file_name) > 15) {
                            $all_files[$attachment_type][$index]['trimmed_name'] = trim(mb_substr($file_name, 0, 8)).'...'.mb_substr($file_name, -8);
                        } else {
                            $all_files[$attachment_type][$index]['trimmed_name'] = $file_name;
                        }

                        $all_files[$attachment_type][$index]['file'] = $file['file'];
                        $all_files[$attachment_type][$index]['file_type'] = $file_type;
                        $all_files[$attachment_type][$index]['file_size'] = files('getsize', ['getsize_of' => $file['file'], 'real_path' => true]);

                        if (Registry::load('settings')->cloud_storage !== 'disable') {

                            cloud_storage_module(['upload_file' => $file['file'], 'delete' => true]);

                            if (isset($all_files[$attachment_type][$index]['thumbnail'])) {
                                $upload_thumbnail = $all_files[$attachment_type][$index]['thumbnail'];

                                $basename = basename($upload_thumbnail);

                                if ($basename !== 'video_thumb.jpg') {
                                    cloud_storage_module(['upload_file' => $upload_thumbnail, 'delete' => true]);
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($data['user_id'])) {
                $current_time_stamp = Registry::load('current_user')->time_stamp;
                DB::connect()->update('site_users', ['updated_on' => $current_time_stamp], ['user_id' => $data['user_id']]);
            }

            $result = array();
            $result['success'] = true;

            if (isset($data['frontend'])) {
                $result['todo'] = 'reload';
                $result['reload'] = 'site_user_files';
            } else {
                $result['files'] = $all_files;
            }
        }
    }
}