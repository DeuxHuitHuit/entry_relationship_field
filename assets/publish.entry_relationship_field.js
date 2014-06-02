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
	
	var body = $();
	
	var removeUI = function () {
		body.addClass('entry_relationship');
		S.Elements.header.detach();
		S.Elements.context.detach();
	};
	
	var appendUI = function () {
		var ctn = $('<div id="entry-relationship-ctn" />');
		body.append(ctn);
		
		
	};
	
	var init = function () {
		body = $('body');
		if (body.attr('id') === 'publish') {
			if (window.location.toString().indexOf('entry-relationship=1') !== -1) {
				if (!!window.top) {
					removeUI();
				}
			} else {
				appendUI();
			}
		}
	};
	
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
		url += '?entry-relationship=1';
	};
	
	var openIframe = function () {
		
	};
	
	var initOne = function (index, t) {
		t  = $(t);
		
		
		var btnCreateClick = function (e) {
			
		};
		
		var btnLinkClick = function (e) {
			
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