<div class="comments">
	<h2>Comments <span class="add_comment"><a target="_blank" href="{{#content}}{{url}}{{/content}}">Post a Comment</a></span></h2>
	<ul class="comment_list">
	{{#comments}}
		<li class="comment_item">
			<img class="comment_author_image" src="{{author_image}}" />
			<p class="comment_content">{{{content}}}</p>
			<div class="clear"></div>
		</li>
	{{/comments}}
	{{^comments}}
		<li class="comment_item">No comments :(</li>
	{{/comments}}
		<li class="comment_item"><a target="_blank" href="{{#content}}{{url}}{{/content}}">Post a Comment</a></li>
	</ul>
</div>