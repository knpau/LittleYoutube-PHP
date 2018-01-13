<!DOCTYPE html>
<html>
	<head>
		<title>LittleYoutube</title>
		<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css">
		<link rel="stylesheet" href="style.css">
		<script src="//code.jquery.com/jquery-3.1.1.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js"></script>
		<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js"></script>
	</head>
	<body>
    <div class="masthead clearfix">
      <div class="inner">
        <h3 class="masthead-brand">LittleYoutube</h3>
        <nav class="nav nav-masthead">
          <a class="nav-link active" href="#collapseVideo" data-toggle="tab">Video</a>
          <a class="nav-link" href="#collapseChannel" data-toggle="tab">Channel</a>
          <a class="nav-link" href="#collapsePlaylist" data-toggle="tab">Playlist</a>
          <a class="nav-link" href="#collapseSearch" data-toggle="tab">Search</a>
        </nav>
      </div>
    </div>

    <div class="site-wrapper">
      <div class="site-wrapper-inner">
        <div class="cover-container">

          <div class="inner cover">
    		<div id='content' class="tab-content">
				<div class="tab-pane fade active show" aria-expanded="true" id="collapseVideo">
					<div class="form-group">
					  <label>Video URL:</label>
					  <input type="text" class="form-control text-half" id="urlVideo">
					  <small id="urlVideoText" class="form-text text-muted"></small>
					</div>
    		  		<p class="lead">
					  <p id="videoError"></p>
    		  		  <a onclick="videoButton()" class="btn btn-lg btn-secondary">Submit</a>
    		  		</p>

					<div id="videoDetail" style="display: none">
						<h2 id="title"></h2>
						<p id="info"></p>
						<img id="picture" src="" alt="" height="340px"/><br><br>
					</div>
					<div id="encoded" style="display: none">
						<label>Encoded (Audio+Video):</label>
						<div class="button-group">
						</div><br><br>
					</div>
					<div id="adaptive" style="display: none">
						<label>Adaptive (Audio/Video only):</label>
						<div class="button-group">
						</div>
					</div><br><br>
					<div id="subtitle" style="display: none">
						<label>Subtitle:</label>
						<div class="button-group">
						</div>
					</div>
				</div>
				<div class="tab-pane fade" id="collapseChannel">
					<div class="form-group">
					  <label>Channel URL:</label>
					  <input type="text" class="form-control text-half" id="urlChannel">
					  <small id="urlChannelText" class="form-text text-muted"></small>
					</div>
    		  		<p class="lead">
					  <p id="channelError"></p>
    		  		  <a onclick="channelButton()" class="btn btn-lg btn-secondary">Submit</a>
    		  		</p>
    		  		<div id="channelGroupList" style="width: 760px;margin: 0 auto;" class="list-group">
					</div>
				</div>
				<div class="tab-pane fade" id="collapsePlaylist">
					<div class="form-group">
					  <label>Playlist URL:</label>
					  <input type="text" class="form-control text-half" id="urlPlaylist">
					  <small id="urlPlaylistText" class="form-text text-muted"></small>
					</div>
    		  		<p class="lead">
					  <p id="playlistError"></p>
    		  		  <a onclick="playlistButton()" class="btn btn-lg btn-secondary">Submit</a>
    		  		</p>
    		  		<div id="playlistGroupList" style="width: 760px;margin: 0 auto;" class="list-group">
					</div>
				</div>
				<div class="tab-pane fade" id="collapseSearch">
					<div class="form-group">
					  <label>Search on youtube:</label>
					  <input type="text" class="form-control text-half" id="urlSearch">
					  <small id="urlSearchText" class="form-text text-muted"></small>
					</div>
    		  		<p class="lead">
					  <p id="searchError"></p>
    		  		  <a onclick="searchButton()" class="btn btn-lg btn-secondary">Submit</a>
    		  		</p>
    		  		<div id="searchGroupList" style="width: 760px;margin: 0 auto;" class="list-group">
					</div>
				</div>
			</div>
          </div>
        </div>
      </div>
    </div>

    <div class="mastfoot">
      <button id="nextButton" onclick="searchNext()" style="margin-top: 20px;display: none;">Next</button>
      <div class="inner">
        <p>Have you ever dreamed put your own channel on your own website?<br><a href="https://github.com/StefansArya/LittleYoutube-PHP" target="_blank">LittleYoutube</a> is here to help you</p>
      </div>
    </div>
	<script src="script.js?version=1"></script>
  </body>
</html>