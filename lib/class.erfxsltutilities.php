<?php
	/*
	Copyright: Deux Huit Huit 2016
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/
	
	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");
	
	class ERFXSLTUTilities {
		public static function entryToXml($parentField, $entry, $entrySectionHandle, $entryFields, $mode, $debug = false)
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
			$entryData = $entry->getData();
			$includedElements = FieldEntry_relationship::parseElements($parentField);
			
			$xslFilePath = WORKSPACE . '/er-templates/' . $entrySectionHandle . '.xsl';
			if (!empty($entryData) && !!@file_exists($xslFilePath)) {
				$xmlData = new XMLElement('data');
				$xmlData->setIncludeHeader(true);
				$xml = new XMLElement('entry');
				$xml->setAttribute('id', $entryId);
				$xmlData->appendChild(self::getXmlParams($params));
				$xmlData->appendChild($xml);
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
							$parentIncludableElement = FieldEntry_relationship::getSectionElementName($fieldName, $fieldIncludedElement);
							$parentIncludableElementMode = FieldEntry_relationship::extractMode($fieldName, $parentIncludableElement);
							
							// Special treatments for ERF
							if ($field instanceof FieldEntry_relationship) {
								// Increment recursive level
								$field->recursiveLevel = $recursiveLevel + 1;
								$field->recursiveDeepness = $deepness;
							}
							
							if ($parentIncludableElementMode == null) {
								$submodes = array_map(function ($fieldIncludableElement) use ($fieldName) {
									return FieldEntry_relationship::extractMode($fieldName, $fieldIncludableElement);
								}, $field->fetchIncludableElements());
							}
							else {
								$submodes = array($parentIncludableElementMode);
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
						<xsl:apply-templates select="entry" ' . $xmlMode . ' />
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
	}