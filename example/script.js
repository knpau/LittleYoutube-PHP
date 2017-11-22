var buttonTemplate = '<a target="_blank" href="*url*" type="button" class="btn btn-secondary">*text*</a>';
var listGroupTemplate = '<a target="_blank" href="*url*" style="" class="list-group-item"><img src="*picture*" alt="" style="display: inline-block;width: 200px;"><div style="width: 70%;"><div class="d-flex w-100 justify-content-between" style="margin-left: 10px;"><h5 class="mb-1">*title*</h5><small class="text-muted">*floatrightinfo*</small></div><p class="mb-1">*desc*</p><small class="text-muted">*bottominfo*</small></div></a>';

function videoButton(){
	request({video:$('#urlVideo').val()}, function(respond){
		try{
			var json = JSON.parse(respond);
		} catch (e) {
			$("#videoError").html('Parse error\n<br>'+respond);
        	return;
    	}
		if(json.error.length>=4){
			$("#videoError").html(json.error);
			return;
		}
		$("#videoError").html('');
		$('#videoDetail').css('display', '');
		$('#videoDetail #title').html(json.data.title);
		$('#videoDetail #info').html("Duration: "+secondsToMinutes(json.data.duration)+"\nViewed: "+json.data.viewCount);
		$('#videoDetail #picture').prop("src", json.picture[0]);

		$('#encoded').css('display', 'none');
		$('#adaptive').css('display', 'none');
		$('#encoded .button-group').html('');
		$('#adaptive .button-group').html('');
		var encoded = json.data.video.encoded;
		var adaptive = json.data.video.adaptive;
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
		if(json.error.length>=4){
			$("#channelError").html(json.error);
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
		if(json.error.length>=4){
			$("#playlistError").html(json.error);
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
				.replace("*picture*", 'http://i1.ytimg.com/vi/'+list[i].videoID+'/hqdefault.jpg')
			);
		}
	}, function(text){
		$('#urlPlaylistText').val(text);
	});
}
function searchButton(){
	request({search:$('#urlSearch').val()}, function(respond){
		try{
			var json = JSON.parse(respond);
		} catch (e) {
			$("#videoError").html('Parse error\n<br>'+respond);
        	return;
    	}
		if(json.error.length>=4){
			$("#searchError").html(json.error);
			console.log(json.error);
			return;
		}
		var list = json.data.videos;
		$('#searchGroupList').html('');
		for (var i = 0; i < list.length; i++) {
			$('#searchGroupList').append(listGroupTemplate
				.replace("*bottominfo*", list[i].views)
				.replace("*floatrightinfo*", list[i].duration)
				.replace("*title*", list[i].title)
				.replace("*desc*", "")
				.replace("*url*", "https://www.youtube.com/watch?v="+list[i].videoID)
				.replace("*picture*", 'http://i1.ytimg.com/vi/'+list[i].videoID+'/hqdefault.jpg')
				);
		}
	}, function(text){
		$('#urlSearchText').val(text);
	});
}

function request(data, callback, error){
	$.ajax({
		url:"base.php",
		data:data,
		success:function(respond){
			if(callback) callback(respond);
		},
		error:function(respond){
			if(error) error(respond);
		}
	});
}

function secondsToMinutes(time){
    return Math.floor(time / 60)+':'+Math.floor(time % 60);
}