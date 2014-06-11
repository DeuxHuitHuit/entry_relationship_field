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
					
					$li = new XMLElement('li', null, array(
						'data-entry-id' => $entryId,
						'data-section' => $this->getSectionName($entry, 'handle')
					));
					$header = new XMLElement('header', null, array('class' => 'frame-header'));
					$title = new XMLElement('h4');
					$title->appendChild(new XMLElement('strong', $this->getEntryTitle($entry)));
					$title->appendChild(new XMLElement('span', $this->getSectionName($entry)));
					$header->appendChild($title);
					$options = new XMLElement('div', null, array('class' => 'destructor'));
					$options->appendChild(new XMLElement('a', __('Edit'), array('class' => 'edit')));
					$options->appendChild(new XMLElement('a', __('Un-link'), array('class' => 'unlink')));
					$header->appendChild($options);
					$li->appendChild($header);
					
					$entryData = $entry->getData();
					$entrySection = SectionManager::fetch($entry->get('section_id'));
					$entryFields = $entrySection->fetchFields();
					
					$xslFilePath = WORKSPACE . '/er_templates/' . $this->getSectionName($entry, 'handle') . '.xsl';
					
					if (!empty($entryData) && @file_exists($xslFilePath)) {
						$xml = new XMLElement('entry');
						$xml->setIncludeHeader(true);
						
						foreach ($entryData as $fieldId => $data) {
							$entryFields[$fieldId]->appendFormattedElement($xml, $data);
						}
						
						$mode = $parentField->get('mode');
						$xmlMode = empty($mode) ? '' : 'mode="' . $mode . '"';
						
						$xsl = '<?xml version="1.0" encoding="UTF-8"?>
						<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
							<xsl:import href="' . $xslFilePath . '"/>
							<xsl:output method="xml" omit-xml-declaration="yes" encoding="UTF-8" indent="no" />
							<xsl:template match="/">
								<xsl:apply-templates select="./entry" ' . $xmlMode . ' />
							</xsl:template>
							<xsl:template match="entry">
								<xsl:value-of select="." />
							</xsl:template>
							<xsl:template match="entry" mode="debug">
								<textarea>
									<xsl:copy-of select="." />
								</textarea>
							</xsl:template>
						</xsl:stylesheet>';
						
						$xslt = new XsltProcess($xml->generate(), $xsl);
						$result = $xslt->process();
						
						if ($xslt->isErrors()) {
							$error = $xslt->getError();
							$result = $error[1]['message'];
						}
						
						$content = new XMLElement('div', $result, array('class' => 'content'));
						$li->appendChild($content);
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
		
		public function getEntryTitle($entry) {
			$data = $entry->getData();
			$dataKeys = array_keys($data);
			$field = FieldManager::fetch($dataKeys[0]);
			return trim(strip_tags($field->prepareTableValue($data[$field->get('id')], null, $entry->get('id'))));
		}
		
	}