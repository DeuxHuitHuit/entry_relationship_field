<?php
	/**
	 * Copyright: Deux Huit Huit 2014
	 * LICENCE: MIT https://deuxhuithuit.mit-license.org
	 */

	require_once(TOOLKIT . '/class.jsonpage.php');
	require_once(EXTENSIONS . '/entry_relationship_field/lib/class.sectionsinfos.php');

	Class contentExtensionEntry_relationship_fieldSectionsinfos extends JSONPage {

		public function view() {
			$sectionIDs = array_map(array('General', 'intval'), explode(',', General::sanitize($this->_context[0])));

			if (empty($sectionIDs)) {
				$this->_Result['status'] = Page::HTTP_STATUS_BAD_REQUEST;
				$this->_Result['error'] = __('No section id found');
				return;
			}

			$options = (new SectionsInfos)->fetch($sectionIDs);

			if (empty($options)) {
				$this->_Result['status'] = Page::HTTP_STATUS_NOT_FOUND;
				$this->_Result['error'] = __('No section found');
			}

			$this->_Result['sections'] = $options;
		}
	}
