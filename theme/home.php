<?php
include_once('../theme/header.php');
?>

{{#content}}

<h1>Lyle Pratt . com</h1>
<ul>
{{#items}}
	<li>
		<h2><a href="?activity={{id}}">{{title}}</a></h2>
	</li>
{{/items}}
</ul>

{{/content}}

<?php
include_once('../theme/footer.php');
?>