/*
	Copyright: Deux Huit Huit 2014
	License: MIT, see the LICENCE file
*/

/**
 * JS for entry relationship field
 */

/* Settings behavior */
(function ($, S) {
	
	'use strict';
	
	var baseurl = function () {
		return S.Context.get('symphony');
	};
	
	var INSTANCES_SEL = '.field-entry_relationship.instance';
	var SECTIONS_SEL = ' .entry_relationship-sections';
	var FIELD_CHOICES_SEL = ' .entry_relationship-field-choices';
	var ELEMENTS_SEL = ' .entry_relationship-elements';
	
	var SECTIONS = baseurl() + '/ajaxsections/';
	
	var instances = $();
	
	var refreshInstances = function (context) {
		instances = context.find();
	};
	
	var renderElementsName = function (field) {
		var sections = field.find(SECTIONS_SEL);
		var fieldChoices = field.find(FIELD_CHOICES_SEL);
		var temp = $();
		var values = {};
		sections.find('option:selected').each(function (index, value) {
			values[$(value).text()] = true;
		});
		
		fieldChoices.empty();
		$.get(SECTIONS).done(function (data) {
			$.each(data.sections, function (index, section) {
				if (values[section.name]) {
					$.each(section.fields, function (index, field) {
						var li = $('<li />').text(section.handle + '.' + field.handle);
						temp = temp.add(li);
					})
				}
			});
			fieldChoices.append(temp);
			field.css('max-height', '+=' + fieldChoices.outerHeight(true) + 'px');
		});
	};
	
	var init = function () {
		var body = $('body');
		if (body.is('#blueprints-sections') && (body.hasClass('edit') || body.hasClass('new'))) {
			$('#fields-duplicator').on('constructshow.duplicator, destructstart.duplicator', function (e) {
				var t = $(this);
				refreshInstances(t);
			}).on('change', INSTANCES_SEL + SECTIONS_SEL, function () {
				var t = $(this);
				var parent = t.closest(INSTANCES_SEL);
				renderElementsName(parent);
			}).on('click', INSTANCES_SEL + FIELD_CHOICES_SEL + '>li', function () {
				var t = $(this);
				var parent = t.closest(INSTANCES_SEL);
				var elements = parent.find(ELEMENTS_SEL);
				var val = elements.val() || '';
				if (!!val) {
					val += ', ';
				}
				val += t.text();
				elements.val(val);
			}).find(INSTANCES_SEL).each(function (index, elem) {
				renderElementsName($(elem));
			});
		}
	};
	
	$(init);
	
})(jQuery, Symphony);