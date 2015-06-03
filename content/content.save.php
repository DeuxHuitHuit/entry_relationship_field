<?php
	/*
	Copyright: Deux Huit Huit 2014
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(TOOLKIT . '/class.jsonpage.php');
	//require_once(EXTENTIONS . '/entry_relationship_field/fields/field.entry_relationship.php');

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
			
			$rawEntriesId = explode(',', MySQL::cleanValue($this->_context[0]));
			$entriesId = array_map(array('General', 'intval'), $rawEntriesId);
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
			
			$rawEntryId = MySQL::cleanValue($this->_context[2]);
			$entryId = General::intval($rawEntryId );
			if ($entryId < 1) {
				$this->_Result['error'] = sprintf(
					__('Parent entry id `%s` not valid'),
					$rawEntryId
				);
				return;
			}
			
			$entry = EntryManager::fetch($entryId);
			if ($entry == null || count($entry) != 1) {
				$this->_Result['error'] = __('Parent entry not found');
				return;
			}
			if (is_array($entry)) {
				$entry = $entry[0];
			}
			$entryData = $entry->getData();
			
			// save the new data
			$entryData[$parentFieldId]['entries'] = implode(',', $entriesId);
			$entry->setData($parentFieldId, $entryData[$parentFieldId]);
			if (!$entry->commit()) {
				$this->_Result['error'] = __('Could not save entry');
				return;
			}
			
			$this->_Result['entry-id'] = $entryId;
			$this->_Result['ok'] = true;
			$this->_Result['entries'] = $entryData[$parentFieldId];
		}
	}