<?php
include_once('../theme/header.php');
?>

{{#content}}
<h1 class="title">{{title}}</h1>

	{{#object}}
		<div class="meta">
			<p class="commentsCount">Comments: {{#replies}}{{totalItems}}{{/replies}}</p>
			<p class="plusOneCount">+1s: {{#plusoners}}{{totalItems}}{{/plusoners}}</p>
			<p class="timestamp">Timestamp: {{published}}</p>
		</div>
		
		<div id="post">
		{{#attachments}}			
			<p>{{#url}}<a href="{{url}}">{{displayName}}</a>{{/url}}{{^url}}{{displayName}}{{/url}}</p>
			<p>{{{content}}}</p>			
		{{/attachments}}

		{{^attachments}}
			<p>{{content}}</p>
		{{/attachments}}
		</div>
	{{/object}}
</p>

{{/content}}

<div id="comments">
	<h2>Comments</h2>
	{{#comments}}
		<ul>
		{{#items}}
			<li>
			{{#object}}
				{{{content}}}
			{{/object}}
			</li>
		{{/items}}
		</ul>
		{{^items}}
  			No comments :(
		{{/items}}
	{{/comments}}
</div>

<?php
include_once('../theme/footer.php');
?>