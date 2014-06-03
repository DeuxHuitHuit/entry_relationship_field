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
	var QUERY_PARAM = '?entry-relationship=1';
	
	var removeUI = function () {
		body.addClass('entry_relationship');
		S.Elements.header.detach();
		S.Elements.contents.find('table th:not([id])').remove();
		S.Elements.contents.find('table td:not([class])').remove();
		var btnClose = $('<button />').attr('type', 'button').text('Close').click(function (e) {
			window.parent.Symphony.Extensions.EntryRelationship.hide();
		});
		S.Elements.wrapper.find('.actions').empty().filter(function () {
			return body.hasClass('page-index') || $(this).is('ul');
		}).append(btnClose);
		var form = Symphony.Elements.contents.find('form');
		form.attr('action', form.attr('action') + QUERY_PARAM);
		form.find('td:first-child a').click(function (e) {
			e.preventDefault();
			
			var entryId = $(this).closest('tr').attr('id');
			
			window.parent.Symphony.Extensions.EntryRelationship.link(entryId);
			
			return false;
		});
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
		S.Extensions.EntryRelationship = {
			QUERY_PARAM: QUERY_PARAM,
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
				
			}
		};
	};
	
	var init = function () {
		body = $('body');
		if (body.attr('id') === 'publish') {
			if (window.location.toString().indexOf('entry-relationship=1') !== -1) {
				// child (iframe)
				if (!!window.top) {
					removeUI();
				}
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
	
	var baseurl = function () {
		return S.Context.get('symphony');
	};
	
	var createPublishUrl = function (handle, action) {
		var url = baseurl() + '/publish/' + handle + '/';
		if (!!action) {
			url += action + '/';
		}
		url += S.Extensions.EntryRelationship.QUERY_PARAM;
		return url;
	};
	
	var openIframe = function (handle, action) {
		S.Extensions.EntryRelationship.show(createPublishUrl(handle, action));
	};
	
	var initOne = function (index, t) {
		t  = $(t);
		var sections = t.find('select.sections');
		var hidden = t.find('input[type="hidden"]');
		
		var btnCreateClick = function (e) {
			openIframe(sections.val(), 'new');
		};
		
		var btnLinkClick = function (e) {
			openIframe(sections.val());
		};
		
		t.find('button.create').click(btnCreateClick);
		t.find('button.link').click(btnLinkClick);
	};
	
	var init = function () {
		//doc.on('*.entry-relationship', processEvent);
		S.Elements.contents.find('.field.field-entry_relationship').each(initOne);
	};
	
	$(init);
	
})(jQuery, Symphony);