<?php
	/**
	 * Copyright: Deux Huit Huit 2016
	 * LICENCE: MIT https://deuxhuithuit.mit-license.org
	 */
	
	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");
	
	class ERFXSLTUTilities {
		public static function processXSLT($parentField, $entry, $entrySectionHandle, $entryFields, $mode, $debug = false, $select = 'entry', $position = 0)
		{
			$date = new DateTime();
			$params = array(
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
			
			$xslFilePath = WORKSPACE . '/er-templates/' . $entrySectionHandle . '.xsl';
			if (!!@file_exists($xslFilePath)) {
				$xmlData = new XMLElement('data');
				$xmlData->setIncludeHeader(true);
				
				// params
				$xmlData->appendChild(self::getXmlParams($params));
				
				// entry data
				if ($entry) {
					$includedElements = FieldEntry_relationship::parseElements($parentField);
					$xmlData->appendChild(self::entryToXML($entry, $entrySectionHandle, $includedElements, $entryFields, $position));
				}
				
				// field data
				$xmlData->appendChild(self::fieldToXML($parentField));
				
				// process XSLT
				$indent = false;
				$mode = $parentField->get($mode);
				if ($debug) {
					$mode = 'debug';
				}
				if ($mode == 'debug') {
					$indent = true;
				}
				$xmlMode = empty($mode) ? '' : 'mode="' . $mode . '"';
				$xmlString = $xmlData->generate($indent, 0);
				$xsl = '<?xml version="1.0" encoding="UTF-8"?>
				<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
					<xsl:import href="' . str_replace('\\', '/',  $xslFilePath) . '"/>
					<xsl:output method="xml" omit-xml-declaration="yes" encoding="UTF-8" indent="no" />
					<xsl:template match="/">
						<xsl:apply-templates select="/data" ' . $xmlMode . ' />
					</xsl:template>
					<xsl:template match="/data" ' . $xmlMode . '>
						<xsl:apply-templates select="' . $select . '" ' . $xmlMode . ' />
					</xsl:template>
					<xsl:template match="/data" mode="debug">
						<xsl:copy-of select="/" />
					</xsl:template>
				</xsl:stylesheet>';
				$xslt = new XsltProcess();
				$result = $xslt->process($xmlString, $xsl, $params);
				
				if ($mode == 'debug') {
					$result = '<pre><code>' .
						str_replace('<', '&lt;', str_replace('>', '&gt;', $xmlString)) .
						'</code></pre>';
				}
				
				if ($xslt->isErrors()) {
					$error = $xslt->getError();
					$result = $error[1]['message'];
				}
				
				if (General::strlen(trim($result)) > 0) {
					return $result;
				}
			}
			return null;
		}
		
		public static function getXmlParams(array $params) {
			$xmlparams = new XMLElement('params');
			foreach ($params as $key => $value) {
				$xmlparams->appendChild(new XMLElement($key, $value));
			}
			return $xmlparams;
		}
		
		public static function fieldToXML($field) {
			// field data
			$xmlField = new XMLElement('field');
			$xmlField->setAttribute('id', $field->get('id'));
			$xmlField->setAttribute('handle', $field->get('element_name'));
			$xmlField->appendChild(new XMLElement('allow-new', $field->get('allow_new')));
			$xmlField->appendChild(new XMLElement('allow-edit', $field->get('allow_edit')));
			$xmlField->appendChild(new XMLElement('allow-delete', $field->get('allow_delete')));
			$xmlField->appendChild(new XMLElement('allow-link', $field->get('allow_link')));
			$xmlField->appendChild(new XMLElement('allow-collapse', $field->get('allow_collapse')));
			$xmlField->appendChild(new XMLElement('allow-search', $field->get('allow_search')));
			$xmlField->appendChild(new XMLElement('show-header', $field->get('show_header')));
			$xmlField->appendChild(new XMLElement('show-association', $field->get('show_association')));
			$xmlField->appendChild(new XMLElement('deepness', $field->get('deepness')));
			$xmlField->appendChild(new XMLElement('required', $field->get('required')));
			$xmlField->appendChild(new XMLElement('min-entries', $field->get('min_entries')));
			$xmlField->appendChild(new XMLElement('max-entries', $field->get('max_entries')));
			$xmlField->appendChild(new XMLElement('sort-order', $field->get('sortorder')));
			$sections = $field->getArray('sections');
			$sections = SectionManager::fetch($sections);
			$xmlSections = new XMLElement('sections');
			foreach ($sections as $section) {
				$xmlSections->appendChild(new XMLElement('section', $section->get('name'), array(
					'id' => $section->get('id'),
					'handle' => $section->get('handle'),
				)));
			}
			$xmlField->appendChild($xmlSections);
			return $xmlField;
		}
		
		public static function entryToXML($entry, $entrySectionHandle, $includedElements, $entryFields, $position = 0) {
			$entryData = $entry->getData();
			$entryId = General::intval($entry->get('id'));
			$xml = new XMLElement('entry');
			$xml->setAttribute('id', $entryId);
			$xml->setAttribute('section-id', $entry->get('section_id'));
			$xml->setAttribute('section', $entrySectionHandle);
			if ($position) {
				$xml->setAttribute('position', (string)$position);
			}
			if (!empty($entryData)) {
				foreach ($entryData as $fieldId => $data) {
					$filteredData = array_filter($data, function ($value) {
						return $value != null;
					});
					if (empty($filteredData)) {
						continue;
					}
					$field = $entryFields[$fieldId];
					$fieldName = $field->get('element_name');
					$fieldIncludedElement = $includedElements[$entrySectionHandle];
					
					try {
						if (FieldEntry_relationship::isFieldIncluded($fieldName, $fieldIncludedElement)) {
							$submodes = FieldEntry_relationship::getAllSelectedFieldModes($fieldName, $fieldIncludedElement);
							
							// Special treatments for ERF
							if ($field instanceof FieldEntry_relationship) {
								// Increment recursive level
								$field->incrementRecursiveLevel();
								$field->setRecursiveDeepness($deepness);
							}
							
							if ($submodes == null) {
								if ($field instanceof FieldEntry_Relationship) {
									$field->expandIncludableElements = false;
								}
								$submodes = array_map(function ($fieldIncludableElement) use ($fieldName) {
									return FieldEntry_relationship::extractMode($fieldName, $fieldIncludableElement);
								}, $field->fetchIncludableElements());
								if ($field instanceof FieldEntry_Relationship) {
									$field->expandIncludableElements = true;
								}
							}
							
							foreach ($submodes as $submode) {
								$field->appendFormattedElement($xml, $filteredData, false, $submode, $entryId);
							}
						}
					}
					catch (Exception $ex) {
						$xml->appendChild(new XMLElement('error', $ex->getMessage() . ' on ' . $ex->getLine() . ' of file ' . $ex->getFile()));
					}
				}
			}
			return $xml;
		}
	}