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
	var offsetY = 0;
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
		var created = loc.indexOf('/created/') !== -1
		
		if (saved || created) {
			if (created) {
				parent.link(Symphony.Context.get('env').entry_id);
			}
			parent.hide(true);
			return;
		}
		
		var form = Symphony.Elements.contents.find('form');
		
		if (!!parent) {
			// block already link items
			$.each(parent.current.values(), function (index, value) {
				form.find('#id-' + value).addClass('inactive er-already-linked');
			});
		}
		
		body.addClass('entry_relationship');
		// has errors
		if (!!S.Elements.header.find('.notifier .notice.error').length) {
			S.Elements.header.children().not('.notifier').remove();
		} else {
			S.Elements.header.detach();
		}
		S.Elements.contents.find('table th:not([id])').remove();
		S.Elements.contents.find('table td:not([class])').remove();
		S.Elements.contents.find('#drawer-section-associations').remove();
		S.Elements.context.find('#drawer-filtering').remove();
		var btnClose = $('<button />').attr('type', 'button').text('Close').click(function (e) {
			parent.hide();
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
				var entryId = t.closest('tr').attr('id').replace('id-', '');
				t.closest('tr').addClass('selected');
				parent.link(entryId);
				parent.hide();
			}
			
			return false;
		});
	};
	
	var appendUI = function () {
		ctn = $('<div id="entry-relationship-ctn" />');
		body.append(ctn);
		ctn.on('click', function () {
			S.Extensions.EntryRelationship.hide();
		});
	};
	
	var resizeIframe = function (iframe) {
		var parent = window.parent !== window;
		offsetY = !parent ? 
			S.Elements.header.outerHeight() + S.Elements.context.outerHeight() + S.Elements.nav.outerHeight() :
			S.Elements.context.outerHeight();
		var css = {
			left: '7px',
			top: offsetY + 'px',
			width: 0,
			height: 0
		};
		css.width = (win.width() - parseInt(css.left)) + 'px';
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
			},
			show: function (url) {
				var ictn = $('<div />').attr('class', 'iframe');
				var iframe = $('<iframe />').attr('src', url);
				
				html.addClass('no-scroll');
				ictn.append(iframe);
				resizeIframe(iframe);
				ctn.empty().append(ictn);
				
				Symphony.Utilities.requestAnimationFrame(function () {
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
	
	var instances = {};
	
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
	
	var renderurl = function (value, fieldid, debug) {
		var url = RENDER + value + '/';
		if (!!fieldid) {
			url += fieldid + '/';
		}
		if (!!debug) {
			url += '?debug';
		}
		return url;
	};
	
	var saveurl = function (value, fieldid, entryid) {
		var url = SAVE + value + '/';
		if (!!fieldid) {
			url += fieldid + '/';
		}
		if (!!entryid) {
			url += entryid + '/';
		}
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
				hidden.val(val);
				ajaxSave();
			}
			return isDifferent;
		};
		var isRendering = false;
		var render = function () {
			if (isRendering || !hidden.val()) {
				return;
			}
			isRendering = true;
			$.get(renderurl(hidden.val(), fieldId, debug)).done(function (data) {
				var li = $(data).find('li');
				var fx = !li.length ? 'addClass' : 'removeClass';
				
				list.empty().append(li);
				frame[fx]('empty');
				
				list.symphonyOrderable({
					handles: '>header'
				});
				
			}).always(function () {
				isRendering = false;
			});
		};
		var self = {
			link: function (entryId) {
				var val = values();
				var found = false;
				
				for (var x = 0; x < val.length; x++) {
					if (!val[x]) {
						val.splice(x, 1);
					} if (val[x] === entryId) {
						found = true;
						break;
					}
				}
				
				if (!found) {
					val.push(entryId);
					if (saveValues(val)) {
						render();
					}
				}
			},
			unlink: function (entryId, noRender) {
				var val = values();
				
				for (var x = 0; x < val.length; x++) {
					if (!val[x] || val[x] === entryId) {
						val.splice(x, 1);
					}
				}
				
				if (saveValues(val) && noRender !== true) {
					render();
				}
			},
			values: values,
			render: render
		};
		
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
			openIframe(t.attr('data-link') || sections.val());
			e.stopPropagation();
		};
		
		var ajaxSaveTimeout = 0;
		var ajaxSave = function () {
			var notifier = Symphony.Elements.header.find('div.notifier');
			clearTimeout(ajaxSaveTimeout);
			ajaxSaveTimeout = setTimeout(function ajaxSaveTimer() {
				$.post(saveurl(hidden.val(), fieldId, Symphony.Context.get().env.entry_id))
				.done(function (data) {
					notifier.trigger('attach.notify', [
						Symphony.Language.get('The field “{$title}” has been saved', {
							title: label
						}),
						'success'
					]);
				}).error(function (data) {
					notifier.trigger('attach.notify', [
						Symphony.Language.get('Error while save field “{$title}”. {$error}', {
							title: label,
							error: data.error
						}),
						'error'
					]);
				});
			}, 200);
		};
		
		t.on('click', '[data-create]', btnCreateClick);
		t.on('click', '[data-link]', btnLinkClick);
		t.on('click', '[data-unlink]', function (e) {
			var t = $(this);
			var li = $(this).closest('li');
			var id = t.attr('data-unlink') || li.attr('data-entry-id');
			self.unlink(id, true);
			li.empty().remove();
			e.stopPropagation();
		});
		t.on('click', '[data-edit]', function (e) {
			var t = $(this);
			syncCurrent();
			var li = $(this).closest('li');
			var id = t.attr('data-edit') || li.attr('data-entry-id');
			var section = li.attr('data-section');
			openIframe(section, 'edit/' + id);
			e.stopPropagation();
		});
		
		if (sections.find('option').length < 2) {
			sections.attr('disabled', 'disabled').addClass('disabled irrelevant');
			sections.after($('<label />').text(sections.text()).addClass('sections'));
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
		//doc.on('*.entry-relationship', processEvent);
		S.Elements.contents.find('.field.field-entry_relationship').each(initOne);
	};
	
	$(init);
	
})(jQuery, Symphony);
