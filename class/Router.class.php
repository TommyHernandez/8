<?php

class Router {

	public static $filename = 'router.json';
	public static $root = null;

	public static $url;
	public static $parts;
	public static $parameters;
	public static $language;
	public static $node;

	private function __construct() {}

	public static function setUrl($url) {
		self::load();

		// TODO: [OPTIONAL] save previous state in $history

		// Reset all static vars
		self::$url = $url;
		self::$parts = array();
		self::$parameters = array();
		self::$language = '';
		self::$node = null;

		// Calculate vars
		self::_preprocess_url();
		self::_extract_language();
		self::_select_starting_node();
		self::_search_node();
	}

	public static function export() {
		return '<?php '
		.'Router::$root = new Node();'
		.'Router::$root->fromArray('.var_export(self::$root->toArray(), true).');'
		.'Router::$node = Router::$root->getById('.var_export(self::$node->id, true).');'
		.'Router::$url = '.var_export(self::$url, true).';'
		.'Router::$parts = '.var_export(self::$parts, true).';'
		.'Router::$parameters = '.var_export(self::$parameters, true).';'
		.'Router::$language = '.var_export(self::$language, true).';'
		.'?>';
	}

	public static function getNodeUrl($node, $query=false) {
		$parts = array();

		$default_page_id = Config::get('DEFAULT_PAGE');

		$current = $node;
		while (null !== $current->parent && $default_page_id != $current->id) {
			$parts[] = $current->key;


			$current = $current->parent;
		}



		$url = '/'.implode('/', array_reverse($parts));

		if (self::$language != Config::get('DEFAULT_LANGUAGE')) {
			$url = '/'.self::$language.$url;
		}

		if ($query) {
			$query = http_build_query($_GET);
			if ('' != $query) {
				$url .= '?'.$query;
			}
		}

		return $url;
	}

	private static function _preprocess_url() {
		// Parse url
		$parse = parse_url('http://dummy:80'.self::$url);
		$path = $parse['path'];
		$query = $parse['query'];

		// Split by '/'
		self::$parts = explode('/', $path);


		// Remove first if empty
		if (count(self::$parts) && '' === self::$parts[0]) {
			array_shift(self::$parts);
		}

		// Remove last if empty
		if (count(self::$parts) && '' === end(self::$parts)) {
			array_pop(self::$parts);
		}

		// Decode url parts
		foreach (self::$parts as $i=>$part) {
			self::$parts[$i] = rawurldecode($part);
		}
	}

	private static function _extract_language() {
		$default_language = Config::get('DEFAULT_LANGUAGE');
		$available_languages = explode(',', Config::get('AVAILABLE_LANGUAGES'));
		$tentative_language = self::$parts[0];
		if ($tentative_language != $default_language && in_array($tentative_language, $available_languages)) {
			array_shift(self::$parts);
			self::$language = $tentative_language;
		} else {
			self::$language = $default_language;
		}
	}

	private static function _select_starting_node() {
		$start = self::$root->getById(Config::get('DEFAULT_PAGE'));

		// Only to fix the extreme case with corrupt configuration
		if (null === $start) {
			$start = self::$root;
		}

		// If home page does not match
		if (count(self::$parts) && !self::_node_match($start, self::$parts[0])) {
			$start = self::$root;
		}

		self::$node = $start;
	}

	private static function _search_node() {
		foreach (self::$parts as $part) {

			$found = false;
			foreach (self::$node->children as $key=>$node) {
				if ($key == $part) {
					// do nothing
				} else if (self::_is_parameter($key)) {
					self::$parameters[$key] = $part;
				} else {
					continue;
				}
				$found = true;
				self::$node = $node;
				array_shift(self::$parts);
				break;
			}
			
			if (!$found) {
				break;
			}

		}
	}

	private static function _is_parameter($part) {
		return '{' == mb_substr($part, 0, 1) && mb_substr($part, -1, 1) == '}';
	}

	private static function _node_match($node, $key) {
		if (null === $node) {
			return false;
		}

		foreach ($node->children as $k=>$child) {
			if ($k == $key || self::_is_parameter($k)) {
				return true;
			}
		}

		return false;
	}

	public static function load() {
		if (null !== self::$root) {
			return;
		}

		self::$root = new Node();

		$content = @file_get_contents(self::$filename);
		$data = json_decode($content, true);
		if (null !== $data) {
			self::$root->fromArray($data);
		}
	}

	public static function save() {
		file_put_contents(
			self::$filename,
			json_encode(self::$root->toArray(), Config::get('JSON_ENCODE_OPTIONS')));
	}

	public static function print_r() {
		echo '<pre>';
		print_r(self::$url); echo "\n";
		print_r(self::$parts); echo "\n";
		print_r(self::$parameters); echo "\n";
		print_r(self::$language); echo "\n";
		self::$node->print_r(); echo "\n";
		echo '</pre>';
	}

}
