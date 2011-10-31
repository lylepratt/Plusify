<?php
include_once($this->SETTINGS_TEMPLATE_DIR . 'header.php');
?>

{{#content}}
<h1 class="title">{{title}}</h1>
<div class="meta">
	<p class="commentsCount">Comments: {{comments}}</p>
	<p class="plusOneCount"> 
		<div class="plusOneWrap">
			<!-- Place this tag where you want the +1 button to render -->
	        <g:plusone size="medium" href="{{url}}"></g:plusone>
	        <!--  Place this tag after the last plusone tag -->
	        <script type="text/javascript">
	          (function() {
	            var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
	            po.src = 'https://apis.google.com/js/plusone.js';
	            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
	          })();
	        </script>
        </div>
	</p>
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
include_once($this->SETTINGS_TEMPLATE_DIR . 'comments.php');
?>

<?php
include_once($this->SETTINGS_TEMPLATE_DIR . 'footer.php');
?>