/*
	Copyright: Deux Huit Huit 2014
	License: MIT, see the LICENCE file
*/

/**
 * JS for entry relationship field
 */

/* Publish page customization */
(function ($, S) {

	'use strict';

	var win = $(window);
	var html = $('html');
	var body = $();
	var ctn = $();
	var loc = window.location.toString();
	var opacity = 0.7;
	var opacityFactor = 0.1;

	var updateOpacity = function(direction) {
		if (direction !== -1) {
			direction = 1;
		}
		opacity = opacity + (direction * opacityFactor);
		var color = ctn.css('background-color');
		color = color.replace(/rgba\((\d+,)\s*(\d+,)\s*(\d+,)\s*[^\)]+\)/i, 'rgba($1 $2 $3 ' + opacity + ')');
		ctn.css('background-color', color);
	};

	var removeUI = function () {
		var parent = window.parent.Symphony.Extensions.EntryRelationship;
		var saved = loc.indexOf('/saved/') !== -1;
		var created = loc.indexOf('/created/') !== -1;

		if (saved || created) {
			if (created) {
				parent.link(S.Context.get().env.entry_id);
			}
			parent.hide(true);
			return;
		}

		var form = S.Elements.contents.find('form');

		if (!!parent) {
			// block already link items
			$.each(parent.current.values(), function (index, value) {
				form.find('#id-' + value).addClass('inactive er-already-linked');
			});
		}

		body.addClass('entry_relationship');

		// remove everything in header, except notifier
		S.Elements.header.children().not('.notifier').remove();
		// Remove everything from the notifier except errors
		S.Elements.header.find('.notifier .notice:not(.error)').trigger('detach.notify');
		form.find('>table th:not([id])').remove();
		form.find('>table td:not([class]):not(:first-child)').each(function () {
			var td = $(this);
			td.find('input').appendTo(td.prev('td'));
			td.remove();
		});
		form.removeAttr('style');
		S.Elements.contents.find('#drawer-section-associations').remove();
		// Close support
		var btnClose = $('<button />').attr('type', 'button').append('<svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="19.9px" height="19.9px" viewBox="0 0 19.9 19.9"><path fill="currentColor" d="M1,19.9c-0.3,0-0.5-0.1-0.7-0.3c-0.4-0.4-0.4-1,0-1.4L18.2,0.3c0.4-0.4,1-0.4,1.4,0s0.4,1,0,1.4L1.7,19.6C1.5,19.8,1.3,19.9,1,19.9z"/><path fill="currentColor" d="M18.9,19.9c-0.3,0-0.5-0.1-0.7-0.3L0.3,1.7c-0.4-0.4-0.4-1,0-1.4s1-0.4,1.4,0l17.9,17.9c0.4,0.4,0.4,1,0,1.4C19.4,19.8,19.2,19.9,18.9,19.9z"/></svg>').append('<span><span>Close</span></span>').click(function (e) {
			parent.cancel();
			parent.hide();
		});
		var btnCloseWrapper = $('<li />').append(btnClose);
		$(document).on('keydown', function (e) {
			if (e.which === 27) {
				parent.cancel();
				parent.hide();
				e.preventDefault();
				return false;
			}
		});

		// Drawers support
		S.Elements.wrapper.find('.actions').filter(function () {
			return body.hasClass('page-index') || $(this).is('ul');
		}).find('li').filter(function () {
			return !$(this).find('a[href^="#drawer-"]').length;
		}).remove().end().end().append(btnCloseWrapper);

		// makes all link open in new window/tab
		form.find('table tr td a').attr('target', '_blank');
		// disable breadcrumbs links
		S.Elements.context.find('#breadcrumbs nav a').attr('href', '#').click(function (e) {
			e.preventDefault();
			return false;
		});
		form.find('table tr td').css('cursor', 'pointer').click(function (e) {
			var t = $(this);
			var target = $(e.target);

			e.preventDefault();

			// click on a link, but not in the first td
			if (!!target.closest('a').length && !target.closest('tr td:first-child').length) {
				// bail out
				return true;
			}

			if (!t.closest('.er-already-linked').length) {
				var tr = t.closest('tr');
				var entryId = tr.attr('id').replace('id-', '');
				var timestamp = tr.find('input#entry-' + entryId).val();
				tr.addClass('selected');
				parent.link(entryId, timestamp);
				parent.hide();
			}

			return false;
		});
		win.focus();
	};

	var appendUI = function () {
		ctn = $('<div id="entry-relationship-ctn" />');
		body.append(ctn);
		ctn.on('click', function () {
			S.Extensions.EntryRelationship.cancel();
			S.Extensions.EntryRelationship.hide();
		});
	};

	var defineExternals = function () {
		var self = {
			hide: function (reRender) {
				ctn.removeClass('show').find('.iframe>iframe').fadeOut(1, function () {
					// raise unload events
					var iw = this.contentWindow;
					var i$ = iw.jQuery;
					if (!!i$) {
						i$(iw).trigger('beforeunload').trigger('unload');
					}
					// remove iframe
					$(this).empty().remove();
					html.removeClass('no-scroll');
					ctn.css('background-color', '');
				});
				if (window.parent !== window && window.parent.Symphony.Extensions.EntryRelationship) {
					window.parent.Symphony.Extensions.EntryRelationship.updateOpacity(-1);
				}
				if (reRender) {
					self.current.render();
				}
				self.current = null;
				win.focus();
			},
			show: function (url) {
				var ictn = $('<div />').attr('class', 'iframe');
				var iframe = $('<iframe />').attr('src', url);

				html.addClass('no-scroll');
				ictn.append(iframe);
				ctn.empty().append(ictn);

				S.Utilities.requestAnimationFrame(function () {
					ctn.addClass('show');

					if (window.parent !== window && window.parent.Symphony.Extensions.EntryRelationship) {
						window.parent.Symphony.Extensions.EntryRelationship.updateOpacity(1);
					}
				});
			},
			link: function (entryId, timestamp) {
				if (!self.current) {
					console.error('Parent not found.');
					return;
				}
				self.current.link(entryId, timestamp);
			},
			cancel: function () {
				if (!self.current) {
					console.error('Parent not found.');
					return;
				}
				self.current.cancel();
			},
			updateOpacity: updateOpacity,
			instances: {},
			current: null
		};

		// export
		S.Extensions.EntryRelationship = self;
	};

	var init = function () {
		body = $('body');
		if (body.is('#publish')) {
			var er = window.parent !== window && window.parent.Symphony &&
				window.parent.Symphony.Extensions.EntryRelationship;
			if (!!er && !!er.current) {
				// child (iframe)
				removeUI();
			}

			// parent (can always be parent)
			appendUI();
		}
	};

	defineExternals();

	$(init);

})(jQuery, Symphony);

/* Field behavior */
(function ($, S) {

	'use strict';

	var doc = $(document);
	var notifier;
	var entryId = S.Context.get().env.entry_id;

	var identity = function (x) {
		return !!x;
	};

	var baseurl = function () {
		return S.Context.get('symphony');
	};

	var createPublishUrl = function (handle, action) {
		var url = baseurl() + '/publish/' + handle + '/';
		if (!!action) {
			url += action + '/';
		}
		url += '?no-lse-redirect';
		return url;
	};

	var CONTENTPAGES = '/extension/entry_relationship_field/';
	var RENDER = baseurl() + CONTENTPAGES +'render/';
	var SAVE = baseurl() + CONTENTPAGES +'save/';
	var DELETE = baseurl() + CONTENTPAGES +'delete/';
	var SEARCH = baseurl() + CONTENTPAGES +'search/';

	var renderurl = function (value, fieldid, debug) {
		var url = RENDER + (value || 'null') + '/';
		url += fieldid + '/';
		if (!!debug) {
			url += '?debug';
		}
		return url;
	};

	var saveurl = function (value, fieldid, entryid) {
		var url = SAVE + (value || 'null') + '/';
		url += fieldid + '/';
		url += entryid + '/';
		return url;
	};

	var searchurl = function (section, entries) {
		var url = SEARCH + section + '/';
		if (entries) {
			url += entries + '/';
		}
		return url;
	};

	var postdata = function (timestamp) {
		return {
			timestamp: timestamp || $('input[name="action[timestamp]"]').val(),
			xsrf: S.Utilities.getXSRF()
		};
	};

	var updateTimestamp = function (t) {
		$('input[name="action[timestamp]"]').val(t || '');
	};

	var deleteurl = function (entrytodeleteid, fieldid, entryid) {
		var url = DELETE + entrytodeleteid + '/';
		url += fieldid + '/';
		url += entryid + '/';
		return url;
	};

	var gotourl = function (section, entry_id) {
		return baseurl() + '/publish/' + section + '/edit/' + entry_id + '/';
	};

	var openIframe = function (handle, action) {
		S.Extensions.EntryRelationship.show(createPublishUrl(handle, action));
	};

	var syncCurrent = function (self) {
		S.Extensions.EntryRelationship.current = self;
	};

	var link = function (val, entryId) {
		var found = false;

		for (var x = 0; x < val.length; x++) {
			if (!val[x]) {
				val.splice(x, 1);
			} else if (val[x] === entryId) {
				found = true;
				break;
			}
		}
		if (!found) {
			val.push(entryId);
		}
		val.changed = !found;
		return val;
	};
	var unlink = function (val, entryId) {
		var found = false;

		for (var x = 0; x < val.length; x++) {
			if (!val[x] || val[x] === entryId) {
				val.splice(x, 1);
				found = true;
			}
		}
		val.changed = found;
		return val;
	};
	var replace = function (val, entryId, replaceId) {
		var found = false;

		for (var x = 0; x < val.length; x++) {
			if (!val[x] || val[x] === replaceId) {
				val[x] = entryId;
				found = true;
			}
		}
		val.changed = found;
		return val;
	};
	var insert = function (val, insertPosition, entryId) {
		var found = false;
		for (var x = 0; x < val.length; x++) {
			if (!val[x] || val[x] === entryId) {
				found = true;
			}
		}
		if (!found) {
			if (insertPosition === undefined || insertPosition >= val.length) {
				val.push(entryId);
			}
			else {
				val.splice(insertPosition + 1, 0, entryId);
			}
		}
		val.changed = !found;
		return val;
	};

	var initOneEntryField = function (index, t) {
		t = $(t);
		var id = t.attr('id');
		var fieldId = t.attr('data-field-id');
		var label = t.attr('data-field-label');
		var debug = t.is('[data-debug]');
		var required = t.is('[data-required="yes"]');
		var minimum = parseInt(t.attr('data-min'), 10) || 0;
		var maximum = parseInt(t.attr('data-max'), 10) || 0;
		var sections = t.find('select.sections');
		var hidden = t.find('input[type="hidden"]');
		var frame = t.find('.frame');
		var list = frame.find('ul');
		var memento;
		var replaceId;
		var insertPosition;
		var storageKeys = {
			selection: 'symphony.ERF.section-selection-' + id,
			collapsible: 'symphony.collapsible.ERF.' + id + '.collasped'
		};
		var values = function () {
			var val = hidden.val() || '';
			return val.split(',').filter(identity);
		};
		var saveValues = function (val) {
			var oldValue = hidden.val();
			if ($.isArray(val)) {
				val = val.join(',');
			}
			var count = !val ? 0 : val.split(',').length;
			var isDifferent = oldValue !== val;
			if (isDifferent) {
				memento = oldValue;
				hidden.val(val);
				// Only save when one of those criteria is true
				// 1. The field is required and the minimum is reached
				// 2. The field is optional and has the number of items is either 0 or >= minimum
				if ((!!required && count >= minimum) ||
					(!required && (count >= minimum || count === 0))) {
					ajaxSave();
				}
				else {
					render();
				}
			}
			return isDifferent;
		};

		var updateActionBar = function (li) {
			var createLinkBtn = t.find('[data-create],[data-link],.sections-selection, [data-interactive].search');
			var maxReached = !!maximum && li.length >= maximum;
			createLinkBtn.add(sections)[maxReached ? 'hide' : 'show']();
		};

		var updateSearchUrl = function () {
			t.find('[data-search]').attr('data-url', searchurl(sections.val(), hidden.val()));
		};

		var isRendering = false;
		var render = function () {
			if (isRendering) {
				return;
			}
			if (!hidden.val()) {
				updateActionBar($());
				return;
			}
			isRendering = true;
			$.get(renderurl(hidden.val(), fieldId, debug)).done(function (data) {
				data = $(data);
				var error = data.find('error');
				var li = data.find('li');
				var fx = !li.length ? 'addClass' : 'removeClass';

				if (!!error.length) {
					list.empty().append(
						$('<li />').text(
							S.Language.get('Error while rendering field “{$title}”: {$error}', {
								title: label,
								error: error.text()
							})
						).addClass('error invalid')
					);
					frame.addClass('empty');
				}
				else {
					list.empty().append(li);
					frame[fx]('empty');

					if (!list.hasClass('orderable') && !!list.find('[data-orderable-handle]').length) {
						list.symphonyOrderable({
							items: 'li:has([data-orderable-handle])',
							handles: '[data-orderable-handle]',
							ignore: '.ignore-orderable, .ignore',
						});
					}
					if (list.is('[data-collapsible]') && !!list.find('[data-collapsible-handle]').length) {
						if (!list.hasClass('collapsible')) {
							list.symphonyCollapsible({
								items: 'li:has([data-collapsible-content]):has([data-collapsible-handle])',
								handles: '[data-collapsible-handle]',
								content: '[data-collapsible-content]',
								ignore: '.ignore-collapsible, .ignore',
								save_state: false
							}).on('collapsestop.collapsible expandstop.collapsible', collapsingChanged);
						}
						else {
							list.find('li:has([data-collapsible-content]):has([data-collapsible-handle])')
								.addClass('instance')
								.trigger('updatesize.collapsible')
								.trigger('setsize.collapsible');
							list.trigger('restore.collapsible');
						}
						restoreCollapsing();
					}
					updateActionBar(li);
					updateSearchUrl();
				}
			}).error(function (data) {
				notifier.trigger('attach.notify', [
					S.Language.get('Error while rendering field “{$title}”: {$error}', {
						title: label,
						error: data.statusText || ''
					}),
					'error'
				]);
			}).always(function () {
				isRendering = false;
			});
		};
		var self = {
			link: function (entryId) {
				var val;
				if (!!replaceId) {
					val = replace(values(), entryId, replaceId);
				}
				else if (insertPosition !== undefined) {
					val = insert(values(), insertPosition, entryId);
				}
				else {
					val = link(values(), entryId);
				}

				if (!!val.changed) {
					saveValues(val);
				}
				replaceId = undefined;
				insertPosition = undefined;
			},
			unlink: function (entryId) {
				var val = unlink(values(), entryId);

				if (!!val.changed) {
					saveValues(val);
				}
			},
			values: values,
			render: render,
			cancel: function () {
				if (!!replaceId) {
					render();
					replaceId = undefined;
				}
				insertPosition = undefined;
			}
		};

		var unlinkAndUpdateUI = function (li, id) {
			if (!!id) {
				self.unlink(id);
			}
			li.empty().remove();
			if (!list.children().length) {
				frame.addClass('empty');
			}
		}

		var getInsertPosition = function (t) {
			// Has data insert attribute but no value in it
			if (!!t.filter('[data-insert]').length && !t.attr('data-insert')) {
				return t.closest('li').index();
			// data-insert is -1
			} else if (t.attr('data-insert') === '-1') {
				return t.closest('li').index() - 1;
			// data-insert has a value
			} else if (!!t.attr('data-insert')) {
				return Math.max(0, parseInt(t.attr('data-insert'), 10) || 0);
			}
			return undefined;
		};

		var btnCreateClick = function (e) {
			var t = $(this);
			syncCurrent(self);
			replaceId = undefined;
			insertPosition = getInsertPosition(t);
			openIframe(t.attr('data-create') || sections.val(), 'new');
			e.stopPropagation();
			e.preventDefault();
		};

		var btnLinkClick = function (e) {
			var t = $(this);
			syncCurrent(self);
			replaceId = undefined;
			insertPosition = getInsertPosition(t);
			openIframe(t.attr('data-link') || sections.val());
			e.stopPropagation();
			e.preventDefault();
		};

		var btnUnlinkClick = function (e) {
			var t = $(this);
			syncCurrent(self);
			var li = t.closest('li');
			var id = t.attr('data-unlink') || li.attr('data-entry-id');
			unlinkAndUpdateUI(li, id);
			e.stopPropagation();
			e.preventDefault();
		};

		var btnEditClick = function (e) {
			var t = $(this);
			syncCurrent(self);
			var li = t.closest('li');
			var id = t.attr('data-edit') || li.attr('data-entry-id');
			var section = t.attr('data-section') || li.attr('data-section');
			replaceId = undefined;
			insertPosition = undefined;
			openIframe(section, 'edit/' + id);
			e.stopPropagation();
			e.preventDefault();
		};

		var btnReplaceClick = function (e) {
			var t = $(this);
			syncCurrent(self);
			var li = t.closest('li');
			var id = t.attr('data-replace') || li.attr('data-entry-id');
			insertPosition = undefined;
			if (!!unlink(values(), id).changed) {
				unlinkAndUpdateUI(li);
				replaceId = id;
				openIframe(sections.val());
			}
			e.stopPropagation();
			e.preventDefault();
		};

		var btnDeleteClick = function (e) {
			var t = $(this);
			syncCurrent(self);
			var li = $(this).closest('li');
			var id = t.attr('data-delete') || li.attr('data-entry-id');
			var section = t.attr('data-section') || li.attr('data-section');
			var confirmMsg = t.attr('data-message') || S.Language.get('Are you sure you want to un-link AND delete this entry?');
			if (confirm(confirmMsg)) {
				ajaxDelete(id, function () {
					unlinkAndUpdateUI(li, id);
				});
			}
			e.stopPropagation();
			e.preventDefault();
		};

		var searchChange = function (e) {
			syncCurrent(self);
			var input = t.find('[data-search]');
			var id = input.attr('data-value');
			input.val('');
			if (!!id) {
				replaceId = undefined;
				insertPosition = undefined;
				self.link(id);
			}
		};

		var saveToStorage = function (key, value) {
			if (!S.Support.localStorage) {
				return;
			}
			try {
				window.localStorage.setItem(key, value);
			}
			catch (ex) {
				console.error(ex);
			}
		};

		var sectionChanged = function (e) {
			saveToStorage(storageKeys.selection, sections.val());
		};

		var collapsingChanged = function (e) {
			var collapsed = [];
			list.filter('.orderable').find('.instance.collapsed').each(function (index, elem) {
				collapsed.push($(elem).attr('data-entry-id'));
			});
			saveToStorage(storageKeys.collapsible, collapsed.join(','));
		};

		var restoreCollapsing = function (e) {
			if (!S.Support.localStorage) {
				return;
			}
			var ids = (window.localStorage.getItem(storageKeys.collapsible) || '').split(',');
			$.each(ids, function (index, id) {
				list.find('.instance[data-entry-id="' + id + '"]').trigger('collapse.collapsible', [0]);
			});
		};

		var ajaxSaveTimeout = 0;
		var ajaxSave = function () {
			clearTimeout(ajaxSaveTimeout);
			ajaxSaveTimeout = setTimeout(function ajaxSaveTimer() {
				if (!entryId) {
					// entry is being created... we can't save right now...
					render();
					return;
				}
				$.post(saveurl(hidden.val(), fieldId, entryId), postdata())
				.done(function (data) {
					var hasError = !data || !data.ok || !!data.error;
					var msg = hasError ?
						S.Language.get('Error while saving field “{$title}”. {$error}', {
							title: label,
							error: data.error || ''
						}) :
						S.Language.get('The field “{$title}” has been saved', {
							title: label
						});
					notifier.trigger('attach.notify', [
						msg,
						hasError ? 'error' : 'success'
					]);
					if (hasError) {
						// restore old value
						hidden.val(memento);
					} else {
						updateTimestamp(data.timestamp);
					}
				}).error(function (data) {
					notifier.trigger('attach.notify', [
						S.Language.get('Server error, field “{$title}”. {$error}', {
							title: label,
							error: typeof data.error === 'string' ? data.error : data.statusText
						}),
						'error'
					]);
				})
				.always(function () {
					render();
				});
			}, 200);
		};

		var ajaxDelete = function (entryToDeleteId, success, noAssoc) {
			noAssoc = noAssoc === true ? '?no-assoc' : '';
			$.post(deleteurl(entryToDeleteId, fieldId, entryId) + noAssoc, postdata())
			.done(function (data) {
				var hasError = !data || !data.ok || !!data.error;
				var hasAssoc = hasError && data.assoc;
				if (hasAssoc) {
					if (confirm(data.error)) {
						ajaxDelete(entryToDeleteId, success, true);
					}
					return;
				}
				var msg = hasError ?
					S.Language.get('Error while deleting entry “{$id}”. {$error}', {
						id: entryToDeleteId,
						error: data.error || ''
					}) :
					S.Language.get('The entry “{$id}” has been deleted', {
						id: entryToDeleteId,
					});
				notifier.trigger('attach.notify', [
					msg,
					hasError ? 'error' : 'success'
				]);
				if (hasError) {
					// restore old value
					hidden.val(memento);
				}
				else {
					if (!!data.timestamp) {
						updateTimestamp(data.timestamp);
					}
					if ($.isFunction(success)) {
						success(entryToDeleteId);
					}
				}
			}).error(function (data) {
				notifier.trigger('attach.notify', [
					S.Language.get('Server error, field “{$title}”. {$error}', {
						title: label,
						error: typeof data.error === 'string' ? data.error : data.statusText
					}),
					'error'
				]);
			});
		};

		t.on('click', '[data-create]', btnCreateClick);
		t.on('click', '[data-link]', btnLinkClick);
		t.on('click', '[data-unlink]', btnUnlinkClick);
		t.on('click', '[data-edit]', btnEditClick);
		t.on('click', '[data-replace]', btnReplaceClick);
		t.on('click', '[data-delete]', btnDeleteClick);
		updateSearchUrl();
		S.Interface.Suggestions.init(t, '[data-search]', {
			editSuggestion: function (suggestion, index, data, result) {
				var value = data.value.split(':');
				var id = value.shift();
				suggestion.attr('data-value', id).text(value.join(':'));
			}
		});
		t.on('mousedown.suggestions', '.suggestions li', searchChange);
		t.on('keydown.suggestions', '[data-search]', function (e) {
			if (e.which === 13) {
				searchChange(e);
			}
		});

		if (sections.find('option').length < 2) {
			sections.attr('disabled', 'disabled').addClass('disabled irrelevant');
			sections.after($('<label />').text(sections.text()).addClass('sections sections-selection'));
		}
		else {
			if (S.Support.localStorage) {
				var lastSelection = window.localStorage.getItem(storageKeys.selection);
				if (!!lastSelection) {
					sections.find('option[value="' + lastSelection + '"]')
						.attr('selected', 'selected');
					updateSearchUrl();
				}
				sections.on('change', sectionChanged);
			}
			sections.on('change', updateSearchUrl);
		}

		frame.on('orderstop.orderable', '*', function () {
			var oldValue = hidden.val();
			var val = [];
			list.find('li[data-entry-id]').each(function () {
				var id = $(this).attr('data-entry-id');
				if (!!id) {
					val.push(id);
				}
			});
			saveValues(val);
		});

		// render
		render();

		// export
		S.Extensions.EntryRelationship.instances[id] = self;
	};

	var initOneReverseField = function (index, t) {
		t = $(t);
		var id = t.attr('id');
		var fieldId = t.attr('data-field-id');
		var debug = t.is('[data-debug]');
		var entries = t.attr('data-entries') || '';
		var section = t.attr('data-linked-section');
		var linkedFieldId = t.attr('data-linked-field-id');
		var label = t.attr('data-field-label');
		var frame = t.find('.frame');
		var list = frame.find('ul');
		var isRendering = false;
		var dirty = false;
		var render = function () {
			if (isRendering || !entries || !entries.length) {
				return;
			}
			isRendering = true;
			$.get(renderurl(entries, fieldId, debug)).done(function (data) {
				data = $(data);
				var error = data.find('error');
				var li = data.find('li');
				var fx = !li.length ? 'addClass' : 'removeClass';

				if (!!error.length) {
					list.empty().append(
						$('<li />').text(
							S.Language.get('Error while rendering field “{$title}”: {$error}', {
								title: label,
								error: error.text()
							})
						).addClass('error invalid')
					);
					frame.addClass('empty');
				}
				else {
					list.empty().append(li);
					frame[fx]('empty');
				}
			}).error(function (data) {
				notifier.trigger('attach.notify', [
					S.Language.get('Error while rendering field “{$title}”: {$error}', {
						title: label,
						error: data.statusText || ''
					}),
					'error'
				]);
			}).always(function () {
				isRendering = false;
			});
		};

		var values = function () {
			if ($.isArray(entries)) {
				return entries;
			}
			return entries.split(',').filter(identity);
		};
		var memento = [].concat(values());

		var self = {
			link: function (entryId, timestamp) {
				entries = link(values(), entryId);
				ajaxSave('＋', entryId, timestamp);
			},
			unlink: function (entryId, timestamp) {
				entries = unlink(values(), entryId);
				ajaxSave('−', entryId, timestamp);
			},
			values: values,
			render: render,
			cancel: function () {
				if (dirty) {
					render();
				}
				dirty = false;
			}
		};

		var unlinkAndUpdateUI = function (li, id, timestamp) {
			if (!!id) {
				self.unlink(id, timestamp);
			}
			li.empty().remove();
			if (!list.children().length) {
				frame.addClass('empty');
			}
		};

		var btnGotoClick = function (e) {
			var t = $(this);
			window.location = gotourl(section, t.attr('data-goto'));
			e.stopPropagation();
			e.preventDefault();
		};

		var btnUnlinkClick = function (e) {
			var t = $(this);
			syncCurrent(self);
			var li = t.closest('li');
			var id = t.attr('data-unlink') || li.attr('data-entry-id');
			var timestamp = li.attr('data-timestamp');
			unlinkAndUpdateUI(li, id, timestamp);
			dirty = true;
			e.stopPropagation();
			e.preventDefault();
		};

		var btnAddClick = function (e) {
			var t = $(this);
			syncCurrent(self);
			openIframe(t.attr('data-add'));
			e.stopPropagation();
			e.preventDefault();
		};

		var ajaxSaveTimeout = 0;
		var ajaxSave = function (op, entryId, timestamp) {
			clearTimeout(ajaxSaveTimeout);
			ajaxSaveTimeout = setTimeout(function ajaxSaveTimer() {
				var eId = Symphony.Context.get('env').entry_id;
				if (!eId) {
					return;
				}
				$.post(saveurl(encodeURIComponent(op) + eId, linkedFieldId, entryId), postdata(timestamp))
				.done(function (data) {
					var hasError = !data || !data.ok || !!data.error;
					var msg = hasError ?
						S.Language.get('Error while saving field “{$title}”. {$error}', {
							title: label,
							error: data.error || ''
						}) :
						S.Language.get('The field “{$title}” has been saved', {
							title: label
						});
					notifier.trigger('attach.notify', [
						msg,
						hasError ? 'error' : 'success'
					]);
					if (hasError) {
						entries = memento;
					} else {
						memento = [].concat(values());
					}
				}).error(function (data) {
					notifier.trigger('attach.notify', [
						S.Language.get('Server error, field “{$title}”. {$error}', {
							title: label,
							error: typeof data.error === 'string' ? data.error : data.statusText
						}),
						'error'
					]);
					memento = entries;
				})
				.always(function () {
					render();
				});
			}, 200);
		};

		t.on('click', '[data-goto]', btnGotoClick);
		t.on('click', '[data-unlink]', btnUnlinkClick);
		t.on('click', '[data-add]', btnAddClick);

		// render
		render();

		// export
		S.Extensions.EntryRelationship.instances[id] = self;
	};

	var init = function () {
		notifier = S.Elements.header.find('div.notifier');
		S.Elements.contents.find('.field.field-entry_relationship').each(initOneEntryField);
		S.Elements.contents.find('.field.field-reverse_relationship').each(initOneReverseField);
	};

	$(init);

})(jQuery, Symphony);
