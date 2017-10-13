function ShowCountDown(year, month, day, divname) {
	var now = new Date();
	var endDate = new Date(year, month, day);
	var leftTime = endDate.getTime() - now.getTime();
	var dd = parseInt(leftTime / 1000 / 60 / 60 / 24, 10); //计算剩余的天数
	var hh = parseInt(leftTime / 1000 / 60 / 60 % 24, 10); //计算剩余的小时数
	var mm = parseInt(leftTime / 1000 / 60 % 60, 10); //计算剩余的分钟数
	var ss = parseInt(leftTime / 1000 % 60, 10); //计算剩余的秒数
	dd = checkTime(dd);
	hh = checkTime(hh);
	mm = checkTime(mm);
	ss = checkTime(ss); //小于10的话加0
//	var cc = $(divname);
	divname.html(dd + "：" + hh + "：" + mm + "：" + ss + " " + "后结束");
}

function checkTime(i) {
	if(i < 10) {
		i = "0" + i;
	}
	return i;
}

function mask(text) {
	$('.layer').remove();
	var content = '<div class="layer"> ' + text + '</div>'
	$('body').append(content);

	var w = $('.layer').width() + 40;
	var win = $(window).width();

	$('.layer').css('left', (win - w) / 2 + 'px');
	$('.layer').fadeIn();
	setTimeout(function() {
		$('.layer').fadeOut();
		$('.layer').remove();
	}, 1200);
}