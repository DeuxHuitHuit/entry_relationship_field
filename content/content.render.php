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
		private $params;
		
		public function __construct() {
			parent::__construct();
			$date = new DateTime();
			$this->sectionCache = array();
			$this->fieldCache = array();
			$this->params = array(
				'today' => $date->format('Y-m-d'),
				'current-time' => $date->format('H:i'),
				'this-year' => $date->format('Y'),
				'this-month' => $date->format('m'),
				'this-day' => $date->format('d'),
				'timezone' => $date->format('P'),
				'website-name' => Symphony::Configuration()->get('sitename', 'general'),
				'root' => URL,
				'workspace' => URL . '/workspace',
				'http-host' => HTTP_HOST
			);
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
			$entriesId = array_map(array('General', 'intval'), $entriesId);
			if (!is_array($entriesId) || empty($entriesId)) {
				$this->_Result->appendChild(new XMLElement('error', 'No entry no found'));
				return;
			}
			
			$parentFieldId = General::intval($this->_context[1]);
			if ($parentFieldId < 1) {
				$this->_Result->appendChild(new XMLElement('error', 'Parent id not valid'));
				return;
			}
			
			$parentField = FieldManager::fetch($parentFieldId);
			if (!$parentField || empty($parentField)) {
				$this->_Result->appendChild(new XMLElement('error', 'Parent field not found'));
				return;
			}
			
			$includedElements = $this->parseIncludedElements($parentField);
			$xmlParams = self::getXmlParams();
			
			// Get entries one by one since they may belong to
			// different sections, which prevents us from
			// passing an array of entryId.
			foreach ($entriesId as $key => $entryId) {
				$entry = EntryManager::fetch($entryId);
				if (empty($entry)) {
					$li = new XMLElement('li', null, array(
						'data-entry-id' => $entryId
					));
					$header = new XMLElement('header', null, array('class' => 'frame-header'));
					$title = new XMLElement('h4');
					$title->appendChild(new XMLElement('strong', __('Entry %s not found', array($entryId))));
					$header->appendChild($title);
					$options = new XMLElement('div', null, array('class' => 'destructor'));
					$options->appendChild(new XMLElement('a', __('Un-link'), array('class' => 'unlink')));
					$header->appendChild($options);
					$li->appendChild($header);
					$this->_Result->appendChild($li);
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
					
					$xslFilePath = WORKSPACE . '/er-templates/' . $entrySectionHandle . '.xsl';
					
					if (!empty($entryData) && !!@file_exists($xslFilePath)) {
						$xmlData = new XMLElement('data');
						$xmlData->setIncludeHeader(true);
						$xml = new XMLElement('entry');
						$xml->setAttribute('id', $entryId);
						$xmlData->appendChild($xmlParams);
						$xmlData->appendChild($xml);
						foreach ($entryData as $fieldId => $data) {
							$filteredData = array_filter($data);
							if (empty($filteredData)) {
								continue;
							}
							$field = $entryFields[$fieldId];
							$fieldName = $field->get('element_name');
							$fieldIncludedElement = $includedElements[$entrySectionHandle];
							if ($fieldIncludedElement === true ||
								(is_array($fieldIncludedElement) && in_array($fieldName, $fieldIncludedElement))) {
								$fieldIncludableElements = $field->fetchIncludableElements();
								if ($field instanceof FieldEntry_relationship) {
									$fieldIncludableElements = null;
								}
								if (!empty($fieldIncludableElements) && count($fieldIncludableElements) > 1) {
									foreach ($fieldIncludableElements as $fieldIncludableElement) {
										$mode = preg_replace('/^' . $fieldName . '\s*\:\s*/i', '', $fieldIncludableElement, 1);
										$field->appendFormattedElement($xml, $data, false, $mode, $entryId);
									}
								} else {
									$field->appendFormattedElement($xml, $data, false, null, $entryId);
								}
							}
						}
						
						$indent = false;
						$mode = $parentField->get('mode');
						if (isset($_REQUEST['debug'])) {
							$mode = 'debug';
						}
						if ($mode == 'debug') {
							$indent = true;
						}
						$xmlMode = empty($mode) ? '' : 'mode="' . $mode . '"';
						$xmlString = $xmlData->generate($indent);
						$xsl = '<?xml version="1.0" encoding="UTF-8"?>
						<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
							<xsl:import href="' . $xslFilePath . '"/>
							<xsl:output method="xml" omit-xml-declaration="yes" encoding="UTF-8" indent="no" />
							<xsl:template match="/">
								<xsl:apply-templates select="/data" ' . $xmlMode . ' />
							</xsl:template>
							<xsl:template match="/data" ' . $xmlMode . '>
								<xsl:apply-templates select="entry" ' . $xmlMode . ' />
							</xsl:template>
							<xsl:template match="/data" mode="debug">
								<pre><code>' .
									str_replace('<', '&lt;', str_replace('>', '&gt;', $xmlString)) .
								'</code></pre>
							</xsl:template>
						</xsl:stylesheet>';
						
						$xslt = new XsltProcess();
						$result = $xslt->process($xmlString, $xsl, $this->params);
						
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
		
		public function getXmlParams() {
			$params = new XMLElement('params');
			foreach ($this->params as $key => $value) {
				$params->appendChild(new XMLElement($key, $value));
			}
			
			return $params;
		}
	}