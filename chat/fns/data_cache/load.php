<?php

function data_cache($parameters) {
    static $useRedis = null;
    static $redis = null;
    
    if(!isset($parameters['filename'])){
        $parameters['filename'] = '';
    }

    $lockFile = 'assets/cache/redis_disabled.lock';
    $folder = 'assets/cache/files_cache/' . $parameters['folder'];
    $filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $parameters['filename']);
    $method = strtolower($parameters['method']);
    $filepath = $folder . '/' . $filename . '.cache';
    $redisKey = str_replace('/', ':', $parameters['folder']) . ':' . $filename;

    $redis_host = Registry::load('config')->redis_host;
    $redis_port = Registry::load('config')->redis_port;
    $redis_password = Registry::load('config')->redis_password;

    if ($method === 'add') {
        $method = 'set';
    }

    if ($method === 'reset_redis') {
        if (file_exists($lockFile)) {
            unlink($lockFile);
            return true;
        }
        return false;
    }

    if (!Registry::load('config')->enable_redis) {
        $useRedis = false;
    }

    if (isset($parameters['fs_cache']) && $parameters['fs_cache']) {
        $useRedis = false;
    }

    if ($useRedis === null) {
        if (file_exists($lockFile)) {
            $useRedis = false;
        } elseif (class_exists('Redis')) {
            try {
                $redis = new Redis();
                $redis->connect($redis_host, $redis_port);

                if (!empty($redis_password)) {
                    $redis->auth($redis_password);
                }

                $useRedis = true;
            } catch (Exception $e) {
                $useRedis = false;
                file_put_contents($lockFile, 'Redis disabled on: ' . date('Y-m-d H:i:s'));
            }
        } else {
            $useRedis = false;
            file_put_contents($lockFile, 'Redis class not found');
        }
    }

    if ($useRedis) {
        switch ($method) {
            case 'set':
                if (isset($parameters['data'])) {
                    $redis->set($redisKey, json_encode($parameters['data'], JSON_PRETTY_PRINT));
                }
                break;

            case 'append':
                if (isset($parameters['data']) && is_array($parameters['data'])) {
                    $existingData = [];
                    if ($redis->exists($redisKey)) {
                        $existingData = json_decode($redis->get($redisKey), true);
                    }

                    if (!is_array($existingData)) {
                        $existingData = [];
                    }

                    $mergedData = array_merge($existingData, $parameters['data']);
                    $redis->set($redisKey, json_encode($mergedData, JSON_PRETTY_PRINT));
                }
                break;

            case 'delete':
                $redis->del($redisKey);
                break;

            case 'get':
                if ($redis->exists($redisKey)) {
                    $content = $redis->get($redisKey);
                    return json_decode($content, true);
                }
                return null;
        }
        return true;
    }

    return handle_file_cache($parameters, $method, $filepath);
}

function handle_file_cache($parameters, $method, $filepath) {
    $folder = 'assets/cache/files_cache/' . $parameters['folder'];

    if (in_array($method, ['set', 'append']) && !is_dir($folder)) {
        mkdir($folder, 0755, true);
    }

    switch ($method) {
        case 'set':
            if (isset($parameters['data'])) {
                file_put_contents($filepath, json_encode($parameters['data'], JSON_PRETTY_PRINT));
            }
            break;

        case 'append':
            $existingData = [];
            if (file_exists($filepath)) {
                $existingData = json_decode(file_get_contents($filepath), true);
            }

            if (!is_array($existingData)) {
                $existingData = [];
            }

            if (isset($parameters['data']) && is_array($parameters['data'])) {
                $mergedData = array_merge($existingData, $parameters['data']);
                file_put_contents($filepath, json_encode($mergedData, JSON_PRETTY_PRINT));
            }
            break;

        case 'delete':
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            break;
            
        case 'clear_all':
            if (is_dir($folder)) {
                $files = glob($folder . '/*.cache');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                @rmdir($folder);
            }
            break;

        case 'get':
            if (file_exists($filepath)) {
                $content = file_get_contents($filepath);
                return json_decode($content, true);
            }
            return null;
    }

    return true;
}