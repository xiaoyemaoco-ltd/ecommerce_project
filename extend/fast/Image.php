<?php
/**
 * Created by PhpStorm.
 * User: LENOVO
 * Date: 2020/9/7
 * Time: 21:00
 */
namespace fast;
use think\facade\Config;
/**
  *  基本图片处理，用于完成图片缩入，水印添加
  *  当水印图超过目标图片尺寸时，水印图能自动适应目标图片而缩小
  *  水印图可以设置跟背景的合并度
  *  Copyright(c) 2005 by ustb99. All rights reserved
  *  To contact the author write to {@link mailto:ustb80@163.com}
  * @author 偶然
  * @version $Id: thumb.class.php,v 1.9 2006/09/30 09:31:56 zengjian Exp $
  * @package system
 * $t->setSrcImg("img/test.jpg");
 */

class Image{
    //图片后缀对应的处理函数：GD库
    private static $ext = array(
        'jpg' => 'jpeg',
        'jpeg' => 'jpeg',
        'png' => 'png',
        'gif' => 'gif'
    );
    //记录错误信息
    public static $error;

    /**
     * @desc 检测文件有效性
     * @param $file,文件名
     * @return bool
     */
    public static function checkFile(&$file){
        //字符串处理
        $file = trim($file);
        //判定资源有效性
        if (!is_file($file)) {
            self::$error = "图片{$file}不存在！";
            return false;
        }
        //获取文件信息：判定是否可以处理文件
        $file_info = pathinfo($file);
        if (!array_key_exists($file_info['extension'], self::$ext)) {
            self::$error = "系统无法处理图片{$file}的类型！";
            return false;
        }
        return true;
    }
    /**
     * @desc 检测路径有效性
     * @param $path,文件名
     * @return bool
     */
    public static function checkPath(&$path){
        //字符串处理
        $path = rtrim(trim($path), '/'). '/';
        if (!is_dir($path)) {
            self::$error = "{$path}存储路径不存在！";
            return false;
        }
        return true;
    }
    /**
     * @desc 制作缩略图
     * @param array $info,关联数组参数,应该包含以下元素：
     * string file => 缩略图存储路径
     * string path => 缩略图存储路径
     * int width => 缩略图宽
     * int height => 缩略图高
     * @return bool|string,返回缩略图文件名，错误返回false
     */
    public static function thumb($info){
        $file = $info['file'];
        $path = $info['path'];

        if(!self::checkFile($file)) return false;
        if(!self::checkPath($path)) return false;
        $file_info = pathinfo($file);
        $file_ext = $file_info['extension'];    //文件扩展名
        $img_info = getimagesize($file);

        //根据文件扩展名确定原图资源函数：打开函数和保存函数
        $open = 'imagecreatefrom' . self::$ext[$file_ext];
        $save = 'image' . self::$ext[$file_ext];
        //打开图片资源
        $src = $open($file);

        if(isset($info['width']) && isset($info['height'])){
            //固定宽高，背景补白
            $width = $info['width'];
            $height = $info['height'];
            //补白计算：计算宽高比
            $src_b = $img_info[0] / $img_info[1];
            $thumb_b = $width / $height;
            //原图宽高比大于缩略图：原图太宽，缩略图的宽度要占满
            if ($src_b > $thumb_b) {
                //缩略图实际宽高
                $w = $width;
                $h = ceil($width / $src_b);
                //缩略图起始位置
                $x = 0;
                $y = ceil(($height - $h) / 2);
            } else {
                //原图宽高比小于缩略图：原图太高，缩略图的高度要占满
                $w = ceil($src_b * $width);
                $h = $height;
                $x = ceil(($width - $w) / 2);
                $y = 0;
            }

        }else if(isset($info['width']) && !isset($info['height'])){
            //固定宽度
            $width = $info['width'];
            //计算缩略图高度
            $src_b = $img_info[0] / $img_info[1];
            $height = $width / $src_b;
            $x = 0;
            $y = 0;
            $w = $width;
            $h = $height;
        }else if(!isset($info['width']) && isset($info['height'])){
            //固定高度
            $height = $info['height'];
            //计算缩略图宽度
            $src_b = $img_info[0] / $img_info[1];
            $width = $height * $src_b;
            $x = 0;
            $y = 0;
            $w = $width;
            $h = $height;
        }else{
            self::$error = '必须给出缩略图宽度或高度！';
            return false;
        }
        $thumb = imagecreatetruecolor($width, $height);
        //背景补白
        $bg_color = imagecolorallocate($thumb, 255, 255, 255);
        imagefill($thumb, 0, 0, $bg_color);

        //复制合并：缩略图
        if (!imagecopyresampled($thumb, $src, $x, $y, 0, 0, $w, $h, $img_info[0], $img_info[1])) {
            //采样复制失败
            self::$error = '缩略图制作失败！';
            return false;
        }
        //保存图片
        $res = $save($thumb, $path . 'thumb_' . $file_info['basename']);
        //销毁资源
        imagedestroy($src);
        imagedestroy($thumb);
        if ($res) {     //保存成功
            return 'thumb_' . $file_info['basename'];
        } else {        //保存失败
            self::$error = '图片保存失败！';
            return false;
        }
    }
    /**
     * @desc 图片裁剪
     * @param $file,源文件名
     * @param $path,裁剪图存储路径
     * @param int $width = 60,裁剪图宽
     * @param int $height = 60,裁剪图高
     * @param int $src_x = 0,原图裁剪始点x坐标
     * @param int $src_y = 0,原图裁剪始点y坐标
     * @return bool|string,返回裁剪图文件名，错误返回false
     */
    public static function crop($file, $path, $width = 60, $height = 60, $src_x = 0, $src_y = 0){
        if(!self::checkFile($file)) return false;
        if(!self::checkPath($path)) return false;

        $file_info = pathinfo($file);
        $file_ext = $file_info['extension'];    //文件扩展名
        $img_info = getimagesize($file);

        if($src_x + $width > $img_info[0] || $src_y + $height >  $img_info[1]){
            self::$error = '图片区域选择越界！';
            return false;
        }

        //根据文件扩展名确定原图资源函数：打开函数和保存函数
        $open = 'imagecreatefrom' . self::$ext[$file_ext];
        $save = 'image' . self::$ext[$file_ext];
        //打开图片资源
        $src = $open($file);
        $crop = imagecreatetruecolor($width, $height);

        if (!imagecopyresampled($crop, $src, 0, 0, $src_x, $src_y, $width, $height, $width, $height)){
            self::$error = '裁剪失败！';
            return false;
        }
        //保存图片
        $res = $save($crop, $path . 'corp_' . $file_info['basename']);
        //销毁资源
        imagedestroy($src);
        imagedestroy($crop);
        if ($res) {     //保存成功
            return 'corp_' . $file_info['basename'];
        } else {        //保存失败
            self::$error = '图片保存失败！';
            return false;
        }
    }
    /**
     * @desc 单个图片水印添加
     * @param $dst_file,目标图片文件名
     * @param $src_file,水印图片文件名
     * @param $path,添加水印的图片存储路径
     * @param int $dst_x,水印在目标图片的始点x坐标
     * @param int $dst_y,水印在目标图片的始点y坐标
     * @return bool|string,返回添加水印图文件名，错误返回false
     */
    public static function watermark($dst_file, $src_file, $path, $dst_x = 0, $dst_y = 0){
        if(!self::checkFile($dst_file)) return false;
        if(!self::checkFile($src_file)) return false;
        if(!self::checkPath($path)) return false;
        $src_file_info = pathinfo($src_file);
        $dst_file_info = pathinfo($dst_file);
        $src_file_ext = $src_file_info['extension'];
        $dst_file_ext = $dst_file_info['extension'];
        $open_src = 'imagecreatefrom' . self::$ext[$src_file_ext];
        $open_dst = 'imagecreatefrom' . self::$ext[$dst_file_ext];
        $save_dst = 'image' . self::$ext[$dst_file_ext];
        $src = $open_src($src_file);
        $dst = $open_dst($dst_file);
        if(imagesx($src) > imagesx($dst) || imagesy($src) > imagesy($dst) ){
            self::$error = '水印过大！';
            return false;
        }
        if(!imagecopy($dst,$src,$dst_x,$dst_y,0,0,imagesx($src),imagesy($src))){
            self::$error = '水印添加失败！';
            return false;
        }
        $res = $save_dst($dst, $path . 'watermark_' . $dst_file_info['basename']);
        imagedestroy($src);
        imagedestroy($dst);
        if ($res) {
            return 'watermark_' . $dst_file_info['basename'];
        } else {
            self::$error = '图片保存失败！';
            return false;
        }
    }
}