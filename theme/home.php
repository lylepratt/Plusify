<?php
include_once('../theme/header.php');
?>



<h1>Lyle Pratt . com</h1>
<ul>
{{#items}}
	<li>
		<h2 class="title"><a href="{{local_url}}">{{title}}</a></h2>
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
			{{/attachment_url}}
			{{^attachment_url}}
				<p>{{attachment_title}}</p>
			{{/attachment_url}}
		</div>

	</li>
{{/items}}
</ul>

<?php
include_once('../theme/footer.php');
?>