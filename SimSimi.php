<?php

class SimSimi {
	private $db = [];
	
	public function __construct ($db = []) {
		if (isset($db->configs) and $db->configs['database']['status']) $this->db = $db;
	}
	
	public function keyInput ($text, $lang = 'en') {
		$q = $this->db->query('SELECT text FROM phrases WHERE lang = ? and demand LIKE ? ' . $this->db->limit(5), [$lang, '%' . $text . '%'], 2);
		if (!empty($q) and !$q['error']) {
			return ['ok' => 1, 'result' => $q[rand(0, count($q) - 1)]['text']];
		} else {
			return ['ok' => 1, 'result' => [], 'query' => $q];
		}
	}
	
	public function addWord ($word, $text, $user_id, $lang = 'en') {
		return $this->db->query('INSERT INTO phrases (demand, text, user_id, lang, registration, last_use) VALUES (?,?,?,?,?,?)', [$text, $word, $user_id, $lang, time(), '0'], 1);
	}
	
	public function removeWord ($serialKey) {
		return $this->db->query('DELETE FROM phrases WHERE id = ?', [$serialKey], 1);
	}
}

?>