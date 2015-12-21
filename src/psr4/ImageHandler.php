<?php

namespace Amostajo\Wordpress;

use File;
use Eventviva\ImageResize;
use Amostajo\WPPluginCore\Cache;

/**
 * Image handler for Wordpress.
 * Alternative to Wordpress' image editor.
 *
 * @author Alejandro Mostajo <http://about.me/amostajo>
 * @license MIT
 * @package 
 * @version 1.0.0
 */
class ImageHandler
{
    /**
     * Returns a thumb url based on the url an size provided.
     *
     * @param string $url    Base url.
     * @param int    $width  Returned image width.
     * @param int    $height Returned image height.
     *
     * @return string
     */
    public static function thumb($url, $width = 0, $height = 0)
    {
        if (empty($width) || !is_numeric($width)) $width = get_option('thumbnail_size_w');
        if (empty($height) || !is_numeric($height)) $height = get_option('thumbnail_size_h');
        $info = pathinfo($url);
        if (!isset($info['extension'])) {
            $uniqid = explode('&', $info['filename']);
            $info['filename'] = $uniqid[count($uniqid) - 1];
            $info['extension'] = 'jpg';
        }
        $info['extension'] = explode('?', $info['extension'])[0];
        $cacheKey = preg_replace(
            [
                '/:filename/',
                '/:width/',
                '/:height/',
            ], 
            [
                $info['filename'],
                $width,
                $height
            ], 
            get_option('thumbnail_cache_format', ':filename_:widthx:height')
        );
        return Cache::remember(
            $cacheKey,
            get_option('thumbnail_cache_minutes', 43200),
            function () use($url, $width, $height, $info) {
                $upload_dir = wp_upload_dir();
                $assetPath = sprintf(
                        '/%s_%sx%s.%s',
                        $info['filename'],
                        $width,
                        $height,
                        $info['extension']
                );
                if (!file_exists($upload_dir['path'] . $assetPath)) {
                    $image = new ImageResize($url);
                    /// Process image
                    $size = getimagesize($url);
                    // Resize to fit wanted width is too small
                    if ($size[0] < $width) {
                        $scaledPath = sprintf(
                            '/%s_%sx.%s',
                            $info['filename'],
                            $width,
                            $info['extension']
                        );
                        $image->interlace = 1;
                        $image->scale(ceil(100 + ((($width - $size[0]) / $size[0]) * 100)));
                        $image->save($upload_dir['path'] . $scaledPath);
                        $image = new ImageResize( $upload_dir['url'] . $scaledPath );
                        $size = getimagesize( $upload_dir['url'] . $scaledPath );
                    }
                    // Resize to fit wanted height is too small
                    if ($size[1] < $height) {
                        $scaledPath = sprintf(
                            '/%s_x%s.%s',
                            $info['filename'],
                            $height,
                            $info['extension']
                        );
                        $image->interlace = 1;
                        $image->scale(ceil(100 + ((($height - $size[1]) / $size[1]) * 100)));
                        $image->save($upload_dir['path'] . $scaledPath);
                        $image = new ImageResize( $upload_dir['url'] . $scaledPath );
                    }
                    // Final crop
                    $image->crop($width, $height);
                    $image->save($upload_dir['path'] . $assetPath);
                }
                return $upload_dir['url'] . $assetPath;
            }
        );
    }

    /**
     * Returns a resized image url.
     * Resized on width constraint.
     *
     * @param string $url   Base url.
     * @param int    $width Width to resize to.
     *
     * @return string
     */
    public static function width($url, $width = 0)
    {
        if (empty($width) || !is_numeric($width)) $width = get_option('thumbnail_size_w');
        $info = pathinfo($url);
        $cacheKey = preg_replace(
            [
                '/:filename/',
                '/:width/',
                '/:height/',
            ], 
            [
                $info['filename'],
                $width,
                '',
            ], 
            get_option('thumbnail_cache_format', ':filename_:widthx:height')
        );
        return Cache::remember(
            $cacheKey,
            get_option('thumbnail_cache_minutes', 43200),
            function () use($url, $width, $info) {
                $upload_dir = wp_upload_dir();
                $size = getimagesize($url);
                $assetPath = sprintf(
                    '/%s_%sx.%s',
                    $info['filename'],
                    $width,
                    $info['extension']
                );
                if (!file_exists($upload_dir['path'] . $assetPath)) {
                    $image = new ImageResize($url);
                    $image->interlace = 1;
                    $image->scale(ceil(100 + ((($width - $size[0]) / $size[0]) * 100)));
                    $image->save($upload_dir['path'] . $assetPath);
                }
                return $upload_dir['url'] . $assetPath;
            }
        );
    }
    /**
     * Returns a resized image url.
     * Resized on height constraint.
     *
     * @param string $url    Base url.
     * @param int    $height Height to resize to.
     *
     * @return string
     */
    public static function height($url, $height = 0)
    {
        if (empty($height) || !is_numeric($height)) $height = get_option('thumbnail_size_h');
        $info = pathinfo($url);
        $cacheKey = preg_replace(
            [
                '/:filename/',
                '/:width/',
                '/:height/',
            ], 
            [
                $info['filename'],
                '',
                $height,
            ], 
            get_option('thumbnail_cache_format', ':filename_:widthx:height')
        );
        return Cache::remember(
            $cacheKey,
            get_option('thumbnail_cache_minutes', 43200),
            function () use($url, $height, $info) {
                $upload_dir = wp_upload_dir();
                $size = getimagesize($url);
                $assetPath = sprintf(
                    '/%s_x%s.%s',
                    $info['filename'],
                    $height,
                    $info['extension']
                );
                if (!file_exists($upload_dir['path'] . $assetPath)) {
                    $image = new ImageResize($url);
                    $image->interlace = 1;
                    $image->scale(ceil(100 + ((($height - $size[1]) / $size[1]) * 100)));
                    $image->save($upload_dir['path'] . $assetPath);
                }
                return $upload_dir['url'] . $assetPath;
            }
        );
    }
}