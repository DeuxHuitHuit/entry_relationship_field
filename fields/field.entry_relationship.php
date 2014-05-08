<?php

	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT . '/class.field.php');
	
	/**
	 *
	 * Field class that will represent relationships between entries
	 * @author Deux Huit Huit
	 *
	 */
	class FieldEntry_relationship extends Field {
		
		/**
		 *
		 * Name of the field table
		 * @var string
		 */
		const FIELD_TBL_NAME = 'tbl_fields_entry_relationship';
		
		/**
		 * 
		 * Separator char for the entries
		 */
		const ENTRIES_SEPARATOR = ',';
		
		
		/**
		 *
		 * Constructor for the oEmbed Field object
		 * @param mixed $parent
		 */
		public function __construct(){
			// call the parent constructor
			parent::__construct();
			// set the name of the field
			$this->_name = __('Entry Relationship');
			// permits to make it required
			$this->_required = true;
			// permits the make it show in the table columns
			$this->_showcolumn = true;
			// set as not required by default
			$this->set('required', 'no');
		}

		public function isSortable(){
			return false;
		}

		public function canFilter(){
			return false;
		}

		public function canImport(){
			return false;
		}

		public function canPrePopulate(){
			return false;
		}

		public function allowDatasourceOutputGrouping(){
			return false;
		}

		public function requiresSQLGrouping(){
			return false;
		}

		public function allowDatasourceParamOutput(){
			return false;
		}


		/* ********** INPUT AND FIELD *********** */


		/**
		 * 
		 * Validates input
		 * Called before <code>processRawFieldData</code>
		 * @param $data
		 * @param $message
		 * @param $entry_id
		 */
		public function checkPostFieldData($data, &$message, $entry_id=NULL){
			$message = NULL;
			$required = ($this->get('required') == 'yes');
			
			if ($required && strlen($data) == 0){
				$message = __("'%s' is a required field.", array($this->get('label')));
				return self::__MISSING_FIELDS__;
			}
			
			return self::__OK__;
		}


		/**
		 *
		 * Process data before saving into database.
		 *
		 * @param array $data
		 * @param int $status
		 * @param boolean $simulate
		 * @param int $entry_id
		 *
		 * @return Array - data to be inserted into DB
		 */
		public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null) {
			$status = self::__OK__;

			$errorFlag = false;

			$xml = array();

			//var_dump($data);die;

			

			$row = array(
				
			);

			// return row
			return $row;
		}

		/**
		 * This function permits parsing different field settings values
		 *
		 * @param array $settings
		 *	the data array to initialize if necessary.
		 */
		public function setFromPOST(Array &$settings = array()) {

			// call the default behavior
			parent::setFromPOST($settings);

			// declare a new setting array
			$new_settings = array();

			// set new settings
			$new_settings['sections'] = is_array($settings['sections']) ? 
				implode(self::ENTRIES_SEPARATOR, $settings['sections']) : 
				null;

			// save it into the array
			$this->setArray($new_settings);
		}


		/**
		 *
		 * Validates the field settings before saving it into the field's table
		 */
		public function checkFields(Array &$errors, $checkForDuplicates) {
			parent::checkFields($errors, $checkForDuplicates);

			

			return (!empty($errors) ? self::__ERROR__ : self::__OK__);
		}

		/**
		 *
		 * Save field settings into the field's table
		 */
		public function commit() {

			// if the default implementation works...
			if(!parent::commit()) return false;

			//var_dump($this->get());die;

			$id = $this->get('id');

			// exit if there is no id
			if($id == false) return false;

			// declare an array contains the field's settings
			$settings = array(
				'sections' => $this->get('sections')
			);

			return FieldManager::saveSettings($id, $settings);
		}

		/**
		 *
		 * Remove the entry data of this field from the database, when deleting an entry
		 * @param integer|array $entry_id
		 * @param array $data
		 * @return boolean
		 */
		public function entryDataCleanup($entry_id, array $data) {
			if (empty($entry_id) || !parent::entryDataCleanup($entry_id, $data)) {
				return false;
			}

			return true;
		}

		/**
		 *
		 * This function allows Fields to cleanup any additional things before it is removed
		 * from the section.
		 * @return boolean
		 */
		public function tearDown() {
			return parent::tearDown();
		}




		/* ******* DATA SOURCE ******* */

		/**
		 * Appends data into the XML tree of a Data Source
		 * @param $wrapper
		 * @param $data
		 */
		public function appendFormattedElement(&$wrapper, $data) {
			
			if(!is_array($data) || empty($data)) return;
			
			// root for all values
			$field = new XMLElement($this->get('element_name'));
			
			
			$wrapper->appendChild($field);
		}


		/* ********* Utils *********** */
		
		public function getAllSections() {
			$sections = SectionManager::fetch();
			
			return $sections;
		}
		
		private function buildSectionSelect($name) {
			$sections = $this->getAllSections();
			$options = array();
			$selectedSections = $this->get('sections');
			//var_dump($sections);die;
			
			foreach ($sections as $section) {
				$driver = $section->get('handle');
				$selected = strpos($selectedSections, $driver) !== false;
				$options[] = array($driver, $selected, $section->get('name'));
			}
			
			return Widget::Select($name, $options, array('multiple' => 'multiple'));
		} 
		
		private function appendSelectionSelect(&$wrapper) {
			$order = $this->get('sortorder');
			$name = "fields[{$order}][sections][]";

			$input = $this->buildSectionSelect($name);

			$label = Widget::Label();
			$label->setAttribute('class', 'column');

			$label->setValue(__('Available sections %s', array($input->generate())));

			$wrapper->appendChild($label);
		}


		/* ********* UI *********** */
		
		/**
		 *
		 * Builds the UI for the field's settings when creating/editing a section
		 * @param XMLElement $wrapper
		 * @param array $errors
		 */
		public function displaySettingsPanel(&$wrapper, $errors=NULL){
			
			/* first line, label and such */
			parent::displaySettingsPanel($wrapper, $errors);
			
			$this->appendSelectionSelect($wrapper);
			$this->appendStatusFooter($wrapper);
			
		}

		/**
		 *
		 * Builds the UI for the publish page
		 * @param XMLElement $wrapper
		 * @param mixed $data
		 * @param mixed $flagWithError
		 * @param string $fieldnamePrefix
		 * @param string $fieldnamePostfix
		 */
		public function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL, $entry_id = null) {
			
			$isRequired = $this->get('required') == 'yes';
			
			$value = '';
			$label = Widget::Label($this->get('label'));
			
			// not required label
			if(!$isRequired) {
				$label->appendChild(new XMLElement('i', __('Optional')));
			}
			
			
			// error management
			if ($flagWithError != NULL) {
				$wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			} else {
				$wrapper->appendChild($label);
			}
		}

		/**
		 *
		 * Build the UI for the table view
		 * @param Array $data
		 * @param XMLElement $link
		 * @return string - the html of the link
		 */
		public function prepareTableValue($data, XMLElement $link=NULL, $entry_id = null){

			
			$textValue = $this->preparePlainTextValue($data, $entry_id);

			//var_dump($data);die;

			// does this cell serve as a link ?
			if (!!$link){
				// if so, set our html as the link's value
				$link->setValue($textValue);
				$link->setAttribute('title', $textValue . ' | ' . $link->getAttribute('title'));

			} else {
				// if not, use a span
				$link = new XMLElement('span', $textValue);
			}

			// returns the link's html code
			return $link->generate();
		}

		/**
		 *
		 * Return a plain text representation of the field's data
		 * @param array $data
		 * @param int $entry_id
		 */
		public function preparePlainTextValue($data, $entry_id = null) {
			if ($entry_id == null || empty($data)) {
				return __('None');
			}
			$entries = explode(self::ENTRIES_SEPARATOR, $data['sections']);
			return __('%s items', array(count($entries)));
		}





		/* ********* SQL Data Definition ************* */

		/**
		 *
		 * Creates table needed for entries of invidual fields
		 */
		public function createTable(){
			$id = $this->get('id');

			return Symphony::Database()->query("
				CREATE TABLE `tbl_entries_data_$id` (
					`id` int(11) 		unsigned NOT NULL auto_increment,
					`entry_id` 			int(11) unsigned NOT NULL,
					`entries` 			varchar(255),
					PRIMARY KEY  (`id`),
					UNIQUE KEY `entry_id` (`entry_id`)
				)  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}

		/**
		 * Creates the table needed for the settings of the field
		 */
		public static function createFieldTable() {

			$tbl = self::FIELD_TBL_NAME;

			return Symphony::Database()->query("
				CREATE TABLE IF NOT EXISTS `$tbl` (
					`id` 			int(11) unsigned NOT NULL auto_increment,
					`field_id` 		int(11) unsigned NOT NULL,
					`sections`		varchar(255) NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `field_id` (`field_id`)
				)  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			");
		}
		
		
		/**
		 *
		 * Drops the table needed for the settings of the field
		 */
		public static function deleteFieldTable() {
			$tbl = self::FIELD_TBL_NAME;
			
			return Symphony::Database()->query("
				DROP TABLE IF EXISTS `$tbl`
			");
		}
		
	}