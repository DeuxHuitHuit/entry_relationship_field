<?php
/**
 * Copyright: Deux Huit Huit 2017
 * LICENCE: MIT https://deuxhuithuit.mit-license.org
 */

require_once(TOOLKIT . '/class.jsonpage.php');

class contentExtensionEntry_relationship_fieldSearch extends JSONPage
{
	const MAX_FILTERABLE_FIELDS = 7;
	const MAX_RESULT = 10;

	public function view()
	{
		if (class_exists('FLang')) {
			try {
				FLang::setMainLang(Lang::get());
				FLang::setLangCode(Lang::get(), '');
			} catch (Exception $ex) { }
		}

		$section = General::sanitize($this->_context[0]);
		$sectionId = SectionManager::fetchIDFromHandle($section);
		$sectionId = General::intval($sectionId);
		$excludes = !isset($this->_context[1]) ? [] : explode(',', General::sanitize($this->_context[1]));

		if ($sectionId < 1) {
			$this->_Result['status'] = Page::HTTP_STATUS_BAD_REQUEST;
			$this->_Result['error'] = __('No section id found');
			return;
		}

		$section = (new SectionManager)
			->select()
			->section($sectionId)
			->execute()
			->next();

		if (empty($section)) {
			$this->_Result['status'] = Page::HTTP_STATUS_NOT_FOUND;
			$this->_Result['error'] = __('Section not found');
			return;
		}

		$query = trim(General::sanitize($_GET['query']));
		$entries = array();
		$filterableFields = $section->fetchFilterableFields();
		if (empty($filterableFields)) {
			$this->_Result['status'] = Page::HTTP_STATUS_BAD_REQUEST;
			$this->_Result['error'] = __('Section not filterable');
			return;
		}

		foreach ($filterableFields as $key => $field) {
			if ($field instanceof FieldRelationship || $field instanceof FieldDate) {
				unset($filterableFields[$key]);
			}
		}
		$filterableFields = array_values($filterableFields);
		if (count($filterableFields) > self::MAX_FILTERABLE_FIELDS) {
			$filterableFields = array_slice($filterableFields, 0, self::MAX_FILTERABLE_FIELDS);
		}

		$primaryField = $section->fetchVisibleColumns();

		$getId = function ($obj) { return $obj->get('id'); };
		$intersection = array_intersect(array_map($getId, $filterableFields), array_map($getId, $primaryField));

		if (empty($primaryField)) {
			$primaryField = current($filterableFields);
			reset($filterableFields);
		} else {
			$primaryField = current($primaryField);
		}

		foreach ($filterableFields as $field) {
			if (!empty($intersection) && !in_array($field->get('id'), $intersection)) {
				continue;
			}
			$q = (new EntryManager)
				->select()
				->sort('system:id', 'asc')
				->schema([$primaryField->get('element_name')])
				->section($sectionId)
				->disableDefaultSort()
				->limit(self::MAX_RESULT);

			if (!empty($query)) {
				try {
					$opt = array_map(function ($op) {
						return trim($op['filter']);
					}, $field->fetchFilterableOperators());
					if (in_array('contains:', $opt)) {
						$q->filter($field, ['contains: ' . $query . '%']);
					} elseif (in_array('regexp:', $opt)) {
						$q->filter($field, ['regexp: ' . $query]);
					} else {
						$q->filter($field, [$query]);
					}
				} catch (DatabaseStatementException $ex) {
					continue;
				}
			}

			if (!empty($excludes)) {
				$q->filter('system:id', ['not:' . implode(',', $excludes)]);
			}

			$fEntries = $q
				->execute()
				->rows();

			if (!empty($fEntries)) {
				$entries = array_merge($entries, $fEntries);
				$excludes = array_merge($excludes, array_map($getId, $fEntries));
			}

			if (count($entries) > self::MAX_RESULT) {
				break;
			}
		}

		$entries = array_map(function ($entry) use ($primaryField) {
			return array(
				'value' => $entry->get('id') . ':' .
					$primaryField->prepareReadableValue(
						$entry->getData($primaryField->get('id')),
						$entry->get('id')
				),
			);
		}, $entries);

		$this->_Result['entries'] = $entries;
	}
}
