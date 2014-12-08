<?php
	/*
	Copyright: Deux Huit Huit 2014
	LICENCE: MIT http://deuxhuithuit.mit-license.org;
	*/

	require_once(TOOLKIT . '/class.jsonpage.php');

	Class contentExtensionEntry_relationship_fieldSectionsinfos extends JSONPage {
		
		public function view() {
			$sectionIDs = array_map(array('General', 'intval'), explode(',', General::sanitize($this->_context[0])));
			
			if (empty($sectionIDs)) {
				$this->_Result['status'] = Page::HTTP_STATUS_BAD_REQUEST;
				$this->_Result['error'] = __('No section id found');
				return;
			}
			
			$sections = SectionManager::fetch($sectionIDs);
			$options = array();
			
			if(!empty($sections)) {
				foreach ($sections as $section) {
					$section_fields = $section->fetchFields();
					if(!is_array($section_fields)) {
						continue;
					}

					$fields = array();
					foreach($section_fields as $f) {
						$modes = $f->fetchIncludableElements();
						
						if (is_array($modes)) {
							// include default
							$fields[] = array(
								'id' => $f->get('id'),
								'name' => $f->get('label'),
								'handle' => $f->get('element_name'),
								'type' => $f->get('type')
							);
							if (count($modes) > 1) {
								foreach ($modes as $mode) {
									$fields[] = array(
										'id' => $f->get('id'),
										'name' => $f->get('label'),
										'handle' => $mode,
										'type' => $f->get('type')
									);
								}
							}
						}
					}

					$options[] = array(
						'name' => $section->get('name'),
						'handle' => $section->get('handle'),
						'fields' => $fields
					);
				}
			} else {
				$this->_Result['status'] = Page::HTTP_STATUS_NOT_FOUND;
				$this->_Result['error'] = __('No section found');
			}

			$this->_Result['sections'] = $options;
		}
	}
