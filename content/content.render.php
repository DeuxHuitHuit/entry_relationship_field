<?php
	/*
	Copyright: Deux Huit Huit 2014
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(TOOLKIT . '/class.xmlpage.php');

	class contentExtensionEntry_Relationship_FieldRender extends XMLPage {
		
		private $sectionCache;
		private $fieldCache;
		
		public function __construct() {
			parent::__construct();
			$this->sectionCache = array();
			$this->fieldCache = array();
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
			
			$entriesId = explode(',', MySQL::cleanValue($this->_context[0]));
			$entriesId = array_map(intval, $entriesId);
			
			$parentFieldId = intval(MySQL::cleanValue($this->_context[1]));
			$parentField = FieldManager::fetch($parentFieldId);
			$includedElements = $this->parseIncludedElements($parentField);
			
			if (!$parentField || empty($parentField)) {
				$this->_Result->appendChild(new XMLElement('error', 'Parent field not found'));
				return;
			}
			
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
					$entryData = $entry->getData();
					$entrySection = SectionManager::fetch($entry->get('section_id'));
					$entryVisibleFields = $entrySection->fetchVisibleColumns();
					$entryFields = $entrySection->fetchFields();
					$entrySectionHandle = $this->getSectionName($entry, 'handle');
					
					$li = new XMLElement('li', null, array(
						'data-entry-id' => $entryId,
						'data-section' => $entrySectionHandle
					));
					$header = new XMLElement('header', null, array('class' => 'frame-header'));
					$title = new XMLElement('h4');
					$title->appendChild(new XMLElement('strong', $this->getEntryTitle($entry, $entryVisibleFields, $entryFields)));
					$title->appendChild(new XMLElement('span', $this->getSectionName($entry)));
					$header->appendChild($title);
					$options = new XMLElement('div', null, array('class' => 'destructor'));
					$options->appendChild(new XMLElement('a', __('Edit'), array('class' => 'edit')));
					$options->appendChild(new XMLElement('a', __('Un-link'), array('class' => 'unlink')));
					$header->appendChild($options);
					$li->appendChild($header);
					
					$xslFilePath = WORKSPACE . '/er_templates/' . $this->getSectionName($entry, 'handle') . '.xsl';
					
					if (!empty($entryData) && @file_exists($xslFilePath)) {
						$xml = new XMLElement('entry');
						$xml->setAttribute('id', $entryId);
						$xml->setIncludeHeader(true);
						
						foreach ($entryData as $fieldId => $data) {
							if ($includedElements[$entrySectionHandle] === true || 
								in_array($entryFields[$fieldId]->get('element_name'), $includedElements[$entrySectionHandle])) {
								$entryFields[$fieldId]->appendFormattedElement($xml, $data);
							}
						}
						
						$indent = false;
						$mode = $parentField->get('mode');
						if (isset($_REQUEST['debug'])) {
							$mode = 'debug';
							$indent = true;
						}
						$xmlMode = empty($mode) ? '' : 'mode="' . $mode . '"';
						
						$xsl = '<?xml version="1.0" encoding="UTF-8"?>
						<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
							<xsl:import href="' . $xslFilePath . '"/>
							<xsl:output method="xml" omit-xml-declaration="yes" encoding="UTF-8" indent="no" />
							<xsl:template match="/">
								<xsl:apply-templates select="./entry" ' . $xmlMode . ' />
							</xsl:template>
							<xsl:template match="entry" mode="debug">
								<textarea>
									<xsl:copy-of select="." />
								</textarea>
							</xsl:template>
						</xsl:stylesheet>';
						
						$xslt = new XsltProcess($xml->generate($indent), $xsl);
						$result = $xslt->process();
						
						if ($xslt->isErrors()) {
							$error = $xslt->getError();
							$result = $error[1]['message'];
						}
						
						if (!!$xslt && strlen($result) > 0) {
							$content = new XMLElement('div', $result, array('class' => 'content'));
							$li->appendChild($content);
						}
					}
					
					$this->_Result->appendChild($li);
				}
				
			}
			
			// clean up
			$this->sectionCache = null;
			$this->fieldCache = null;
		}
		
		public function getSectionName($entry, $name = 'name') {
			$sectionId = $entry->get('section_id');
			
			if (!isset($this->sectionCache[$sectionId])) {
				$this->sectionCache[$sectionId] = SectionManager::fetch($sectionId);
			}
			
			return $this->sectionCache[$sectionId]->get($name);
		}
		
		public function getEntryTitle($entry, $entryVisibleFields, $entryFields) {
			$data = $entry->getData();
			$field = empty($entryVisibleFields) ? $entryFields : $entryVisibleFields;
			if (is_array($field)) {
				$field = current($field);
			}
			
			if ($field == null) {
				return __('None');
			}
			
			return $field->prepareTextValue($data[$field->get('id')], $entry->get('id'));
		}
		
		public function parseIncludedElements($field) {
			$elements = $field->get('elements');
			$parsedElements = array();
			if (!empty($elements)) {
				$elements = array_map(trim, explode(',', $elements));
				foreach ($elements as $element) {
					$parts = array_map(trim, explode('.', $element));
					if (count($parts) === 2) {
						$sectionname = $parts[0];
						$fieldname = $parts[1];
						
						// skip all included
						if ($parsedElements[$sectionname] === true) {
							continue;
						}
						// set all included
						else if ($fieldname == '*' || !$fieldname) {
							$parsedElements[$sectionname] = true;
							continue;
						}
						// first time seeing this section
						else if (!is_array($parsedElements[$sectionname])) {
							$parsedElements[$sectionname] = array();
						}
						// add current value
						$parsedElements[$sectionname][] = $fieldname;
					}
				}
			}
			return $parsedElements;
		}
		
	}