<?php
	/**
	 * Copyright: Deux Huit Huit 2014
	 * LICENCE: MIT https://deuxhuithuit.mit-license.org
	 */

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(TOOLKIT . '/class.xmlpage.php');
	require_once(EXTENSIONS . '/entry_relationship_field/lib/class.erfxsltutilities.php');

	class contentExtensionEntry_Relationship_FieldRender extends XMLPage {

		const NUMBER_OF_URL_PARAMETERS = 2;

		private $sectionManager;
		private $fieldManager;
		private $entryManager;

		public function __construct() {
			parent::__construct();
			// cache managers
			$this->sectionManager = new SectionManager;
			$this->fieldManager = new FieldManager;
			$this->entryManager = new EntryManager;
			// fix jquery
			$this->_Result->setIncludeHeader(false);
			$this->addHeaderToPage('Content-Type', 'text/html');
		}

		/**
		 *
		 * Builds the content view
		 */
		public function view() {
			if (class_exists('FLang')) {
				try {
					FLang::setMainLang(Lang::get());
					FLang::setLangCode(Lang::get(), '');
				} catch (Exception $ex) {}
			}
			// _context[0] => entry values
			// _context[1] => fieldId
			if (!is_array($this->_context) || empty($this->_context) || $this->_context[0] === 'null') {
				$this->_Result->appendChild(new XMLElement('error', __('Parameters not found')));
				return;
			}
			else if (count($this->_context) < self::NUMBER_OF_URL_PARAMETERS) {
				$this->_Result->appendChild(new XMLElement('error', __('Not enough parameters')));
				return;
			}
			else if (count($this->_context) > self::NUMBER_OF_URL_PARAMETERS) {
				$this->_Result->appendChild(new XMLElement('error', __('Too many parameters')));
				return;
			}

			$entriesId = explode(',', $this->_context[0]);
			$entriesId = array_map(array('General', 'intval'), $entriesId);
			if (!is_array($entriesId) || empty($entriesId)) {
				$this->_Result->appendChild(new XMLElement('error', __('No entry no found')));
				return;
			}

			$parentFieldId = General::intval($this->_context[1]);
			if ($parentFieldId < 1) {
				$this->_Result->appendChild(new XMLElement('error', __('Parent field id not valid')));
				return;
			}

			$parentField = $this->fieldManager
				->select()
				->field($parentFieldId)
				->execute()
				->next();
			if (!$parentField || empty($parentField)) {
				$this->_Result->appendChild(new XMLElement('error', __('Parent field not found')));
				return;
			}

			if (!($parentField instanceof FieldRelationship)) {
				$this->_Result->appendChild(new XMLElement('error', __('Parent field is `%s`, not `relationship field`', array($parentField->get('type')))));
				return;
			}
			if (!$parentField->get('elements')) {
				$parentField->set('elements', '*');
			}
			if (!$parentField->get('sections') && $parentField->get('linked_section_id')) {
				$parentField->set('sections', $parentField->get('linked_section_id'));
			}

			// Get entries one by one since they may belong to
			// different sections, which prevents us from
			// passing an array of entryId.
			foreach ($entriesId as $key => $entryId) {
				$entry = $this->entryManager
					->select()
					->entry($entryId)
					->execute()
					->next();
				if (empty($entry)) {
					$li = new XMLElement('li', null, array(
						'data-entry-id' => $entryId
					));
					$header = new XMLElement('header', null, array('class' => 'frame-header no-content ignore-collapsible'));
					$title = new XMLElement('h4');
					$title->appendChild(new XMLElement('strong', __('Entry %s not found', array($entryId))));
					$header->appendChild($title);
					$options = new XMLElement('div', null, array('class' => 'destructor'));
					$options->appendChild(new XMLElement('a', __('Un-link'), array(
						'class' => 'unlink ignore-collapsible',
						'data-unlink' => $entryId,
					)));
					$header->appendChild($options);
					$li->appendChild($header);
					$this->_Result->appendChild($li);
				} else {
					$entry = $this->entryManager
						->select()
						->entry($entryId)
						->section($entry->get('section_id'))
						->includeAllFields()
						->execute()
						->next();
					$entrySection = $this->sectionManager
						->select()
						->section($entry->get('section_id'))
						->execute()
						->next();
					$entryVisibleFields = $entrySection->fetchVisibleColumns();
					$entryFields = $entrySection->fetchFields();
					$entrySectionHandle = $this->getSectionName($entry, 'handle');

					$li = new XMLElement('li', null, array(
						'data-entry-id' => $entryId,
						'data-section' => $entrySectionHandle,
						'data-section-id' => $entrySection->get('id'),
						'data-timestamp' => $entry->get('modification_date'),
					));
					if ($parentField->is('show_header')) {
						$header = new XMLElement('header', null, array(
							'class' => 'frame-header',
							'data-orderable-handle' => '',
							'data-collapsible-handle' => ''
						));
						$title = new XMLElement('h4', null, array('class' => 'ignore-collapsible'));
						if (!$parentField->get('mode_header')) {
							$title->appendChildArray($this->buildDefaultTitle($entry, $entryVisibleFields, $entryFields));
						}
						else {
							$title->setValue(ERFXSLTUTilities::processXSLT($parentField, $entry, $entrySectionHandle, $entryFields, 'mode_header'));
						}
						$header->appendChild($title);

						$options = new XMLElement('div', null, array('class' => 'destructor'));
						if ($parentField->is('allow_edit')) {
							$title->setAttribute('data-edit', $entryId);
							$options->appendChild(new XMLElement('a', __('Edit'), array(
								'class' => 'edit ignore-collapsible',
								'data-edit' => $entryId,
							)));
						}
						if ($parentField->is('allow_delete')) {
							$options->appendChild(new XMLElement('a', __('Delete'), array(
								'class' => 'delete ignore-collapsible',
								'data-delete' => $entryId,
							)));
						}
						if ($parentField->is('allow_link')) {
							$options->appendChild(new XMLElement('a', __('Replace'), array(
								'class' => 'unlink ignore-collapsible',
								'data-replace' => $entryId,
							)));
						}
						if ($parentField->is('allow_goto')) {
							$options->appendChild(new XMLElement('a', __('Go to'), array(
								'class' => 'goto ignore-collapsible',
								'data-goto' => $entryId,
							)));
						}
						if ($parentField->is('allow_delete') ||
							$parentField->is('allow_link') || $parentField->is('allow_unlink') ||
							$parentField->is('allow_search')) {
							$options->appendChild(new XMLElement('a', __('Un-link'), array(
								'class' => 'unlink ignore-collapsible',
								'data-unlink' => $entryId,
							)));
						}
						$header->appendChild($options);
						$li->appendChild($header);
					}

					$content = ERFXSLTUTilities::processXSLT($parentField, $entry, $entrySectionHandle, $entryFields, 'mode', isset($_REQUEST['debug']));

					if ($content) {
						$li->appendChild(new XMLElement('div', $content, array(
							'class' => 'content',
							'data-collapsible-content' => '',
						)));
					}
					else {
						if ($parentField->is('show_header')) {
							$header->setAttribute('class', $header->getAttribute('class') . ' no-content');
						}
						else {
							$content = new XMLElement('div', null, array('class' => 'content'));
							$content->appendChildArray($this->buildDefaultTitle($entry, $entryVisibleFields, $entryFields));
							$li->appendChild($content);
						}
					}

					$this->_Result->appendChild($li);
				}
			}
		}

		public function getSectionName($entry, $name = 'name') {
			$sectionId = $entry->get('section_id');
			return $this->sectionManager
				->select()
				->section($sectionId)
				->execute()
				->next()
				->get($name);
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

			return $field->prepareReadableValue($data[$field->get('id')], $entry->get('id'), true);
		}

		public function buildDefaultTitle($entry, $entryVisibleFields, $entryFields) {
			return array(
				new XMLElement('strong', $this->getEntryTitle($entry, $entryVisibleFields, $entryFields), array('class' => 'ignore-collapsible')),
				new XMLElement('span', $this->getSectionName($entry), array('class' => 'ignore-collapsible'))
			);
		}
	}
