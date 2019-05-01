<?php
	/**
	 * Copyright: Deux Huit Huit 2014
	 * LICENCE: MIT https://deuxhuithuit.mit-license.org
	 */

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(TOOLKIT . '/class.jsonpage.php');

	class contentExtensionEntry_Relationship_FieldSave extends JSONPage {

		const NUMBER_OF_URL_PARAMETERS = 3;
		
		/**
		 *
		 * Builds the content view
		 */
		public function view() {
			if ($_SERVER['REQUEST_METHOD'] != 'POST') {
				$this->_Result['status'] = Page::HTTP_STATUS_BAD_REQUEST;
				$this->_Result['error'] = __('This page accepts posts only');
				$this->setHttpStatus($this->_Result['status']);
				return;
			}
			
			// _context[0] => entry values
			// _context[1] => fieldId
			// _context[2] => current entry id
			if (!is_array($this->_context) || empty($this->_context)) {
				$this->_Result['error'] = __('Parameters not found');
				return;
			}
			else if (count($this->_context) < self::NUMBER_OF_URL_PARAMETERS) {
				$this->_Result['error'] = __('Not enough parameters');
				return;
			}
			else if (count($this->_context) > self::NUMBER_OF_URL_PARAMETERS) {
				$this->_Result['error'] = __('Too many parameters');
				return;
			}
			
			// Validate ALL entries ID
			$rawEntriesId = array_filter(explode(',', MySQL::cleanValue(urldecode($this->_context[0]))));
			
			// Check for operators
			$operator = null;
			if (!empty($rawEntriesId)) {
				// '��' == '＋''
				if (General::strpos($rawEntriesId[0], '＋') === 0) {
					$operator = '+';
				} else if (General::strpos($rawEntriesId[0], '−') === 0) {
					$operator = '-';
				}
				if ($operator) {
					$rawEntriesId[0] = General::substr($rawEntriesId[0], 1);
				}
			}
			
			// Convert all values to int
			$entriesId = array_map(array('General', 'intval'), $rawEntriesId);
			
			// Check result
			if (!is_array($entriesId) || empty($entriesId)) {
				$this->_Result['error'] = __('No entry no found');
				return;
			}
			if (in_array('null', $rawEntriesId)) {
				$entriesId = array();
			}
			foreach ($entriesId as $entryPos => $entryId) {
				if ($entryId < 1) {
					$this->_Result['error'] = sprintf(
						__('Entry id `%s` not valid'),
						$rawEntriesId[$entryPos]
					);
					return;
				}
			}
			
			// Validate parent field exists
			$parentFieldId = General::intval(MySQL::cleanValue($this->_context[1]));
			if ($parentFieldId < 1) {
				$this->_Result['error'] = __('Parent id not valid');
				return;
			}
			$parentField = FieldManager::fetch($parentFieldId);
			if (!$parentField || empty($parentField)) {
				$this->_Result['error'] = __('Parent field not found');
				return;
			}
			
			// Validate parent entry ID
			$rawEntryId = MySQL::cleanValue($this->_context[2]);
			$entryId = General::intval($rawEntryId );
			if ($entryId < 1) {
				$this->_Result['error'] = sprintf(
					__('Parent entry id `%s` not valid'),
					$rawEntryId
				);
				return;
			}
			
			// Validate parent entry exists
			$entry = EntryManager::fetch($entryId);
			if ($entry == null || count($entry) != 1) {
				$this->_Result['error'] = __('Parent entry not found');
				return;
			}
			if (is_array($entry)) {
				$entry = $entry[0];
			}
			if ($entry->get('section_id') != $parentField->get('parent_section')) {
				$this->_Result['error'] = __('Field and entry do not belong together');
				return;
			}
			$entryData = $entry->getData();
			
			// Perform operation, if needed
			if ($operator) {
				$opEntries = array_filter(explode(',', $entryData[$parentFieldId]['entries']));
				if ($operator === '+') {
					$opEntries[] = $entriesId[0];
				} else if ($operator === '-') {
					$opEntries = array_filter($opEntries, function ($item) use ($entriesId) {
						return General::intval($item) !== $entriesId[0];
					});
				}
				$entriesId = $opEntries;
				unset($opEntries);
			}
			
			// Validate timestamp
			if (!$this->validateTimestamp($entryId, true)) {
				return;
			}
			
			// set new data
			$entryData[$parentFieldId]['entries'] = empty($entriesId) ? null : implode(',', $entriesId);
			
			// check if data are valid
			$resMessage = null;
			$res = $parentField->checkPostFieldData(
				$entryData[$parentFieldId],
				$resMessage,
				$entryId
			);
			if ($res != Field::__OK__) {
				$this->_Result['error'] = $resMessage;
				return;
			}
			
			// save the new data
			$entry->setData($parentFieldId, $entryData[$parentFieldId]);
			if (!$entry->commit()) {
				$this->_Result['error'] = __('Could not save entry');
				return;
			}
			
			$this->_Result['entry-id'] = $entryId;
			$this->_Result['ok'] = true;
			$this->_Result['entries'] = $entryData[$parentFieldId]['entries'];
			$this->_Result['timestamp'] = DateTimeObj::format($entry->get('modification_date'), 'c');
		}
		
		protected function validateTimestamp($entry_id, $checkMissing = false)
		{
			if ($checkMissing && !isset($_POST['timestamp'])) {
				$this->_Result['error'] = __('The entry could not be saved due to conflicting changes');
				return false;
			} elseif (isset($_POST['timestamp'])) {
				$tv = new TimestampValidator('entries');
				if (!$tv->check($entry_id, $_POST['timestamp'])) {
					$this->_Result['error'] = __('The entry could not be saved due to conflicting changes');
					return false;
				}
			}
			return true;
		}
	}
