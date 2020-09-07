var i=0;
var Timer;
$(function(){
    $(".picImg").eq(0).show().siblings().hide();   //默认第一张图片显示，其他的隐藏
    //自动轮播
    TimerBanner();

    //点击红圈

    $(".tabs li").hover(function(){  //鼠标移动上去
        clearInterval(Timer); //让计时器暂时停止   清除计时器
        i=$(this).index();   //获取该圈的索引
        showPic();           //调用显示图片的方法，显示该索引对应的图片
    },function(){  //鼠标离开
        TimerBanner();    //继续轮播   计时器开始
    });

    //点击左右按钮
    $(".btn1").click(function(){
        clearInterval(Timer);
        i--;   //往左
        if(i==-1){
            i=4;
        }
        showPic();
        TimerBanner();
    });
    $(".btn2").click(function(){
        clearInterval(Timer);
        i++;   //往右
        if(i==5){
            i=0;
        }
        showPic();
        TimerBanner();
    });
});
//轮播部分
function TimerBanner(){
    Timer = setInterval(function(){
        i++;
        if(i==5){
            i=0;
        }
        showPic()
    },3000);
}
//显示图片
function showPic(){
    $(".picImg").eq(i).show().siblings().hide();
    $(".tabs li").eq(i).addClass("bg").siblings().removeClass("bg");
}