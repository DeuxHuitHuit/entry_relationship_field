<?php
	/*
	Copyright: Deux Huit Huit 2014
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(TOOLKIT . '/class.xmlpage.php');

	class contentExtensionEntry_Relationship_FieldRender extends XMLPage {
		
		private $sectionCache;
		
		public function __construct() {
			parent::__construct();
			$this->sectionCache = array();
			// fix jquery
			$this->_Result->setIncludeHeader(false);
			$this->addHeaderToPage('Content-Type', 'text/html');
		}
		
		/**
		 *
		 * Builds the content view
		 */
		public function view() {
			if (!is_array($this->_context) || empty($this->_context)) {
				$this->_Result->appendChild(new XMLElement('error', 'Parameters not found'));
				return;
			}
			
			$entriesId = explode(',', $this->_context[0]);
			$entriesId = array_map(intval, $entriesId);
			
			//var_dump($entriesId);
			
			//$entries = EntryManager::fetch($entriesId, 8);
			
			if (!is_array($entriesId)) {
				$this->_Result->appendChild(new XMLElement('error', 'No entry no found'));
				return;
			}
			
			// Get entries one by one since they may belong to
			// different sections, which prevents us from
			// passing an array of entryId.
			foreach ($entriesId as $key => $entryId) {
				$entry = EntryManager::fetch($entryId);
				if (empty($entry)) {
					$this->_Result->appendChild(new XMLElement('li', __('Entry %s not found', array($entryId))));
				} else {
					$entry = $entry[0];
					
					$li = new XMLElement('li', null, array('data-entry-id' => $entryId));
					$header = new XMLElement('header', null, array('class' => 'frame-header'));
					$title = new XMLElement('h4');
					$title->appendChild(new XMLElement('strong', $this->getEntryTitle($entry)));
					$title->appendChild(new XMLElement('span', $this->getSectionName($entry)));
					$header->appendChild($title);
					$header->appendChild(new XMLElement('a', __('Un-link'), array('class' => 'destructor')));
					$li->appendChild($header);
					
					$content = new XMLElement('div', null, array('class' => 'content'));
					$this->appendContent($content, $entry);
					$li->appendChild($content);
					
					$this->_Result->appendChild($li);
				}
				
			}
			
			// clean up
			$this->sectionCache = null;
		}
		
		public function getSectionName($entry) {
			$sectionId = $entry->get('section_id');
			
			if (!isset($this->sectionCache[$sectionId])) {
				$this->sectionCache[$sectionId] = SectionManager::fetch($sectionId);
			}
			
			return $this->sectionCache[$sectionId]->get('name');
		}
		
		public function getEntryTitle($entry) {
			$data = $entry->getData();
			$dataKeys = array_keys($data);
			$field = FieldManager::fetch($dataKeys[0]);
			return trim(strip_tags($field->prepareTableValue($data[$field->get('id')], null, $entry->get('id'))));
		}
		
		public function appendContent(&$content, $entry) {
			$data = $entry->getData();
		}
	}