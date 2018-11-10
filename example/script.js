var buttonTemplate = '<a target="_blank" href="*url*" type="button" class="btn btn-secondary">*text*</a>';
var listGroupTemplate = '<a target="_blank" href="*url*" class="list-group-item"><img src="*picture*" alt="" style="display: inline-block;width:200px"/><div style="width: 70%;"><div class="d-flex w-100 justify-content-between" style="margin-left: 10px;"><h5 class="mb-1">*title*</h5><small class="text-muted">*floatrightinfo*</small></div><p class="mb-1" style="margin-left: 10px;">*desc*</p><small class="text-muted">*bottominfo*</small></div></a>';

$(function(){
	$('#urlVideo').keypress(function(e) {
	    if(e.keyCode == '13')
	    	videoButton();
	});
	$('#urlChannel').keypress(function(e) {
	    if(e.keyCode == '13')
	    	channelButton();
	});
	$('#urlPlaylist').keypress(function(e) {
	    if(e.keyCode == '13')
	    	playlistButton();
	});
	$('#urlSearch').keypress(function(e) {
	    if(e.keyCode == '13')
	    	searchButton();
	});
});

function videoButton(){
	request({video:$('#urlVideo').val()}, function(respond){
		try{
			var json = JSON.parse(respond);
		} catch (e) {
			$("#videoError").html('Parse error\n<br>'+respond);
        	return;
    	}
		$("#videoError").html('');
		$('#videoDetail').css('display', '');
		$('#videoDetail #title').html(json.data.title);
		$('#videoDetail #info').html("Duration: "+secondsToMinutes(json.data.duration)+"\nViewed: "+json.data.viewCount);
		$('#videoDetail #picture').prop("src", json.picture[0]);

		$('#encoded').css('display', 'none');
		$('#adaptive').css('display', 'none');
		$('#subtitle').css('display', 'none');
		$('#encoded .button-group').html('');
		$('#adaptive .button-group').html('');
		$('#subtitle .button-group').html('');
		var encoded = json.data.video.encoded;
		var adaptive = json.data.video.adaptive;
		var stream = json.data.video.stream;
		var subtitle = json.data.subtitle;
		if(encoded){
			$('#encoded').css('display', '');
			for (var i = 0; i < encoded.length; i++) {
				$('#encoded .button-group').append(buttonTemplate
					.replace("*url*", encoded[i].url)
					.replace("*text*", encoded[i].quality+'('+encoded[i].type[0]+'/'+encoded[i].type[1]+')'));
			}
		}
		if(adaptive){
			$('#adaptive').css('display', '');
			for (var i = 0; i < adaptive.length; i++) {
				$('#adaptive .button-group').append(buttonTemplate
					.replace("*url*", adaptive[i].url)
					.replace("*text*", adaptive[i].quality+'('+adaptive[i].type[0]+'/'+adaptive[i].type[1]+')'));
			}
		}
		if(stream){
			$('#encoded').css('display', '');
			$('#encoded .button-group').append(buttonTemplate
				.replace("*url*", stream)
				.replace("*text*", 'Streaming link'));
		}
		if(subtitle){
			$('#subtitle').css('display', '');
			for (var i = 0; i < subtitle.length; i++) {
				$('#subtitle .button-group').append(buttonTemplate
					.replace("*url*", "example/base.php?lyric="+encodeURIComponent(subtitle[i].url))
					.replace("*text*", subtitle[i].lang));
			}
		}
	}, function(text){
		$('#urlVideoText').val(text);
	});
}
function channelButton(){
	request({channel:$('#urlChannel').val()}, function(respond){
		try{
			var json = JSON.parse(respond);
		} catch (e) {
			$("#channelError").html('Parse error\n<br>'+respond);
        	return;
    	}
		$("#channelError").html('');
		var list = json.data.playlists;
		$('#channelGroupList').html('');
		for (var i = 0; i < list.length; i++) {
			$('#channelGroupList').append(buttonTemplate
				.replace("*url*", 'https://www.youtube.com/playlist?list='+list[i].playlistID)
				.replace("*text*", list[i].title)
			);
		}
	}, function(text){
		$('#urlChannelText').val(text);
	});
}
function playlistButton(){
	request({playlist:$('#urlPlaylist').val()}, function(respond){
		try{
			var json = JSON.parse(respond);
		} catch (e) {
			$("#playlistError").html('Parse error\n<br>'+respond);
        	return;
    	}
		$("#playlistError").html('');
		var list = json.data.videos;
		$('#playlistGroupList').html('');
		for (var i = 0; i < list.length; i++) {
			$('#playlistGroupList').append(listGroupTemplate
				.replace("*bottominfo*", '')
				.replace("*floatrightinfo*", '')
				.replace("*title*", list[i].title)
				.replace("*desc*", "")
				.replace("*url*", "https://www.youtube.com/watch?v="+list[i].videoID)
				.replace("*picture*", 'http://i1.ytimg.com/vi/'+list[i].videoID+'/mqdefault.jpg')
			);
		}
	}, function(text){
		$('#urlPlaylistText').val(text);
	});
}

var searchNext_ = null;
function parseSearchResult(respond){
	try{
		var json = JSON.parse(respond);
	} catch (e) {
		$("#searchError").html('Parse error\n<br>'+respond);
    	return;
    }
	$("#searchError").html('');
	var list = json.data.videos;
	var temp = '';
	for (var i = 0; i < list.length; i++) {
		temp = temp + listGroupTemplate
			.replace("*bottominfo*", list[i].views)
			.replace("*floatrightinfo*", list[i].duration)
			.replace("*title*", list[i].title)
			.replace("*desc*", list[i].description)
			.replace("*url*", "https://www.youtube.com/watch?v="+list[i].videoID)
			.replace("*picture*", 'http://i1.ytimg.com/vi/'+list[i].videoID+'/mqdefault.jpg')
			;
	}
	$('#searchGroupList').append(temp);
	$('#nextButton').css('display', '');
}

var page = 1;
function searchButton(next){
	if(!page){
		page = 1;
		$('#searchGroupList').html('');
	}
	else page++;
	request({search:$('#urlSearch').val(), page:page}, function(respond){
    	parseSearchResult(respond);
	}, function(text){
		$('#urlSearchText').val(text);
	});
}

function request(data, callback, error){
	$('.tab-pane.active a.btn')[0].innerHTML = "Loading";
	$.ajax({
		url:"example/base.php",
		data:data
	}).done(function(respond){
		if(callback) callback(respond);
	}).fail(function(respond){
		if(error) error(respond);
	}).always(function(){
		$('.tab-pane.active a.btn')[0].innerHTML = "Submit";
	});
}

function secondsToMinutes(time){
    return Math.floor(time / 60)+':'+Math.floor(time % 60);
}