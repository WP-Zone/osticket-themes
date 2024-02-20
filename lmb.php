<?php
// This file was modified by Jonathan Hall on 2024-02-20

require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.thread.php');
require_once(INCLUDE_DIR . 'class.config.php');
require_once(INCLUDE_DIR . 'class.format.php');

class LastMessageByPlugin extends Plugin {
	public $config_class = 'LastMessageByPluginConfig';

    function bootstrap() {
		if (isset(Ticket::$meta['joins']['thread']['reverse'])) {
			Ticket::$meta['joins']['thread']['reverse'] = 'LMBTicketThread.ticket';
		}
    }
}

class LastMessageByPluginConfig extends PluginConfig implements PluginCustomConfig {
	function saveConfig() { return true; }
	function renderConfig() { }
}

class LMBTicketThread extends TicketThread {
	static function getSearchableFields() {
		$fields = parent::getSearchableFields();
		$fields['lastmessageby'] = new LMBField([
			'label' => 'Last Message By'
		]);
		return $fields;
	}
}

class LMBField extends TextboxField implements AnnotatedField {
	function annotate($a, $b='lastmessageby') {
		return $a->annotate([
			$b =>
				TicketThread::objects()
				->filter(['ticket__ticket_id' => new SqlField('ticket_id', 1)])
				->exclude(['entries__flags__hasbit' => ThreadEntry::FLAG_HIDDEN])
				->order_by('entries__id', QuerySet::DESC)
				->limit(1)
				->values('entries__poster')
		]);
	}
	
	function addToQuery($a, $b='lastmessageby') {
		return $this->annotate($a, $b);
	}
	
	function from_query($data, $b='lastmessageby') {
		return isset($data[$b]) ? Format::htmlchars($data[$b]) : '';
	}
}