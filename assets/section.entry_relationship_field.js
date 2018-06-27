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

	var MODES = /^(.+):(.+)/i;
	var WHITESPACE = /\s*/i;

	var baseurl = function () {
		return S.Context.get('symphony');
	};

	var INSTANCES_SEL = '.field-entry_relationship.instance';
	var SECTIONS_SEL = ' .entry_relationship-sections';
	var FIELD_CHOICES_SEL = ' .entry_relationship-field-choices';
	var ELEMENTS_SEL = ' .entry_relationship-elements';

	var SECTIONS = baseurl() + '/extension/entry_relationship_field/sectionsinfos/';

	var instances = $();

	var refreshInstances = function (context) {
		instances = context.find();
	};

	var updateElementsNameVisibility = function (field) {
		var fieldElements = field.find(ELEMENTS_SEL);
		var fieldChoices = field.find(FIELD_CHOICES_SEL);
		var values = {};
		var hiddenCount = 0;
		var lis = fieldChoices.find('>li');

		// parse input
		$.each(fieldElements.val().split(','), function (index, value) {
			if (!value) {
				return;
			}
			var parts = value.split('.');
			if (!!parts.length) {
				var sectionname = parts.shift().replace(WHITESPACE, '');
				var fieldname = parts.join('.');

				// skip all included
				if (values[sectionname] === true) {
					return true;
				}
				// set all included
				else if (fieldname === '*' || !fieldname) {
					values[sectionname] = true;
					return true;
				}
				// first time seeing this section
				else if (!values[sectionname]) {
					values[sectionname] = [];
				}
				// add current value
				values[sectionname].push(sectionname + '.' + fieldname);
			}
		});

		// show/hide
		lis.each(function (index, value) {
			var t = $(this);
			var sectionname = t.attr('data-section');
			var value = t.attr('data-value');
			var text = t.text().replace(WHITESPACE, '');
			var field = value || text;
			var fx = 'removeClass';

			var isFieldSelected = function () {
				if (!values[sectionname]) {
					return false;
				}
				var found = false;
				$.each(values[sectionname], function (index, elem) {
					if (field === elem) {
						found = true;
						return false;
					}
					// detect presence of a mode
					else if (!!~field.indexOf(':')) {
						if (field.replace(MODES, '$1') === elem) {
							found = true;
							return false;
						}
					}
				});
				return found;
			};

			if (values['*'] === true || values[sectionname] === true || isFieldSelected()) {
				fx = 'addClass';
				hiddenCount++;
			}
			t[fx]('chosen');
		});

		if (hiddenCount === lis.length - 1) {
			lis.hide();
		}
	};

	var createElementInstance = function (section, text, cssclass) {
		var li = $('<li />')
			.attr('data-section', section.handle)
			.text(text);
		if (!!cssclass) {
			li.addClass(cssclass);
		}
		return li;
	};

	var resizeField = function (field, fieldChoices) {
		var maxHeight = 0;
		if (!field.is('.collapsed')) {
			fieldChoices = fieldChoices || field.find(FIELD_CHOICES_SEL);
			field.css('max-height', '+=' + fieldChoices.outerHeight(true) + 'px');
			maxHeight = parseInt(field.css('max-height'));
		} else {
			var temp = field.css('max-height');
			field.css('max-height', '').height();
			maxHeight = field.height();
			field.css('max-height', temp);
		}
		// update duplicator (collapsible) cached values
		field.data('heightMax', maxHeight);
	};

	var renderElementsName = function (field) {
		var sections = field.find(SECTIONS_SEL);
		var fieldChoices = field.find(FIELD_CHOICES_SEL);
		var temp = $();
		var values = [];

		sections.find('option:selected').each(function (index, value) {
			values.push($(value).val());
		});

		fieldChoices.empty();

		console.log('YO');

		$.get(SECTIONS + values.join(',') + '/').done(function (data) {
			if (!!data.sections) {
				var all = createElementInstance({handle: '*'}, 'Include all elements', 'header');
				all.attr('data-value', '*');
				temp = temp.add(all);
				$.each(data.sections, function (index, section) {
					temp = temp.add(createElementInstance(section, section.handle + '.*', 'header'));
					$.each(section.fields, function (index, field) {
						if (field.type === 'entry_relationship') {
							return;
						}
						var li = createElementInstance(section, field.handle);
						li.attr('data-value', section.handle + '.' + field.handle);
						temp = temp.add(li);
					});
					$.each(section.fields, function (index, field) {
						if (field.type !== 'entry_relationship') {
							return;
						}
						if (field.default === true) {
							temp = temp.add(createElementInstance(field, section.handle + '.' + field.handle, 'header'));
						} else {
							var li = createElementInstance(section, field.handle);
							li.attr('data-value', section.handle + '.' + field.handle);
							temp = temp.add(li);
						}
					});
				});
			}

			fieldChoices.append(temp);

			resizeField(field, fieldChoices);

			updateElementsNameVisibility(field);
		});
	};

	var init = function () {
		var body = $('body');
		if (body.is('#blueprints-sections') && (body.attr('data-action') == 'edit' || body.attr('data-action') == 'new')) {
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
				var value = t.attr('data-value') || t.text();
				var patternText = value;
				if (patternText === '*') {
					patternText = '[^.]*';
				}
				patternText = patternText.replace('*', '\\*').replace('.', '\\.');
				var pattern = new RegExp(patternText + '([,][\s]*|$)');
				if (pattern.test(' ' + val)) {
					// append a space to fix beginning of line problems
					val = ' ' + val;
					// remove it
					val = val.replace(pattern, '');
					val = val.replace(/^[\s]*/g, '');
					val = val.replace(/,\s*,/g, ', ');
					val = val.replace(/,\s*$/g, '');
				}
				else {
					// append it
					if (!!val) {
						val += ', ';
					}
					val += value;
				}
				elements.val(val);
				updateElementsNameVisibility(parent);
			}).on('keyup', ELEMENTS_SEL, function () {
				var t = $(this);
				var parent = t.closest(INSTANCES_SEL);
				updateElementsNameVisibility(parent);
			}).find(INSTANCES_SEL).each(function (index, elem) {
				setTimeout(function () {
					renderElementsName($(elem));
				}, 100);
			});
		}
	};

	$(init);

})(jQuery, Symphony);

/**
 * JS for reverse relationship field
 */

/* Settings behavior */
(function ($, S) {

	'use strict';

	var baseurl = function () {
		return S.Context.get('symphony');
	};

	var INSTANCES_SEL = '.field-reverse_relationship.instance';
	var SECTIONS_SEL = ' .reverse_relationship-sections';
	var FIELD_SEL = ' .reverse_relationship-field';

	var SECTIONS = baseurl() + '/extension/entry_relationship_field/sectionsinfos/';

	var updateFieldNameUI = function (fieldChoices, options) {
		fieldChoices.next('div').remove();
		if (options.length < 2) {
			fieldChoices.hide();
			fieldChoices.after($('<div />').text(options.text()));
		} else {
			fieldChoices.show();
		}
	};

	var renderFieldNames = function (field) {
		var sections = field.find(SECTIONS_SEL);
		var fieldChoices = field.find(FIELD_SEL);
		var temp = $();
		var selectedSection = sections.find('option:selected').val();

		fieldChoices.empty().prop('disabled', true);

		$.get(SECTIONS + selectedSection + '/').done(function (data) {
			var temp = $();
			if (!!data.sections) {
				$.each(data.sections, function (index, section) {
					$.each(section.fields, function (index, field) {
						if (field.default && field.type === 'entry_relationship') {
							temp = temp.add($('<option />').attr('value', field.id).text(field.name));
						}
					});
				});
			}
			fieldChoices.append(temp).prop('disabled', false);
			updateFieldNameUI(fieldChoices, temp);
		});
	};

	var init = function () {
		var body = $('body');
		if (body.is('#blueprints-sections') && (body.hasClass('edit') || body.hasClass('new'))) {
			$('#fields-duplicator').on('change', INSTANCES_SEL + SECTIONS_SEL, function () {
				var t = $(this);
				var parent = t.closest(INSTANCES_SEL);
				renderFieldNames(parent);
			});
			$(INSTANCES_SEL + FIELD_SEL).each(function (index, fieldChoices) {
				fieldChoices = $(fieldChoices);
				updateFieldNameUI(fieldChoices, fieldChoices.find('> option'));
			});
			body.on('constructshow.duplicator', INSTANCES_SEL, function () {
				renderFieldNames($(this));
			});
		}
	};

	$(init);

})(jQuery, Symphony);
