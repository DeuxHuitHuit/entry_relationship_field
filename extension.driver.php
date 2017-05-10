<?php
	/*
	Copyight: Deux Huit Huit 2014
	LICENCE: MIT https://deuxhuithuit.mit-license.org
	*/

	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");

	require_once(EXTENSIONS . '/entry_relationship_field/fields/field.entry_relationship.php');
	require_once(EXTENSIONS . '/entry_relationship_field/fields/field.reverse_relationship.php');

	/**
	 *
	 * @author Deux Huit Huit
	 * https://deuxhuithuit.com/
	 *
	 */
	class extension_entry_relationship_field extends Extension {

		/**
		 * Name of the extension
		 * @var string
		 */
		const EXT_NAME = 'Entry Relationship Field';

		/**
		 * Symphony utility function that permits to
		 * implement the Observer/Observable pattern.
		 * We register here delegate that will be fired by Symphony
		 */

		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'appendAssets'
				)
			);
		}

		/**
		 * Delegate fired to add a link to Cache Management
		 */
		public function fetchNavigation() {
			if (is_callable(array('Symphony', 'Author'))) {
				$author = Symphony::Author();
			} else {
				$author = Administration::instance()->Author;
			}

			// Work around single group limit in nav
			$group = $author->isDeveloper() ? 'developer' : 'manager';

			return array(
					array (
						'location' => __('System'),
						'name' => __('ERF Clean up'),
						'link' => 'cleanup/',
						'limit' => $group,
					) // nav group
			); // nav
		}

		/**
		 *
		 * Appends javascript file references into the head, if needed
		 * @param array $context
		 */
		public function appendAssets(array $context) {
			// store the callback array locally
			$c = Administration::instance()->getPageCallback();

			// publish page
			if($c['driver'] == 'publish') {
				Administration::instance()->Page->addStylesheetToHead(
					URL . '/extensions/entry_relationship_field/assets/publish.entry_relationship_field.css',
					'screen',
					time() + 1,
					false
				);
				Administration::instance()->Page->addScriptToHead(
					URL . '/extensions/entry_relationship_field/assets/publish.entry_relationship_field.js',
					10,
					false
				);

			} else if ($c['driver'] == 'blueprintssections') {
				Administration::instance()->Page->addStylesheetToHead(
					URL . '/extensions/entry_relationship_field/assets/section.entry_relationship_field.css',
					'screen',
					time() + 1,
					false
				);
				Administration::instance()->Page->addScriptToHead(
					URL . '/extensions/entry_relationship_field/assets/section.entry_relationship_field.js',
					time(),
					false
				);
			}
		}

		/* ********* INSTALL/UPDATE/UNINSTALL ******* */

		/**
		 * Creates the table needed for the settings of the field
		 */
		public function install() {
			General::realiseDirectory(WORKSPACE . '/er-templates');
			return FieldEntry_Relationship::createFieldTable() && FieldReverse_Relationship::createFieldTable();
		}

		/**
		 * This method will update the extension according to the
		 * previous and current version parameters.
		 * @param string $previousVersion
		 */
		public function update($previousVersion = false) {
			$ret = true;

			if (!$previousVersion) {
				$previousVersion = '0.0.1';
			}

			// less than 1.0.2
			if ($ret && version_compare($previousVersion, '1.0.2', '<')) {
				$ret = FieldEntry_Relationship::update_102();
			}

			// less than 1.0.3
			if ($ret && version_compare($previousVersion, '1.0.3', '<')) {
				$ret = FieldEntry_Relationship::update_103();
			}

			// less than 2.0.0.beta5
			if ($ret && version_compare($previousVersion, '2.0.0.beta5', '<')) {
				$ret = FieldEntry_Relationship::update_200();
			}

			// less than 2.0.0.beta6
			if ($ret && version_compare($previousVersion, '2.0.0.beta6', '<')) {
				$ret = FieldReverse_Relationship::update_200();
			}

			// less than 2.0.0.beta8
			if ($ret && version_compare($previousVersion, '2.0.0.beta8', '<')) {
				$ret = FieldEntry_Relationship::update_2008();
			}

			// less than 2.1.0 and more recent than 2.0.0.beta6
			if ($ret && version_compare($previousVersion, '2.1.0', '<') &&
				version_compare($previousVersion, '2.0.0.beta6', '>=')) {
				$ret = FieldReverse_Relationship::update_210();
			}

			return $ret;
		}

		/**
		 * Drops the table needed for the settings of the field
		 */
		public function uninstall() {
			return FieldEntry_Relationship::deleteFieldTable() && FieldReverse_Relationship::deleteFieldTable();
		}

	}
