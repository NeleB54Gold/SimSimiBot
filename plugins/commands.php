<?php
	
# Ignore inline messages (via @)
if ($v->via_bot) die;

# Commands for all chats
if ($v->chat_type == 'private' or $v->inline_message_id) {
	# Private chat with Bot
	if ($bot->configs['database']['status'] and $user['status'] !== 'started') $db->setStatus($v->user_id, 'started');
	if ($v->inline_message_id) {
		$v->message_id = $v->inline_message_id;
		$v->chat_id = 0;
	}
	if ($v->update['edited_message'] and $v->text and !$v->command) {
		$bot->sendMessage($v->chat_id, $bot->italic($tr->getTranslation('editMessageWarning'), 1), [], 'def', 0, $v->message_id);
		die;
	}
	$action = $db->rget('SSB-action-' . $v->user_id);
	if ($action and $v->text and !$v->command and !$v->query_data) {
		if (strpos($action, 'addword_') === 0) {
			if (strlen($v->text) >= 128) {
				$t = $bot->italic($tr->getTranslation('notMoreThanLimit', [128]), 1);
			} elseif (strlen($v->text) <= 3) {
				$t = $bot->italic($tr->getTranslation('lassThanLimit', [3]), 1);
			} elseif ($v->entities or strpos($v->text, '@') !== false) {
				$t = $bot->italic($tr->getTranslation('spamNotAllowed'), 1);
			} else {
				$demand = str_replace('addword_', '', $action);
				$ss = new SimSimi($db);
				$ss->addWord($v->text, $demand, $v->user_id, $user['lang']);
				$t = $bot->bold($tr->getTranslation('saved'), 1);
				$db->rdel('SSB-action-' . $v->user_id);
			}
			$bot->sendMessage($v->chat_id, $t, $buttons);
			die;
		} else {
			$db->rdel('SSB-action-' . $v->user_id);
			unset($action);
		}
	}
	if ($v->command == 'cancel' or $v->query_data == 'cancel') {
		if ($action) {
			$t = $tr->getTranslation('commandCanceled');
		} else {
			$t = $tr->getTranslation('noCommandRun');
		}
		$db->rdel('SSB-action-' . $v->user_id);
		unset($action);
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	} elseif (in_array($v->command, ['start', 'start start']) or $v->query_data == 'start') {
		$t = $tr->getTranslation('startMessage');
		$buttons[] = [
			$bot->createInlineButton($tr->getTranslation('aboutButton'), 'about'),
			$bot->createInlineButton($tr->getTranslation('helpButton'), 'help')
		];
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('changeLanguage'), 'lang');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	} elseif ($v->command == 'help' or $v->query_data == 'help') {
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('tryInlineMode'), $tr->getTranslation('tryInlineMode'), 'switch_inline_query');
		$buttons[][] = $bot->createInlineButton('◀️', 'start');
		$t = $bot->bold($watermark) . $tr->getTranslation('helpMessage');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	} elseif ($v->command == 'about' or $v->query_data == 'about') {
		$buttons[][] = $bot->createInlineButton('◀️', 'start');
		$t = $tr->getTranslation('aboutMessage', [explode('-', phpversion(), 2)[0]]);
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	} elseif ($v->command == 'lang' or $v->query_data == 'lang' or strpos($v->query_data, 'changeLanguage-') === 0) {
		$langnames = [
			'en' => '🇬🇧 English',
			'es' => '🇪🇸 Español',
			'de' => '🇩🇪 Deutsch',
			'fr' => '🇫🇷 Français',
			'id' => '🇮🇩 Indonesia',
			'it' => '🇮🇹 Italiano',
			'pt' => '🇧🇷 Português',
			'scn' => '🇮🇹+ Siciliano'
		];
		if (strpos($v->query_data, 'changeLanguage-') === 0) {
			$select = str_replace('changeLanguage-', '', $v->query_data);
			if (in_array($select, array_keys($langnames))) {
				$tr->setLanguage($select);
				$user['lang'] = $select;
				$db->query('UPDATE users SET lang = ? WHERE id = ?', [$user['lang'], $user['id']]);
			}
		}
		$langnames[$user['lang']] .= ' ✅';
		$t = '🔡 Select your language';
		$formenu = 2;
		$mcount = 0;
		foreach ($langnames as $lang_code => $name) {
			if (isset($buttons[$mcount]) and count($buttons[$mcount]) >= $formenu) $mcount += 1;
			$buttons[$mcount][] = $bot->createInlineButton($name, 'changeLanguage-' . $lang_code);
		}
		$buttons[][] = $bot->createInlineButton('◀️', 'start');
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	} elseif ($v->command == 'start inline') {
		$buttons[][] = $bot->createInlineButton($tr->getTranslation('tryInlineMode'), $tr->getTranslation('tryInlineMode'), 'switch_inline_query');
		$t = $tr->getTranslation('inlineHelp', ['SimSimi_Bot']);
		$bot->sendMessage($v->chat_id, $t, $buttons);
	} elseif (strpos($v->command, 'addword') === 0 or $v->query_data == 'addword') {
		if ($demand = $v->reply_to_message['text']) {
		} elseif (strpos($v->command, 'addword ') === 0) {
			$demand = str_replace('addword ', '', $v->command);
		}
		if ($demand) {
			$ss = new SimSimi($db);
			$response = $ss->keyInput($demand, $user['lang']);
			$t = $bot->bold($tr->getTranslation('demand') . ': ', 1) . $demand;
			$t .= PHP_EOL . $bot->bold($tr->getTranslation('response') . ': ') . count($response['result']);
			$t .= PHP_EOL . $bot->italic($tr->getTranslation('insertNewResponse'));
			$buttons[][] = $bot->createInlineButton($tr->getTranslation('cancel'), 'cancel');
			$db->rset('SSB-action-' . $v->user_id, 'addword_' . $demand, 60 * 10);
		} else {
			$t = $tr->getTranslation('syntaxError');
		}
		if ($v->query_id) {
			$bot->editText($v->chat_id, $v->message_id, $t, $buttons);
			$bot->answerCBQ($v->query_id);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons);
		}
	} else {
		if ($v->command) {
			$t = $tr->getTranslation('unknownCommand');
		} elseif ($v->query_data) {
			$t = '😶 Unknown button...';
		} else {
			if ($v->text) {
				if (strlen($v->text) >= 128) {
					$t = $bot->italic($tr->getTranslation('notMoreThanLimit', [128]), 1);
				} elseif (strlen($v->text) <= 2) {
					$t = $bot->italic($tr->getTranslation('lassThanLimit', [2]), 1);
				} else {
					$ss = new SimSimi($db);
					$response = $ss->keyInput($v->text, $user['lang']);
					if (!empty($response['result'])) {
						$t = $response['result'];
					} else {
						$buttons[][] = $bot->createInlineButton($tr->getTranslation('okayButton'), 'addword');
						$t = $tr->getTranslation('wordNotFound');
					}
				}
			} else {
				$t = $tr->getTranslation('noCommandRun');
			}
		}
		if ($v->query_id) {
			$bot->answerCBQ($v->query_id, $t);
		} else {
			$bot->sendMessage($v->chat_id, $t, $buttons, 'def', 0, $v->message_id);
		}
	}
} elseif (in_array($v->chat_type, ['group', 'supergroup', 'channels'])) {
	$bot->leave($v->chat_id);
	die;
}

# Inline commands
if ($v->update['inline_query']) {
	$sw_text = $tr->getTranslation('helpInline');
	$sw_arg = 'inline'; // The message the bot receive is '/start inline'
	$results = [];
	if (!$v->query) {
	} elseif (strlen($v->query) >= 128) {
		$sw_text = $tr->getTranslation('notMoreThanLimit', [128]);
	} elseif (strlen($v->query) <= 3) {
		$sw_text = $tr->getTranslation('lassThanLimit', [3]);
	} else {
		$ss = new SimSimi($db);
		$response = $ss->keyInput($v->query, $user['lang']);
		if (!empty($response['result'])) {
			$t = $bot->bold($tr->getTranslation('you') . ': ') . $v->query . PHP_EOL . PHP_EOL . $bot->bold('🙂 SimSimi: ') . $response['result'];
			$results[] = $bot->createInlineArticle(
				$v->query . time(),
				$tr->getTranslation('inlineResponseFound'),
				$tr->getTranslation('clickToSend'),
				$bot->createTextInput($t, 'def', 1),
				0,
				0,
				0,
				'https://telegra.ph/file/b96c146df2c17f1e656e3.jpg'
			);
		} else {
			$buttons[][] = $bot->createInlineButton($tr->getTranslation('okayButton'), 'https://t.me/SimSimi_Bot', 'url');
			$t = $tr->getTranslation('wordNotFound');
			$results[] = $bot->createInlineArticle(
				$v->query . time(),
				$tr->getTranslation('inlineResponseNotFound'),
				$tr->getTranslation('clickToSend'),
				$bot->createTextInput($t, 'def', 1),
				0,
				0,
				0,
				'https://telegra.ph/file/b96c146df2c17f1e656e3.jpg'
			);
		}
	}
	$bot->answerIQ($v->id, $results, $sw_text, $sw_arg);
}

?>
