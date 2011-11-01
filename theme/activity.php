<?php
include_once($this->SETTINGS_TEMPLATE_DIR . 'header.php');
?>

{{#content}}
	<div class="activity">
		<div class="meta">
			<p class="date">{{timestamp}}</p>
			<p> | </p>
			<p class="commentsCount">Comments: {{comments}}</p>
			<p> | </p>
			<p class="plusOneCount">
				<p class="plusOneCount"> 
					<div class="plusOneWrap">
						<!-- Place this tag where you want the +1 button to render -->
				        <g:plusone size="small" href="{{url}}"></g:plusone>
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
			</p>
			<div class="clear"></div>
		</div>
		
		<h2 class="title"><a href="{{local_url}}">{{title}}</a></h2>
			
			{{#annotation}}
				<p>{{{annotation}}}</p>
			{{/annotation}}

			<p>{{{content}}}</p>

			{{#attachment_url}}
				<p><a href="{{attachment_url}}">{{&attachment_title}}</a></p>
			{{/attachment_url}}
			{{^attachment_url}}
				<p>{{attachment_title}}</p>
			{{/attachment_url}}

			{{#attachment_content}}
				<p>{{attachment_content}}</p>
			{{/attachment_content}}
			
		<div class="attachment_media">
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
		</div>
		<div class="clear"><div>
	</div>
{{/content}}

<?
include_once($this->SETTINGS_TEMPLATE_DIR . 'comments.php');
?>

<?php
include_once($this->SETTINGS_TEMPLATE_DIR . 'footer.php');
?>