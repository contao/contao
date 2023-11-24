/**
 * Provide methods to handle Ajax requests.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
window.AjaxRequest =
{
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
		if (window.console) {
			console.warn('AjaxRequest.toggleNavigation() is deprecated. Please use the stimulus controller instead.');
		}

		var item = $(id),
			parent = $(el).getParent('li');

		if (item) {
			if (parent.hasClass('collapsed')) {
				parent.removeClass('collapsed');
				$(el).setAttribute('aria-expanded', 'true');
				$(el).setAttribute('title', Contao.lang.collapse);
				new Request.Contao({ url: url }).post({'action':'toggleNavigation', 'id':id, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
			} else {
				parent.addClass('collapsed');
				$(el).setAttribute('aria-expanded', 'false');
				$(el).setAttribute('title', Contao.lang.expand);
				new Request.Contao({ url: url }).post({'action':'toggleNavigation', 'id':id, 'state':0, 'REQUEST_TOKEN':Contao.request_token});
			}
			return false;
		}

		return false;
	},

	/**
	 * Toggle the page tree
	 *
	 * @param {object} el    The DOM element
	 * @param {string} id    The ID of the target element
	 * @param {int}    level The indentation level
	 * @param {int}    mode  The insert mode
	 *
	 * @returns {boolean}
	 */
	toggleStructure: function(el, id, level, mode) {
		if (window.console) {
			console.warn('AjaxRequest.toggleStructure() is deprecated. Please use the stimulus controller instead.');
		}

		el.blur();

		var item = $(id);

		if (item) {
			if (item.getStyle('display') == 'none') {
				item.setStyle('display', null);

				$(el).addClass('foldable--open');
				$(el).setAttribute('title', Contao.lang.collapse);

				new Request.Contao({field:el}).post({'action':'toggleStructure', 'id':id, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
			} else {
				item.setStyle('display', 'none');

				$(el).removeClass('foldable--open');
				$(el).setAttribute('title', Contao.lang.expand);

				new Request.Contao({field:el}).post({'action':'toggleStructure', 'id':id, 'state':0, 'REQUEST_TOKEN':Contao.request_token});
			}
			return false;
		}

		new Request.Contao({
			field: el,
			evalScripts: true,
			onRequest: function() {
				AjaxRequest.displayBox(Contao.lang.loading + ' …');
			},
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

				$(el).addClass('foldable--open');
				$(el).setAttribute('title', Contao.lang.collapse);

				window.fireEvent('structure');
				AjaxRequest.hideBox();

				// HOOK
				window.fireEvent('ajax_change');
   			}
		}).post({'action':'loadStructure', 'id':id, 'level':level, 'state':1, 'REQUEST_TOKEN':Contao.request_token});

		return false;
	},

	/**
	 * Toggle the file tree
	 *
	 * @param {object} el     The DOM element
	 * @param {string} id     The ID of the target element
	 * @param {string} folder The folder's path
	 * @param {int}    level  The indentation level
	 *
	 * @returns {boolean}
	 */
	toggleFileManager: function(el, id, folder, level) {
		if (window.console) {
			console.warn('AjaxRequest.toggleFileManager() is deprecated. Please use the stimulus controller instead.');
		}

		el.blur();

		var item = $(id);

		if (item) {
			if (item.getStyle('display') == 'none') {
				item.setStyle('display', null);

				$(el).addClass('foldable--open');
				$(el).setAttribute('title', Contao.lang.collapse);

				new Request.Contao({field:el}).post({'action':'toggleFileManager', 'id':id, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
			} else {
				item.setStyle('display', 'none');

				$(el).removeClass('foldable--open');
				$(el).setAttribute('title', Contao.lang.expand);

				new Request.Contao({field:el}).post({'action':'toggleFileManager', 'id':id, 'state':0, 'REQUEST_TOKEN':Contao.request_token});
			}
			return false;
		}

		new Request.Contao({
			field: el,
			evalScripts: true,
			onRequest: function() {
				AjaxRequest.displayBox(Contao.lang.loading + ' …');
			},
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

				$(el).addClass('foldable--open');
				$(el).setAttribute('title', Contao.lang.collapse);

				AjaxRequest.hideBox();

				// HOOK
				window.fireEvent('ajax_change');
   			}
		}).post({'action':'loadFileManager', 'id':id, 'level':level, 'folder':folder, 'state':1, 'REQUEST_TOKEN':Contao.request_token});

		return false;
	},

	/**
	 * Toggle sub-palettes in edit mode
	 *
	 * @param {object} el    The DOM element
	 * @param {string} id    The ID of the target element
	 * @param {string} field The field name
	 */
	toggleSubpalette: function(el, id, field) {
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
			onRequest: function() {
				AjaxRequest.displayBox(Contao.lang.loading + ' …');
			},
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
				window.fireEvent('ajax_change');
			}
		}).post({'action':'toggleSubpalette', 'id':id, 'field':field, 'load':1, 'state':1, 'REQUEST_TOKEN':Contao.request_token});

		function updateVersionNumber(html) {
			var fields = el.form.elements.VERSION_NUMBER || [];
			if (!fields.forEach) {
				fields = [fields];
			}
			fields.forEach(function(field) {
				field.value = /<input\s+[^>]*?name="VERSION_NUMBER"\s+[^>]*?value="([^"]*)"/i.exec(html)[1];
			});
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
			images = $(el).getElements('img'),
			published = (images[0].get('data-state') == 1),
			div = el.getParent('div'),
			next, pa;

		if (rowIcon) {
			// Find the icon depending on the view (tree view, list view, parent view)
			if (div.hasClass('tl_right')) {
				img = div.getPrevious('div').getElements('img');
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
				if (!(img instanceof HTMLElement) && img.forEach) {
					img.forEach((img) => {
						if (img.nodeName.toLowerCase() == 'img') {
							if (!img.getParent('ul.tl_listing').hasClass('tl_tree_xtnd')) {
								pa = img.getParent('a');

								if (pa && pa.href.indexOf('contao/preview') == -1) {
									if (next = pa.getNext('a')) {
										img = next.getElement('img');
									} else {
										img = new Element('img'); // no icons used (see #2286)
									}
								}
							}

							const newSrc = !published ? img.get('data-icon') : img.get('data-icon-disabled');
							img.src = (img.src.includes('/') && !newSrc.includes('/')) ? img.src.slice(0, img.src.lastIndexOf('/') + 1) + newSrc : newSrc;
						}
					})
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
		images.forEach(function(image) {
			const newSrc = !published ? image.get('data-icon') : image.get('data-icon-disabled');
			image.src = (image.src.includes('/') && !newSrc.includes('/')) ? image.src.slice(0, image.src.lastIndexOf('/') + 1) + newSrc : newSrc;
			image.set('data-state', !published ? 1 : 0);
		});

		if (!published && $(el).get('data-title')) {
			el.title = $(el).get('data-title');
		} else if (published && $(el).get('data-title-disabled')) {
			el.title = $(el).get('data-title-disabled');
		}

		new Request.Contao({'url':el.href, 'followRedirects':false}).get();

		// Return false to stop the click event on link
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

		var item = $(id);

		if (item) {
			if (item.getStyle('display') == 'none') {
				item.setStyle('display', null);
				$(el).addClass('foldable--open');

				new Request.Contao().post({'action':'toggleCheckboxGroup', 'id':id, 'state':1, 'REQUEST_TOKEN':Contao.request_token});
			} else {
				item.setStyle('display', 'none');
				$(el).removeClass('foldable--open');

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
window.Backend =
{
	/**
	 * The current ID
	 * @member {(string|null)}
	 */
	currentId: null,

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
				val = [], ul, inp, i, pickerValue, sIndex;
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
			opt.callback(ul.get('data-table'), val);
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
	 * Scroll to the current offset if
	 * it was defined and add the "down" CSS class to the header.
	 */
	initScrollOffset: function() {
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

		var additionalOffset = 0;

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
				currentHover, currentHoverTime, expandLink;

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
						expandLink = droppable.getElement('a.foldable');

						if (expandLink && !expandLink.hasClass('foldable--open')) {
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
									next = childs[i].clone(true).inject(ntr, 'bottom');
									if (textarea = childs[i].getFirst('textarea')) {
										next.getFirst('textarea').value = textarea.value;
									}
								}
								ntr.inject(tr, 'after');
								addEventsTo(ntr);
								makeSortable(tbody);
							});
							break;
						case 'rdelete':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								if (tbody.getChildren().length > 1) {
									tr.destroy();
								}
								makeSortable(tbody);
							});
							break;
						case 'ccopy':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								index = getIndex(bt);
								childs = tbody.getChildren();
								for (i=0; i<childs.length; i++) {
									current = childs[i].getChildren()[index];
									next = current.clone(true).inject(current, 'after');
									if (textarea = current.getFirst('textarea')) {
										next.getFirst('textarea').value = textarea.value;
									}
									addEventsTo(next);
								}
								var headFirst = head.getFirst('td');
								next = headFirst.clone(true).inject(head.getLast('td'), 'before');
								addEventsTo(next);
								makeSortable(tbody);
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
							});
							break;
						case 'delete':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								if (tbody.getChildren().length > 1) {
									tr.destroy();
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
									next = childs[i].clone(true).inject(ntr, 'bottom');
									if (input = childs[i].getFirst('input')) {
										next.getFirst().value = input.value;
									}
								}
								ntr.inject(tr, 'after');
								addEventsTo(ntr);
								makeSortable(tbody);
							});
							break;
						case 'delete':
							bt.addEvent('click', function() {
								Backend.getScrollOffset();
								if (tbody.getChildren().length > 1) {
									tr.destroy();
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
			currentHover, currentHoverTime, expandLink;

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
					expandLink = folder.getElement('a.foldable');

					if (expandLink && !expandLink.hasClass('foldable--open')) {
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

		dz.on('drop', function(event) {
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
			results = crawl.getElement('div.results'),
			debugLog = crawl.getElement('p.debug-log');

		function updateData(response) {
			var total = response.total,
				done = total - response.pending,
				percentage = total > 0 ? parseInt(done / total * 100, 10) : 100,
				result;

			// Initialize the status bar at 10%
			if (done < 1 && percentage < 1) {
				done = 1;
				percentage = 10;
				total = 10;
			}

			progressBar.setStyle('width', percentage + '%');
			progressBar.set('html', percentage + '%');
			progressBar.setAttribute('aria-valuenow', percentage);
			progressCount.set('html', done + ' / ' + total);

			if (response.hasDebugLog) {
				debugLog.setStyle('display', 'block');
			}

			if (response.hasDebugLog) {
				debugLog.setStyle('display', 'block');
			}

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

		function execRequest(onlyStatusUpdate = false) {
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

window.Theme =
{
	/**
	 * Check for WebKit
	 * @member {boolean}
 	 */
	isWebkit: (Browser.chrome || Browser.safari || navigator.userAgent.match(/(?:webkit|khtml)/i)),

	/**
	 * Stop the propagation of click events of certain elements
	 */
	stopClickPropagation: function() {
		// Do not propagate the click events of the icons
		$$('.picker_selector').each(function(ul) {
			ul.getElements('a').each(function(el) {
				el.addEvent('click', function(e) {
					e.stopPropagation();
				});
			});
		});

		// Do not propagate the click events of the checkboxes
		$$('.picker_selector,.click2edit').each(function(ul) {
			ul.getElements('input[type="checkbox"]').each(function(el) {
				el.addEvent('click', function(e) {
					e.stopPropagation();
				});
			});
		});
	},

	/**
	 * Set up the [Ctrl] + click to edit functionality
	 */
	setupCtrlClick: function() {
		$$('.click2edit').each(function(el) {

			// Do not propagate the click events of the default buttons (see #5731)
			el.getElements('a').each(function(a) {
				a.addEvent('click', function(e) {
					e.stopPropagation();
				});
			});

			// Set up regular click events on touch devices
			if (Browser.Features.Touch) {
				el.addEvent('click', function() {
					if (!el.getAttribute('data-visited')) {
						el.setAttribute('data-visited', '1');
					} else {
						el.getElements('a').each(function(a) {
							if (a.hasClass('edit')) {
								document.location.href = a.href;
							}
						});
						el.removeAttribute('data-visited');
					}
				});
			} else {
				el.addEvent('click', function(e) {
					var key = Browser.Platform.mac ? e.event.metaKey : e.event.ctrlKey;
					if (!key) return;

					if (e.event.shiftKey) {
						el.getElements('a').each(function(a) {
							if (a.hasClass('children')) {
								document.location.href = a.href;
							}
						});
					} else {
						el.getElements('a').each(function(a) {
							if (a.hasClass('edit')) {
								document.location.href = a.href;
							}
						});
					}
				});
			}
		});
	},

	/**
	 * Set up the textarea resizing
	 */
	setupTextareaResizing: function() {
		$$('.tl_textarea').each(function(el) {
			if (Browser.ie6 || Browser.ie7 || Browser.ie8) return;
			if (el.hasClass('noresize') || el.retrieve('autogrow')) return;

			// Set up the dummy element
			var dummy = new Element('div', {
				html: 'X',
				styles: {
					'position':'absolute',
					'top':0,
					'left':'-999em',
					'overflow-x':'hidden'
				}
			}).setStyles(
				el.getStyles('font-size', 'font-family', 'width', 'line-height')
			).inject(document.body);

			// Also consider the box-sizing
			if (el.getStyle('-moz-box-sizing') == 'border-box' || el.getStyle('-webkit-box-sizing') == 'border-box' || el.getStyle('box-sizing') == 'border-box') {
				dummy.setStyles({
					'padding': el.getStyle('padding'),
					'border': el.getStyle('border-left')
				});
			}

			// Single line height
			var line = Math.max(dummy.clientHeight, 30);

			// Respond to the "input" event
			el.addEvent('input', function() {
				dummy.set('html', this.get('value')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/\n|\r\n/g, '<br>X'));
				var height = Math.max(line, dummy.getSize().y);
				if (this.clientHeight != height) this.tween('height', height);
			}).set('tween', { 'duration':100 }).setStyle('height', line + 'px');

			// Fire the event
			el.fireEvent('input');
			el.store('autogrow', true);
		});
	},

	/**
	 * Set up the menu toggle
	 */
	setupMenuToggle: function() {
		var burger = $('burger');
		if (!burger) return;

		burger
			.addEvent('click', function() {
				document.body.toggleClass('show-navigation');
				burger.setAttribute('aria-expanded', document.body.hasClass('show-navigation') ? 'true' : 'false')
			})
			.addEvent('keydown', function(e) {
				if (e.event.keyCode == 27) {
					document.body.removeClass('show-navigation');
				}
			})
		;

		if (window.matchMedia) {
			var matchMedia = window.matchMedia('(max-width:991px)');
			var setAriaControls = function() {
				if (matchMedia.matches) {
					burger.setAttribute('aria-controls', 'left')
					burger.setAttribute('aria-expanded', document.body.hasClass('show-navigation') ? 'true' : 'false')
				} else {
					burger.removeAttribute('aria-controls');
					burger.removeAttribute('aria-expanded');
				}
			};
			matchMedia.addEventListener('change', setAriaControls);
			setAriaControls();
		}
	},

	/**
	 * Set up the profile toggle
	 */
	setupProfileToggle: function() {
		var tmenu = $('tmenu');
		if (!tmenu) return;

		var li = tmenu.getElement('.submenu'),
			button = li.getFirst('span').getFirst('button'),
			menu = li.getFirst('ul');
		if (!li || !button || !menu) return;

		button.setAttribute('aria-controls', 'tmenu__profile');
		button.setAttribute('aria-expanded', 'false');

		menu.id = 'tmenu__profile';

		button.addEvent('click', function(e) {
			if (li.hasClass('active')) {
				li.removeClass('active');
				button.setAttribute('aria-expanded', 'false');
			} else {
				li.addClass('active');
				button.setAttribute('aria-expanded', 'true');
			}
			e.stopPropagation();
		});

		$(document.body).addEvent('click', function() {
			if (li.hasClass('active')) {
				li.removeClass('active');
			}
		});
	},

	/**
	 * Set up the split button toggle
	 */
	setupSplitButtonToggle: function() {
		var toggle = $('sbtog');
		if (!toggle) return;

		var ul = toggle.getParent('.split-button').getElement('ul'),
			tab, timer;

		toggle.addEvent('click', function(e) {
			tab = false;
			ul.toggleClass('invisible');
			toggle.toggleClass('active');
			e.stopPropagation();
		});

		$(document.body).addEvent('click', function() {
			tab = false;
			ul.addClass('invisible');
			toggle.removeClass('active');
		});

		$(document.body).addEvent('keydown', function(e) {
			tab = (e.event.keyCode == 9);
		});

		[toggle].append(ul.getElements('button')).each(function(el) {
			el.addEvent('focus', function() {
				if (!tab) return;
				ul.removeClass('invisible');
				toggle.addClass('active');
				clearTimeout(timer);
			});

			el.addEvent('blur', function() {
				if (!tab) return;
				timer = setTimeout(function() {
					ul.addClass('invisible');
					toggle.removeClass('active');
				}, 100);
			});
		});

		toggle.set('tabindex', '-1');
	}
};

// Initialize the back end script
window.addEvent('domready', function() {
	$(document.body).addClass('js');

	// Mark touch devices (see #5563)
	if (Browser.Features.Touch) {
		$(document.body).addClass('touch');
	}

	Backend.tableWizardSetWidth();
	Backend.enableImageSizeWidgets();
	Backend.enableToggleSelect();

	Theme.stopClickPropagation();
	Theme.setupCtrlClick();
	Theme.setupTextareaResizing();
	Theme.setupMenuToggle();
	Theme.setupProfileToggle();
	Theme.setupSplitButtonToggle();
});

// Resize the table wizard
window.addEvent('resize', function() {
	Backend.tableWizardSetWidth();
});

// Re-apply certain changes upon ajax_change
window.addEvent('ajax_change', function() {
	Backend.enableImageSizeWidgets();
	Backend.enableToggleSelect();

	Theme.stopClickPropagation();
	Theme.setupCtrlClick();
	Theme.setupTextareaResizing();
});
