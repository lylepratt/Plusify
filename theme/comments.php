<div id="comments">
	<h2>Comments</h2>
	<ul>
	{{#comments}}
			<li>
				<p>{{{content}}}</p>
			</li>
	{{/comments}}
	{{^comments}}
		No comments :(
	{{/comments}}
	</ul>
	
</div>