<?php
/**
 * Copyright: Deux Huit Huit 2017
 * LICENCE: MIT https://deuxhuithuit.mit-license.org
 */

if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

require_once(EXTENSIONS . '/entry_relationship_field/lib/class.field.relationship.php');
require_once(EXTENSIONS . '/entry_relationship_field/lib/class.cacheablefetch.php');
require_once(EXTENSIONS . '/entry_relationship_field/lib/class.erfxsltutilities.php');

/**
 *
 * Field class that will represent a reverse relationship between entries
 * @author Deux Huit Huit
 *
 */
class FieldReverse_Relationship extends FieldRelationship
{
    /**
     *
     * Name of the field table
     *  @var string
     */
    const FIELD_TBL_NAME = 'tbl_fields_reverse_relationship';

    /**
     *
     * Constructor for the Reverse_Relationship Field object
     */
    public function __construct()
    {
        // call the parent constructor
        parent::__construct();
        // set the name of the field
        $this->_name = __('Reverse Relationship');
        // permits to make it required
        $this->_required = true;
        // forbid the make it show in the table columns
        $this->_showcolumn = false;
        // forbid association
        $this->_showassociation = false;
        // set as not required by default
        $this->set('required', 'no');
        // show header by default
        $this->set('show_header', 'yes');
        // allow link by default
        $this->set('allow_unlink', 'yes');
        // allow go to by default
        $this->set('allow_goto', 'yes');
    }

    public function isSortable()
    {
        return false;
    }

    public function canFilter()
    {
        return false;
    }
    
    public function canPublishFilter()
    {
        return false;
    }

    public function canImport()
    {
        return false;
    }

    public function canPrePopulate()
    {
        return false;
    }
    
    public function mustBeUnique()
    {
        return false;
    }

    public function allowDatasourceOutputGrouping()
    {
        return false;
    }

    public function requiresSQLGrouping()
    {
        return false;
    }

    public function allowDatasourceParamOutput()
    {
        return false;
    }

    public function requiresTable()
    {
        return false;
    }

    public function createTable()
    {
        return false;
    }

    public function fetchIncludableElements()
    {
    }

    /* ********** INPUT AND FIELD *********** */

    /**
     * 
     * Validates input
     * Called before <code>processRawFieldData</code>
     * @param $data
     * @param $message
     * @param $entry_id
     */
    public function checkPostFieldData($data, &$message, $entry_id=null)
    {
        return self::__OK__;
    }


    /**
     *
     * Process data before saving into database.
     *
     * @param array $data
     * @param int $status
     * @param boolean $simulate
     * @param int $entry_id
     *
     * @return Array - data to be inserted into DB
     */
    public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
    {
        $status = self::__OK__;
        return null;
    }


    /**
     *
     * Validates the field settings before saving it into the field's table
     */
    public function checkFields(Array &$errors, $checkForDuplicates = true)
    {
        $parent = parent::checkFields($errors, $checkForDuplicates);
        if ($parent != self::__OK__) {
            return $parent;
        }
        
        $sections = $this->get('linked_section_id');
        if (empty($sections)) {
            $errors['sections'] = __('A section must be chosen');
        }
        $field = $this->get('linked_field_id');
        if (empty($field)) {
            $errors['field'] = __('A field must be chosen');
        }

        return (!empty($errors) ? self::__ERROR__ : self::__OK__);
    }

    /**
     *
     * Save field settings into the field's table
     */
    public function commit()
    {
        // if the default implementation works...
        if(!parent::commit()) return false;
        
        $id = $this->get('id');
        
        // exit if there is no id
        if ($id == false) return false;
        
        // declare an array contains the field's settings
        $settings = array(
            'linked_section_id' => $this->get('linked_section_id'),
            'linked_field_id' => $this->get('linked_field_id'),
        );
        
        return FieldManager::saveSettings($id, $settings);
    }

    /**
     * Appends data into the XML tree of a Data Source
     * @param $wrapper
     * @param $data
     */
    public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null)
    {
        // nothing to do
    }

    /* ********* UI *********** */
    
    /**
     *
     * Builds the UI for the field's settings when creating/editing a section
     * @param XMLElement $wrapper
     * @param array $errors
     */
    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
    {
        /* first line, label and such */
        parent::displaySettingsPanel($wrapper, $errors);
        
        // fieldset
        $fieldset = new XMLElement('fieldset', null);
        
        // group
        $group = new XMLElement('div', null, array('class' => 'two columns'));
        $fieldset->appendChild($group);
        
        // sections
        $sections = new XMLElement('div', null, array('class' => 'column'));
        $this->appendSelectionSelect($sections);
        if (is_array($errors) && isset($errors['sections'])) {
            $sections = Widget::Error($sections, $errors['sections']);
        }
        $group->appendChild($sections);
        
        // field
        $field = new XMLElement('div', null, array('class' => 'column'));
        $this->appendFieldSelect($field);
        if (is_array($errors) && isset($errors['field'])) {
            $field = Widget::Error($field, $errors['field']);
        }
        $group->appendChild($field);
        
        $wrapper->appendChild($fieldset);
    }

    /**
     *
     * Builds the UI for the publish page
     * @param XMLElement $wrapper
     * @param mixed $data
     * @param mixed $flagWithError
     * @param string $fieldnamePrefix
     * @param string $fieldnamePostfix
     */
    public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null)
    {
        if (!$entry_id) {
            return;
        }
        $field = FieldManager::fetch($this->get('linked_field_id'));
        $section = SectionManager::fetch($this->get('linked_section_id'));
        if (!($field instanceof FieldRelationship)) {
            $flagWithError = __('Linked field is not valid. Please edit this field to set it to a valid ER field.');
        }
        
        $label = Widget::Label($this->get('label'));
        // label error management
        if ($flagWithError != null) {
            $wrapper->appendChild(Widget::Error($label, $flagWithError));
        } else {
            $wrapper->appendChild($label);
            $wrapper->appendChild($this->createEntriesList(array()));
            $wrapper->appendChild($this->createActionBarMenu($field));
        }
        
        $wrapper->setAttribute('data-field-id', $this->get('id'));
        $wrapper->setAttribute('data-linked-field-id', $this->get('linked_field_id'));
        $wrapper->setAttribute('data-linked-section-id', $this->get('linked_section_id'));
        $wrapper->setAttribute('data-linked-section', $section->get('handle'));
        $wrapper->setAttribute('data-field-label', $field->get('label'));
        $wrapper->setAttribute(
            'data-entries',
            implode(self::SEPARATOR, $field->findRelatedEntries($entry_id, null))
        );
        if (isset($_REQUEST['debug'])) {
            $wrapper->setAttribute('data-debug', true);
        }
    }

    private function createActionBarMenu($field)
    {
        $section = SectionManager::fetch($this->get('linked_section_id'));
        $wrap = new XMLElement('div');

        $fieldset = new XMLElement('fieldset');
        $fieldset->setAttribute('class', 'single');
        $fieldset->appendChild(new XMLElement(
            'span',
            __('Related section: '),
            array('class' => 'reverse-selection')
        ));
        $fieldset->appendChild(new XMLElement(
            'label',
            General::sanitize($section->get('name') . ': ' . $field->get('label')),
            array('class' => 'reverse-selection')
        ));
        $fieldset->appendChild(new XMLElement('button', __('Add to entry'), array(
            'type' => 'button',
            'class' => 'add',
            'data-add' => $section->get('handle'),
        )));

        $wrap->appendChild($fieldset);

        return $wrap;
    }

    private static $erFields = array();
    private function getERFields()
    {
        if (empty(self::$erFields)) {
            self::$erFields = FieldManager::fetch(null, null, null, 'id', 'entry_relationship');
        }
        return self::$erFields;
    }

    private static $erSections = array();
    private function getERSections()
    {
        if (empty(self::$erSections)) {
            $erFields = self::getERFields();
            $sectionIds = array_map(function ($erField) {
                return $erField->get('parent_section');
            }, $erFields);
            self::$erSections = SectionManager::fetch($sectionIds);
        }
        return self::$erSections;
    }

    private function buildSectionSelect($name)
    {
        $sections = static::getERSections();
        $options = array();
        
        foreach ($sections as $section) {
            $driver = $section->get('id');
            $selected = $driver === $this->get('linked_section_id');
            $options[] = array($driver, $selected, General::sanitize($section->get('name')));
        }
        
        return Widget::Select($name, $options);
    } 

    private function appendSelectionSelect(&$wrapper)
    {
        $name = $this->createSettingsFieldName('linked_section_id', false);

        $input = $this->buildSectionSelect($name);
        $input->setAttribute('class', 'reverse_relationship-sections');

        $label = Widget::Label();

        $label->setValue(__('Available sections %s', array($input->generate())));

        $wrapper->appendChild($label);
    }

    private function buildFieldSelect($name)
    {
        $section = $this->get('linked_section_id') ? SectionManager::fetch($this->get('linked_section_id')) : null;
        $fields = static::getERFields();
        $options = array();
        
        foreach ($fields as $field) {
            if ($section && $section->get('id') !== $field->get('parent_section')) {
                continue;
            }
            $driver = $field->get('id');
            $selected = $driver === $this->get('linked_field_id');
            $options[] = array($driver, $selected, General::sanitize($field->get('label')));
        }
        
        return Widget::Select($name, $options);
    } 

    protected function appendFieldSelect(&$wrapper)
    {
        $name = $this->createSettingsFieldName('linked_field_id', false);

        $input = $this->buildFieldSelect($name);
        $input->setAttribute('class', 'reverse_relationship-field');

        $label = Widget::Label();

        $label->setValue(__('Available Fields %s', array($input->generate())));

        $wrapper->appendChild($label);
    }

    /**
     * Creates the table needed for the settings of the field
     */
    public static function createFieldTable()
    {
        $tbl = self::FIELD_TBL_NAME;

        return Symphony::Database()->query("
            CREATE TABLE IF NOT EXISTS `$tbl` (
                `id`                int(11) unsigned NOT NULL AUTO_INCREMENT,
                `field_id`          int(11) unsigned NOT NULL,
                `linked_section_id` int(11) unsigned NOT NULL,
                `linked_field_id`   int(11) unsigned NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `field_id` (`field_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        ");
    }

    public static function update_200()
    {
        return static::createFieldTable();
    }

    /**
     *
     * Drops the table needed for the settings of the field
     */
    public static function deleteFieldTable()
    {
        $tbl = self::FIELD_TBL_NAME;
        
        return Symphony::Database()->query("
            DROP TABLE IF EXISTS `$tbl`
        ");
    }
}
