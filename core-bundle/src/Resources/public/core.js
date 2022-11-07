/*!
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

/**
 * Provide methods to handle Ajax requests.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
var AjaxRequest =
{
	/**
	 * The theme path
	 * @member {string}
	 */
	themePath: Contao.script_url + 'system/themes/' + Contao.theme + '/',

	/**
	 * Toggle the navigation menu
	 *
	 * @param {object} el  The DOM element
	 * @param {string} id  The ID of the menu item
	 * @param {string} url The Ajax URL
	 *
	 * @returns {boolean}
	 */
	toggleNavigation: function(el, id, url) {
		var item = $(id),
			parent = $(el).getParent('li');

		if (item) {
			if (parent.hasClass('collapsed')) {
				parent.removeClass('collapsed');
				$(el).setAttribute('aria-expanded', 'true');
				$(el).store('tip:title', Contao.lang.collapse);
				new Request.Contao({ url: url }).post({'action':'toggleNavigation', 'id':id, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
			} else {
				parent.addClass('collapsed');
				$(el).setAttribute('aria-expanded', 'false');
				$(el).store('tip:title', Contao.lang.expand);
				new Request.Contao({ url: url }).post({'action':'toggleNavigation', 'id':id, 'state':0, 'REQUEST_TOKEN':Contao.request_token});
			}
			return false;
		}

		return false;
	},

	/**
	 * Toggle the site structure tree
	 *
	 * @param {object} el    The DOM element
	 * @param {string} id    The ID of the target element
	 * @param {int}    level The indentation level
	 * @param {int}    mode  The insert mode
	 *
	 * @returns {boolean}
	 */
	toggleStructure: function (el, id, level, mode) {
		el.blur();

		var item = $(id),
			image = $(el).getFirst('img');

		if (item) {
			if (item.getStyle('display') == 'none') {
				item.setStyle('display', null);
				image.src = AjaxRequest.themePath + 'icons/folMinus.svg';
				$(el).store('tip:title', Contao.lang.collapse);
				new Request.Contao({field:el}).post({'action':'toggleStructure', 'id':id, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
			} else {
				item.setStyle('display', 'none');
				image.src = AjaxRequest.themePath + 'icons/folPlus.svg';
				$(el).store('tip:title', Contao.lang.expand);
				new Request.Contao({field:el}).post({'action':'toggleStructure', 'id':id, 'state':0, 'REQUEST_TOKEN':Contao.request_token});
			}
			return false;
		}

		new Request.Contao({
			field: el,
			evalScripts: true,
			onRequest: AjaxRequest.displayBox(Contao.lang.loading + ' …'),
			onSuccess: function(txt) {
				var li = new Element('li', {
					'id': id,
					'class': 'parent',
					'styles': {
						'display': 'inline'
					}
				});

				new Element('ul', {
					'class': 'level_' + level,
					'html': txt
				}).inject(li, 'bottom');

				if (mode == 5) {
					li.inject($(el).getParent('li'), 'after');
				} else {
					var folder = false,
						parent = $(el).getParent('li'),
						next;

					while (typeOf(parent) == 'element' && (next = parent.getNext('li'))) {
						parent = next;
						if (parent.hasClass('tl_folder')) {
							folder = true;
							break;
						}
					}

					if (folder) {
						li.inject(parent, 'before');
					} else {
						li.inject(parent, 'after');
					}
				}

				// Update the referer ID
				li.getElements('a').each(function(el) {
					el.href = el.href.replace(/&ref=[a-f0-9]+/, '&ref=' + Contao.referer_id);
				});

				$(el).store('tip:title', Contao.lang.collapse);
				image.src = AjaxRequest.themePath + 'icons/folMinus.svg';
				window.fireEvent('structure');
				AjaxRequest.hideBox();

				// HOOK
				window.fireEvent('ajax_change');
   			}
		}).post({'action':'loadStructure', 'id':id, 'level':level, 'state':1, 'REQUEST_TOKEN':Contao.request_token});

		return false;
	},

	/**
	 * Toggle the file manager tree
	 *
	 * @param {object} el     The DOM element
	 * @param {string} id     The ID of the target element
	 * @param {string} folder The folder's path
	 * @param {int}    level  The indentation level
	 *
	 * @returns {boolean}
	 */
	toggleFileManager: function (el, id, folder, level) {
		el.blur();

		var item = $(id),
			image = $(el).getFirst('img');

		if (item) {
			if (item.getStyle('display') == 'none') {
				item.setStyle('display', null);
				image.src = AjaxRequest.themePath + 'icons/folMinus.svg';
				$(el).store('tip:title', Contao.lang.collapse);
				new Request.Contao({field:el}).post({'action':'toggleFileManager', 'id':id, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
			} else {
				item.setStyle('display', 'none');
				image.src = AjaxRequest.themePath + 'icons/folPlus.svg';
				$(el).store('tip:title', Contao.lang.expand);
				new Request.Contao({field:el}).post({'action':'toggleFileManager', 'id':id, 'state':0, 'REQUEST_TOKEN':Contao.request_token});
			}
			return false;
		}

		new Request.Contao({
			field: el,
			evalScripts: true,
			onRequest: AjaxRequest.displayBox(Contao.lang.loading + ' …'),
			onSuccess: function(txt) {
				var li = new Element('li', {
					'id': id,
					'class': 'parent',
					'styles': {
						'display': 'inline'
					}
				});

				new Element('ul', {
					'class': 'level_' + level,
					'html': txt
				}).inject(li, 'bottom');

				li.inject($(el).getParent('li'), 'after');

				// Update the referer ID
				li.getElements('a').each(function(el) {
					el.href = el.href.replace(/&ref=[a-f0-9]+/, '&ref=' + Contao.referer_id);
				});

				$(el).store('tip:title', Contao.lang.collapse);
				image.src = AjaxRequest.themePath + 'icons/folMinus.svg';
				AjaxRequest.hideBox();

				// HOOK
				window.fireEvent('ajax_change');
   			}
		}).post({'action':'loadFileManager', 'id':id, 'level':level, 'folder':folder, 'state':1, 'REQUEST_TOKEN':Contao.request_token});

		return false;
	},

	/**
	 * Toggle the page tree input field
	 *
	 * @param {object} el    The DOM element
	 * @param {string} id    The ID of the target element
	 * @param {string} field The field name
	 * @param {string} name  The Ajax field name
	 * @param {int}    level The indentation level
	 *
	 * @returns {boolean}
	 */
	togglePagetree: function (el, id, field, name, level) {
		el.blur();
		Backend.getScrollOffset();

		var item = $(id),
			image = $(el).getFirst('img');

		if (item) {
			if (item.getStyle('display') == 'none') {
				item.setStyle('display', null);
				image.src = AjaxRequest.themePath + 'icons/folMinus.svg';
				$(el).store('tip:title', Contao.lang.collapse);
				new Request.Contao({field:el}).post({'action':'togglePagetree', 'id':id, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
			} else {
				item.setStyle('display', 'none');
				image.src = AjaxRequest.themePath + 'icons/folPlus.svg';
				$(el).store('tip:title', Contao.lang.expand);
				new Request.Contao({field:el}).post({'action':'togglePagetree', 'id':id, 'state':0, 'REQUEST_TOKEN':Contao.request_token});
			}
			return false;
		}

		new Request.Contao({
			field: el,
			evalScripts: true,
			onRequest: AjaxRequest.displayBox(Contao.lang.loading + ' …'),
			onSuccess: function(txt) {
				var li = new Element('li', {
					'id': id,
					'class': 'parent',
					'styles': {
						'display': 'inline'
					}
				});

				new Element('ul', {
					'class': 'level_' + level,
					'html': txt
				}).inject(li, 'bottom');

				li.inject($(el).getParent('li'), 'after');

				// Update the referer ID
				li.getElements('a').each(function(el) {
					el.href = el.href.replace(/&ref=[a-f0-9]+/, '&ref=' + Contao.referer_id);
				});

				$(el).store('tip:title', Contao.lang.collapse);
				image.src = AjaxRequest.themePath + 'icons/folMinus.svg';
				AjaxRequest.hideBox();

				// HOOK
				window.fireEvent('ajax_change');
   			}
		}).post({'action':'loadPagetree', 'id':id, 'level':level, 'field':field, 'name':name, 'state':1, 'REQUEST_TOKEN':Contao.request_token});

		return false;
	},

	/**
	 * Toggle the file tree input field
	 *
	 * @param {object} el     The DOM element
	 * @param {string} id     The ID of the target element
	 * @param {string} folder The folder name
	 * @param {string} field  The field name
	 * @param {string} name   The Ajax field name
	 * @param {int}    level  The indentation level
	 *
	 * @returns {boolean}
	 */
	toggleFiletree: function (el, id, folder, field, name, level) {
		el.blur();
		Backend.getScrollOffset();

		var item = $(id),
			image = $(el).getFirst('img');

		if (item) {
			if (item.getStyle('display') == 'none') {
				item.setStyle('display', null);
				image.src = AjaxRequest.themePath + 'icons/folMinus.svg';
				$(el).store('tip:title', Contao.lang.collapse);
				new Request.Contao({field:el}).post({'action':'toggleFiletree', 'id':id, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
			} else {
				item.setStyle('display', 'none');
				image.src = AjaxRequest.themePath + 'icons/folPlus.svg';
				$(el).store('tip:title', Contao.lang.expand);
				new Request.Contao({field:el}).post({'action':'toggleFiletree', 'id':id, 'state':0, 'REQUEST_TOKEN':Contao.request_token});
			}
			return false;
		}

		new Request.Contao({
			field: el,
			evalScripts: true,
			onRequest: AjaxRequest.displayBox(Contao.lang.loading + ' …'),
			onSuccess: function(txt) {
				var li = new Element('li', {
					'id': id,
					'class': 'parent',
					'styles': {
						'display': 'inline'
					}
				});

				new Element('ul', {
					'class': 'level_' + level,
					'html': txt
				}).inject(li, 'bottom');

				li.inject($(el).getParent('li'), 'after');

				// Update the referer ID
				li.getElements('a').each(function(el) {
					el.href = el.href.replace(/&ref=[a-f0-9]+/, '&ref=' + Contao.referer_id);
				});

				$(el).store('tip:title', Contao.lang.collapse);
				image.src = AjaxRequest.themePath + 'icons/folMinus.svg';
				AjaxRequest.hideBox();

				// HOOK
				window.fireEvent('ajax_change');
   			}
		}).post({'action':'loadFiletree', 'id':id, 'folder':folder, 'level':level, 'field':field, 'name':name, 'state':1, 'REQUEST_TOKEN':Contao.request_token});

		return false;
	},

	/**
	 * Toggle subpalettes in edit mode
	 *
	 * @param {object} el    The DOM element
	 * @param {string} id    The ID of the target element
	 * @param {string} field The field name
	 */
	toggleSubpalette: function (el, id, field) {
		el.blur();
		var item = $(id);

		if (item) {
			if (!el.value) {
				el.value = 1;
				el.checked = 'checked';
				item.setStyle('display', null);
				item.getElements('[data-required]').each(function(el) {
					el.set('required', '').set('data-required', null);
				});
				new Request.Contao({field: el, onSuccess:updateVersionNumber}).post({'action':'toggleSubpalette', 'id':id, 'field':field, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
			} else {
				el.value = '';
				el.checked = '';
				item.setStyle('display', 'none');
				item.getElements('[required]').each(function(el) {
					el.set('required', null).set('data-required', '');
				});
				new Request.Contao({field: el, onSuccess:updateVersionNumber}).post({'action':'toggleSubpalette', 'id':id, 'field':field, 'state':0, 'REQUEST_TOKEN':Contao.request_token});
			}
			return;
		}

		new Request.Contao({
			field: el,
			evalScripts: false,
			onRequest: AjaxRequest.displayBox(Contao.lang.loading + ' …'),
			onSuccess: function(txt, json) {
				var div = new Element('div', {
					'id': id,
					'class': 'subpal cf',
					'html': txt,
					'styles': {
						'display': 'block'
					}
				}).inject($(el).getParent('div').getParent('div'), 'after');

				// Execute scripts after the DOM has been updated
				if (json.javascript) {

					// Use Asset.javascript() instead of document.write() to load a
					// JavaScript file and re-execute the code after it has been loaded
					document.write = function(str) {
						var src = '';
						str.replace(/<script src="([^"]+)"/i, function(all, match){
							src = match;
						});
						src && Asset.javascript(src, {
							onLoad: function() {
								Browser.exec(json.javascript);
							}
						});
					};

					Browser.exec(json.javascript);
				}

				el.value = 1;
				el.checked = 'checked';

				// Update the referer ID
				div.getElements('a').each(function(el) {
					el.href = el.href.replace(/&ref=[a-f0-9]+/, '&ref=' + Contao.referer_id);
				});

				updateVersionNumber(txt);

				AjaxRequest.hideBox();

				// HOOK
				window.fireEvent('subpalette'); // Backwards compatibility
				window.fireEvent('ajax_change');
			}
		}).post({'action':'toggleSubpalette', 'id':id, 'field':field, 'load':1, 'state':1, 'REQUEST_TOKEN':Contao.request_token});

		function updateVersionNumber(html) {
			if (!el.form.elements.VERSION_NUMBER) {
				return;
			}
			el.form.elements.VERSION_NUMBER.value = /<input\s+[^>]*?name="VERSION_NUMBER"\s+[^>]*?value="([^"]*)"/i.exec(html)[1];
		}
	},

	/**
	 * Toggle the state of a checkbox field
	 *
	 * @param {object}  el      The DOM element
	 * @param {boolean} rowIcon Whether the row icon should be toggled as well
	 *
	 * @returns {boolean}
	 */
	toggleField: function(el, rowIcon) {
		el.blur();

		var img = null,
			image = $(el).getFirst('img'),
			published = (image.get('data-state') == 1),
			div = el.getParent('div'),
			next, pa;

		if (rowIcon) {
			// Find the icon depending on the view (tree view, list view, parent view)
			if (div.hasClass('tl_right')) {
				img = div.getPrevious('div').getElement('img');
			} else if (div.hasClass('tl_listing_container')) {
				img = el.getParent('td').getPrevious('td').getFirst('div.list_icon');
				if (img === null) { // comments
					img = el.getParent('td').getPrevious('td').getElement('div.cte_type');
				}
				if (img === null) { // showColumns
					img = el.getParent('tr').getFirst('td').getElement('div.list_icon_new');
				}
			} else if (next = div.getNext('div')) {
				if (next.hasClass('cte_type')) {
					img = next;
				}
				if (img === null) { // newsletter recipients
					img = next.getFirst('div.list_icon');
				}
			}

			// Change the row icon
			if (img !== null) {
				// Tree view
				if (img.nodeName.toLowerCase() == 'img') {
					if (img.getParent('ul.tl_listing').hasClass('tl_tree_xtnd')) {
						img.src = !published ? img.get('data-icon') : img.get('data-icon-disabled');
					} else {
						pa = img.getParent('a');

						if (pa && pa.href.indexOf('contao/preview') == -1) {
							if (next = pa.getNext('a')) {
								img = next.getFirst('img');
							} else {
								img = new Element('img'); // no icons used (see #2286)
							}
						}

						img.src = !published ? img.get('data-icon') : img.get('data-icon-disabled');
					}
				}
				// Parent view
				else if (img.hasClass('cte_type')) {
					if (!published) {
						img.addClass('published');
						img.removeClass('unpublished');
					} else {
						img.addClass('unpublished');
						img.removeClass('published');
					}
				}
				// List view
				else {
					img.setStyle('background-image', 'url(' + (!published ? img.get('data-icon') : img.get('data-icon-disabled')) + ')');
				}
			}
		}

		// Send request
		image.src = !published ? image.get('data-icon') : image.get('data-icon-disabled');
		image.set('data-state', !published ? 1 : 0);

		new Request.Contao({'url':el.href, 'followRedirects':false}).get();

		// Return false to stop the click event on link
		return false;
	},

	/**
	 * Toggle the visibility of an element
	 *
	 * @param {object} el    The DOM element
	 * @param {string} id    The ID of the target element
	 * @param {string} table The table name
	 *
	 * @returns {boolean}
	 *
	 * @deprecated
	 */
	toggleVisibility: function(el, id, table) {
		window.console && console.warn('AjaxRequest.toggleVisibility() is deprecated. Please use the new toggle operation.');

		el.blur();

		var img = null,
			image = $(el).getFirst('img'),
			published = (image.get('data-state') == 1),
			div = el.getParent('div'),
			index, next, icon, icond, pa, params;

		// Backwards compatibility
		if (image.get('data-state') === null) {
			published = (image.src.indexOf('invisible') == -1);
			window.console && console.warn('Using a visibility toggle without a "data-state" attribute is deprecated. Please adjust your Contao DCA file.');
		}

		// Find the icon depending on the view (tree view, list view, parent view)
		if (div.hasClass('tl_right')) {
			img = div.getPrevious('div').getElement('img');
		} else if (div.hasClass('tl_listing_container')) {
			img = el.getParent('td').getPrevious('td').getFirst('div.list_icon');
			if (img === null) { // comments
				img = el.getParent('td').getPrevious('td').getElement('div.cte_type');
			}
			if (img === null) { // showColumns
				img = el.getParent('tr').getFirst('td').getElement('div.list_icon_new');
			}
		} else if (next = div.getNext('div')) {
			if (next.hasClass('cte_type')) {
				img = next;
			}
			if (img === null) { // newsletter recipients
				img = next.getFirst('div.list_icon');
			}
		}

		// Change the icon
		if (img !== null) {
			// Tree view
			if (img.nodeName.toLowerCase() == 'img') {
				if (img.getParent('ul.tl_listing').hasClass('tl_tree_xtnd')) {
					icon = img.get('data-icon');
					icond = img.get('data-icon-disabled');

					// Backwards compatibility
					if (icon === null) {
						icon = img.src.replace(/(.*)\/([a-z0-9]+)_?\.(gif|png|jpe?g|svg)$/, '$1/$2.$3');
						window.console && console.warn('Using a row icon without a "data-icon" attribute is deprecated. Please adjust your Contao DCA file.');
					}
					if (icond === null) {
						icond = img.src.replace(/(.*)\/([a-z0-9]+)_?\.(gif|png|jpe?g|svg)$/, '$1/$2_.$3');
						window.console && console.warn('Using a row icon without a "data-icon-disabled" attribute is deprecated. Please adjust your Contao DCA file.');
					}

					// Prepend the theme path
					if (icon.indexOf('/') == -1) {
						icon = AjaxRequest.themePath + (icon.match(/\.svg$/) ? 'icons/' : 'images/') + icon;
					}
					if (icond.indexOf('/') == -1) {
						icond = AjaxRequest.themePath + (icond.match(/\.svg$/) ? 'icons/' : 'images/') + icond;
					}

					img.src = !published ? icon : icond;
				} else {
					pa = img.getParent('a');

					if (pa && pa.href.indexOf('contao/preview') == -1) {
						if (next = pa.getNext('a')) {
							img = next.getFirst('img');
						} else {
							img = new Element('img'); // no icons used (see #2286)
						}
					}

					icon = img.get('data-icon');
					icond = img.get('data-icon-disabled');

					// Backwards compatibility
					if (icon === null) {
						index = img.src.replace(/.*_([0-9])\.(gif|png|jpe?g|svg)/, '$1');
						icon = img.src.replace(/_[0-9]\.(gif|png|jpe?g|svg)/, ((index.toInt() == 1) ? '' : '_' + (index.toInt() - 1)) + '.$1').split(/[\\/]/).pop();
						window.console && console.warn('Using a row icon without a "data-icon" attribute is deprecated. Please adjust your Contao DCA file.');
					}
					if (icond === null) {
						index = img.src.replace(/.*_([0-9])\.(gif|png|jpe?g|svg)/, '$1');
						icond = img.src.replace(/(_[0-9])?\.(gif|png|jpe?g|svg)/, ((index == img.src) ? '_1' : '_' + (index.toInt() + 1)) + '.$2').split(/[\\/]/).pop();
						window.console && console.warn('Using a row icon without a "data-icon-disabled" attribute is deprecated. Please adjust your Contao DCA file.');
					}

					// Prepend the theme path
					if (icon.indexOf('/') == -1) {
						icon = AjaxRequest.themePath + (icon.match(/\.svg$/) ? 'icons/' : 'images/') + icon;
					}
					if (icond.indexOf('/') == -1) {
						icond = AjaxRequest.themePath + (icond.match(/\.svg$/) ? 'icons/' : 'images/') + icond;
					}

					img.src = !published ? icon : icond;
				}
			}
			// Parent view
			else if (img.hasClass('cte_type')) {
				if (!published) {
					img.addClass('published');
					img.removeClass('unpublished');
				} else {
					img.addClass('unpublished');
					img.removeClass('published');
				}
			}
			// List view
			else {
				icon = img.get('data-icon');
				icond = img.get('data-icon-disabled');

				// Backwards compatibility
				if (icon === null) {
					icon = img.getStyle('background-image').replace(/(.*)\/([a-z0-9]+)_?\.(gif|png|jpe?g|svg)\);?$/, '$1/$2.$2');
					window.console && console.warn('Using a row icon without a "data-icon" attribute is deprecated. Please adjust your Contao DCA file.');
				}
				if (icond === null) {
					icond = img.getStyle('background-image').replace(/(.*)\/([a-z0-9]+)_?\.(gif|png|jpe?g|svg)\);?$/, '$1/$2_.$3');
					window.console && console.warn('Using a row icon without a "data-icon-disabled" attribute is deprecated. Please adjust your Contao DCA file.');
				}

				// Prepend the theme path
				if (icon.indexOf('/') == -1) {
					icon = AjaxRequest.themePath + (icon.match(/\.svg$/) ? 'icons/' : 'images/') + icon;
				}
				if (icond.indexOf('/') == -1) {
					icond = AjaxRequest.themePath + (icond.match(/\.svg$/) ? 'icons/' : 'images/') + icond;
				}

				img.setStyle('background-image', 'url(' + (!published ? icon : icond) + ')');
			}
		}

		// Mark disabled format definitions
		if (table == 'tl_style') {
			div.getParent('div').getElement('pre').toggleClass('disabled');
		}

		icon = image.get('data-icon') || AjaxRequest.themePath + 'icons/visible.svg';
		icond = image.get('data-icon-disabled') || AjaxRequest.themePath + 'icons/invisible.svg';

		// Send request
		if (el.href.indexOf('act=toggle') !== -1) {
			image.src = !published ? icon : icond;
			image.set('data-state', !published ? 1 : 0);

			new Request.Contao({'url':el.href, 'followRedirects':false}).get();
		} else {
			image.src = published ? icond : icon;
			image.set('data-state', published ? 0 : 1);

			params = {'state':published ? 0 : 1, 'rt':Contao.request_token};
			params[$(el).get('data-tid') || 'tid'] = id;

			new Request.Contao({'url':window.location.href, 'followRedirects':false}).get(params);
		}

		return false;
	},

	/**
	 * Feature/unfeature an element
	 *
	 * @param {object} el The DOM element
	 * @param {string} id The ID of the target element
	 *
	 * @returns {boolean}
	 *
	 * @deprecated
	 */
	toggleFeatured: function(el, id) {
		window.console && console.warn('AjaxRequest.toggleFeatured() is deprecated. Please use the new toggle operation.');

		el.blur();

		var image = $(el).getFirst('img'),
			featured = (image.get('data-state') == 1);

		// Backwards compatibility
		if (image.get('data-state') === null) {
			featured = (image.src.indexOf('featured_') == -1);
			window.console && console.warn('Using a featured toggle without a "data-state" attribute is deprecated. Please adjust your Contao DCA file.');
		}

		// Send the request
		if (!featured) {
			image.src = AjaxRequest.themePath + 'icons/featured.svg';
			image.set('data-state', 1);
			new Request.Contao().post({'action':'toggleFeatured', 'id':id, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
		} else {
			image.src = AjaxRequest.themePath + 'icons/featured_.svg';
			image.set('data-state', 0);
			new Request.Contao().post({'action':'toggleFeatured', 'id':id, 'state':0, 'REQUEST_TOKEN':Contao.request_token});
		}

		return false;
	},

	/**
	 * Toggle the visibility of a fieldset
	 *
	 * @param {object} el    The DOM element
	 * @param {string} id    The ID of the target element
	 * @param {string} table The table name
	 *
	 * @returns {boolean}
	 */
	toggleFieldset: function(el, id, table) {
		el.blur();
		Backend.getScrollOffset();

		var fs = $('pal_' + id);

		if (fs.hasClass('collapsed')) {
			fs.removeClass('collapsed');
			new Request.Contao().post({'action':'toggleFieldset', 'id':id, 'table':table, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
		} else {
			var form = fs.getParent('form'),
				inp = fs.getElements('[required]'),
				collapse = true;

			for (var i=0; i<inp.length; i++) {
				if (!inp[i].get('value')) {
					collapse = false;
					break;
				}
			}

			if (!collapse) {
				if (typeof(form.checkValidity) == 'function') form.getElement('button[type="submit"]').click();
			} else {
				fs.addClass('collapsed');
				new Request.Contao().post({'action':'toggleFieldset', 'id':id, 'table':table, 'state':0, 'REQUEST_TOKEN':Contao.request_token});
			}
		}

		return false;
	},

	/**
	 * Toggle a group of a multi-checkbox field
	 *
	 * @param {object} el The DOM element
	 * @param {string} id The ID of the target element
	 *
	 * @returns {boolean}
	 */
	toggleCheckboxGroup: function(el, id) {
		el.blur();

		var item = $(id),
			image = $(el).getFirst('img');

		if (item) {
			if (item.getStyle('display') == 'none') {
				item.setStyle('display', null);
				image.src = AjaxRequest.themePath + 'icons/folMinus.svg';
				new Request.Contao().post({'action':'toggleCheckboxGroup', 'id':id, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
			} else {
				item.setStyle('display', 'none');
				image.src = AjaxRequest.themePath + 'icons/folPlus.svg';
				new Request.Contao().post({'action':'toggleCheckboxGroup', 'id':id, 'state':0, 'REQUEST_TOKEN':Contao.request_token});
			}
			return true;
		}

		return false;
	},

	/**
	 * Display the "loading data" message
	 *
	 * @param {string} message The message text
	 */
	displayBox: function(message) {
		var box = $('tl_ajaxBox'),
			overlay = $('tl_ajaxOverlay'),
			scroll = window.getScroll();

		if (overlay === null) {
			overlay = new Element('div', {
				'id': 'tl_ajaxOverlay'
			}).inject($(document.body), 'bottom');
		}

		overlay.set({
			'styles': {
				'display': 'block',
				'top': scroll.y + 'px'
			}
		});

		if (box === null) {
			box = new Element('div', {
				'id': 'tl_ajaxBox'
			}).inject($(document.body), 'bottom');
		}

		box.set({
			'html': message,
			'styles': {
				'display': 'block',
				'top': (scroll.y + 100) + 'px'
			}
		})
	},

	/**
	 * Hide the "loading data" message
	 */
	hideBox: function() {
		var box = $('tl_ajaxBox'),
			overlay = $('tl_ajaxOverlay');

		if (overlay) {
			overlay.setStyle('display', 'none');
		}

		if (box) {
			box.setStyle('display', 'none');
		}
	}
};

/**
 * Provide methods to handle back end tasks.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
var Backend =
{
	/**
	 * The current ID
	 * @member {(string|null)}
	 */
	currentId: null,

	/**
	 * The x mouse position
	 * @member {int}
	 */
	xMousePosition: 0,

	/**
	 * The Y mouse position
	 * @member {int}
	 */
	yMousePosition: 0,

	/**
	 * The popup window
	 * @member {object}
	 */
	popupWindow: null,

	/**
	 * The theme path
	 * @member {string}
	 */
	themePath: Contao.script_url + 'system/themes/' + Contao.theme + '/',

	/**
	 * Get the current mouse position
	 *
	 * @param {object} event The event object
	 */
	getMousePosition: function(event) {
		Backend.xMousePosition = event.client.x;
		Backend.yMousePosition = event.client.y;
	},

	/**
	 * Open a new window
	 *
	 * @param {object} el     The DOM element
	 * @param {int}    width  The width in pixels
	 * @param {int}    height The height in pixels
	 *
	 * @deprecated Use Backend.openModalWindow() instead
	 */
	openWindow: function(el, width, height) {
		el.blur();
		width = Browser.ie ? (width + 40) : (width + 17);
		height = Browser.ie ? (height + 30) : (height + 17);
		Backend.popupWindow = window.open(el.href, '', 'width=' + width + ',height=' + height + ',modal=yes,left=100,top=50,location=no,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no');
	},

	/**
	 * Open a modal window
	 *
	 * @param {int}    width   The width in pixels
	 * @param {string} title   The window's title
	 * @param {string} content The window's content
	 */
	openModalWindow: function(width, title, content) {
		new SimpleModal({
			'width': width,
			'hideFooter': true,
			'draggable': false,
			'overlayOpacity': .7,
			'onShow': function() { document.body.setStyle('overflow', 'hidden'); },
			'onHide': function() { document.body.setStyle('overflow', 'auto'); }
		}).show({
			'title': title,
			'contents': content
		});
	},

	/**
	 * Open an image in a modal window
	 *
	 * @param {object} options An optional options object
	 */
	openModalImage: function(options) {
		var opt = options || {},
			maxWidth = (window.getSize().x - 20).toInt();
		if (!opt.width || opt.width > maxWidth) opt.width = Math.min(maxWidth, 900);
		var M = new SimpleModal({
			'width': opt.width,
			'hideFooter': true,
			'draggable': false,
			'overlayOpacity': .7,
			'onShow': function() { document.body.setStyle('overflow', 'hidden'); },
			'onHide': function() { document.body.setStyle('overflow', 'auto'); }
		});
		M.show({
			'title': opt.title,
			'contents': '<img src="' + opt.url + '" alt="">'
		});
	},

	/**
	 * Open an iframe in a modal window
	 *
	 * @param {object} options An optional options object
	 */
	openModalIframe: function(options) {
		var opt = options || {},
			maxWidth = (window.getSize().x - 20).toInt(),
			maxHeight = (window.getSize().y - 137).toInt();
		if (!opt.width || opt.width > maxWidth) opt.width = Math.min(maxWidth, 900);
		if (!opt.height || opt.height > maxHeight) opt.height = maxHeight;
		var M = new SimpleModal({
			'width': opt.width,
			'hideFooter': true,
			'draggable': false,
			'overlayOpacity': .7,
			'onShow': function() { document.body.setStyle('overflow', 'hidden'); },
			'onHide': function() { document.body.setStyle('overflow', 'auto'); }
		});
		M.show({
			'title': opt.title,
			'contents': '<iframe src="' + opt.url + '" width="100%" height="' + opt.height + '" frameborder="0"></iframe>',
			'model': 'modal'
		});
	},

	/**
	 * Open a selector page in a modal window
	 *
	 * @param {object} options An optional options object
	 */
	openModalSelector: function(options) {
		var opt = options || {},
			maxWidth = (window.getSize().x - 20).toInt(),
			maxHeight = (window.getSize().y - 192).toInt();
		if (!opt.id) opt.id = 'tl_select';
		if (!opt.width || opt.width > maxWidth) opt.width = Math.min(maxWidth, 900);
		if (!opt.height || opt.height > maxHeight) opt.height = maxHeight;
		var M = new SimpleModal({
			'width': opt.width,
			'draggable': false,
			'overlayOpacity': .7,
			'onShow': function() { document.body.setStyle('overflow', 'hidden'); },
			'onHide': function() { document.body.setStyle('overflow', 'auto'); }
		});
		M.addButton(Contao.lang.cancel, 'btn', function() {
			if (this.buttons[0].hasClass('btn-disabled')) {
				return;
			}
			this.hide();
		});
		M.addButton(Contao.lang.apply, 'btn primary', function() {
			if (this.buttons[1].hasClass('btn-disabled')) {
				return;
			}
			var frm = window.frames['simple-modal-iframe'],
				val = [], ul, inp, field, act, it, i, pickerValue, sIndex;
			if (frm === undefined) {
				alert('Could not find the SimpleModal frame');
				return;
			}
			ul = frm.document.getElementById(opt.id);
			// Load the previous values (#1816)
			if (pickerValue = ul.get('data-picker-value')) {
				val = JSON.parse(pickerValue);
			}
			inp = ul.getElementsByTagName('input');
			for (i=0; i<inp.length; i++) {
				if (inp[i].id.match(/^(check_all_|reset_)/)) {
					continue;
				}
				// Add currently selected value, otherwise remove (#1816)
				sIndex = val.indexOf(inp[i].get('value'));
				if (inp[i].checked) {
					if (sIndex == -1) {
						val.push(inp[i].get('value'));
					}
				} else if (sIndex != -1) {
					val.splice(sIndex, 1);
				}
			}
			if (opt.callback) {
				opt.callback(ul.get('data-table'), val);
			} else if (opt.tag && (field = $(opt.tag))) {
				window.console && console.warn('Using the modal selector without a callback function is deprecated. Please adjust your Contao DCA file.');
				field.value = val.join(',');
				if (it = ul.get('data-inserttag')) {
					field.value = '{{' + it + '::' + field.value + '}}';
				}
				opt.self.set('href', opt.self.get('href').replace(/&value=[^&]*/, '&value=' + val.join(',')));
			} else if (opt.id && (field = $('ctrl_' + opt.id)) && (act = ul.get('data-callback'))) {
				window.console && console.warn('Using the modal selector without a callback function is deprecated. Please adjust your Contao DCA file.');
				field.value = val.join("\t");
				new Request.Contao({
					field: field,
					evalScripts: false,
					onRequest: AjaxRequest.displayBox(Contao.lang.loading + ' …'),
					onSuccess: function(txt, json) {
						$('ctrl_' + opt.id).getParent('div').set('html', json.content);
						json.javascript && Browser.exec(json.javascript);
						AjaxRequest.hideBox();
						window.fireEvent('ajax_change');
					}
				}).post({'action':act, 'name':opt.id, 'value':field.value, 'REQUEST_TOKEN':Contao.request_token});
			}
			this.hide();
		});
		M.show({
			'title': opt.title,
			'contents': '<iframe src="' + opt.url + '" name="simple-modal-iframe" width="100%" height="' + opt.height + '" frameborder="0"></iframe>',
			'model': 'modal'
		});
	},

	/**
	 * Open a TinyMCE file browser in a modal window
	 *
	 * @param {string} field_name The field name
	 * @param {string} url        The URL
	 * @param {string} type       The picker type
	 * @param {object} win        The window object
	 * @param {string} source     The source record
	 */
	openModalBrowser: function(field_name, url, type, win, source) {
		Backend.openModalSelector({
			'id': 'tl_listing',
			'title': win.document.getElement('div.mce-title').get('text'),
			'url': Contao.routes.backend_picker + '?context=' + (type == 'file' ? 'link' : 'file') + '&amp;extras[fieldType]=radio&amp;extras[filesOnly]=true&amp;extras[source]=' + source + '&amp;value=' + url + '&amp;popup=1',
			'callback': function(table, value) {
				win.document.getElementById(field_name).value = value.join(',');
			}
		});
	},

	/**
	 * Store the current scroll offset in sessionStorage
	 */
	getScrollOffset: function() {
		window.sessionStorage.setItem('contao_backend_offset', window.getScroll().y);
	},

	/**
	 * Remove the legacy BE_PAGE_OFFSET cookie, scroll to the current offset if
	 * it was defined and add the "down" CSS class to the header.
	 */
	initScrollOffset: function() {
		// Kill the legacy cookie here; this way it can be sent by the server,
		// but it won't be resent by the client in the next request
		Cookie.dispose('BE_PAGE_OFFSET');

		// Add events to the submit buttons, so they can reset the offset
		// (except for "save", which always stays on the same page)
		$$('.tl_submit_container button[name][name!="save"]').each(function(button) {
			button.addEvent('click', function() {
				window.sessionStorage.removeItem('contao_backend_offset');
			});
		});

		var offset = window.sessionStorage.getItem('contao_backend_offset');
		window.sessionStorage.removeItem('contao_backend_offset');

		if (!offset) return;

		var header = window.document.getElementById('header'),
			additionalOffset = 0;

		if (header) {
			header.addClass('down');
		}

		$$('[data-add-to-scroll-offset]').each(function(el) {
			var offset = el.get('data-add-to-scroll-offset'),
				scrollSize = el.getScrollSize().y,
				negative = false,
				percent = false;

			// No specific offset desired, take scrollSize
			if (!offset) {
				additionalOffset += scrollSize;
				return;
			}

			// Negative
			if (offset.charAt(0) === '-') {
				negative = true;
				offset = offset.substring(1);
			}

			// Percent
			if (offset.charAt(offset.length - 1) === '%') {
				percent = true;
				offset = offset.substring(0, offset.length - 1);
			}

			offset = parseInt(offset, 10);

			if (percent) {
				offset = Math.round(scrollSize * offset / 100);
			}

			if (negative) {
				offset = offset * -1;
			}

			additionalOffset += offset;
		});

		this.vScrollTo(parseInt(offset, 10) + additionalOffset);
	},

	/**
	 * Automatically submit a form
	 *
	 * @param {object} el The DOM element
	 */
	autoSubmit: function(el) {
		Backend.getScrollOffset();

		var hidden = new Element('input', {
			'type': 'hidden',
			'name': 'SUBMIT_TYPE',
			'value': 'auto'
		});

		var form = $(el) || el;
		hidden.inject(form, 'bottom');
		form.submit();
	},

	/**
	 * Scroll the window to a certain vertical position
	 *
	 * @param {int} offset The offset to scroll to
	 */
	vScrollTo: function(offset) {
		window.addEvent('load', function() {
			window.scrollTo(null, parseInt(offset));
		});
	},

	/**
	 * Limit the height of the preview pane
	 */
	limitPreviewHeight: function() {
		var hgt = 0;

		$$('div.limit_height').each(function(div) {
			var parent = div.getParent('.tl_content'),
				toggler, button, size, style;

			// Return if the element is a wrapper
			if (parent && (parent.hasClass('wrapper_start') || parent.hasClass('wrapper_stop'))) return;

			if (hgt === 0) {
				hgt = div.className.replace(/[^0-9]*/, '').toInt();
			}

			// Return if there is no height value
			if (!hgt) return;

			toggler = new Element('div', {
				'class': 'limit_toggler'
			});

			button = new Element('button', {
				'type': 'button',
				'html': '<span>...</span>',
				'class': 'unselectable',
				'data-state': 0
			}).inject(toggler);

			size = div.getCoordinates();
			div.setStyle('height', hgt);

			// Disable the function if the preview height is below the max-height
			if (size.height <= hgt) {
				return;
			}

			button.addEvent('click', function() {
				style = toggler.getPrevious('div').getStyle('height').toInt();
				toggler.getPrevious('div').setStyle('height', ((style > hgt) ? hgt : ''));
				button.set('data-state', button.get('data-state') ? 0 : 1);
			});

			toggler.inject(div, 'after');
		});
	},

	/**
	 * Toggle checkboxes
	 *
	 * @param {object} el   The DOM element
	 * @param {string} [id] The ID of the target element
	 */
	toggleCheckboxes: function(el, id) {
		var items = $$('input'),
			status = $(el).checked ? 'checked' : '';

		for (var i=0; i<items.length; i++) {
			if (items[i].type.toLowerCase() != 'checkbox') {
				continue;
			}
			if (id !== undefined && id != items[i].id.substr(0, id.length)) {
				continue;
			}
			items[i].checked = status;
		}
	},

	/**
	 * Toggle a checkbox group
	 *
	 * @param {object} el The DOM element
	 * @param {string} id The ID of the target element
	 */
	toggleCheckboxGroup: function(el, id) {
		var cls = $(el).className,
			status = $(el).checked ? 'checked' : '';

		if (cls == 'tl_checkbox') {
			var cbx = $(id) ? $$('#' + id + ' .tl_checkbox') : $(el).getParent('fieldset').getElements('.tl_checkbox');
			cbx.each(function(checkbox) {
				checkbox.checked = status;
			});
		} else if (cls == 'tl_tree_checkbox') {
			$$('#' + id + ' .parent .tl_tree_checkbox').each(function(checkbox) {
				checkbox.checked = status;
			});
		}

		Backend.getScrollOffset();
	},

	/**
	 * Toggle checkbox elements
	 *
	 * @param {string} el  The DOM element
	 * @param {string} cls The CSS class name
	 */
	toggleCheckboxElements: function(el, cls) {
		var status = $(el).checked ? 'checked' : '';

		$$('.' + cls).each(function(checkbox) {
			if (checkbox.hasClass('tl_checkbox')) {
				checkbox.checked = status;
			}
		});

		Backend.getScrollOffset();
	},

	/**
	 * Toggle the line wrapping mode of a textarea
	 *
	 * @param {string} id The ID of the target element
	 */
	toggleWrap: function(id) {
		var textarea = $(id),
			status = (textarea.getProperty('wrap') == 'off') ? 'soft' : 'off';
		textarea.setProperty('wrap', status);
	},

	/**
	 * Toggle the synchronization results
	 */
	toggleUnchanged: function() {
		$$('#result-list .tl_confirm').each(function(el) {
			el.toggleClass('hidden');
		});
	},

	/**
	 * Toggle the opacity of the paste buttons
	 *
	 * @deprecated Not required anymore
	 */
	blink: function() {},

	/**
	 * Initialize the mootools color picker
	 *
	 * @returns {boolean}
	 *
	 * @deprecated Not required anymore
	 */
	addColorPicker: function() {
		return true;
	},

	/**
	 * Collapse all palettes
	 */
	collapsePalettes: function() {
		$$('fieldset.hide').each(function(el) {
			el.addClass('collapsed');
		});
		$$('label.error, label.mandatory').each(function(el) {
			var fs = el.getParent('fieldset');
			fs && fs.removeClass('collapsed');
		});
	},

	/**
	 * Add the interactive help
	 */
	addInteractiveHelp: function() {
		new Tips.Contao('p.tl_tip', {
			offset: {x:9, y:23},
			text: function(e) {
				return e.get('html');
			}
		});

		// Home
		new Tips.Contao($('home'), {
			offset: {x:15, y:42}
		});

		// Top navigation links
		new Tips.Contao($$('#tmenu a[title]').filter(function(i) {
			return i.title != '';
		}), {
			offset: {x:9, y:42}
		});

		// Navigation groups
		new Tips.Contao($$('a[title][class^="group-"]').filter(function(i) {
			return i.title != '';
		}), {
			offset: {x:3, y:27}
		});

		// Navigation links
		new Tips.Contao($$('a[title].navigation').filter(function(i) {
			return i.title != '';
		}), {
			offset: {x:34, y:32}
		});

		// Images
		$$('img[title]').filter(function(i) {
			return i.title != '';
		}).each(function(el) {
			new Tips.Contao(el, {
				offset: {x:0, y:((el.get('class') == 'gimage') ? 60 : 30)}
			});
		});

		// Links and input elements
		['a[title]', 'input[title]', 'button[title]', 'time[title]', 'span[title]'].each(function(el) {
			new Tips.Contao($$(el).filter(function(i) {
				return i.title != ''
			}), {
				offset: {x:0, y:((el == 'time[title]' || el == 'span[title]') ? 26 : 30)}
			});
		});
	},

	/**
	 * Retrieve the interactive help
	 */
	retrieveInteractiveHelp: function (elements) {
		elements && elements.each(function (element) {
			var title = element.retrieve('tip:title');
			title && element.set('title', title);
		});
	},

	/**
	 * Hide the interactive help
	 */
	hideInteractiveHelp: function () {
		var hideTips = function () {
			document.querySelectorAll('.tip-wrap').forEach(function (tip) {
				tip.setStyle('display', 'none');
			});
		};
		hideTips();
		setTimeout(hideTips, (new Tips.Contao).options.showDelay); // hide delayed tips
	},

	/**
	 * Make parent view items sortable
	 *
	 * @param {object} ul The DOM element
	 *
	 * @author Joe Ray Gregory
	 * @author Martin Auswöger
	 */
	makeParentViewSortable: function(ul) {
		var ds = new Scroller(document.getElement('body'), {
			onChange: function(x, y) {
				this.element.scrollTo(this.element.getScroll().x, y);
			}
		});

		var list = new Sortables(ul, {
			constrain: true,
			opacity: 0.6,
			onStart: function() {
				ds.start();
			},
			onComplete: function() {
				ds.stop();
			},
			onSort: function(el) {
				var ul = el.getParent('ul'),
					wrapLevel = 0, divs, i;

				if (!ul) return;

				divs = ul.getChildren('li > div:first-child');

				if (!divs) return;

				for (i=0; i<divs.length; i++) {
					if (divs[i].hasClass('wrapper_stop') && wrapLevel > 0) {
						wrapLevel--;
					}

					divs[i].className = divs[i].className.replace(/(^|\s)indent[^\s]*/g, '');

					if (wrapLevel > 0) {
						divs[i].addClass('indent').addClass('indent_' + wrapLevel);
					}

					if (divs[i].hasClass('wrapper_start')) {
						wrapLevel++;
					}

					divs[i].removeClass('indent_first');
					divs[i].removeClass('indent_last');

					if (divs[i-1] && divs[i-1].hasClass('wrapper_start')) {
						divs[i].addClass('indent_first');
					}

					if (divs[i+1] && divs[i+1].hasClass('wrapper_stop')) {
						divs[i].addClass('indent_last');
					}
				}
			},
			handle: '.drag-handle'
		});

		list.active = false;

		list.addEvent('start', function() {
			list.active = true;
		});

		list.addEvent('complete', function(el) {
			if (!list.active) return;
			var id, pid, req, href;

			if (el.getPrevious('li')) {
				id = el.get('id').replace(/li_/, '');
				pid = el.getPrevious('li').get('id').replace(/li_/, '');
				req = window.location.search.replace(/id=[0-9]*/, 'id=' + id) + '&act=cut&mode=1&pid=' + pid;
				href = window.location.href.replace(/\?.*$/, '');
				new Request.Contao({'url':href + req, 'followRedirects':false}).get();
			} else if (el.getParent('ul')) {
				id = el.get('id').replace(/li_/, '');
				pid = el.getParent('ul').get('id').replace(/ul_/, '');
				req = window.location.search.replace(/id=[0-9]*/, 'id=' + id) + '&act=cut&mode=2&pid=' + pid;
				href = window.location.href.replace(/\?.*$/, '');
				new Request.Contao({'url':href + req, 'followRedirects':false}).get();
			}
		});
	},

	/**
	 * Make multiSRC items sortable
	 *
	 * @param {string} id  The ID of the target element
	 * @param {string} oid The order field
	 * @param {string} val The value field
	 */
	makeMultiSrcSortable: function(id, oid, val) {
		var list = new Sortables($(id), {
			constrain: true,
			opacity: 0.6
		}).addEvent('complete', function() {
			var els = [],
				lis = $(id).getChildren('[data-id]'),
				i;
			for (i=0; i<lis.length; i++) {
				els.push(lis[i].get('data-id'));
			}
			if (oid === val) {
				$(val).value.split(',').forEach(function(j) {
					if (els.indexOf(j) === -1) {
						els.push(j);
					}
				});
			}
			$(oid).value = els.join(',');
		});
		$(id).getElements('.gimage').each(function(el) {
			if (el.hasClass('removable')) {
				new Element('button', {
					type: 'button',
					html: '&times;',
					'class': 'tl_red'
				}).addEvent('click', function() {
					var li = el.getParent('li'),
						did = li.get('data-id');
					$(val).value = $(val).value.split(',').filter(function(j) { return j != did; }).join(',');
					$(oid).value = $(oid).value.split(',').filter(function(j) { return j != did; }).join(',');
					li.dispose();
				}).inject(el, 'after');
			} else {
				new Element('button', {
					type: 'button',
					html: '&times',
					disabled: true
				}).inject(el, 'after');
			}
		});
		list.fireEvent("complete"); // Initial sorting
	},

	/**
	 * Enable drag and drop for the file tree
	 *
	 * @param {object} ul      The DOM element
	 * @param {object} options An optional options object
	 */
	enableFileTreeDragAndDrop: function(ul, options) {
		var ds = new Scroller(document.getElement('body'), {
			onChange: function(x, y) {
				this.element.scrollTo(this.element.getScroll().x, y);
			}
		});

		ul.addEvent('mousedown', function(event) {
			var dragHandle = event.target.hasClass('drag-handle') ? event.target : event.target.getParent('.drag-handle');
			var dragElement = event.target.getParent('.tl_file,.tl_folder');

			if (!dragHandle || !dragElement || event.rightClick) {
				return;
			}

			ds.start();
			ul.addClass('tl_listing_dragging');

			var cloneBase = (dragElement.getElements('.tl_left')[0] || dragElement),
				clone = cloneBase.clone(true)
					.inject(ul)
					.addClass('tl_left_dragging'),
				currentHover, currentHoverTime;

			clone.setPosition({
				x: event.page.x - cloneBase.getOffsetParent().getPosition().x - clone.getSize().x,
				y: cloneBase.getPosition(cloneBase.getOffsetParent()).y
			}).setStyle('display', 'none');

			var move = new Drag.Move(clone, {
				droppables: $$([ul]).append(ul.getElements('.tl_folder,li.parent,.tl_folder_top')),
				unDraggableTags: [],
				modifiers: {
					x: 'left',
					y: 'top'
				},
				onStart: function() {
					clone.setStyle('display', '');
				},
				onEnter: function(element, droppable) {
					droppable = fixDroppable(droppable);
					droppable.addClass('tl_folder_dropping');

					if (droppable.hasClass('tl_folder') && currentHover !== droppable) {
						currentHover = droppable;
						currentHoverTime = new Date().getTime();

						var expandLink = droppable.getElement('img[src$="/icons/folPlus.svg"]');
						expandLink = expandLink && expandLink.getParent('a');

						if (expandLink) {
							// Expand the folder after one second hover time
							setTimeout(function() {
								if (currentHover === droppable && currentHoverTime + 900 < new Date().getTime()) {
									var event = document.createEvent('HTMLEvents');
									event.initEvent('click', true, true);
									expandLink.dispatchEvent(event);

									currentHover = undefined;
									currentHoverTime = undefined;

									window.addEvent('ajax_change', function onAjax() {
										if (move && move.droppables && ul && ul.getElements) {
											move.droppables = $$([ul]).append(ul.getElements('.tl_folder,li.parent'));
										}
										window.removeEvent('ajax_change', onAjax);
									});
								}
							}, 1000);
						}
					}
				},
				onCancel: function() {
					currentHover = undefined;
					currentHoverTime = undefined;

					ds.stop();
					clone.destroy();
					window.removeEvent('keyup', onKeyup);
					ul.getElements('.tl_folder_dropping').removeClass('tl_folder_dropping');
					ul.removeClass('tl_listing_dragging');
				},
				onDrop: function(element, droppable) {
					currentHover = undefined;
					currentHoverTime = undefined;

					ds.stop();
					clone.destroy();
					window.removeEvent('keyup', onKeyup);
					ul.getElements('.tl_folder_dropping').removeClass('tl_folder_dropping');
					ul.removeClass('tl_listing_dragging');

					droppable = fixDroppable(droppable);

					if (!droppable) {
						return;
					}

					var id = dragElement.get('data-id'),
						pid = droppable.get('data-id') || decodeURIComponent(options.url.split(/[?&]pid=/)[1].split('&')[0]);

					// Ignore invalid move operations
					if (id && pid && ((pid+'/').indexOf(id+'/') === 0 || pid+'/' === id.replace(/[^/]+$/, ''))) {
						return;
					}

					Backend.getScrollOffset();
					document.location.href = options.url + '&id=' + encodeURIComponent(id) + '&pid=' + encodeURIComponent(pid);
				},
				onLeave: function(element, droppable) {
					droppable = fixDroppable(droppable);
					droppable.removeClass('tl_folder_dropping');
					currentHover = undefined;
					currentHoverTime = undefined;
				}
			});

			move.start(event);
			window.addEvent('keyup', onKeyup);

			function onKeyup(event) {
				if (event.key === 'esc' && move && move.stop) {
					move.droppables = $$([]);
					move.stop();
				}
			}
		});

		function fixDroppable(droppable) {
			if (droppable && droppable.hasClass('parent') && droppable.getPrevious('.tl_folder')) {
				return droppable.getPrevious('.tl_folder');
			}

			return droppable;
		}
	},

	/**
	 * List wizard
	 *
	 * @param {string} id The ID of the target element
	 */
	listWizard: function(id) {
		var ul = $(id),
			makeSortable = function(ul) {
				new Sortables(ul, {
					constrain: true,
					opacity: 0.6,
					handle: '.drag-handle'
				});
			},
			addEventsTo = function(li) {
				var command, clone, input, previous, next;

				li.getElements('button').each(function(bt) {
					if (bt.hasEvent('click')) return;
					command = bt.getProperty('data-command');

					switch (command) {
						case 'copy':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								clone = li.clone(true).inject(li, 'before');
								if (input = li.getFirst('input')) {
									clone.getFirst('input').value = input.value;
								}
								addEventsTo(clone);
							});
							break;
						case 'delete':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								if (ul.getChildren().length > 1) {
									li.destroy();
								}
							});
							break;
						case null:
							bt.addEvent('keydown', function(e) {
								if (e.event.keyCode == 38) {
									e.preventDefault();
									if (previous = li.getPrevious('li')) {
										li.inject(previous, 'before');
									} else {
										li.inject(ul, 'bottom');
									}
									bt.focus();
								} else if (e.event.keyCode == 40) {
									e.preventDefault();
									if (next = li.getNext('li')) {
										li.inject(next, 'after');
									} else {
										li.inject(ul.getFirst('li'), 'before');
									}
									bt.focus();
								}
							});
							break;
					}
				});
			};

		makeSortable(ul);

		ul.getChildren().each(function(li) {
			addEventsTo(li);
		});
	},

	/**
	 * Table wizard
	 *
	 * @param {string} id The ID of the target element
	 */
	tableWizard: function(id) {
		var table = $(id),
			thead = table.getElement('thead'),
			tbody = table.getElement('tbody'),
			makeSortable = function(tbody) {
				var rows = tbody.getChildren(),
					textarea, childs, i, j;

				for (i=0; i<rows.length; i++) {
					childs = rows[i].getChildren();
					for (j=0; j<childs.length; j++) {
						if (textarea = childs[j].getFirst('textarea')) {
							textarea.name = textarea.name.replace(/\[[0-9]+][[0-9]+]/g, '[' + i + '][' + j + ']')
						}
					}
				}

				new Sortables(tbody, {
					constrain: true,
					opacity: 0.6,
					handle: '.drag-handle',
					onComplete: function() {
						makeSortable(tbody);
					}
				});
			},
			addEventsTo = function(tr) {
				var head = thead.getFirst('tr'),
					command, textarea, current, next, ntr, childs, index, i;

				tr.getElements('button').each(function(bt) {
					if (bt.hasEvent('click')) return;
					command = bt.getProperty('data-command');

					switch (command) {
						case 'rcopy':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								ntr = new Element('tr');
								childs = tr.getChildren();
								for (i=0; i<childs.length; i++) {
									Backend.retrieveInteractiveHelp(childs[i].getElements('button,a'));
									next = childs[i].clone(true).inject(ntr, 'bottom');
									if (textarea = childs[i].getFirst('textarea')) {
										next.getFirst('textarea').value = textarea.value;
									}
								}
								ntr.inject(tr, 'after');
								addEventsTo(ntr);
								makeSortable(tbody);
								Backend.addInteractiveHelp();
							});
							break;
						case 'rdelete':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								if (tbody.getChildren().length > 1) {
									tr.destroy();
								}
								makeSortable(tbody);
								Backend.hideInteractiveHelp();
							});
							break;
						case 'ccopy':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								index = getIndex(bt);
								childs = tbody.getChildren();
								for (i=0; i<childs.length; i++) {
									current = childs[i].getChildren()[index];
									Backend.retrieveInteractiveHelp(current.getElements('button,a'));
									next = current.clone(true).inject(current, 'after');
									if (textarea = current.getFirst('textarea')) {
										next.getFirst('textarea').value = textarea.value;
									}
									addEventsTo(next);
								}
								var headFirst = head.getFirst('td');
								Backend.retrieveInteractiveHelp(headFirst.getElements('button,a'));
								next = headFirst.clone(true).inject(head.getLast('td'), 'before');
								addEventsTo(next);
								makeSortable(tbody);
								Backend.addInteractiveHelp();
							});
							break;
						case 'cmovel':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								index = getIndex(bt);
								childs = tbody.getChildren();
								if (index > 0) {
									for (i=0; i<childs.length; i++) {
										current = childs[i].getChildren()[index];
										current.inject(current.getPrevious(), 'before');
									}
								} else {
									for (i=0; i<childs.length; i++) {
										current = childs[i].getChildren()[index];
										current.inject(childs[i].getLast(), 'before');
									}
								}
								makeSortable(tbody);
							});
							break;
						case 'cmover':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								index = getIndex(bt);
								childs = tbody.getChildren();
								if (index < (tr.getChildren().length - 2)) {
									for (i=0; i<childs.length; i++) {
										current = childs[i].getChildren()[index];
										current.inject(current.getNext(), 'after');
									}
								} else {
									for (i=0; i<childs.length; i++) {
										current = childs[i].getChildren()[index];
										current.inject(childs[i].getFirst(), 'before');
									}
								}
								makeSortable(tbody);
							});
							break;
						case 'cdelete':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								index = getIndex(bt);
								childs = tbody.getChildren();
								if (tr.getChildren().length > 2) {
									for (i=0; i<childs.length; i++) {
										childs[i].getChildren()[index].destroy();
									}
									head.getFirst('td').destroy();
								}
								makeSortable(tbody);
								Backend.hideInteractiveHelp();
							});
							break;
						case null:
							bt.addEvent('keydown', function(e) {
								if (e.event.keyCode == 38) {
									e.preventDefault();
									if (ntr = tr.getPrevious('tr')) {
										tr.inject(ntr, 'before');
									} else {
										tr.inject(tbody, 'bottom');
									}
									bt.focus();
									makeSortable(tbody);
								} else if (e.event.keyCode == 40) {
									e.preventDefault();
									if (ntr = tr.getNext('tr')) {
										tr.inject(ntr, 'after');
									} else {
										tr.inject(tbody, 'top');
									}
									bt.focus();
									makeSortable(tbody);
								}
							});
							break;
					}
				});
			},
			getIndex = function(bt) {
				var td = $(bt).getParent('td'),
					tr = td.getParent('tr'),
					cols = tr.getChildren(),
					index = 0, i;

				for (i=0; i<cols.length; i++) {
					if (cols[i] == td) {
						break;
					}
					index++;
				}

				return index;
			};

		makeSortable(tbody);

		thead.getChildren().each(function(tr) {
			addEventsTo(tr);
		});

		tbody.getChildren().each(function(tr) {
			addEventsTo(tr);
		});

		Backend.tableWizardResize();
	},

	/**
	 * Resize the table wizard fields on focus
	 *
	 * @param {float} [factor] The resize factor
	 */
	tableWizardResize: function(factor) {
		var size = window.localStorage.getItem('contao_table_wizard_cell_size');

		if (factor !== undefined) {
			size = '';
			$$('.tl_tablewizard textarea').each(function(el) {
				el.setStyle('width', (el.getStyle('width').toInt() * factor).round().limit(142, 284));
				el.setStyle('height', (el.getStyle('height').toInt() * factor).round().limit(66, 132));
				if (size == '') {
					size = el.getStyle('width') + '|' + el.getStyle('height');
				}
			});
			window.localStorage.setItem('contao_table_wizard_cell_size', size);
		} else if (size !== null) {
			var chunks = size.split('|');
			$$('.tl_tablewizard textarea').each(function(el) {
				el.setStyle('width', chunks[0]);
				el.setStyle('height', chunks[1]);
			});
		}
	},

	/**
	 * Set the width of the table wizard
	 */
	tableWizardSetWidth: function() {
		var wrap = $('tl_tablewizard');
		if (!wrap) return;
		wrap.setStyle('width', Math.round(wrap.getParent('.tl_formbody_edit').getComputedSize().width * 0.96));
	},

	/**
	 * Module wizard
	 *
	 * @param {string} id The ID of the target element
	 */
	moduleWizard: function(id) {
		var table = $(id),
			tbody = table.getElement('tbody'),
			makeSortable = function(tbody) {
				var rows = tbody.getChildren(),
					childs, i, j, select, input;

				for (i=0; i<rows.length; i++) {
					childs = rows[i].getChildren();
					for (j=0; j<childs.length; j++) {
						if (select = childs[j].getElement('select')) {
							select.name = select.name.replace(/\[[0-9]+]/g, '[' + i + ']');
						}
						if (input = childs[j].getElement('input[type="checkbox"]')) {
							input.set('tabindex', -1);
							input.name = input.name.replace(/\[[0-9]+]/g, '[' + i + ']');
						}
					}
				}

				new Sortables(tbody, {
					constrain: true,
					opacity: 0.6,
					handle: '.drag-handle',
					onComplete: function() {
						makeSortable(tbody);
					}
				});
			},
			addEventsTo = function(tr) {
				var command, select, next, ntr, childs, cbx, i;
				tr.getElements('button').each(function(bt) {
					if (bt.hasEvent('click')) return;
					command = bt.getProperty('data-command');

					switch (command) {
						case 'copy':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								ntr = new Element('tr');
								childs = tr.getChildren();
								for (i=0; i<childs.length; i++) {
									Backend.retrieveInteractiveHelp(childs[i].getElements('button,a'));
									next = childs[i].clone(true).inject(ntr, 'bottom');
									if (select = childs[i].getElement('select')) {
										next.getElement('select').value = select.value;
									}
								}
								ntr.inject(tr, 'after');
								ntr.getElement('.chzn-container').destroy();
								new Chosen(ntr.getElement('select.tl_select'));
								addEventsTo(ntr);
								makeSortable(tbody);
								Backend.addInteractiveHelp();
							});
							break;
						case 'delete':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								if (tbody.getChildren().length > 1) {
									tr.destroy();
								}
								makeSortable(tbody);
								Backend.hideInteractiveHelp();
							});
							break;
						case 'enable':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								cbx = bt.getNext('input[type="checkbox"]');
								if (cbx.checked) {
									cbx.checked = '';
									bt.getElement('img').src = Backend.themePath + 'icons/invisible.svg';
								} else {
									cbx.checked = 'checked';
									bt.getElement('img').src = Backend.themePath + 'icons/visible.svg';
								}
								makeSortable(tbody);
							});
							break;
						case null:
							bt.addEvent('keydown', function(e) {
								if (e.event.keyCode == 38) {
									e.preventDefault();
									if (ntr = tr.getPrevious('tr')) {
										tr.inject(ntr, 'before');
									} else {
										tr.inject(tbody, 'bottom');
									}
									bt.focus();
									makeSortable(tbody);
								} else if (e.event.keyCode == 40) {
									e.preventDefault();
									if (ntr = tr.getNext('tr')) {
										tr.inject(ntr, 'after');
									} else {
										tr.inject(tbody, 'top');
									}
									bt.focus();
									makeSortable(tbody);
								}
							});
							break;
					}
				});
			};

		makeSortable(tbody);

		tbody.getChildren().each(function(tr) {
			addEventsTo(tr);
		});
	},

	/**
	 * Options wizard
	 *
	 * @param {string} id The ID of the target element
	 */
	optionsWizard: function(id) {
		var table = $(id),
			tbody = table.getElement('tbody'),
			makeSortable = function(tbody) {
				var rows = tbody.getChildren(),
					childs, i, j, input;

				for (i=0; i<rows.length; i++) {
					childs = rows[i].getChildren();
					for (j=0; j<childs.length; j++) {
						if (input = childs[j].getFirst('input')) {
							input.name = input.name.replace(/\[[0-9]+]/g, '[' + i + ']');
							if (input.type == 'checkbox') {
								input.id = input.name.replace(/\[[0-9]+]/g, '').replace(/\[/g, '_').replace(/]/g, '') + '_' + i;
								input.getNext('label').set('for', input.id);
							}
						}
					}
				}

				new Sortables(tbody, {
					constrain: true,
					opacity: 0.6,
					handle: '.drag-handle',
					onComplete: function() {
						makeSortable(tbody);
					}
				});
			},
			addEventsTo = function(tr) {
				var command, input, next, ntr, childs, i;
				tr.getElements('button').each(function(bt) {
					if (bt.hasEvent('click')) return;
					command = bt.getProperty('data-command');

					switch (command) {
						case 'copy':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								ntr = new Element('tr');
								childs = tr.getChildren();
								for (i=0; i<childs.length; i++) {
									Backend.retrieveInteractiveHelp(childs[i].getElements('button,a'));
									next = childs[i].clone(true).inject(ntr, 'bottom');
									if (input = childs[i].getFirst('input')) {
										next.getFirst('input').value = input.value;
										if (input.type == 'checkbox') {
											next.getFirst('input').checked = input.checked ? 'checked' : '';
										}
									}
								}
								ntr.inject(tr, 'after');
								addEventsTo(ntr);
								makeSortable(tbody);
								Backend.addInteractiveHelp();
							});
							break;
						case 'delete':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								if (tbody.getChildren().length > 1) {
									tr.destroy();
								}
								makeSortable(tbody);
								Backend.hideInteractiveHelp();
							});
							break;
						case null:
							bt.addEvent('keydown', function(e) {
								if (e.event.keyCode == 38) {
									e.preventDefault();
									if (ntr = tr.getPrevious('tr')) {
										tr.inject(ntr, 'before');
									} else {
										tr.inject(tbody, 'bottom');
									}
									bt.focus();
									makeSortable(tbody);
								} else if (e.event.keyCode == 40) {
									e.preventDefault();
									if (ntr = tr.getNext('tr')) {
										tr.inject(ntr, 'after');
									} else {
										tr.inject(tbody, 'top');
									}
									bt.focus();
									makeSortable(tbody);
								}
							});
							break;
					}
				});
			};

		makeSortable(tbody);

		tbody.getChildren().each(function(tr) {
			addEventsTo(tr);
		});
	},

	/**
	 * Key/value wizard
	 *
	 * @param {string} id The ID of the target element
	 */
	keyValueWizard: function(id) {
		var table = $(id),
			tbody = table.getElement('tbody'),
			makeSortable = function(tbody) {
				var rows = tbody.getChildren(),
					childs, i, j, input;

				for (i=0; i<rows.length; i++) {
					childs = rows[i].getChildren();
					for (j=0; j<childs.length; j++) {
						if (input = childs[j].getFirst('input')) {
							input.name = input.name.replace(/\[[0-9]+]/g, '[' + i + ']')
						}
					}
				}

				new Sortables(tbody, {
					constrain: true,
					opacity: 0.6,
					handle: '.drag-handle',
					onComplete: function() {
						makeSortable(tbody);
					}
				});
			},
			addEventsTo = function(tr) {
				var command, input, next, ntr, childs, i;
				tr.getElements('button').each(function(bt) {
					if (bt.hasEvent('click')) return;
					command = bt.getProperty('data-command');

					switch (command) {
						case 'copy':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								ntr = new Element('tr');
								childs = tr.getChildren();
								for (i=0; i<childs.length; i++) {
									Backend.retrieveInteractiveHelp(childs[i].getElements('button,a'));
									next = childs[i].clone(true).inject(ntr, 'bottom');
									if (input = childs[i].getFirst('input')) {
										next.getFirst().value = input.value;
									}
								}
								ntr.inject(tr, 'after');
								addEventsTo(ntr);
								makeSortable(tbody);
								Backend.addInteractiveHelp();
							});
							break;
						case 'delete':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								if (tbody.getChildren().length > 1) {
									tr.destroy();
								}
								makeSortable(tbody);
								Backend.hideInteractiveHelp();
							});
							break;
						case null:
							bt.addEvent('keydown', function(e) {
								if (e.event.keyCode == 38) {
									e.preventDefault();
									if (ntr = tr.getPrevious('tr')) {
										tr.inject(ntr, 'before');
									} else {
										tr.inject(tbody, 'bottom');
									}
									bt.focus();
									makeSortable(tbody);
								} else if (e.event.keyCode == 40) {
									e.preventDefault();
									if (ntr = tr.getNext('tr')) {
										tr.inject(ntr, 'after');
									} else {
										tr.inject(tbody, 'top');
									}
									bt.focus();
									makeSortable(tbody);
								}
							});
							break;
					}
				});
			};

		makeSortable(tbody);

		tbody.getChildren().each(function(tr) {
			addEventsTo(tr);
		});
	},

	/**
	 * Checkbox wizard
	 *
	 * @param {string} id The ID of the target element
	 */
	checkboxWizard: function(id) {
		var container = $(id).getElement('.sortable'),
			makeSortable = function(container) {
				new Sortables(container, {
					constrain: true,
					opacity: 0.6,
					handle: '.drag-handle'
				});
			},
			addEventsTo = function(span) {
				var nspan;
				span.getElements('button').each(function(bt) {
					if (bt.hasEvent('click')) return;
					bt.addEvent('keydown', function(e) {
						if (e.event.keyCode == 38) {
							e.preventDefault();
							if ((nspan = span.getPrevious('span'))) {
								span.inject(nspan, 'before');
							} else {
								span.inject(container, 'bottom');
							}
							bt.focus();
						} else if (e.event.keyCode == 40) {
							e.preventDefault();
							if (nspan = span.getNext('span')) {
								span.inject(nspan, 'after');
							} else {
								span.inject(container, 'top');
							}
							bt.focus();
						}
					});
				});
			};

		makeSortable(container);

		container.getChildren().each(function(span) {
			addEventsTo(span);
		});
	},

	/**
	 * Remove a meta entry
	 *
	 * @param {object} el The DOM element
	 */
	metaDelete: function(el) {
		var li = el.getParent('li');

		// Empty the last element instead of removing it (see #4858)
		if (li.getPrevious() === null && li.getNext() === null) {
			li.getElements('input, textarea').each(function(input) {
				input.value = '';
			});
		} else {
			li.destroy();
		}
	},

	/**
	 * Toggle the "add language" button
	 *
	 * @param {object} el The DOM element
	 */
	toggleAddLanguageButton: function(el) {
		var inp = el.getParent('div').getElement('input[type="button"]');
		if (el.value != '') {
			inp.removeProperty('disabled');
		} else {
			inp.setProperty('disabled', true);
		}
	},

	/**
	 * Section wizard
	 *
	 * @param {string} id The ID of the target element
	 */
	sectionWizard: function(id) {
		var table = $(id),
			tbody = table.getElement('tbody'),
			makeSortable = function(tbody) {
				var rows = tbody.getChildren(),
					childs, i, j;

				for (i=0; i<rows.length; i++) {
					childs = rows[i].getChildren();
					for (j=0; j<childs.length; j++) {
						childs[j].getElements('input').each(function(input) {
							input.name = input.name.replace(/\[[0-9]+]/g, '[' + i + ']')
						});
						childs[j].getElements('select').each(function(select) {
							select.name = select.name.replace(/\[[0-9]+]/g, '[' + i + ']');
						});
					}
				}

				new Sortables(tbody, {
					constrain: true,
					opacity: 0.6,
					handle: '.drag-handle',
					onComplete: function() {
						makeSortable(tbody);
					}
				});
			},
			addEventsTo = function(tr) {
				var command, next, ntr, childs, selects, nselects, i, j;
				tr.getElements('button').each(function(bt) {
					if (bt.hasEvent('click')) return;
					command = bt.getProperty('data-command');

					switch (command) {
						case 'copy':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								ntr = new Element('tr');
								childs = tr.getChildren();
								for (i=0; i<childs.length; i++) {
									Backend.retrieveInteractiveHelp(childs[i].getElements('button,a'));
									next = childs[i].clone(true).inject(ntr, 'bottom');
									selects = childs[i].getElements('select');
									nselects = next.getElements('select');
									for (j=0; j<selects.length; j++) {
										nselects[j].value = selects[j].value;
									}
								}
								ntr.inject(tr, 'after');
								addEventsTo(ntr);
								makeSortable(tbody);
								Backend.addInteractiveHelp();
							});
							break;
						case 'delete':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								if (tbody.getChildren().length > 1) {
									tr.destroy();
								}
								makeSortable(tbody);
								Backend.hideInteractiveHelp();
							});
							break;
						case null:
							bt.addEvent('keydown', function(e) {
								if (e.event.keyCode == 38) {
									e.preventDefault();
									if (ntr = tr.getPrevious('tr')) {
										tr.inject(ntr, 'before');
									} else {
										tr.inject(tbody, 'bottom');
									}
									bt.focus();
									makeSortable(tbody);
								} else if (e.event.keyCode == 40) {
									e.preventDefault();
									if (ntr = tr.getNext('tr')) {
										tr.inject(ntr, 'after');
									} else {
										tr.inject(tbody, 'top');
									}
									bt.focus();
									makeSortable(tbody);
								}
							});
							break;
					}
				});
			};

		makeSortable(tbody);

		tbody.getChildren().each(function(tr) {
			addEventsTo(tr);
		});
	},

	/**
	 * Update the "edit module" links in the module wizard
	 *
	 * @param {object} el The DOM element
	 */
	updateModuleLink: function(el) {
		var td = el.getParent('tr').getLast('td'),
			a = td.getElement('a.module_link');

		a.href = a.href.replace(/id=[0-9]+/, 'id=' + el.value);

		if (el.value > 0) {
			td.getElement('a.module_link').setStyle('display', null);
			td.getElement('img.module_image').setStyle('display', 'none');
		} else {
			td.getElement('a.module_link').setStyle('display', 'none');
			td.getElement('img.module_image').setStyle('display', null);
		}
	},

	/**
	 * Update the fields of the imageSize widget upon change
	 */
	enableImageSizeWidgets: function() {
		$$('.tl_image_size').each(function(el) {
			var select = el.getElement('select'),
				widthInput = el.getChildren('input')[0],
				heightInput = el.getChildren('input')[1],
				update = function() {
					if (select.get('value') === '' || select.get('value').indexOf('_') === 0 || select.get('value').toInt().toString() === select.get('value')) {
						widthInput.readOnly = true;
						heightInput.readOnly = true;
						var dimensions = $(select.getSelected()[0]).get('text');
						dimensions = dimensions.split('(').length > 1
							? dimensions.split('(').getLast().split(')')[0].split('x')
							: ['', ''];
						widthInput.set('value', '').set('placeholder', dimensions[0] * 1 || '');
						heightInput.set('value', '').set('placeholder', dimensions[1] * 1 || '');
					} else {
						widthInput.set('placeholder', '');
						heightInput.set('placeholder', '');
						widthInput.readOnly = false;
						heightInput.readOnly = false;
					}
				}
			;

			update();
			select.addEvent('change', update);
			select.addEvent('keyup', update);
		});
	},

	/**
	 * Allow toggling checkboxes or radio buttons by clicking a row
	 *
	 * @author Kamil Kuzminski
	 */
	enableToggleSelect: function() {
		var container = $('tl_listing'),
			shiftToggle = function(el) {
				thisIndex = checkboxes.indexOf(el);
				startIndex = checkboxes.indexOf(start);
				from = Math.min(thisIndex, startIndex);
				to = Math.max(thisIndex, startIndex);
				status = !!checkboxes[startIndex].checked;

				for (from; from<=to; from++) {
					checkboxes[from].checked = status;
				}
			},
			clickEvent = function(e) {
				var input = this.getElement('input[type="checkbox"],input[type="radio"]'),
					limitToggler = $(e.target).getParent('.limit_toggler');

				if (!input || input.get('disabled') || limitToggler !== null) {
					return;
				}

				// Radio buttons
				if (input.type == 'radio') {
					if (!input.checked) {
						input.checked = 'checked';
					}

					return;
				}

				// Checkboxes
				if (e.shift && start) {
					shiftToggle(input);
				} else {
					input.checked = input.checked ? '' : 'checked';

					if (input.get('onclick') == 'Backend.toggleCheckboxes(this)') {
						Backend.toggleCheckboxes(input); // see #6399
					}
				}

				start = input;
			},
			checkboxes = [], start, thisIndex, startIndex, status, from, to;

		if (container) {
			checkboxes = container.getElements('input[type="checkbox"]');
		}

		// Row click
		$$('.toggle_select').each(function(el) {
			var boundEvent = el.retrieve('boundEvent');

			if (boundEvent) {
				el.removeEvent('click', boundEvent);
			}

			// Do not propagate the form field click events
			el.getElements('label,input[type="checkbox"],input[type="radio"]').each(function(i) {
				i.addEvent('click', function(e) {
					e.stopPropagation();
				});
			});

			boundEvent = clickEvent.bind(el);

			el.addEvent('click', boundEvent);
			el.store('boundEvent', boundEvent);
		});

		// Checkbox click
		checkboxes.each(function(el) {
			el.addEvent('click', function(e) {
				if (e.shift && start) {
					shiftToggle(this);
				}

				start = this;
			});
		});
	},

	/**
	 * Try to focus the first input field in the main section.
	 *
	 * @author Yanick Witschi
	 */
	autoFocusFirstInputField: function() {
		var edit = document.id('main').getElement('.tl_formbody_edit');
		if (!edit) return;

		var inputs = edit
			.getElements('input, textarea')
			.filter(function(item) {
				return !item.get('disabled') && !item.get('readonly') && item.isVisible() && item.get('type') !== 'checkbox' && item.get('type') !== 'radio' && item.get('type') !== 'submit' && item.get('type') !== 'image' && (!item.get('autocomplete') || item.get('autocomplete') === 'off' || !item.get('value'));
			});

		if (inputs[0]) inputs[0].focus();
	},

	/**
	 * Allow to mark the important part of an image
	 *
	 * @param {object} el The DOM element
	 */
	editPreviewWizard: function(el) {
		el = $(el);
		var imageElement = el.getElement('img'),
			inputElements = {},
			isDrawing = false,
			partElement, startPos,
			getScale = function() {
				return {
					x: imageElement.getComputedSize().width,
					y: imageElement.getComputedSize().height
				};
			},
			updateImage = function() {
				var scale = getScale(),
					imageSize = imageElement.getComputedSize();
				partElement.setStyles({
					top: imageSize.computedTop + (inputElements.y.get('value') * scale.y).round() + 'px',
					left: imageSize.computedLeft + (inputElements.x.get('value') * scale.x).round() + 'px',
					width: (inputElements.width.get('value') * scale.x).round() + 'px',
					height: (inputElements.height.get('value') * scale.y).round() + 'px'
				});
				if (!inputElements.width.get('value').toFloat() || !inputElements.height.get('value').toFloat()) {
					partElement.setStyle('display', 'none');
				} else {
					partElement.setStyle('display', null);
				}
			},
			updateValues = function() {
				var scale = getScale(),
					styles = partElement.getStyles('top', 'left', 'width', 'height'),
					imageSize = imageElement.getComputedSize(),
					values = {
						x: Math.max(0, Math.min(1, (styles.left.toFloat() - imageSize.computedLeft) / scale.x)),
						y: Math.max(0, Math.min(1, (styles.top.toFloat() - imageSize.computedTop) / scale.y))
					};
				values.width = Math.min(1 - values.x, styles.width.toFloat() / scale.x);
				values.height = Math.min(1 - values.y, styles.height.toFloat() / scale.y);
				if (!values.width || !values.height) {
					values.x = values.y = values.width = values.height = '';
					partElement.setStyle('display', 'none');
				} else {
					partElement.setStyle('display', null);
				}
				Object.each(values, function(value, key) {
					inputElements[key].set('value', value === '' ? '' : Number(value).toFixed(15));
				});
			},
			start = function(event) {
				event.preventDefault();
				if (isDrawing) {
					return;
				}
				isDrawing = true;
				startPos = {
					x: event.page.x - el.getPosition().x - imageElement.getComputedSize().computedLeft,
					y: event.page.y - el.getPosition().y - imageElement.getComputedSize().computedTop
				};
				move(event);
			},
			move = function(event) {
				if (!isDrawing) {
					return;
				}
				event.preventDefault();
				var imageSize = imageElement.getComputedSize();
				var rect = {
					x: [
						Math.max(0, Math.min(imageSize.width, startPos.x)),
						Math.max(0, Math.min(imageSize.width, event.page.x - el.getPosition().x - imageSize.computedLeft))
					],
					y: [
						Math.max(0, Math.min(imageSize.height, startPos.y)),
						Math.max(0, Math.min(imageSize.height, event.page.y - el.getPosition().y - imageSize.computedTop))
					]
				};
				partElement.setStyles({
					top: Math.min(rect.y[0], rect.y[1]) + imageSize.computedTop + 'px',
					left: Math.min(rect.x[0], rect.x[1]) + imageSize.computedLeft + 'px',
					width: Math.abs(rect.x[0] - rect.x[1]) + 'px',
					height: Math.abs(rect.y[0] - rect.y[1]) + 'px'
				});
				updateValues();
			},
			stop = function(event) {
				move(event);
				isDrawing = false;
			},
			init = function() {
				el.getParent('.tl_tbox,.tl_box').getElements('input[name^="importantPart"]').each(function(input) {
					['x', 'y', 'width', 'height'].each(function(key) {
						if (input.get('name').substr(13, key.length) === key.capitalize()) {
							inputElements[key] = input = $(input);
						}
					});
				});
				if (Object.getLength(inputElements) !== 4) {
					return;
				}
				Object.each(inputElements, function(input) {
					input.getParent().setStyle('display', 'none');
				});
				el.addClass('tl_edit_preview_enabled');
				partElement = new Element('div', {
					'class': 'tl_edit_preview_important_part'
				}).inject(el);
				updateImage();
				imageElement.addEvent('load', updateImage);
				el.addEvents({
					mousedown: start,
					touchstart: start
				});
				$(document.documentElement).addEvents({
					mousemove: move,
					touchmove: move,
					mouseup: stop,
					touchend: stop,
					touchcancel: stop,
					resize: updateImage
				});
			}
		;

		window.addEvent('domready', init);
	},

	/**
	 * Enable drag and drop file upload for the file tree
	 *
	 * @param {object} wrap    The DOM element
	 * @param {object} options An optional options object
	 */
	enableFileTreeUpload: function(wrap, options) {
		wrap = $(wrap);

		var fallbackUrl = options.url,
			dzElement = new Element('div', {
				'class': 'dropzone dropzone-filetree',
				html: '<span class="dropzone-previews"></span>'
			}).inject(wrap, 'top'),
			currentHover, currentHoverTime;

		options.previewsContainer = dzElement.getElement('.dropzone-previews');
		options.clickable = false;

		var dz = new Dropzone(wrap, options);

		dz.on('queuecomplete', function() {
			window.location.reload();
		});

		dz.on('dragover', function(event) {
			if (!event.dataTransfer || !event.dataTransfer.types || event.dataTransfer.types.indexOf('Files') === -1) {
				return;
			}

			wrap.getElements('.tl_folder_dropping').removeClass('tl_folder_dropping');
			var target = event.target && $(event.target);

			if (target) {
				var folder = target.match('.tl_folder') ? target : target.getParent('.tl_folder');

				if (!folder) {
					folder = target.getParent('.parent');
					folder = folder && folder.getPrevious('.tl_folder');
				}

				if (folder) {
					var link = folder.getElement('img[src$="/icons/new.svg"]');
					link = link && link.getParent('a');
				}
			}

			if (link && link.href) {
				dz.options.url = ''+link.href;
				folder.addClass('tl_folder_dropping');

				if (currentHover !== folder) {
					currentHover = folder;
					currentHoverTime = new Date().getTime();

					var expandLink = folder.getElement('img[src$="/icons/folPlus.svg"]');
					expandLink = expandLink && expandLink.getParent('a');

					if (expandLink) {
						// Expand the folder after one second hover time
						setTimeout(function() {
							if (currentHover === folder && currentHoverTime + 900 < new Date().getTime()) {
								var event = document.createEvent('HTMLEvents');
								event.initEvent('click', true, true);
								expandLink.dispatchEvent(event);
								currentHover = undefined;
								currentHoverTime = undefined;
							}
						}, 1000);
					}
				}
			} else {
				dz.options.url = fallbackUrl;
				currentHover = undefined;
				currentHoverTime = undefined;
			}
		});

		dz.on('drop', function (event) {
			if (!event.dataTransfer || !event.dataTransfer.types || event.dataTransfer.types.indexOf('Files') === -1) {
				return;
			}

			dzElement.addClass('dropzone-filetree-enabled');
			Backend.getScrollOffset();
		});

		dz.on('dragleave', function() {
			wrap.getElements('.tl_folder_dropping').removeClass('tl_folder_dropping');
			currentHover = undefined;
			currentHoverTime = undefined;
		});
	},

	/**
	 * Crawl the website
	 */
	crawl: function() {
		var timeout = 2000,
			crawl = $('tl_crawl'),
			progressBar = crawl.getElement('div.progress-bar'),
			progressCount = crawl.getElement('p.progress-count'),
			results = crawl.getElement('div.results');

		function updateData(response) {
			var done = response.total - response.pending,
				percentage = response.total > 0 ? parseInt(done / response.total * 100, 10) : 100,
				result;

			progressBar.setStyle('width', percentage + '%');
			progressBar.set('html', percentage + '%');
			progressBar.setAttribute('aria-valuenow', percentage);
			progressCount.set('html', done + ' / ' + response.total);

			if (!response.finished) {
				return;
			}

			progressBar.removeClass('running').addClass('finished');
			results.removeClass('running').addClass('finished');

			for (result in response.results) {
				if (response.results.hasOwnProperty(result)) {
					var summary = results.getElement('.result[data-subscriber="' + result + '"] p.summary'),
						warning = results.getElement('.result[data-subscriber="' + result + '"] p.warning'),
						log = results.getElement('.result[data-subscriber="' + result + '"] p.subscriber-log'),
						subscriberResults = response.results[result],
						subscriberSummary = subscriberResults.summary;

					if (subscriberResults.warning) {
						warning.set('html', subscriberResults.warning);
					}

					if (subscriberResults.hasLog) {
						log.setStyle('display', 'block');
					}

					summary.addClass(subscriberResults.wasSuccessful ? 'success' : 'failure');
					summary.set('html', subscriberSummary);
				}
			}
		}

		function execRequest(onlyStatusUpdate) {
			var onlyStatusUpdate = onlyStatusUpdate || false;

			new Request({
				url: window.location.href,
				headers: {
					'Only-Status-Update': onlyStatusUpdate
				},
				onSuccess: function(responseText) {
					var response = JSON.decode(responseText);

					updateData(response);

					if (!response.finished) {
						setTimeout(execRequest, timeout);
					}
				}
			}).send();
		}

		execRequest(true);
	}
};

// Track the mousedown event
document.addEvent('mousedown', function(event) {
	Backend.getMousePosition(event);
});

// Initialize the back end script
window.addEvent('domready', function() {
	$(document.body).addClass('js');

	// Mark touch devices (see #5563)
	if (Browser.Features.Touch) {
		$(document.body).addClass('touch');
	}

	Backend.collapsePalettes();
	Backend.addInteractiveHelp();
	Backend.tableWizardSetWidth();
	Backend.enableImageSizeWidgets();
	Backend.enableToggleSelect();
	Backend.autoFocusFirstInputField();

	// Chosen
	if (Elements.chosen != undefined) {
		$$('select.tl_chosen').chosen();
	}

	// Remove line wraps from textareas
	$$('textarea.monospace').each(function(el) {
		Backend.toggleWrap(el);
	});
});

// Resize the table wizard
window.addEvent('resize', function() {
	Backend.tableWizardSetWidth();
});

// Limit the height of the preview fields
window.addEvent('load', function() {
	Backend.limitPreviewHeight();
});

// Re-apply certain changes upon ajax_change
window.addEvent('ajax_change', function() {
	Backend.addInteractiveHelp();
	Backend.enableImageSizeWidgets();
	Backend.enableToggleSelect();

	// Chosen
	if (Elements.chosen != undefined) {
		$$('select.tl_chosen').filter(function(el) {
			return el.getStyle('display') != 'none';
		}).chosen();
	}
});
