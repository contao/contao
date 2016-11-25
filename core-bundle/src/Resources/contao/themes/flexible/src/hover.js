/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

var Theme = {

	/**
	 * Check for WebKit
	 * @member {boolean}
 	 */
	isWebkit: (Browser.chrome || Browser.safari || navigator.userAgent.match(/(?:webkit|khtml)/i)),

	/**
	 * Colorize a table row when hovering over it
	 *
	 * @param {object} el    The DOM element
	 * @param {int}    state The current state
	 *
	 * @deprecated The Theme.hoverRow() function has been deprecated in Contao 4 and will be removed in Contao 5.
	 *             Assign the CSS class "hover-row" instead.
	 */
	hoverRow: function(el, state) {
		var items = $(el).getChildren();
		for (var i=0; i<items.length; i++) {
			if (items[i].nodeName.toLowerCase() == 'td') {
				items[i].setStyle('background-color', (state ? '#ebfdd7' : ''));
			}
		}
		console.info('The Theme.hoverRow() function has been deprecated in Contao 4 and will be removed in Contao 5. Assign the CSS class "hover-row" instead.');
	},

	/**
	 * Colorize a layer when hovering over it
	 *
	 * @param {object} el    The DOM element
	 * @param {int}    state The current state
	 *
	 * @deprecated The Theme.hoverDiv() function has been deprecated in Contao 4 and will be removed in Contao 5.
	 *             Assign the CSS class "hover-div" instead.
	 */
	hoverDiv: function(el, state) {
		if (!state) {
			el.removeAttribute('data-visited');
		}
		$(el).setStyle('background-color', (state ? '#ebfdd7' : ''));
		console.info('The Theme.hoverDiv() function has been deprecated in Contao 4 and will be removed in Contao 5. Assign the CSS class "hover-div" instead.');
	},

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
					if (key && e.event.shiftKey) {
						el.getElements('a').each(function(a) {
							if (a.hasClass('editheader')) {
								document.location.href = a.href;
							}
						});
					} else if (key) {
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
				var height = Math.max(line, dummy.getSize().y + 2);
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
			.addEvent('click', function(e) {
				document.body.toggleClass('show-navigation');
			})
			.addEvent('keydown', function(e) {
				if (e.event.keyCode == 27) {
					document.body.toggleClass('show-navigation');
				}
			})
		;
	},

	/**
	 * Hide the menu on scroll
	 */
	hideMenuOnScroll: function() {
		if (!('ontouchmove' in window)) return;

		var wh = window.getSize().y,
			dh = window.getScrollSize().y - wh,
			anchor = 0;

		if (wh >= dh) return;

		window
			.addEvent('touchmove', function() {
				var ws = window.getScroll().y;

				if (Math.abs(anchor - ws) < 20) return;

				if (ws > 0 && ws > anchor) {
					$('header').addClass('down');
				} else {
					$('header').removeClass('down');
				}

				anchor = ws;
			})
			.addEvent('scroll', function() {
				if (window.getScroll().y < 1) {
					$('header').removeClass('down');
				}
			})
		;
	},

	/**
	 * Set up the split button toggle
	 */
	setupSplitButtonToggle: function() {
		var toggle = $('sbtog');
		if (!toggle) return;

		toggle.addEvent('click', function(e) {
			toggle.getParent('.split-button').getElement('ul').toggleClass('invisible');
			e.stopPropagation();
		});

		$(document.body).addEvent('click', function() {
			toggle.getParent('.split-button').getElement('ul').addClass('invisible');
		});
	}
};

// Initialize
window.addEvent('domready', function() {
	Theme.stopClickPropagation();
	Theme.setupCtrlClick();
	Theme.setupTextareaResizing();
	Theme.setupMenuToggle();
	Theme.hideMenuOnScroll();
	Theme.setupSplitButtonToggle();
});

// Respond to Ajax changes
window.addEvent('ajax_change', function() {
	Theme.stopClickPropagation();
	Theme.setupCtrlClick();
	Theme.setupTextareaResizing();
});
