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
	
	var removeUI = function () {
		var parent = window.parent.Symphony.Extensions.EntryRelationship;
		
		if (window.location.toString().indexOf('/saved/') !== -1 || 
			window.location.toString().indexOf('/created/') !== -1) {
			parent.link(Symphony.Context.get('env').entry_id);
			parent.hide();
			return;
		}
		
		body.addClass('entry_relationship');
		S.Elements.header.detach();
		S.Elements.contents.find('table th:not([id])').remove();
		S.Elements.contents.find('table td:not([class])').remove();
		S.Elements.context.find('#drawer-filtering').remove();
		var btnClose = $('<button />').attr('type', 'button').text('Close').click(function (e) {
			parent.hide();
		});
		S.Elements.wrapper.find('.actions').filter(function () {
			return body.hasClass('page-index') || $(this).is('ul');
		}).empty().append(btnClose);
		var form = Symphony.Elements.contents.find('form');
		//form.attr('action', window.location.toString());
		form.find('td:first-child a').click(function (e) {
			e.preventDefault();
			
			var entryId = $(this).closest('tr').attr('id').replace('id-', '');
			
			parent.link(entryId);
			parent.hide();
			
			return false;
		});
		if (!!parent) {
			$.each(parent.current.values(), function (index, value) {
				form.find('#id-' + value).addClass('inactive');
			});
		}
	};
	
	var appendUI = function () {
		ctn = $('<div id="entry-relationship-ctn" />');
		body.append(ctn);
	};
	
	var resize = function () {
		ctn.find('iframe')
			.attr('width', win.width())
			.attr('height', win.height());
	};
	
	var defineExternals = function () {
		var self = {
			hide: function () {
				ctn.find('.iframe').fadeOut(300, function () {
					$(this).empty().remove();
					html.removeClass('no-scroll');
					ctn.removeClass('show');
				});
			},
			show: function (url) {
				var ictn = $('<div />').attr('class', 'iframe');
				var iframe = $('<iframe />')
					.attr('src', url)
					.attr('width', win.width())
					.attr('height', win.height());
					
				ictn.append(iframe);
				ctn.empty().append(ictn).addClass('show');
				ctn.find('.iframe').delay(500).fadeIn(300);
				html.addClass('no-scroll');
			},
			link: function (entryId) {
				if (!self.current) {
					console.error('Parent not found.');
					return;
				}
				self.current.link(entryId);
			},
			instances: {},
			current: null
		};
		
		// export
		S.Extensions.EntryRelationship = self;
	};
	
	var init = function () {
		body = $('body');
		if (body.attr('id') === 'publish') {
			var er = window.parent && window.parent.Symphony && 
				window.parent.Symphony.Extensions.EntryRelationship;
			if (!!er && !!er.current) {
				// child (iframe)
				removeUI();
			} else {
				// parent
				appendUI();
				win.resize(resize);
			}
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
	
	var renderurl = function (value) {
		return RENDER + value + '/';
	};
	
	var openIframe = function (handle, action) {
		S.Extensions.EntryRelationship.show(createPublishUrl(handle, action));
	};
	
	var initOne = function (index, t) {
		t  = $(t);
		var id = t.attr('id');
		var sections = t.find('select.sections');
		var hidden = t.find('input[type="hidden"]');
		var frame = t.find('.frame');
		var list = frame.find('ul');
		var values = function () {
			var val = hidden.val() || '';
			return val.split(',');
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
					hidden.val(val.join(','));
					render(hidden.val());
				}
			},
			unlink: function (entryId, noRender) {
				var val = values();
				
				for (var x = 0; x < val.length; x++) {
					if (!val[x] || val[x] === entryId) {
						val.splice(x, 1);
					}
				}
				
				hidden.val(val.join(','));
				
				if (noRender !== true) {
					render(hidden.val());
				}
			},
			values: values
		};
		
		var isRendering = false;
		
		var render = function () {
			if (isRendering) {
				return;
			}
			isRendering = true;
			$.get(renderurl(hidden.val())).done(function (data) {
				var li = $(data).find('li');
				var fx = !li.length ? 'addClass' : 'removeClass';
				
				list.empty().append(li);
				frame[fx]('empty');
				
			}).always(function () {
				isRendering = false;
			});
		};
		
		var syncCurrent = function () {
			S.Extensions.EntryRelationship.current = self;
		};
		
		var btnCreateClick = function (e) {
			syncCurrent();
			openIframe(sections.val(), 'new');
		};
		
		var btnLinkClick = function (e) {
			syncCurrent();
			openIframe(sections.val());
		};
		
		t.find('button.create').click(btnCreateClick);
		t.find('button.link').click(btnLinkClick);
		t.on('click', 'a.unlink', function (e) {
			var li = $(this).closest('li');
			var id = li.attr('data-entry-id');
			self.unlink(id, true);
			li.empty().remove();
		});
		t.on('click', 'a.edit', function (e) {
			syncCurrent();
			var li = $(this).closest('li');
			var id = li.attr('data-entry-id');
			var section = li.attr('data-section');
			openIframe(section, 'edit/' + id);
		});
		
		if (sections.find('option').length < 2) {
			sections.hide();
		}
		
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