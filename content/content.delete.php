<?php
	/**
	 * Copyright: Deux Huit Huit 2015
	 * LICENCE: MIT https://deuxhuithuit.mit-license.org
	 */

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(TOOLKIT . '/class.jsonpage.php');

	class contentExtensionEntry_Relationship_FieldDelete extends JSONPage {

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

			// _context[0] => entry id to delete
			// _context[1] => fieldId
			// _context[2] => current entry id (parent of entry id to delete)
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

			// Validate to delete entry ID
			$rawToDeleteEntryId = $this->_context[0];
			$toDeleteEntryId = General::intval($rawToDeleteEntryId);
			if ($toDeleteEntryId < 1) {
				$this->_Result['error'] = __('No entry no found');
				return;
			}

			// Validate parent field exists
			$parentFieldId = General::intval($this->_context[1]);
			if ($parentFieldId < 1) {
				$this->_Result['error'] = __('Parent id not valid');
				return;
			}
			// $parentField = FieldManager::fetch($parentFieldId);
			$parentField = (new FieldManager)
				->select()
				->field($parentFieldId)
				->execute()
				->next();
			if (!$parentField || empty($parentField)) {
				$this->_Result['error'] = __('Parent field not found');
				return;
			}

			// Validate parent entry ID
			$rawEntryId = $this->_context[2];
			$entryId = General::intval($rawEntryId);
			if ($entryId < 1) {
				$this->_Result['error'] = sprintf(
					__('Parent entry id `%s` not valid'),
					$rawEntryId
				);
				return;
			}

			// Validate parent entry exists
			// $entry = EntryManager::fetch($entryId);
			$entry = (new EntryManager)
				->select()
				->entry($entryId)
				->execute()
				->next();
			if ($entry == null || empty($entry)) {
				$this->_Result['error'] = __('Parent entry not found');
				return;
			}
			if ($entry->get('section_id') != $parentField->get('parent_section')) {
				$this->_Result['error'] = __('Field and entry do not belong together');
				return;
			}

			// Validate to delete entry exists
			// $toDeleteEntry = EntryManager::fetch($toDeleteEntryId);
			$toDeleteEntry = (new EntryManager)
				->select()
				->entry($toDeleteEntryId)
				->execute()
				->next();
			if ($toDeleteEntry == null || empty($toDeleteEntry)) {
				$this->_Result['error'] = __('Entry not found');
				return;
			}

			// Validate entry is not linked anywhere else
			if (!isset($_REQUEST['no-assoc'])) {
				$toDeleteAssoc = SectionManager::fetchChildAssociations($toDeleteEntry->get('section_id'), false);
				// TODO: find if the toDeleteEntry is linked or not.
				if (count($toDeleteAssoc) > 1) {
					$this->_Result['assoc'] = true;
					$this->_Result['error'] = __('Entry might be link elsewhere. Do you want to continue?');
					return;
				}
			}

			// Delete the entry
			if (!EntryManager::delete($toDeleteEntryId)) {
				$this->_Result['error'] = __('Could not delete the entry');
				return;
			}

			$this->_Result['entry-id'] = $entryId;
			$this->_Result['ok'] = true;
		}
	}
