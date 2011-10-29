<?php
include_once('../theme/header.php');
?>
{{#content}}
<h1 class="title">{{title}}</h1>
<div class="meta">
	<p class="commentsCount">Comments: </p>
	<p class="plusOneCount">+1s: </p>
	<p class="timestamp">Timestamp: {{timestamp}}</p>
</div>

<div id="post">

	{{#attachment_video}}
	<iframe class="youtube-player" type="text/html" width="640" height="385" src="http://www.youtube.com/embed/{{attachment_video_id}}" frameborder="0"></iframe>
	{{/attachment_video}}

	{{^attachment_video}}
		{{#attachment_image}}
			<div class="attachment_image">
			<a href="{{attachment_url}}">
				<img src="{{attachment_image}}" />
			</a>
			</div>
		{{/attachment_image}}
	{{/attachment_video}}
	
	{{#annotation}}
		<p>{{{annotation}}}</p>
	{{/annotation}}
	<p>{{{content}}}</p>

	{{#attachment_url}}
		<p><a href="{{attachment_url}}">{{&attachment_title}}</a></p>
		<p>{{&attachment_content}}</p>
	{{/attachment_url}}
	{{^attachment_url}}
		<p>{{attachment_title}}</p>
	{{/attachment_url}}
</div>
{{/content}}

<?
include_once('../theme/comments.php');
?>

<?php
include_once('../theme/footer.php');
?>