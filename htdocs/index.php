<?php

/* ===================
*  ATTENTION
*  ===================
*  BEFORE THIS WILL WORK YOU NEED TO COMPLETE THE CONFIGURATION OPTIONS BELOW!
*/

$plusify = new Plusify;

echo $plusify->routeRequest();

/**
 * Plusify. A simple class to create a blog from your Google Plus profile.
 *
 * {@link https://github.com/lylepratt/Plusify/}
 *
 * Plusify uses the Google+ API to populate a blog from your public Google+ posts.
 * It stores profile data, posts, and comments in a SQLITE database, so this has the
 * added benefit of keeping your Google+ Posts "backed up".
 *
 * @author Lyle Pratt {@link http://www.lylepratt.com}
 */
class Plusify {

	/* START CONFIGURATION */
	
	private $SETTINGS_API_URL = "https://www.googleapis.com/plus/v1";  //Don't change this
	private $SETTINGS_API_KEY = "YOUR GOOGLE API KEY";  //You can get one at https://code.google.com/apis/console/
	public  $SETTINGS_GOOGLE_ID = "YOUR GOOGLE + ID";  //The big number in your Google+ profile URL
	private $SETTINGS_CLEAN_URLS = true;  //You need mod_rewrite enabled to enable this
	private $SETTINGS_TEMPLATE_DIR = "../theme/";  //Theme files go in this directory
	private $SETTINGS_ROOT_URL = "/";  //Use this if you want put this at something like yoursite.com/blog/. In that case it should be /blog/
	private $SETTINGS_SQLITE_FILE = "../plusify.sql";  //Make sure it and its parent directory is writable by your web server 
	private $SETTINGS_TIME_BETWEEN_UPDATES = 30;  //seconds between checks to the api for updates. It only checks when a page is loaded.
	
	/* END CONFIGURATION */

	private $message;
	private $db;
	private $mustache;

	function __construct() {
		$this->mustache = new Mustache;

		if ($this->db = new SQLiteDatabase($this->SETTINGS_SQLITE_FILE)) {
			$test_activity = @$this->db->query('SELECT * FROM activity WHERE id = 1');
			if($test_activity === false) {
				$activity = "CREATE TABLE activity (
					id TEXT PRIMARY KEY,
					local_url TEXT NOT NULL,
					url TEXT NOT NULL,
					timestamp TEXT NOT NULL,
					author TEXT NOT NULL,
					author_id TEXT NOT NULL,
					author_image TEXT NOT NULL,
					author_url TEXT NOT NULL,
					comments INTEGER NOT NULL,
					plus_ones INTEGER NOT NULL,
					content TEXT NOT NULL,
					title TEXT NOT NULL,
					reshared_author TEXT,
					reshared_author_url TEXT,
					reshared_author_image TEXT,
					attachment_title TEXT,
					attachment_content TEXT,
					attachment_url TEXT,
					attachment_video TEXT,
					attachment_video_id TEXT,
					attachment_image TEXT
				);";
					$create_activity = $this->db->query("{$activity}");
			}

			$test_comment = @$this->db->query('SELECT * FROM comment WHERE id = 1');
			if($test_comment === false) {
				$comment = "CREATE TABLE comment (
					id TEXT PRIMARY KEY,
					activity_id TEXT NOT NULL,
					activity_url TEXT NOT NULL,
					timestamp TEXT NOT NULL,
					url TEXT NOT NULL,
					author TEXT NOT NULL,
					author_id TEXT NOT NULL,
					author_url TEXT NOT NULL,
					author_image TEXT NOT NULL,
					content TEXT NOT NULL,
					FOREIGN KEY(activity_id) REFERENCES activity(id)
				);";
				$create_comment = $this->db->query("{$comment}");
			}

			$test_person = @$this->db->query('SELECT * FROM person WHERE id = 1');
			if($test_person === false) {
				$person = "CREATE TABLE person (
					id TEXT PRIMARY KEY,
					display_name TEXT,
					given_name TEXT,
					middle_name TEXT,
					family_name TEXT,
					nickname TEXT,
					tagline TEXT,
					birthday TEXT,
					gender TEXT,
					about_me TEXT,
					current_location TEXT,
					url TEXT,
					image TEXT
				);";
				$create_person = $this->db->query("{$person}");
			}

			$test_meta = @$this->db->query('SELECT * FROM meta');
			if($test_meta === false) {
				$meta = "CREATE TABLE meta (
					key TEXT PRIMARY KEY,
					value TEXT NOT NULL
				);";
				$create_meta = $this->db->query("{$meta}");
			}
		}
	}

	/*
	* CORE APPLICATION ROUTING AND VIEWS ARE HERE
	*/
	function routeRequest() {
		if(isset($_GET['activity'])) {
			$activity_id = $_GET['activity'];

			$object['content'] = $this->getActivity($activity_id);
			$object['person'] = $this->getPerson($this->SETTINGS_GOOGLE_ID);
			$object['comments'] = $this->getComments($activity_id);
			if($object['content']) {
				$template = $this->renderPage('activity.php');
				return $this->mustache->render($template, $object);
			}
			else {
				$template = $this->render404();
				return $this->mustache->render($template, array());
			}
		}
		else if(isset($_GET['style'])) {
			$template = $this->renderPage('style.css');
			return $template;
		}
		else {
			$object['person'] = $this->getPerson($this->SETTINGS_GOOGLE_ID);
			$object['items'] = $this->getActivityList($this->SETTINGS_GOOGLE_ID);
			$object['features'] = $this->getRecentPhotos($this->SETTINGS_GOOGLE_ID);
			$template = $this->renderPage('home.php');
			return $this->mustache->render($template, $object);
		}
	}

	function sqliteInsertArrayQuery($table, $data) {
		foreach ($data as $field=>$value) {
			$fields[] = "'" . $field . "'";
			$values[] = "'" . sqlite_escape_string($value) . "'";
		}
		$field_list = join(',', $fields);
		$value_list = join(', ', $values);
		
		$query = "INSERT INTO '" . $table . "' (" . $field_list . ") VALUES (" . $value_list . ")";
		
		return $query;
	}

	function doRequest($url, $multi_request=false) {
		if(!$multi_request) {
			$ch = curl_init( $url . "?key={$this->SETTINGS_API_KEY}&userIp={$_SERVER['REMOTE_ADDR']}");
		}
		else {
			$ch = curl_init( $url . "&key={$this->SETTINGS_API_KEY}&userIp={$_SERVER['REMOTE_ADDR']}");	
		}

		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => array('Content-type: application/json') ,
		);

		curl_setopt_array( $ch, $options );

		$result = curl_exec($ch);
		$result = json_decode($result, true);
		
		//echo "<pre>";
		//print_r($result);
		//echo "</pre>";
		//flush();
		if(isset($result['error'])) {
			return false;
		}
		else {
			return $result;
		}
	}

	function doMultiRequest($url, $max_results=20) {
		$result = $this->doRequest($url);
		for($i=count($result['items']); $i <= $max_results; $i+count($result['items'])) {
			$sub_request = $this->doRequest($result['nextLink'], true);
			if(isset($sub_request['items'])) {
				array_push($result['items'], $sub_request['items']);
			}
			else {
				break 1;
			}
		}
		return $result;
	}

	function renderPage($template) {
		$template = $this->SETTINGS_TEMPLATE_DIR . $template;
		if(file_exists($template)) {
			ob_start();
			require_once($template);
			$output = ob_get_contents();
			ob_end_clean();
		}
		else {
			header('HTTP/1.0 500 Server Error');
			$output = "<h1>500!</h1><p>Template not found!</p>";
		}
		return $output;
	}

	function render404() {
		$template = $this->SETTINGS_TEMPLATE_DIR . "404.php";
		if(file_exists($template)) {
			ob_start();
			require_once($template);
			$output = ob_get_contents();
			ob_end_clean();
		}
		else {
			header('HTTP/1.0 404 Not Found');
			$output = "<h1>Aww Shucks! 404!</h1><p>You found an unknown page! How does that even happen?</p>";
		}
		return $output;
	}

	function getRawPerson($id) {
		return $this->doRequest("{$this->SETTINGS_API_URL}/people/{$id}");
	}

	function getRawActivityList($person_id) {
		return $this->doMultiRequest("{$this->SETTINGS_API_URL}/people/{$person_id}/activities/public");	
	}

	function getRawActivity($id) {
		return $this->doRequest("{$this->SETTINGS_API_URL}/activities/{$id}");	
	}

	function getRawComments($id) {
		return $this->doRequest("{$this->SETTINGS_API_URL}/activities/{$id}/comments");
	}

	function getOrNone($object, $key) {
		if(isset($object[$key])) {
			return $object[$key];
		}
		else {
			return "";
		}
	}

	function checkForUpdate($key, $force_check=false) {
		$query_check = "SELECT value FROM meta where key = '{$key}';";
		$query_check_result = $this->db->query("{$query_check}");
		$current_time = time();
		if($query_check_result->numRows() == 0) {
			$query_check_insert = "INSERT INTO meta (key, value) VALUES ('{$key}', '{$current_time}');";
			$query_check_insert_result= $this->db->query("{$query_check_insert}");
			$last_checked = $current_time;
			$force_check = true;
		}
		else{
			$last_checked = $query_check_result->fetchAll(SQLITE_ASSOC);
			$last_checked = $last_checked[0]['value'];
		}

		if((($current_time - $last_checked) > $this->SETTINGS_TIME_BETWEEN_UPDATES) || $force_check) {
			$query_check_update = "UPDATE meta SET value = '{$current_time}' WHERE key = '{$key}';";
			$query_check_update_result= $this->db->query("{$query_check_update}");
			return true;
		}
		else {
			return false;
		}
	}

	function getPerson($id, $force_check=false) {
		if($this->checkForUpdate("last_check_person_{$id}", $force_check)) {
			$this->message .= "<span>Checked for Person Updates</span>";

			$object = $this->getRawPerson($id);
			if($object) {
				$object = $this->extractPerson($object);
				$query_dup_check = "SELECT * FROM person WHERE id = '{$object['id']}';";
				$query_dup_check_result = $this->db->query("{$query_dup_check}");
				if($query_dup_check_result->numRows() == 0) {
					$insert_query = $this->sqliteInsertArrayQuery("person", $object);
					//echo "<BR>{$insert_query}<BR><BR><BR>";
					$insert_query_result = $this->db->query("{$insert_query}");				
				}
			}
		}

		$query = "SELECT * FROM person WHERE id = '{$id}';";
		$query_result = $this->db->query("{$query}");
		$object = $query_result->fetchAll(SQLITE_ASSOC);
		$object = $object[0];
		return $object;
	}

	function getRecentPhotos($person_id) {
		$query = "SELECT title, local_url, attachment_image, attachment_title, attachment_url FROM activity WHERE attachment_image NOT NULL LIMIT 5;";
		$query_result = $this->db->query("{$query}");
		$object = $query_result->fetchAll(SQLITE_ASSOC);
		$object = $object;
		return $object;
	}

	function getActivityList($person_id, $force_check=false) {
		if($this->checkForUpdate('last_check_activity_list', $force_check)) {
			$this->message .= "<span>Checked for Activity List Updates</span>";

			$object = $this->getRawActivityList($person_id);
			if($object) {
				$object = $this->extractActivities($object);
				foreach($object as $activity) {
					$query_dup_check = "SELECT * FROM activity WHERE id = '{$activity['id']}';";
					$query_dup_check_result = $this->db->query("{$query_dup_check}");
					if($query_dup_check_result->numRows() == 0) {
						$insert_query = $this->sqliteInsertArrayQuery("activity", $activity);
						//echo "<BR>{$insert_query}<BR><BR><BR>";
						$insert_query_result= $this->db->query("{$insert_query}");					
					}
				}
			}
		}

		$query = "SELECT * FROM activity LIMIT 100;";
		$query_result = $this->db->query("{$query}");
		$object = $query_result->fetchAll(SQLITE_ASSOC);
		$object = $object;
		return $object;
	}

	function getActivity($id, $force_check=false) {
		if($this->checkForUpdate("last_check_activity_{$id}", $force_check)) {
			$this->message .= "<span>Checked for Activity Updates</span>";

			$object = $this->getRawActivity($id);
			if($object) {
				$object = $this->extractActivity($object);
				$query_dup_check = "SELECT * FROM activity WHERE id = '{$object['id']}';";
				$query_dup_check_result = $this->db->query("{$query_dup_check}");
				if($query_dup_check_result->numRows() == 0) {
					$insert_query = $this->sqliteInsertArrayQuery("activity", $object);
					//echo "<BR>{$insert_query}<BR><BR><BR>";
					$insert_query_result = $this->db->query("{$insert_query}");				
				}
			}
		}

		$query = "SELECT * FROM activity WHERE id = '{$id}';";
		$query_result = $this->db->query("{$query}");
		$object = $query_result->fetchAll(SQLITE_ASSOC);
		$object = $object[0];
		return $object;
	}

	function getComments($id, $force_check=false) {
		if($this->checkForUpdate("last_check_comments_activity_{$id}", $force_check)) {
			$this->message .= "<span>Checked for Comment Updates</span>";

			$object = $this->getRawComments($id);
			if($object) {
				$object = $this->extractComments($object);
				foreach($object as $comment) {
					$query_dup_check = "SELECT * FROM comment WHERE id = '{$comment['id']}';";
					$query_dup_check_result = $this->db->query("{$query_dup_check}");
					if($query_dup_check_result->numRows() == 0) {
						$insert_query = $this->sqliteInsertArrayQuery("comment", $comment);
						//echo "{$insert_query}<BR><BR><BR>";
						$insert_query_result= $this->db->query("{$insert_query}");					
					}
				}
			}
		}

		$query = "SELECT * FROM comment WHERE activity_id = '{$id}';";
		$query_result = $this->db->query("{$query}");
		$object = $query_result->fetchAll(SQLITE_ASSOC);
		$object = $object;
		return $object;
	}

	function extractPerson($person) {
		$object = array();
		$object['id'] = $person['id'];
		$object['display_name'] = $this->getOrNone($person, 'displayName');
		if(isset($person['name'])) {
			$object['given_name'] = $this->getOrNone($person['name'], 'givenName');
			$object['middle_name'] = $this->getOrNone($person['name'], 'middleName');
			$object['family_name'] = $this->getOrNone($person['name'], 'familyName');
		}
		$object['nickname'] = $this->getOrNone($person, 'nickname');
		$object['tagline'] = $this->getOrNone($person, 'tagline');
		$object['birthday'] = $this->getOrNone($person, 'birthday');
		$object['gender'] = $this->getOrNone($person, 'gender');
		$object['about_me'] = $this->getOrNone($person, 'aboutMe');
		$object['current_location'] = $this->getOrNone($person, 'currentLocation');
		$object['url'] = $this->getOrNone($person, 'url');
		if(isset($person['image'])) {
			$object['image'] = $this->getOrNone($person['image'], 'url');
		}
		return $object;
	}

	function extractRecentImages($activities) {
		$objects = array();
		if(isset($activities['items'])) {
			foreach($activities['items'] as $key => $activity) {
				$extractedActivity = $this->extractActivity($activity);
				if(isset($extractedActivity['attachment_image'])) {
					$objects[] = "";
				}
			}
		}
		return $objects;
	}

	function extractActivities($activities) {
		$objects = array();
		if(isset($activities['items'])) {
			foreach($activities['items'] as $key => $activity) {
				$objects[$key] = $this->extractActivity($activity);
			}
		}
		return $objects;
	}

	function extractActivity($activity) {
		$object['id'] = $activity['id'];
		if($this->SETTINGS_CLEAN_URLS) {
			$object['local_url'] = "{$this->SETTINGS_ROOT_URL}activity/{$activity['id']}/";
		}
		else {
			$object['local_url'] = "{$this->SETTINGS_ROOT_URL}?activity={$activity['id']}";	
		}
		$object['url'] = $activity['url'];
		$object['timestamp'] = strftime("%m/%d/%y %I:%M %p", strtotime($activity['published']));
		$object['author'] = $activity['actor']['displayName'];
		$object['author_id'] = $activity['actor']['id'];
		$object['author_image'] = $activity['actor']['image']['url'];
		$object['author_url'] = $activity['actor']['url'];
		$object['comments'] = $activity['object']['replies']['totalItems'];
		$object['plus_ones'] = $activity['object']['plusoners']['totalItems'];
		$object['content'] = $activity['object']['content'];
		$object['title'] = $activity['title'];
		if(isset($object['annotation'])) {
			$object['annotation'] = $activity['annotation'];
		}
		$object['reshared_author'] = false;
		if(stripos($activity['title'], "Reshared") !== false) {
			$first_word = explode(" ", $activity['object']['content']);
			$first_word = strip_tags($first_word[0]);
			$author = explode("Reshared post from ", $activity['title']);
			$author = explode($first_word, $author[1]);
			$author = $author[0];
			$object['reshared_author'] = trim($author);
			$title_split =  explode($first_word, $activity['title']);
			$object['title'] = "{$first_word} {$title_split[1]}";
			$object['reshared_author'] = trim($author);
			$object['reshared_author_url'] = $activity['object']['actor']['url'];
			if(isset($activity['object']['actor']['image'])) {
				$object['reshared_author_image'] = $activity['object']['actor']['image']['url'];
			}
		}
		if(isset($activity['object']['attachments'])) {
			foreach($activity['object']['attachments'] as $attachment) {
				if($attachment['objectType'] == "article") {
					$object['attachment_title'] = $attachment['displayName'];
					if(isset($attachment['content'])) {
						$object['attachment_content'] = $attachment['content'];
					}
					$object['attachment_url'] =$attachment['url'];
				}
				if($attachment['objectType'] == "photo") {
					$object['attachment_image'] = $attachment['fullImage']['url'];
				}
				if($attachment['objectType'] == "video") {
					$object['attachment_title'] = $attachment['displayName'];
					if(isset($attachment['content'])) {
						$object['attachment_content'] = $attachment['content'];
					}
					$object['attachment_url'] =$attachment['url'];
					$object['attachment_video'] = $attachment['url'];
					$video_id = explode("/v/", $attachment['url']);
					$video_id = explode("&", $video_id[1]);
					$video_id = $video_id[0];
					$object['attachment_video_id'] = $video_id;
					$object['attachment_image'] = $attachment['image']['url'];
				}
			}
		}
		return $object;
	}

	function extractComments($comments) {
		$objects = array();
		if(isset($comments['items'])) {
			foreach($comments['items'] as $key => $comment) {
				$objects[$key] = $this->extractComment($comment);
			}
		}
		return $objects;
	}

	function extractComment($comment) {
		$object['id'] = $comment['id'];
		$object['activity_id'] = $comment['inReplyTo'][0]['id'];
		$object['activity_url'] = $comment['inReplyTo'][0]['url'];
		$object['timestamp'] = $comment['published'];
		$object['url'] = $comment['selfLink'];
		$object['author'] = $comment['actor']['displayName'];
		$object['author_id'] = $comment['actor']['id'];
		$object['author_url'] = $comment['actor']['url'];
		$object['author_image'] = $comment['actor']['image']['url'];
		$object['content'] = $comment['object']['content'];
		return $object;
	}
}

/**
 * A Mustache implementation in PHP.
 *
 * {@link http://defunkt.github.com/mustache}
 *
 * Mustache is a framework-agnostic logic-less templating language. It enforces separation of view
 * logic from template files. In fact, it is not even possible to embed logic in the template.
 *
 * This is very, very rad.
 *
 * @author Justin Hileman {@link http://justinhileman.com}
 */
class Mustache {

	const VERSION      = '0.8.1';
	const SPEC_VERSION = '1.1.2';

	/**
	 * Should this Mustache throw exceptions when it finds unexpected tags?
	 *
	 * @see self::_throwsException()
	 */
	protected $_throwsExceptions = array(
		MustacheException::UNKNOWN_VARIABLE         => false,
		MustacheException::UNCLOSED_SECTION         => true,
		MustacheException::UNEXPECTED_CLOSE_SECTION => true,
		MustacheException::UNKNOWN_PARTIAL          => false,
		MustacheException::UNKNOWN_PRAGMA           => true,
	);

	// Override charset passed to htmlentities() and htmlspecialchars(). Defaults to UTF-8.
	protected $_charset = 'UTF-8';

	/**
	 * Pragmas are macro-like directives that, when invoked, change the behavior or
	 * syntax of Mustache.
	 *
	 * They should be considered extremely experimental. Most likely their implementation
	 * will change in the future.
	 */

	/**
	 * The {{%UNESCAPED}} pragma swaps the meaning of the {{normal}} and {{{unescaped}}}
	 * Mustache tags. That is, once this pragma is activated the {{normal}} tag will not be
	 * escaped while the {{{unescaped}}} tag will be escaped.
	 *
	 * Pragmas apply only to the current template. Partials, even those included after the
	 * {{%UNESCAPED}} call, will need their own pragma declaration.
	 *
	 * This may be useful in non-HTML Mustache situations.
	 */
	const PRAGMA_UNESCAPED    = 'UNESCAPED';

	/**
	 * Constants used for section and tag RegEx
	 */
	const SECTION_TYPES = '\^#\/';
	const TAG_TYPES = '#\^\/=!<>\\{&';

	protected $_otag = '{{';
	protected $_ctag = '}}';

	protected $_tagRegEx;

	protected $_template = '';
	protected $_context  = array();
	protected $_partials = array();
	protected $_pragmas  = array();

	protected $_pragmasImplemented = array(
		self::PRAGMA_UNESCAPED
	);

	protected $_localPragmas = array();

	/**
	 * Mustache class constructor.
	 *
	 * This method accepts a $template string and a $view object. Optionally, pass an associative
	 * array of partials as well.
	 *
	 * Passing an $options array allows overriding certain Mustache options during instantiation:
	 *
	 *     $options = array(
	 *         // `charset` -- must be supported by `htmlspecialentities()`. defaults to 'UTF-8'
	 *         'charset' => 'ISO-8859-1',
	 *
	 *         // opening and closing delimiters, as an array or a space-separated string
	 *         'delimiters' => '<% %>',
	 *
	 *         // an array of pragmas to enable/disable
	 *         'pragmas' => array(
	 *             Mustache::PRAGMA_UNESCAPED => true
	 *         ),
	 *     );
	 *
	 * @access public
	 * @param string $template (default: null)
	 * @param mixed $view (default: null)
	 * @param array $partials (default: null)
	 * @param array $options (default: array())
	 * @return void
	 */
	public function __construct($template = null, $view = null, $partials = null, array $options = null) {
		if ($template !== null) $this->_template = $template;
		if ($partials !== null) $this->_partials = $partials;
		if ($view !== null)     $this->_context = array($view);
		if ($options !== null)  $this->_setOptions($options);
	}

	/**
	 * Helper function for setting options from constructor args.
	 *
	 * @access protected
	 * @param array $options
	 * @return void
	 */
	protected function _setOptions(array $options) {
		if (isset($options['charset'])) {
			$this->_charset = $options['charset'];
		}

		if (isset($options['delimiters'])) {
			$delims = $options['delimiters'];
			if (!is_array($delims)) {
				$delims = array_map('trim', explode(' ', $delims, 2));
			}
			$this->_otag = $delims[0];
			$this->_ctag = $delims[1];
		}

		if (isset($options['pragmas'])) {
			foreach ($options['pragmas'] as $pragma_name => $pragma_value) {
				if (!in_array($pragma_name, $this->_pragmasImplemented, true)) {
					throw new MustacheException('Unknown pragma: ' . $pragma_name, MustacheException::UNKNOWN_PRAGMA);
				}
			}
			$this->_pragmas = $options['pragmas'];
		}
	}

	/**
	 * Mustache class clone method.
	 *
	 * A cloned Mustache instance should have pragmas, delimeters and root context
	 * reset to default values.
	 *
	 * @access public
	 * @return void
	 */
	public function __clone() {
		$this->_otag = '{{';
		$this->_ctag = '}}';
		$this->_localPragmas = array();

		if ($keys = array_keys($this->_context)) {
			$last = array_pop($keys);
			if ($this->_context[$last] instanceof Mustache) {
				$this->_context[$last] =& $this;
			}
		}
	}

	/**
	 * Render the given template and view object.
	 *
	 * Defaults to the template and view passed to the class constructor unless a new one is provided.
	 * Optionally, pass an associative array of partials as well.
	 *
	 * @access public
	 * @param string $template (default: null)
	 * @param mixed $view (default: null)
	 * @param array $partials (default: null)
	 * @return string Rendered Mustache template.
	 */
	public function render($template = null, $view = null, $partials = null) {
		if ($template === null) $template = $this->_template;
		if ($partials !== null) $this->_partials = $partials;

		$otag_orig = $this->_otag;
		$ctag_orig = $this->_ctag;

		if ($view) {
			$this->_context = array($view);
		} else if (empty($this->_context)) {
			$this->_context = array($this);
		}

		$template = $this->_renderPragmas($template);
		$template = $this->_renderTemplate($template, $this->_context);

		$this->_otag = $otag_orig;
		$this->_ctag = $ctag_orig;

		return $template;
	}

	/**
	 * Wrap the render() function for string conversion.
	 *
	 * @access public
	 * @return string
	 */
	public function __toString() {
		// PHP doesn't like exceptions in __toString.
		// catch any exceptions and convert them to strings.
		try {
			$result = $this->render();
			return $result;
		} catch (Exception $e) {
			return "Error rendering mustache: " . $e->getMessage();
		}
	}

	/**
	 * Internal render function, used for recursive calls.
	 *
	 * @access protected
	 * @param string $template
	 * @return string Rendered Mustache template.
	 */
	protected function _renderTemplate($template) {
		if ($section = $this->_findSection($template)) {
			list($before, $type, $tag_name, $content, $after) = $section;

			$rendered_before = $this->_renderTags($before);

			$rendered_content = '';
			$val = $this->_getVariable($tag_name);
			switch($type) {
				// inverted section
				case '^':
					if (empty($val)) {
						$rendered_content = $this->_renderTemplate($content);
					}
					break;

				// regular section
				case '#':
					// higher order sections
					if ($this->_varIsCallable($val)) {
						$rendered_content = $this->_renderTemplate(call_user_func($val, $content));
					} else if ($this->_varIsIterable($val)) {
						foreach ($val as $local_context) {
							$this->_pushContext($local_context);
							$rendered_content .= $this->_renderTemplate($content);
							$this->_popContext();
						}
					} else if ($val) {
						if (is_array($val) || is_object($val)) {
							$this->_pushContext($val);
							$rendered_content = $this->_renderTemplate($content);
							$this->_popContext();
						} else {
							$rendered_content = $this->_renderTemplate($content);
						}
					}
					break;
			}

			return $rendered_before . $rendered_content . $this->_renderTemplate($after);
		}

		return $this->_renderTags($template);
	}

	/**
	 * Prepare a section RegEx string for the given opening/closing tags.
	 *
	 * @access protected
	 * @param string $otag
	 * @param string $ctag
	 * @return string
	 */
	protected function _prepareSectionRegEx($otag, $ctag) {
		return sprintf(
			'/(?:(?<=\\n)[ \\t]*)?%s(?:(?P<type>[%s])(?P<tag_name>.+?)|=(?P<delims>.*?)=)%s\\n?/s',
			preg_quote($otag, '/'),
			self::SECTION_TYPES,
			preg_quote($ctag, '/')
		);
	}

	/**
	 * Extract the first section from $template.
	 *
	 * @access protected
	 * @param string $template
	 * @return array $before, $type, $tag_name, $content and $after
	 */
	protected function _findSection($template) {
		$regEx = $this->_prepareSectionRegEx($this->_otag, $this->_ctag);

		$section_start = null;
		$section_type  = null;
		$content_start = null;

		$search_offset = 0;

		$section_stack = array();
		$matches = array();
		while (preg_match($regEx, $template, $matches, PREG_OFFSET_CAPTURE, $search_offset)) {
			if (isset($matches['delims'][0])) {
				list($otag, $ctag) = explode(' ', $matches['delims'][0]);
				$regEx = $this->_prepareSectionRegEx($otag, $ctag);
				$search_offset = $matches[0][1] + strlen($matches[0][0]);
				continue;
			}

			$match    = $matches[0][0];
			$offset   = $matches[0][1];
			$type     = $matches['type'][0];
			$tag_name = trim($matches['tag_name'][0]);

			$search_offset = $offset + strlen($match);

			switch ($type) {
				case '^':
				case '#':
					if (empty($section_stack)) {
						$section_start = $offset;
						$section_type  = $type;
						$content_start = $search_offset;
					}
					array_push($section_stack, $tag_name);
					break;
				case '/':
					if (empty($section_stack) || ($tag_name !== array_pop($section_stack))) {
						if ($this->_throwsException(MustacheException::UNEXPECTED_CLOSE_SECTION)) {
							throw new MustacheException('Unexpected close section: ' . $tag_name, MustacheException::UNEXPECTED_CLOSE_SECTION);
						}
					}

					if (empty($section_stack)) {
						// $before, $type, $tag_name, $content, $after
						return array(
							substr($template, 0, $section_start),
							$section_type,
							$tag_name,
							substr($template, $content_start, $offset - $content_start),
							substr($template, $search_offset),
						);
					}
					break;
			}
		}

		if (!empty($section_stack)) {
			if ($this->_throwsException(MustacheException::UNCLOSED_SECTION)) {
				throw new MustacheException('Unclosed section: ' . $section_stack[0], MustacheException::UNCLOSED_SECTION);
			}
		}
	}

	/**
	 * Prepare a pragma RegEx for the given opening/closing tags.
	 *
	 * @access protected
	 * @param string $otag
	 * @param string $ctag
	 * @return string
	 */
	protected function _preparePragmaRegEx($otag, $ctag) {
		return sprintf(
			'/%s%%\\s*(?P<pragma_name>[\\w_-]+)(?P<options_string>(?: [\\w]+=[\\w]+)*)\\s*%s\\n?/s',
			preg_quote($otag, '/'),
			preg_quote($ctag, '/')
		);
	}

	/**
	 * Initialize pragmas and remove all pragma tags.
	 *
	 * @access protected
	 * @param string $template
	 * @return string
	 */
	protected function _renderPragmas($template) {
		$this->_localPragmas = $this->_pragmas;

		// no pragmas
		if (strpos($template, $this->_otag . '%') === false) {
			return $template;
		}

		$regEx = $this->_preparePragmaRegEx($this->_otag, $this->_ctag);
		return preg_replace_callback($regEx, array($this, '_renderPragma'), $template);
	}

	/**
	 * A preg_replace helper to remove {{%PRAGMA}} tags and enable requested pragma.
	 *
	 * @access protected
	 * @param mixed $matches
	 * @return void
	 * @throws MustacheException unknown pragma
	 */
	protected function _renderPragma($matches) {
		$pragma         = $matches[0];
		$pragma_name    = $matches['pragma_name'];
		$options_string = $matches['options_string'];

		if (!in_array($pragma_name, $this->_pragmasImplemented)) {
			throw new MustacheException('Unknown pragma: ' . $pragma_name, MustacheException::UNKNOWN_PRAGMA);
		}

		$options = array();
		foreach (explode(' ', trim($options_string)) as $o) {
			if ($p = trim($o)) {
				$p = explode('=', $p);
				$options[$p[0]] = $p[1];
			}
		}

		if (empty($options)) {
			$this->_localPragmas[$pragma_name] = true;
		} else {
			$this->_localPragmas[$pragma_name] = $options;
		}

		return '';
	}

	/**
	 * Check whether this Mustache has a specific pragma.
	 *
	 * @access protected
	 * @param string $pragma_name
	 * @return bool
	 */
	protected function _hasPragma($pragma_name) {
		if (array_key_exists($pragma_name, $this->_localPragmas) && $this->_localPragmas[$pragma_name]) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Return pragma options, if any.
	 *
	 * @access protected
	 * @param string $pragma_name
	 * @return mixed
	 * @throws MustacheException Unknown pragma
	 */
	protected function _getPragmaOptions($pragma_name) {
		if (!$this->_hasPragma($pragma_name)) {
			throw new MustacheException('Unknown pragma: ' . $pragma_name, MustacheException::UNKNOWN_PRAGMA);
		}

		return (is_array($this->_localPragmas[$pragma_name])) ? $this->_localPragmas[$pragma_name] : array();
	}

	/**
	 * Check whether this Mustache instance throws a given exception.
	 *
	 * Expects exceptions to be MustacheException error codes (i.e. class constants).
	 *
	 * @access protected
	 * @param mixed $exception
	 * @return void
	 */
	protected function _throwsException($exception) {
		return (isset($this->_throwsExceptions[$exception]) && $this->_throwsExceptions[$exception]);
	}

	/**
	 * Prepare a tag RegEx for the given opening/closing tags.
	 *
	 * @access protected
	 * @param string $otag
	 * @param string $ctag
	 * @return string
	 */
	protected function _prepareTagRegEx($otag, $ctag, $first = false) {
		return sprintf(
			'/(?P<leading>(?:%s\\r?\\n)[ \\t]*)?%s(?P<type>[%s]?)(?P<tag_name>.+?)(?:\\2|})?%s(?P<trailing>\\s*(?:\\r?\\n|\\Z))?/s',
			($first ? '\\A|' : ''),
			preg_quote($otag, '/'),
			self::TAG_TYPES,
			preg_quote($ctag, '/')
		);
	}

	/**
	 * Loop through and render individual Mustache tags.
	 *
	 * @access protected
	 * @param string $template
	 * @return void
	 */
	protected function _renderTags($template) {
		if (strpos($template, $this->_otag) === false) {
			return $template;
		}

		$first = true;
		$this->_tagRegEx = $this->_prepareTagRegEx($this->_otag, $this->_ctag, true);

		$html = '';
		$matches = array();
		while (preg_match($this->_tagRegEx, $template, $matches, PREG_OFFSET_CAPTURE)) {
			$tag      = $matches[0][0];
			$offset   = $matches[0][1];
			$modifier = $matches['type'][0];
			$tag_name = trim($matches['tag_name'][0]);

			if (isset($matches['leading']) && $matches['leading'][1] > -1) {
				$leading = $matches['leading'][0];
			} else {
				$leading = null;
			}

			if (isset($matches['trailing']) && $matches['trailing'][1] > -1) {
				$trailing = $matches['trailing'][0];
			} else {
				$trailing = null;
			}

			$html .= substr($template, 0, $offset);

			$next_offset = $offset + strlen($tag);
			if ((substr($html, -1) == "\n") && (substr($template, $next_offset, 1) == "\n")) {
				$next_offset++;
			}
			$template = substr($template, $next_offset);

			$html .= $this->_renderTag($modifier, $tag_name, $leading, $trailing);

			if ($first == true) {
				$first = false;
				$this->_tagRegEx = $this->_prepareTagRegEx($this->_otag, $this->_ctag);
			}
		}

		return $html . $template;
	}

	/**
	 * Render the named tag, given the specified modifier.
	 *
	 * Accepted modifiers are `=` (change delimiter), `!` (comment), `>` (partial)
	 * `{` or `&` (don't escape output), or none (render escaped output).
	 *
	 * @access protected
	 * @param string $modifier
	 * @param string $tag_name
	 * @param string $leading Whitespace
	 * @param string $trailing Whitespace
	 * @throws MustacheException Unmatched section tag encountered.
	 * @return string
	 */
	protected function _renderTag($modifier, $tag_name, $leading, $trailing) {
		switch ($modifier) {
			case '=':
				return $this->_changeDelimiter($tag_name, $leading, $trailing);
				break;
			case '!':
				return $this->_renderComment($tag_name, $leading, $trailing);
				break;
			case '>':
			case '<':
				return $this->_renderPartial($tag_name, $leading, $trailing);
				break;
			case '{':
				// strip the trailing } ...
				if ($tag_name[(strlen($tag_name) - 1)] == '}') {
					$tag_name = substr($tag_name, 0, -1);
				}
			case '&':
				if ($this->_hasPragma(self::PRAGMA_UNESCAPED)) {
					return $this->_renderEscaped($tag_name, $leading, $trailing);
				} else {
					return $this->_renderUnescaped($tag_name, $leading, $trailing);
				}
				break;
			case '#':
			case '^':
			case '/':
				// remove any leftover section tags
				return $leading . $trailing;
				break;
			default:
				if ($this->_hasPragma(self::PRAGMA_UNESCAPED)) {
					return $this->_renderUnescaped($modifier . $tag_name, $leading, $trailing);
				} else {
					return $this->_renderEscaped($modifier . $tag_name, $leading, $trailing);
				}
				break;
		}
	}

	/**
	 * Returns true if any of its args contains the "\r" character.
	 *
	 * @access protected
	 * @param string $str
	 * @return boolean
	 */
	protected function _stringHasR($str) {
		foreach (func_get_args() as $arg) {
			if (strpos($arg, "\r") !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Escape and return the requested tag.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param string $leading Whitespace
	 * @param string $trailing Whitespace
	 * @return string
	 */
	protected function _renderEscaped($tag_name, $leading, $trailing) {
		$rendered = htmlentities($this->_renderUnescaped($tag_name, '', ''), ENT_COMPAT, $this->_charset);
		return $leading . $rendered . $trailing;
	}

	/**
	 * Render a comment (i.e. return an empty string).
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param string $leading Whitespace
	 * @param string $trailing Whitespace
	 * @return string
	 */
	protected function _renderComment($tag_name, $leading, $trailing) {
		if ($leading !== null && $trailing !== null) {
			if (strpos($leading, "\n") === false) {
				return '';
			}
			return $this->_stringHasR($leading, $trailing) ? "\r\n" : "\n";
		}
		return $leading . $trailing;
	}

	/**
	 * Return the requested tag unescaped.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param string $leading Whitespace
	 * @param string $trailing Whitespace
	 * @return string
	 */
	protected function _renderUnescaped($tag_name, $leading, $trailing) {
		$val = $this->_getVariable($tag_name);

		if ($this->_varIsCallable($val)) {
			$val = $this->_renderTemplate(call_user_func($val));
		}

		return $leading . $val . $trailing;
	}

	/**
	 * Render the requested partial.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param string $leading Whitespace
	 * @param string $trailing Whitespace
	 * @return string
	 */
	protected function _renderPartial($tag_name, $leading, $trailing) {
		$partial = $this->_getPartial($tag_name);
		if ($leading !== null && $trailing !== null) {
			$whitespace = trim($leading, "\r\n");
			$partial = preg_replace('/(\\r?\\n)(?!$)/s', "\\1" . $whitespace, $partial);
		}

		$view = clone($this);

		if ($leading !== null && $trailing !== null) {
			return $leading . $view->render($partial);
		} else {
			return $leading . $view->render($partial) . $trailing;
		}
	}

	/**
	 * Change the Mustache tag delimiter. This method also replaces this object's current
	 * tag RegEx with one using the new delimiters.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param string $leading Whitespace
	 * @param string $trailing Whitespace
	 * @return string
	 */
	protected function _changeDelimiter($tag_name, $leading, $trailing) {
		list($otag, $ctag) = explode(' ', $tag_name);
		$this->_otag = $otag;
		$this->_ctag = $ctag;

		$this->_tagRegEx = $this->_prepareTagRegEx($this->_otag, $this->_ctag);

		if ($leading !== null && $trailing !== null) {
			if (strpos($leading, "\n") === false) {
				return '';
			}
			return $this->_stringHasR($leading, $trailing) ? "\r\n" : "\n";
		}
		return $leading . $trailing;
	}

	/**
	 * Push a local context onto the stack.
	 *
	 * @access protected
	 * @param array &$local_context
	 * @return void
	 */
	protected function _pushContext(&$local_context) {
		$new = array();
		$new[] =& $local_context;
		foreach (array_keys($this->_context) as $key) {
			$new[] =& $this->_context[$key];
		}
		$this->_context = $new;
	}

	/**
	 * Remove the latest context from the stack.
	 *
	 * @access protected
	 * @return void
	 */
	protected function _popContext() {
		$new = array();

		$keys = array_keys($this->_context);
		array_shift($keys);
		foreach ($keys as $key) {
			$new[] =& $this->_context[$key];
		}
		$this->_context = $new;
	}

	/**
	 * Get a variable from the context array.
	 *
	 * If the view is an array, returns the value with array key $tag_name.
	 * If the view is an object, this will check for a public member variable
	 * named $tag_name. If none is available, this method will execute and return
	 * any class method named $tag_name. Failing all of the above, this method will
	 * return an empty string.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @throws MustacheException Unknown variable name.
	 * @return string
	 */
	protected function _getVariable($tag_name) {
		if ($tag_name === '.') {
			return $this->_context[0];
		} else if (strpos($tag_name, '.') !== false) {
			$chunks = explode('.', $tag_name);
			$first = array_shift($chunks);

			$ret = $this->_findVariableInContext($first, $this->_context);
			while ($next = array_shift($chunks)) {
				// Slice off a chunk of context for dot notation traversal.
				$c = array($ret);
				$ret = $this->_findVariableInContext($next, $c);
			}
			return $ret;
		} else {
			return $this->_findVariableInContext($tag_name, $this->_context);
		}
	}

	/**
	 * Get a variable from the context array. Internal helper used by getVariable() to abstract
	 * variable traversal for dot notation.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @param array $context
	 * @throws MustacheException Unknown variable name.
	 * @return string
	 */
	protected function _findVariableInContext($tag_name, $context) {
		foreach ($context as $view) {
			if (is_object($view)) {
				if (method_exists($view, $tag_name)) {
					return $view->$tag_name();
				} else if (isset($view->$tag_name)) {
					return $view->$tag_name;
				}
			} else if (is_array($view) && array_key_exists($tag_name, $view)) {
				return $view[$tag_name];
			}
		}

		if ($this->_throwsException(MustacheException::UNKNOWN_VARIABLE)) {
			throw new MustacheException("Unknown variable: " . $tag_name, MustacheException::UNKNOWN_VARIABLE);
		} else {
			return '';
		}
	}

	/**
	 * Retrieve the partial corresponding to the requested tag name.
	 *
	 * Silently fails (i.e. returns '') when the requested partial is not found.
	 *
	 * @access protected
	 * @param string $tag_name
	 * @throws MustacheException Unknown partial name.
	 * @return string
	 */
	protected function _getPartial($tag_name) {
		if (is_array($this->_partials) && isset($this->_partials[$tag_name])) {
			return $this->_partials[$tag_name];
		}

		if ($this->_throwsException(MustacheException::UNKNOWN_PARTIAL)) {
			throw new MustacheException('Unknown partial: ' . $tag_name, MustacheException::UNKNOWN_PARTIAL);
		} else {
			return '';
		}
	}

	/**
	 * Check whether the given $var should be iterated (i.e. in a section context).
	 *
	 * @access protected
	 * @param mixed $var
	 * @return bool
	 */
	protected function _varIsIterable($var) {
		return $var instanceof Traversable || (is_array($var) && !array_diff_key($var, array_keys(array_keys($var))));
	}

	/**
	 * Higher order sections helper: tests whether the section $var is a valid callback.
	 *
	 * In Mustache.php, a variable is considered 'callable' if the variable is:
	 *
	 *  1. an anonymous function.
	 *  2. an object and the name of a public function, i.e. `array($SomeObject, 'methodName')`
	 *  3. a class name and the name of a public static function, i.e. `array('SomeClass', 'methodName')`
	 *
	 * @access protected
	 * @param mixed $var
	 * @return bool
	 */
	protected function _varIsCallable($var) {
	  return !is_string($var) && is_callable($var);
	}
}


/**
 * MustacheException class.
 *
 * @extends Exception
 */
class MustacheException extends Exception {

	// An UNKNOWN_VARIABLE exception is thrown when a {{variable}} is not found
	// in the current context.
	const UNKNOWN_VARIABLE         = 0;

	// An UNCLOSED_SECTION exception is thrown when a {{#section}} is not closed.
	const UNCLOSED_SECTION         = 1;

	// An UNEXPECTED_CLOSE_SECTION exception is thrown when {{/section}} appears
	// without a corresponding {{#section}} or {{^section}}.
	const UNEXPECTED_CLOSE_SECTION = 2;

	// An UNKNOWN_PARTIAL exception is thrown whenever a {{>partial}} tag appears
	// with no associated partial.
	const UNKNOWN_PARTIAL          = 3;

	// An UNKNOWN_PRAGMA exception is thrown whenever a {{%PRAGMA}} tag appears
	// which can't be handled by this Mustache instance.
	const UNKNOWN_PRAGMA           = 4;

}



?>