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
		S.Elements.contents.find('table th:not([id])').remove();
		S.Elements.contents.find('table td:not([class])').remove();
		S.Elements.contents.find('#drawer-section-associations').remove();
		S.Elements.context.find('#drawer-filtering').remove();
		var btnClose = $('<button />').attr('type', 'button').text('Close').click(function (e) {
			parent.cancel();
			parent.hide();
		});
		$(document).on('keydown', function (e) {
			if (e.which === 27) {
				parent.cancel();
				parent.hide();
				e.preventDefault();
				return false;
			}
		});
		S.Elements.wrapper.find('.actions').filter(function () {
			return body.hasClass('page-index') || $(this).is('ul');
		}).empty().append(btnClose);
		
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
				tr.addClass('selected');
				parent.link(entryId);
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
	
	var resizeIframe = function (iframe) {
		var pad = 7;
		var parent = window.parent !== window;
		var offsetY = !parent ?
			S.Elements.header.outerHeight() + S.Elements.context.outerHeight() + S.Elements.nav.outerHeight() :
			S.Elements.context.outerHeight();
		var scrollY = win.scrollTop();
		offsetY = Math.max(pad, offsetY - scrollY);
		var css = {
			left: pad + 'px',
			top: offsetY + 'px',
			width: 0,
			height: 0
		};
		css.width = (win.width() - pad) + 'px';
		css.height = (win.height() - offsetY) + 'px';
		iframe
			.attr('width', css.width)
			.attr('height', css.height)
		.closest('.iframe')
			.css(css);
			
		return iframe;
	};
	
	var resize = function () {
		resizeIframe(ctn.find('iframe'));
	};
	
	var defineExternals = function () {
		var self = {
			hide: function (reRender) {
				ctn.removeClass('show').find('.iframe>iframe').fadeOut(300, function () {
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
				resizeIframe(iframe);
				ctn.empty().append(ictn);
				
				S.Utilities.requestAnimationFrame(function () {
					ctn.addClass('show');
					ctn.find('.iframe>iframe').delay(300).fadeIn(200);
					
					if (window.parent !== window && window.parent.Symphony.Extensions.EntryRelationship) {
						window.parent.Symphony.Extensions.EntryRelationship.updateOpacity(1);
					}
				});
			},
			link: function (entryId) {
				if (!self.current) {
					console.error('Parent not found.');
					return;
				}
				self.current.link(entryId);
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
			win.resize(resize);
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
	
	var baseurl = function () {
		return S.Context.get('symphony');
	};
	
	var createPublishUrl = function (handle, action) {
		var url = baseurl() + '/publish/' + handle + '/';
		if (!!action) {
			url += action + '/';
		}
		return url;
	};
	
	var CONTENTPAGES = '/extension/entry_relationship_field/';
	var RENDER = baseurl() + CONTENTPAGES +'render/';
	var SAVE = baseurl() + CONTENTPAGES +'save/';
	var DELETE = baseurl() + CONTENTPAGES +'delete/';
	
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
	
	var deleteurl = function (entrytodeleteid, fieldid, entryid) {
		var url = DELETE + entrytodeleteid + '/';
		url += fieldid + '/';
		url += entryid + '/';
		return url;
	};
	
	var openIframe = function (handle, action) {
		S.Extensions.EntryRelationship.show(createPublishUrl(handle, action));
	};
	
	var initOne = function (index, t) {
		t  = $(t);
		var id = t.attr('id');
		var fieldId = t.attr('data-field-id');
		var label = t.attr('data-field-label');
		var debug = t.is('[data-debug]');
		var sections = t.find('select.sections');
		var hidden = t.find('input[type="hidden"]');
		var frame = t.find('.frame');
		var list = frame.find('ul');
		var memento, replaceId;
		var storageKeys = {
			selection: 'symphony.ERF.section-selection-' + id
		};
		var values = function () {
			var val = hidden.val() || '';
			return val.split(',');
		};
		var saveValues = function (val) {
			var oldValue = hidden.val();
			if ($.isArray(val)) {
				val = val.join(',');
			}
			var isDifferent = oldValue !== val;
			if (isDifferent) {
				memento = oldValue;
				hidden.val(val);
				ajaxSave();
			}
			return isDifferent;
		};
		var link = function (entryId) {
			var val = values();
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
		var unlink = function (entryId) {
			var val = values();
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
		var replace = function (searchId, entryId) {
			var val = values();
			var found = false;
			
			for (var x = 0; x < val.length; x++) {
				if (!val[x] || val[x] === searchId) {
					val[x] = entryId;
					found = true;
				}
			}
			val.changed = found;
			return val;
		};
		var isRendering = false;
		var render = function () {
			if (isRendering || !hidden.val()) {
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
					
					list.symphonyOrderable({
						handles: '>header'
					});
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
				var val = !!replaceId ?
					replace(replaceId, entryId) :
					link(entryId);

				if (!!val.changed) {
					saveValues(val);
				}
				replaceId = undefined;
			},
			unlink: function (entryId) {
				var val = unlink(entryId);
				
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
		
		var syncCurrent = function () {
			S.Extensions.EntryRelationship.current = self;
		};
		
		var btnCreateClick = function (e) {
			var t = $(this);
			syncCurrent();
			openIframe(t.attr('data-create') || sections.val(), 'new');
			e.stopPropagation();
		};
		
		var btnLinkClick = function (e) {
			var t = $(this);
			syncCurrent();
			replaceId = undefined;
			openIframe(t.attr('data-link') || sections.val());
			e.stopPropagation();
		};
		
		var btnUnlinkClick = function (e) {
			var t = $(this);
			syncCurrent();
			var li = t.closest('li');
			var id = t.attr('data-unlink') || li.attr('data-entry-id');
			unlinkAndUpdateUI(li, id);
			e.stopPropagation();
		};

		var btnEditClick = function (e) {
			var t = $(this);
			syncCurrent();
			var li = $(this).closest('li');
			var id = t.attr('data-edit') || li.attr('data-entry-id');
			var section = li.attr('data-section');
			openIframe(section, 'edit/' + id);
			e.stopPropagation();
		};

		var btnReplaceClick = function (e) {
			var t = $(this);
			syncCurrent();
			var li = t.closest('li');
			var id = t.attr('data-replace') || li.attr('data-entry-id');
			if (!!unlink(id).changed) {
				unlinkAndUpdateUI(li);
				replaceId = id;
				openIframe(sections.val());
			}
			e.stopPropagation();
		};
		
		var btnDeleteClick = function (e) {
			var t = $(this);
			syncCurrent();
			var li = $(this).closest('li');
			var id = t.attr('data-delete') || li.attr('data-entry-id');
			var section = li.attr('data-section');
			var confirmMsg = t.attr('data-message') || S.Language.get('Are you sure you want to un-link AND delete this entry?');
			if (confirm(confirmMsg)) {
				ajaxDelete(id, function () {
					unlinkAndUpdateUI(li, id);
				});
			}
			e.stopPropagation();
		};
		
		var sectionChanged = function (e) {
			try {
				window.localStorage.setItem(storageKeys.selection, sections.val());
			}
			catch (ex) {
				console.error(ex);
			}
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
				$.post(saveurl(hidden.val(), fieldId, entryId))
				.done(function (data) {
					var hasError = !data || !data.ok || !!data.error;
					var msg = hasError ?
						S.Language.get('Error while saving field “{$title}”. {$error}', {
							title: label,
							error: data.error
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
			$.post(deleteurl(entryToDeleteId, fieldId, entryId) + noAssoc)
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
						error: data.error
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
				else if ($.isFunction(success)) {
					success(entryToDeleteId);
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
		
		if (sections.find('option').length < 2) {
			sections.attr('disabled', 'disabled').addClass('disabled irrelevant');
			sections.after($('<label />').text(sections.text()).addClass('sections'));
		}
		else if (S.Support.localStorage) {
			var lastSelection = window.localStorage.getItem(storageKeys.selection);
			if (!!lastSelection) {
				sections.find('option[value="' + lastSelection + '"]')
					.attr('selected', 'selected');
			}
			sections.on('change', sectionChanged);
		}
		
		frame.on('orderstop.orderable', '*', function () {
			var oldValue = hidden.val();
			var val = [];
			list.find('li').each(function () {
				val.push($(this).attr('data-entry-id'));
			});
			saveValues(val);
		});
		
		// render
		render();
		
		// export
		S.Extensions.EntryRelationship.instances[id] = self;
	};
	
	var init = function () {
		notifier = S.Elements.header.find('div.notifier');
		S.Elements.contents.find('.field.field-entry_relationship').each(initOne);
	};
	
	$(init);
	
})(jQuery, Symphony);
