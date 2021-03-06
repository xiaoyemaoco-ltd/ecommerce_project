<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/4
 * Time: 19:14
 */
namespace fast;
use think\facade\Config;
class Image{
    private $path;
   //构造方法用来对图片所在位置进行初始化
    public function __construct($path="./"){
        $this->path=rtrim($path,"/")."/";    //用户在输入路径时，无斜杠则加斜杠，有斜杠则删掉再加上
    }
    /** 把网络图片图片转成base64
     * @param string $img 图片地址
     * @return string
     * $type = 1 需要压缩
     */
    public static function imgtobase64($img='',$type=0,$iwidth="",$iheight=""){
        if($type == 1){
            $img = self::resize_image($img,$iwidth,$iheight);
        }
        $imageInfo = self::getfilesuffix($img);
//    $base64 = "" . chunk_split(base64_encode(file_get_contents($img)));
        $img = 'data:' . 'image/'.$imageInfo . ';base64,' . chunk_split(base64_encode(file_get_contents($img)));
        return $img;
    }

    /**
     * 按照指定的尺寸压缩图片
     * @param $source_path  原图路径
     * @param $dest  保存路径
     * @param $imgWidth     目标宽度
     * @param $imgHeight    目标高度
     * @return bool|string
     */
   protected static function resize_image($source_path,$imgWidth,$imgHeight){
        $dest = './storage/urlimage/';
        $source_info = getimagesize($source_path);
        $source_mime = $source_info['mime'];
        switch ($source_mime) {
            case 'image/gif':
                $source_image = imagecreatefromgif($source_path);
                break;

            case 'image/jpeg':
                $source_image = imagecreatefromjpeg($source_path);
                break;

            case 'image/png':
                $source_image = imagecreatefrompng($source_path);
                break;

            default:
                return false;
                break;
        }
        $target_image    = imagecreatetruecolor($imgWidth, $imgHeight); //创建一个彩色的底图
        imagecopyresampled($target_image, $source_image, 0, 0, 0, 0, $imgWidth, $imgHeight, $source_info[0], $source_info[1]);
        $fileName = $dest.date("YmdHis").uniqid().'.jpg';
        if(!imagejpeg($target_image,'./'.$fileName)){
            $fileName = '';
        }
        imagedestroy($target_image);
        return $fileName;
    }

    /*
        @desc：获取文件真实后缀
        @param   name    文件名
        @return  suffix  文件后缀
    */
    private static function  getfilesuffix($name) {
        $file = fopen($name, "rb");
        $bin = fread($file, 2); // 只读2字节
        fclose($file);
        $info = @unpack("C2chars", $bin);
        $code = intval($info['chars1'] . $info['chars2']);
        $suffix = "unknow";
        if($code == 255216){
            $suffix = "jpg";
        }elseif($code == 7173){
            $suffix = "gif";
        }elseif($code == 13780){
            $suffix = "png";
        }elseif($code == 6677){
            $suffix = "bmp";
        }elseif($code == 7798){
            $suffix = "exe";
        }elseif($code == 7784){
            $suffix = "midi";
        }elseif($code == 8297){
            $suffix = "rar";
        }elseif($code == 7368){
            $suffix = "mp3";
        }elseif($code == 0){
            $suffix = "mp4";
        }elseif($code == 8273){
            $suffix = "wav";
        }
         return $suffix;
    }

    //本地图片转换成base64编码
   public static function base64EncodeImage ($image_file) {
        $base64_image = '';
        $image_info = getimagesize($image_file);
        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        return $base64_image;
    }


    public function imageAddText($path, $content, $x = 'auto', $y = 'auto', $fontSize = 38, $font = './t.ttf'){
        $temp = array(1=>'gif', 2=>'jpeg', 3=>'png');
        // 获取图片信息
        $imageInfo = getimagesize($path);
        $imageType = $temp[$imageInfo[2]];

        $getfunc = "imagecreatefrom$imageType";
        $outfunc = "image$imageType";

        $resource = $getfunc($path);

        $width    = imagesx($resource);
        $height   = imagesy($resource);

        $color = imagecolorallocatealpha($resource, 255, 255, 255, 0);

        $fontBox = imagettfbbox($fontSize, 0, $font, $content);//文字水平居中实质

        if ($x === 'auto'){
            $x = ceil(($width - $fontBox[2]) / 2);
        }
        if ($y === 'auto'){
            $y = ceil(($height - $fontBox[1] - $fontBox[7]) / 2);
        }

        imagettftext($resource, $fontSize, 0, $x, $y, $color, $font, $content);

        /*输出图片*/
        //浏览器输出
        header("Content-type:".$imageType);
        $outfunc($resource);
        // 自动居中
        // imageAddText('./test.jpg', 'My name is Siam，中文名是宣言');
        // 声明x y值
        // imageAddText('./test.jpg', 'My name is Siam，中文名是宣言',200);
        // imageAddText('./test.jpg', 'My name is Siam，中文名是宣言','auto', '300');
    }




    /*   功能：对图片进行缩放
        *    参数$name：需处理的图片名称
        *    参数$width：缩放后的宽度
        *    参数$height：缩放后的高度
        *    参数$qz：新图片的名称前缀
        *    返回值：缩放后的图片名称，失败返回false
        */
    public function thumb($name,$width,$height,$qz="th_"){
                //获取图片信息
             $imgInfo=$this->getInfo($name);    //原图片的信息
            //获取图片资源,通用各种类型的图片(png,jpg,gif)
             $srcImg=$this->getImg($name,$imgInfo);
              //获取计算图片等比例之后的大小
             $size=$this->getNewSize($name,$width,$height,$imgInfo);
              //获取新的图片资源,处理gif透明背景问题
             $newImg=$this->kid0fImage($srcImg,$size,$imgInfo);
              //另存为一个新的图片，返回新的缩放后的图片名称
             return $this->createNewImage($newImg,$qz.$name,$imgInfo);
    }
    private function createNewImage($newImg,$newName,$imgInfo){
        //另存图片
        switch($imgInfo["type"]){
            case 1:                //gif
                     $result=imagegif($newImg,$this->path.$newName);
                break;
            case 2:                //jpg
                     $result=imagejpeg($newImg,$this->path.$newName);
                  break;
            case 3:                //png
                     $result=imagepng($newImg,$this->path.$newName);
                break;
        }
        imagedestroy($newImg);
        return $newName;
    }
    private function kid0fImage($srcImg,$size,$imgInfo){
        //创建新图片资源
        $newImg=imagecreatetruecolor($size["width"],$size["height"]);
        //取出透明色指数
        $otsc=imagecolortransparent($srcImg);
         //判断是否有透明色    //（）取得一幅图像的调色板中颜色的数目
        if($otsc >=0 && $otsc <= imagecolorstotal($srcImg)){
            $tran = imagecolorsforindex($srcImg,$otsc);    //取得某索引的颜色
            $newt = imagecolorallocate($newImg,$tran["red"],$tran["green"],$tran["blue"]);    //为一幅图片分配颜色
            imagefill($newImg,0,0,$newt);    //填充颜色
            imagecolortransparent($newImg,$newt);    //将某个颜色定义为透明色
        }
      //拷贝部分图像并调整大小
       imagecopyresized($newImg, $srcImg, 0, 0, 0, 0, $size["width"], $size["height"], $imgInfo["width"], $imgInfo["height"]);
       imagedestroy($srcImg);
       return $newImg;
    }
    private function getNewSize($name,$width,$height,$imgInfo){
        $size["width"]=$imgInfo["width"];
        $size["height"]=$imgInfo["height"];
        //如果缩放后宽度小于原图片宽度，再重新设置图片宽度
        if($width < $imgInfo["width"]){
            $size["width"]=$width;
        }
       //如果缩放后高度小于原图高度，再重新设置图片高度
       if($height < $imgInfo["height"]){
           $size["height"]=$height;
       }
       //图片等比例缩放的算法
       if($imgInfo["width"]*$width > $imgInfo["height"]*$height){
           $size["height"]=round($imgInfo["height"]*$size["width"]/$imgInfo["width"]);
       }else{
          $size["width"]=round($imgInfo["width"]*$size["height"]/$imgInfo["height"]);
       }
       return $size;
    }
    private function getInfo($name){
        $date=getImageSize($this->path.$name);
        $imageInfo["width"]=$date[0];
        $imageInfo["height"]=$date[1];
        $imageInfo["type"]=$date[2];
        return $imageInfo;
    }
    private function getImg($name,$imgInfo){
        $srcPic=$this->path.$name;        //某路径下的图片
        switch($imgInfo["type"]){
            case "1":        //gif
                $img=imagecreatefromgif($srcPic);
                break;
            case "2":        //jpg
                $img=imagecreatefromjpeg($srcPic);
                break;
            case "3":        //png
                $img=imagecreatefrompng($srcPic);
                break;
            default:
                return false;
        }
        return $img;
    }

        /*    功能：为图片加水印
            *    参数$groundName：背景图片，即需要加水印的图片
            *    参数$waterMark：水印图片
            *    参数$waterPos：水印位置，10种状态
            *        0随机位置
            *            1顶端居左    2顶端居中    3顶端居右
            *            4中部居左    5中部居中    6中部居右
            *            7底部居左    8底部居中    9底部居右
            *    参数$qz：是加水印后图片名称的前缀
            *    返回值：处理后图片的名称
        */
    public function waterMark($groundName,$waterName,$waterPos=0,$qz="wa_"){
        if(file_exists($this->path.$groundName) && file_exists($this->path.$waterName)){
            $groundInfo = $this->getInfo($groundName);
            $waterInfo = $this->getInfo($waterName);
            //水印位置
            if(!$pos = $this->position($groundInfo,$waterInfo,$waterPos)){
                echo "水印不应该比背景图片小";
                return;
            }
            $groundImg = $this->getImg($groundName,$groundInfo);
            $waterImg = $this->getImg($waterName, $waterInfo);
            $groundImg = $this->copyImage($groundImg, $waterImg, $pos, $waterInfo);
            return $this->createNewImage($groundImg, $qz.$groundName, $groundInfo);
        }else{
            echo "图片或水印不存在";
            return false;
        }
    }

    private function copyImage($groundImg, $waterImg, $pos, $waterInfo){
        imagecopy($groundImg, $waterImg, $pos["posX"], $pos["posY"], 0, 0, $waterInfo["width"], $waterInfo["height"]);
        imagedestroy($waterImg);
        return $groundImg;
    }
    private function position($groundInfo,$waterInfo,$waterPos){
        //需要背景比水印图片大
       if(($groundInfo["width"] < $waterInfo["width"]) || ($groundInfo["height"] < $waterInfo["height"])){
           return false;
       }
       switch($waterPos){
           case 1:            //顶部居左
               $posX=0;
               $posY=0;
               break;
           case 2:            //顶部居中
               $posX=($groundInfo["width"]-$waterInfo["width"])/2;
               $posY=0;
               break;
           case 3:            //顶部居右
               $posX=($groundInfo["width"]-$waterInfo["width"]);
               $posY=0;
               break;
           case 4:            //中部居左
               $posX=0;
               $posY=($groundInfo["height"]-$waterInfo["height"])/2;
               break;
           case 5:            //中部居中
               $posX=($groundInfo["width"]-$waterInfo["width"])/2;
               $posY=($groundInfo["height"]-$waterInfo["height"])/2;
               break;
           case 6:            //中部居右
               $posX=($groundInfo["width"]-$waterInfo["width"]);
               $posY=($groundInfo["height"]-$waterInfo["height"])/2;
               break;
           case 7:            //底部居左
               $posX=0;
               $posY=($groundInfo["height"]-$waterInfo["height"]);
               break;
           case 8:            //底部居中
               $posX=($groundInfo["width"]-$waterInfo["width"])/2;
               $posY=($groundInfo["height"]-$waterInfo["height"]);
               break;
            case 9:            //底部居右
                $posX=($groundInfo["width"]-$waterInfo["width"]);
                $posY=($groundInfo["height"]-$waterInfo["height"]);
                break;
            case 0:            //随机位置
                $posX=rand(0,($groundInfo["width"]-$waterInfo["width"]));
                $posY=rand(0,($groundInfo["height"]-$waterInfo["height"]));
                break;
        }
        return array("posX"=>$posX, "posY"=>$posY);
    }
}
