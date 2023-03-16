/*
---

name: Request.Contao

description: Extends the MooTools Request.JSON class with Contao-specific routines.

license: LGPLv3

authors:
 - Leo Feyer

requires: [Request, JSON]

provides: Request.Contao

...
*/

Request.Contao = new Class(
{
	Extends: Request.JSON,

	options: {
		followRedirects: true,
		url: window.location.href
	},

	initialize: function(options) {
		if (options && !options.url && options.field && options.field.form && options.field.form.action) {
			this.options.url = options.field.form.action;
		}
		this.parent(options);
	},

	success: function(text) {
		var url = this.getHeader('X-Ajax-Location'),
			json;

		if (url && this.options.followRedirects) {
			location.replace(url);
			return;
		}

		// Support both plain text and JSON responses
		try {
			json = this.response.json = JSON.decode(text, this.options.secure);
		} catch(e) {
			json = {'content':text};
		}

		// Empty response
		if (json === null) {
			json = {'content':''};
		} else if (typeof(json) != 'object') {
			json = {'content':text};
		}

		// Isolate scripts and execute them
		if (json.content != '') {
			json.content = json.content.stripScripts(function(script) {
				json.javascript = script.replace(/<!--|\/\/-->|<!\[CDATA\[\/\/>|<!]]>/g, '');
			});
			if (json.javascript && this.options.evalScripts) {
				Browser.exec(json.javascript);
			}
		}

		this.onSuccess(json.content, json);
	},

	failure: function() {
		var url = this.getHeader('X-Ajax-Location');

		if (url && 401 === this.status) {
			location.replace(url);
			return;
		}

		if (url && this.options.followRedirects && this.status >= 300 && this.status < 400) {
			location.replace(url);
			return;
		}

		this.onFailure();
	}
});


/*
---

name: Drag

description: Extends the base Drag class with touch support.

license: LGPLv3

authors:
 - Andreas Schempp

requires: [Drag]

provides: Drag

...
*/

Class.refactor(Drag,
{
	attach: function() {
		this.handles.addEvent('touchstart', this.bound.start);
		return this.previous.apply(this, arguments);
	},

	detach: function() {
		this.handles.removeEvent('touchstart', this.bound.start);
		return this.previous.apply(this, arguments);
	},

	start: function() {
		document.addEvents({
			touchmove: this.bound.check,
			touchend: this.bound.cancel
		});
		this.previous.apply(this, arguments);
	},

	check: function(event) {
		if (this.options.preventDefault) event.preventDefault();
		var distance = Math.round(Math.sqrt(Math.pow(event.page.x - this.mouse.start.x, 2) + Math.pow(event.page.y - this.mouse.start.y, 2)));
		if (distance > this.options.snap) {
			this.cancel();
			this.document.addEvents({
				mousemove: this.bound.drag,
				mouseup: this.bound.stop
			});
			document.addEvents({
				touchmove: this.bound.drag,
				touchend: this.bound.stop
			});
			this.fireEvent('start', [this.element, event]).fireEvent('snap', this.element);
		}
	},

	cancel: function() {
		document.removeEvents({
			touchmove: this.bound.check,
			touchend: this.bound.cancel
		});
		return this.previous.apply(this, arguments);
	},

	stop: function() {
		document.removeEvents({
			touchmove: this.bound.drag,
			touchend: this.bound.stop
		});
		return this.previous.apply(this, arguments);
	}
});

/*
---

name: Sortables

description: Extends the base Sortables class with touch support.

license: LGPLv3

authors:
 - Andreas Schempp

requires: [Sortables]

provides: Sortables

...
*/

Class.refactor(Sortables,
{
	initialize: function(lists, options) {
		options.dragOptions = Object.merge(options.dragOptions || {}, { preventDefault: (options.dragOptions && options.dragOptions.preventDefault) || Browser.Features.Touch });
		if (options.dragOptions.unDraggableTags === undefined) {
			options.dragOptions.unDraggableTags = this.options.unDraggableTags.filter(function(tag) {
				return tag != 'button';
			});
		}
		return this.previous.apply(this, arguments);
	},

	addItems: function() {
		Array.flatten(arguments).each(function(element) {
			this.elements.push(element);
			var start = element.retrieve('sortables:start', function(event) {
				this.start.call(this, event, element);
			}.bind(this));
			(this.options.handle ? element.getElement(this.options.handle) || element : element).addEvents({
				mousedown: start,
				touchstart: start
			});
		}, this);
		return this;
	},

	removeItems: function() {
		return $$(Array.flatten(arguments).map(function(element) {
			this.elements.erase(element);
			var start = element.retrieve('sortables:start');
			(this.options.handle ? element.getElement(this.options.handle) || element : element).removeEvents({
				mousedown: start,
				touchend: start
			});
			return element;
		}, this));
	},

	getClone: function(event, element) {
		if (!this.options.clone) return new Element(element.tagName).inject(document.body);
		if (typeOf(this.options.clone) == 'function') return this.options.clone.call(this, event, element, this.list);
		var clone = this.previous.apply(this, arguments);
		clone.addEvent('touchstart', function(event) {
			element.fireEvent('touchstart', event);
		});
		return clone;
	}
});

/*
---

script: Request.Queue.js

name: Request.Queue

description: Extends the base Request.Queue class and attempts to fix some issues.

license: MIT-style license

authors:
 - Leo Feyer

requires:
	- Core/Element
	- Core/Request
	- Class.Binds

provides: [Request.Queue]

...
*/

Class.refactor(Request.Queue,
{
	// Do not fire the "end" event here
	onComplete: function(){
		this.fireEvent('complete', arguments);
	},

	// Call resume() instead of runNext()
	onCancel: function(){
		if (this.options.autoAdvance && !this.error) this.resume();
		this.fireEvent('cancel', arguments);
	},

	// Call resume() instead of runNext() and fire the "end" event
	onSuccess: function(){
		if (this.options.autoAdvance && !this.error) this.resume();
		this.fireEvent('success', arguments);
		if (!this.queue.length && !this.isRunning()) this.fireEvent('end');
	},

	// Call resume() instead of runNext() and fire the "end" event
	onFailure: function(){
		this.error = true;
		if (!this.options.stopOnFailure && this.options.autoAdvance) this.resume();
		this.fireEvent('failure', arguments);
		if (!this.queue.length && !this.isRunning()) this.fireEvent('end');
	},

	// Call resume() instead of runNext()
	onException: function(){
		this.error = true;
		if (!this.options.stopOnFailure && this.options.autoAdvance) this.resume();
		this.fireEvent('exception', arguments);
	}
});

/*
---

name: Contao.SerpPreview

description: Generates a SERP preview

license: LGPLv3

authors:
 - Leo Feyer

requires: [Request, JSON]

provides: Contao.SerpPreview

...
*/

Contao.SerpPreview = new Class(
{
	options: {
		id: 0,
		trail: null,
		titleField: null,
		titleFallbackField: null,
		aliasField: null,
		descriptionField: null,
		descriptionFallbackField: null,
		titleTag: null
	},

	shorten: function(str, max) {
		if (str.length <= max) {
			return str;
		}
		return str.substr(0, str.lastIndexOf(' ', max)) + ' …';
	},

	html2string: function(html) {
		return new DOMParser().parseFromString(html, 'text/html').body.textContent;
	},

	getTinymce: function() {
		if (window.tinyMCE && this.options.descriptionFallbackField) {
			return window.tinyMCE.get(this.options.descriptionFallbackField);
		}
	},

	initialize: function() {
		this.options = Object.merge.apply(null, [{}, this.options].append(arguments));

		var serpTitle = $('serp_title_' + this.options.id),
			serpUrl = $('serp_url_' + this.options.id),
			serpDescription = $('serp_description_' + this.options.id),
			titleField = $(this.options.titleField),
			titleFallbackField = $(this.options.titleFallbackField),
			aliasField = $(this.options.aliasField),
			descriptionField = $(this.options.descriptionField),
			descriptionFallbackField = $(this.options.descriptionFallbackField),
			indexEmpty = this.options.trail.indexOf('›') === -1,
			titleTag = this.options.titleTag || '%s';

		titleField && titleField.addEvent('input', function() {
			if (titleField.value) {
				serpTitle.set('text', this.shorten(titleTag.replace(/%s/, titleField.value).replace(/%%/g, '%').replace(/\[-\]/g, '\xAD').replace(/\[nbsp\]/g, '\xA0'), 64));
			} else if (titleFallbackField && titleFallbackField.value) {
				serpTitle.set('text', this.shorten(this.html2string(titleTag.replace(/%s/, titleFallbackField.value)).replace(/%%/g, '%').replace(/\[-\]/g, '\xAD').replace(/\[nbsp\]/g, '\xA0'), 64));
			} else {
				serpTitle.set('text', '');
			}
		}.bind(this));

		titleFallbackField && titleFallbackField.addEvent('input', function() {
			if (titleField && titleField.value) return;
			serpTitle.set('text', this.shorten(this.html2string(titleTag.replace(/%s/, titleFallbackField.value)).replace(/%%/g, '%').replace(/\[-\]/g, '\xAD').replace(/\[nbsp\]/g, '\xA0'), 64));
		}.bind(this));

		aliasField && aliasField.addEvent('input', function() {
			if (aliasField.value == 'index' && indexEmpty) {
				serpUrl.set('text', this.options.trail);
			} else {
				serpUrl.set('text', this.options.trail + ' › ' + (aliasField.value || this.options.id).replace(/\//g, ' › '));
			}
		}.bind(this));

		descriptionField && descriptionField.addEvent('input', function() {
			if (descriptionField.value) {
				serpDescription.set('text', this.shorten(descriptionField.value, 160));
				return;
			}
			var editor = this.getTinymce();
			if (editor) {
				serpDescription.set('text', this.shorten(this.html2string(editor.getContent()), 160));
			} else if (descriptionFallbackField && descriptionFallbackField.value) {
				serpDescription.set('text', this.shorten(this.html2string(descriptionFallbackField.value), 160));
			} else {
				serpDescription.set('text', '');
			}
		}.bind(this));

		descriptionFallbackField && descriptionFallbackField.addEvent('input', function() {
			if (descriptionField && descriptionField.value) return;
			serpDescription.set('text', this.shorten(this.html2string(descriptionFallbackField.value), 160));
		}.bind(this));

		setTimeout(function() {
			var editor = this.getTinymce();
			editor && editor.on('keyup', function() {
				if (descriptionField && descriptionField.value) return;
				serpDescription.set('text', this.shorten(this.html2string(window.tinyMCE.activeEditor.getContent()), 160));
			}.bind(this));
		}.bind(this), 4);
	}
});
