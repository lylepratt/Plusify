<?php
include_once($this->SETTINGS_TEMPLATE_DIR . 'header.php');
?>
<div id="top">
	<div id="features">
		{{#features}}
			<div class="featured_item">
				<a class="featured_item_link" href="{{local_url}}"><img class="featured_item_image" src="{{attachment_image}}" /></a>
				<div class="featured_item_title"><a href="{{local_url}}">{{title}}</a></div>
			</div>
		{{/features}}
	</div>
	<script>
		$(document).ready(function(){
			$('#features').cycle({
				timeout: 5000,
				fx: 'fade'
			});
		});
	</script>
</div>
<ul class="home_list">
{{#items}}
	<li class="list_item activity">
		<div class="meta">
			<p class="date">{{timestamp}}</p>
			<p> | </p>
			<p class="commentsCount">Comments: {{comments}}</p>
			<p> | </p>
			<p class="plusOneCount">+1s: {{plus_ones}}</p>
			<div class="clear"></div>
		</div>
		
		<h2 class="title"><a href="{{local_url}}">{{title}}</a></h2>
			
			{{#reshared_author}}
				<p>Reshared post from {{reshared_author}}</p>
			{{/reshared_author}}

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
				<p>{{&attachment_content}}</p>
			{{/attachment_content}}

		<div class="attachment_media">
		{{#attachment_video}}
			<iframe class="youtube-player" type="text/html" width="560" height="355" src="http://www.youtube.com/embed/{{attachment_video_id}}" frameborder="0"></iframe>
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
	</li>
{{/items}}
</ul>

<?php
include_once($this->SETTINGS_TEMPLATE_DIR . 'footer.php');
?>