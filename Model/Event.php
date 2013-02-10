<?php

App::uses('EventsAppModel', 'Events.Model');

/**
 * Event Model
 *
 * @property EventSchedule $EventSchedule
 * @property Creator $Creator
 * @property Modifier $Modifier
 * @property EventVenue $EventVenue
 * @property Guest $Guest
 */
class Event extends EventsAppModel {

	public $name = 'Event';
	public $actsAs = array('Metable');

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'event_schedule_id' => array(
			'uuid' => array(
				'rule' => array('uuid'),
			//'message' => 'Your custom message here',
			//'allowEmpty' => false,
			//'required' => false,
			//'last' => false, // Stop validation after this rule
			//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'name' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			//'message' => 'Your custom message here',
			//'allowEmpty' => false,
			//'required' => false,
			//'last' => false, // Stop validation after this rule
			//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'is_public' => array(
			'boolean' => array(
				'rule' => array('boolean'),
			//'message' => 'Your custom message here',
			//'allowEmpty' => false,
			//'required' => false,
			//'last' => false, // Stop validation after this rule
			//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
	);

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'EventSchedule' => array(
			'className' => 'Events.EventSchedule',
			'foreignKey' => 'event_schedule_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
	);

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
		'EventVenue' => array(
			'className' => 'Events.EventVenue',
			'foreignKey' => 'event_id',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

/**
 * hasAndBelongsToMany associations
 *
 * @var array
 */
	public $hasAndBelongsToMany = array(
		'Guest' => array(
			'className' => 'Events.EventsGuests',
			'joinTable' => 'events_guests',
			'foreignKey' => 'event_id',
			'associationForeignKey' => 'user_id',
			'unique' => 'keepExisting',
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'finderQuery' => '',
			'deleteQuery' => '',
			'insertQuery' => ''
		)
	);

	public function beforeFind($queryData) {
		parent::beforeFind($queryData);
	}

/**
 * This trims an object, formats it's values if you need to, and returns the data to be merged with the Transaction data.
 * It is a required function for models that will be for sale via the Transactions Plugin.
 * 
 * @param string $foreignKey
 * @return array The necessary fields to add a Transaction Item
 */
	public function mapTransactionItem($foreignKey) {

		$itemData = $this->find('first', array('conditions' => array('id' => $foreignKey)));

		$fieldsToCopyDirectly = array('name');

		foreach ($itemData['Event'] as $k => $v) {
			if (in_array($k, $fieldsToCopyDirectly)) {
				$return['TransactionItem'][$k] = $v;
			}
		}

		return $return;
	}

/**
 * Import
 * 
 * @param string $filename
 * @return type
 * @todo Make sure fopen can't be hacked, it's the main point of entry for the base64 attack.
 */
	function import($filename) {
		// to avoid having to tweak the contents of 
		// $data you should use your db field name as the heading name 
		// eg: Event.id, Event.title, Event.description
		
		// set the filename to read CSV from
		$filename = TMP . 'uploads' . DS . 'Event' . DS . $filename;

		// open the file
		$handle = fopen($filename, "r");

		// read the 1st row as headings
		$header = fgetcsv($handle);

		// create a message container
		$return = array(
			'messages' => array(),
			'errors' => array(),
		);

		// read each data row in the file
		while (($row = fgetcsv($handle)) !== FALSE) {
			$i++;
			$data = array();

			// for each header field 
			foreach ($header as $k => $head) {
				// get the data field from Model.field
				if (strpos($head, '.') !== false) {
					$h = explode('.', $head);
					$data[$h[0]][$h[1]] = (isset($row[$k])) ? $row[$k] : '';
				}
				// get the data field from field
				else {
					$data['Event'][$head] = (isset($row[$k])) ? $row[$k] : '';
				}
			}

			// see if we have an id             
			$id = isset($data['Event']['id']) ? $data['Event']['id'] : 0;

			// we have an id, so we update
			if ($id) {
				// there is 2 options here, 
				// option 1:
				// load the current row, and merge it with the new data
				//$this->recursive = -1;
				//$post = $this->read(null,$id);
				//$data['Post'] = array_merge($post['Post'],$data['Post']);
				// option 2:
				// set the model id
				$this->id = $id;
			}

			// or create a new record
			else {
				$this->create();
			}

			// see what we have
			// debug($data);
			// validate the row
			$this->set($data);
			if (!$this->validates()) {
				$this->_flash( 'warning');
				$return['errors'][] = __(sprintf('Event for Row %d failed to validate.', $i), true);
			}

			// save the row
			if (!$error && !$this->save($data)) {
				$return['errors'][] = __(sprintf('Event for Row %d failed to save.', $i), true);
			}

			// success message!
			if (!$error) {
				$return['messages'][] = __(sprintf('Event for Row %d was saved.', $i), true);
			}
		}

		// close the file
		fclose($handle);

		// return the messages
		return $return;
	}

}
