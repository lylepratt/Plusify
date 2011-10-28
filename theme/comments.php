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