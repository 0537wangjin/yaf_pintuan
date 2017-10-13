//遮罩
function mask(text1,text2){
    var content = '<div class="layer"><p>'+text1+'</p><span>'+text2+'</span></div>';
    $('body').append(content);
    $('body').append('<div class="zhezhao-w"></div>')
    var w = $('.layer').width() + 40;
    var win = $(window).width();
    $('.layer').css('left', (win-w)/2 + 'px');
    $('.layer').fadeIn();
    setTimeout(function(){
        $('.layer').fadeOut();
        $('.layer').remove();
        $('.zhezhao-w').remove();
    }, 2000);
}

//点击一键参团
$('#popup-btn').on('click',function(){
	$('.popup-btm').css('display','block');
	$('.popup-btm').addClass('bounceInLeft');
	$('body').append('<div class="zhezhao"></div>')
})
//点击X号
$('.icon-x').on('click',function(){
	$('.popup-btm').css('display','none');
	$('.zhezhao').remove();
})
//点击确定
$('#make-sure').on('click',function(){
	//$('.popup-btm').css('display','none');
	//$('.zhezhao').remove();
	cartconfirm();
})
//点击收藏
$('#collection').on('click',function(){
	var index=$(this).attr('index');
	var id=$(this).attr('title');
	$.get('/index/favAdd?id='+ id, function(res){
		if(res == 'success'){
			if(index==0){
				mask('收藏成功',' ');
				$('#collection').find('.icon-collection').addClass('active');
				$('#collection').attr('index',1)
			}else if(index==1){
				mask('取消收藏',' ');
				$('#collection').find('.icon-collection').removeClass('active');
				$('#collection').attr('index',0)
			}
		}else{
			mask(res,' ');
		}
	});

})

//数量的加减
$(".goods-number span").on("click",function(){
	var num=$('.goods-number b').html();
	if($(this).attr("class")=="icon-jian"){
		//减号	
		num=num>1?(--num):1;
		
	}else if($(this).attr("class")=="icon-jia"){
		//加号
		num++; 	
	}
	$('.goods-number b').html(num);
})