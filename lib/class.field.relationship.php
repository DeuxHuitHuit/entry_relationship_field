<?php
/**
 * Copyright: Deux Huit Huit 2017
 * LICENCE: MIT https://deuxhuithuit.mit-license.org
 */

if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

require_once(TOOLKIT . '/class.field.php');

/**
 *
 * Base Field class that will represent a relationship between entries
 * @author Deux Huit Huit
 *
 */
class FieldRelationship extends Field
{
    /**
     * 
     * Separator char for values
     *  @var string
     */
    const SEPARATOR = ',';

    protected $orderable = false;

    /**
     * @param string $name
     */
    public function getInt($name)
    {
        return General::intval($this->get($name));
    }

    /**
     * Check if a given property is == 'yes'
     * @param string $name
     * @return bool
     *  True if the current field's value is 'yes'
     */
    public function is($name)
    {
        return $this->get($name) == 'yes';
    }

    public function getArray($name)
    {
        return array_filter(array_map('trim', explode(self::SEPARATOR, trim($this->get($name)))));
    }

    /**
     * @return bool
     *  True if the current field is required
     */
    public function isRequired()
    {
        return $this->is('required');
    }

    public static function getEntries(array $data)
    {
        return array_map(array('General', 'intval'), array_filter(array_map('trim', explode(self::SEPARATOR, $data['entries']))));
    }

    /**
     * @param string $fieldName
     * @param string $text
     */
    protected function createCheckbox($fieldName, $text) {
        $chk = Widget::Label();
        $chk->setAttribute('class', 'column');
        $attrs = null;
        if ($this->get($fieldName) == 'yes') {
            $attrs = array('checked' => 'checked');
        }
        $chk->appendChild(Widget::Input($this->createSettingsFieldName($fieldName), 'yes', 'checkbox', $attrs));
        $chk->setValue(__($text));
        return $chk;
    }

    /**
     * @param string $prefix
     * @param string $name
     * @param @optional bool $multiple
     */
    protected function createFieldName($prefix, $name, $multiple = false)
    {
        $name = "fields[$prefix][$name]";
        if ($multiple) {
            $name .= '[]';
        }
        return $name;
    }

    /**
     * @param string $name
     */
    protected function createSettingsFieldName($name, $multiple = false)
    {
        return $this->createFieldName($this->get('sortorder'), $name, $multiple);
    }

    /**
     * @param string $name
     */
    protected function createPublishFieldName($name, $multiple = false)
    {
        return $this->createFieldName($this->get('element_name'), $name, $multiple);
    }

    protected function getSelectedSectionsArray()
    {
        $selectedSections = $this->get('sections');
        if (!is_array($selectedSections)) {
            if (is_string($selectedSections) && strlen($selectedSections) > 0) {
                $selectedSections = explode(self::SEPARATOR, $selectedSections);
            }
            else {
                $selectedSections = array();
            }
        }
        return $selectedSections;
    }

    protected function createEntriesList($entries)
    {
        $wrap = new XMLElement('div');
        $wrapperClass = 'frame collapsible';
        if (count($entries) == 0) {
            $wrapperClass .= ' empty';
        }
        if (!$this->is('show_header')) {
            $wrapperClass .= ' no-header';
        }
        if ($this->orderable) {
            $wrapperClass .= ' orderable';
        }
        $wrap->setAttribute('class', $wrapperClass);
        
        $list = new XMLElement('ul');
        $list->setAttribute('class', '');
        if ($this->is('allow_collapse')) {
            $list->setAttribute('data-collapsible', '');
        }
        
        $wrap->appendChild($list);
        
        return $wrap;
    }

    /**
     * @param integer $count
     */
    final static protected function formatCount($count)
    {
        if ($count == 0) {
            return __('No item');
        } else if ($count == 1) {
            return __('1 item');
        }
        return __('%s items', array($count));
    }
}
