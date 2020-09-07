// 导航展开显示
var on_navs_list = 0;
function on_display(){
	//获取宽度
	// var width = $('#navs_list').width();
	if (on_navs_list == 0) {
		$('#navs_list').animate({ width: "toggle"}, 1000, function() {
			 $('#navs_list').show();
		});
		on_navs_list =1;
	}else{
		$('#navs_list').animate({ width: "toggle"}, 1000, function() {
			 $('#navs_list').hide();
		});
		on_navs_list =0;
	}
}
$(function(){
	/*右侧浮层点击事件*/
	$(".navs_list1 li").on('click',function (){
		$(this).addClass('avatar');
		$(this).siblings().removeClass('avatar');
	})
	
	//悬浮下载
	$("#procudt_ul li").hover(function(){
		// $(this).css("cursor", 'pointer');  
		$(this).addClass('procudt1_down_off');  
		$(this).siblings().removeClass('procudt1_down_off');
	})
	
})
$(document).ready(function(){	
	//gotop
	$(".gotop").hide();
	$(window).scroll(function(){
		if ($(window).scrollTop()>100){
			$(".gotop").fadeIn(500);
		}else{
			$(".gotop").fadeOut(500);
		}
	});
	//当点击跳转链接后，回到页面顶部位置
	$(".gotop").click(function(){
		$('body,html').animate({scrollTop:0},800);
		return false;
	});
});
$(function(){

	$(".nav_bbs h3").click(function(){
		var ul=$(".new");
		if(ul.css("display")=="none"){
			ul.slideDown();
		}else{
			ul.slideUp();
		}
	});
	
	$(".set").click(function(){
		var _name = $(this).attr("name");
		if( $("[name="+_name+"]").length > 1 ){
			$("[name="+_name+"]").removeClass("select1");
			$(this).addClass("select1");
		} else {
			if( $(this).hasClass("select1") ){
				$(this).removeClass("select1");
			} else {
				$(this).addClass("select1");
			}
		}
	});
	
	$(".nav_bbs li").click(function(){
		var li=$(this).text();
		$(".nav_bbs h3").html(li);
		$(".new").hide();
		/*$(".set").css({background:'none'});*/
		$("h3").removeClass("select1") ;   
	});
})
$(function(){
	// 地图
	var infoWindow;
	openInfo();
	var map = new AMap.Map("container", {
		resizeEnable: true,
		center: [108.947868,34.335011],
		zoom: 18
	});
	
	//在指定位置打开信息窗体
	function openInfo() {
		//构建信息窗体中显示的内容
		var info = [];
		info.push("<div style=\"padding:7px 0px 0px 0px;\"><h4>陕西天屿懿德信息科技有限公司</h4>");
		info.push("<p class='input-item_phone'>电话 : 17316603472</p>");
		info.push("<p class='input-item_dizhi'>地址 : 陕西省西安市未央区未央路177号旺景国际大厦1402室</p></div></div>");
	
		infoWindow = new AMap.InfoWindow({
			content: info.join("")  //使用默认信息窗体框样式，显示信息内容
		});
	
		
	}
	infoWindow.open(map, map.getCenter());

	// 创建一个 icon
	var endIcon = new AMap.Icon({
		size: new AMap.Size(25, 25),
		image: '/home/images/ICON.png',
		imageSize: new AMap.Size(25, 25),
		imageOffset: new AMap.Pixel(0, -3)
	});
	
	// 将 icon 传入 marker
	var endMarker = new AMap.Marker({
		position: new AMap.LngLat(108.947868,34.335011),
		icon: endIcon,
		offset: new AMap.Pixel(-13, -30)
	});
	
	// 将 markers 添加到地图
	map.add(endMarker);
})

//伪类选择器
// $(function(){
// 	$(".planServer ul li:last-child").css({'margin-right': '0'});
	
// 	$(".conL .conUl:last-child").css({'float': 'right',"margin-right":"3%"});
// 	$(".comUl li:nth-child(4n)").css({'margin-right': '0'});
// 	$(".colOl li:last-child").css({'margin-right': '0'});
// 	$(".contac ul li:last-child").css({'float': 'right'});
// 	$(".serMainUl li:last-child").css({'border-bottom': 'none'});
// 	$(".serMainUl li dl dd:last-child").css({'margin-right': '0'});
// 	$(".soluDl dd:last-child").css({'margin-right': '0'});
// 	$(".trainUl li:last-child").css({'margin-right': '0'});
// 	$("#honOl li:nth-child(n+15)").css({'border-bottom': 'none'});
// 	$(".imporNew ul li:last-child").css({'border-bottom': 'none'});
// 	$(".couseUl li:last-child").css({'border-bottom': 'none'});
// });
// $(function() {
// 	$(".topR .china").click(function(){
// 		$(this).addClass('cur').siblings().removeClass('cur');
// 	});
	
// 	$(".nav ul li").hover(function(){
// 		$(this).addClass('cur').siblings().removeClass('cur');
// 	});
	
// 	$(".directoryTM a").click(function(){
// 		$(this).addClass('cur').siblings().removeClass('cur');
// 	});
	
// 	$(".planTitle ul li").hover(function(){
// 		$(this).addClass('cur').siblings().removeClass('cur');
// 	});
	
// 	$(".paging .num").click(function(){
// 		$(this).addClass('cur').siblings().removeClass('cur');
// 	});
// 	var mark=1;
// 	$(".comNav h2").click(function(){
// 		if(mark==1){//把他展开
// 			$(this).addClass("navH2");
// 			$(this).siblings('#comNav').slideDown();
// 			mark=2;
// 		}else if(mark==2){//收缩
// 			$(this).removeClass("navH2");
// 			$(this).siblings('#comNav').slideUp();
// 			mark=1;
// 		}
// 	});
// 	$("#slide").click(function(){
		
// 		$('.nav').animate({"right":"0"},300);
// 		$("#TB_overlayBG").css({
// 			display:"block",height:$(document).height()
// 		});	
// 		$(".comNav h2").removeClass("navH2");
// 		$(".comNav h2").siblings('#comNav').slideUp();
// 		mark=1;
// 	});
// 	$("#TB_overlayBG").click(function(){
// 		$('.nav').animate({"right":"-240px"},300);
// 		$("#TB_overlayBG").css({
// 			display:"none",height:$(document).height()
// 		});
// 	});
// });














