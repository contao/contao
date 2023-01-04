/*
---
name: Picker
description: Creates a Picker, which can be used for anything
authors: Arian Stolwijk
requires: [Core/Element.Dimensions, Core/Fx.Tween, Core/Fx.Transitions]
provides: Picker
...
*/

window.Picker = new Class({
    Implements: [Options, Events],

    options: {
        pickerClass: 'datepicker',
        inject: null,
        animationDuration: 400,
        useFadeInOut: true,
        positionOffset: {x: 0, y: 0},
        pickerPosition: 'bottom',
        draggable: true,
        showOnInit: true,
        columns: 1,
        footer: false
    },

    initialize: function(options) {
        this.setOptions(options);
        this.constructPicker();
        if (this.options.showOnInit) this.show();
    },

    constructPicker: function() {
        var options = this.options;

        var picker = this.picker = new Element('div', {
            'class': options.pickerClass,
            styles: {
                left: 0,
                top: 0,
                display: 'none',
                opacity: 0
            }
        }).inject(options.inject || document.body);

        picker.addClass('column_' + options.columns);

        if (options.useFadeInOut) {
            picker.set('tween', {
                duration: options.animationDuration,
                link: 'cancel'
            });
        }

        // Build the header
        var header = this.header = new Element('div.header').inject(picker);
        var title = this.title = new Element('div.title').inject(header);
        var titleID = this.titleID = 'pickertitle-' + String.uniqueID();

        this.titleText = new Element('div', {
            'role': 'heading',
            'class': 'titleText',
            'id': titleID,
            'aria-live': 'assertive',
            'aria-atomic': 'true'
        }).inject(title);

        this.closeButton = new Element('div.closeButton[text=x][role=button]')
            .addEvent('click', this.close.pass(false, this))
            .inject(header);

        // Build the body of the picker
        var body = this.body = new Element('div.body').inject(picker);

        if (options.footer) {
            this.footer = new Element('div.footer').inject(picker);
            picker.addClass('footer');
        }

        // oldContents and newContents are used to slide from the old content to a new one.
        var slider = this.slider = new Element('div.slider', {
            styles: {
                position: 'absolute',
                top: 0,
                left: 0
            }
        }).set('tween', {
            duration: options.animationDuration,
            transition: Fx.Transitions.Quad.easeInOut
        }).inject(body);

        this.newContents = new Element('div', {
            styles: {
                position: 'absolute',
                top: 0,
                left: 0
            }
        }).inject(slider);

        this.oldContents = new Element('div', {
            styles: {
                position: 'absolute',
                top: 0
            }
        }).inject(slider);

        this.originalColumns = options.columns;
        this.setColumns(options.columns);

        // IFrameShim for select fields in IE
        var shim = this.shim = window['IframeShim'] ? new IframeShim(picker) : null;

        // Dragging
        if (options.draggable && typeOf(picker.makeDraggable) == 'function') {
            this.dragger = picker.makeDraggable(shim ? {
                onDrag: shim.position.bind(shim)
            } : null);
            picker.setStyle('cursor', 'move');
        }
    },

    open: function(noFx) {
        if (this.opened == true) return this;
        this.opened = true;

        var self = this,
            picker = this.picker.setStyle('display', 'block').set('aria-hidden', 'false')

        if (this.shim) this.shim.show();

        this.fireEvent('open');

        if (this.options.useFadeInOut && !noFx) {
            picker.get('tween').start('opacity', 1).chain(function() {
                self.fireEvent('show');
                this.callChain();
            });
        } else {
            picker.setStyle('opacity', 1);
            this.fireEvent('show');
        }

        return this;
    },

    show: function() {
        return this.open(true);
    },

    close: function(noFx) {
        if (this.opened == false) return this;
        this.opened = false;

        this.fireEvent('close');

        var self = this, picker = this.picker, hide = function() {
            picker.setStyle('display', 'none').set('aria-hidden', 'true');
            if (self.shim) self.shim.hide();
            self.fireEvent('hide');
        };

        if (this.options.useFadeInOut && !noFx) {
            picker.get('tween').start('opacity', 0).chain(hide);
        } else {
            picker.setStyle('opacity', 0);
            hide();
        }

        return this;
    },

    hide: function() {
        return this.close(true);
    },

    toggle: function() {
        return this[this.opened == true ? 'close' : 'open']();
    },

    destroy: function() {
        this.picker.destroy();
        if (this.shim) this.shim.destroy();
    },

    position: function(x, y) {
        var offset = this.options.positionOffset,
            scroll = document.getScroll(),
            size = document.getSize(),
            pickersize = this.picker.getSize();

        if (typeOf(x) == 'element') {
            var element = x,
                where = y || this.options.pickerPosition;

            var elementCoords = element.getCoordinates();

            x = (where == 'left') ? elementCoords.left - pickersize.x
                : (where == 'bottom' || where == 'top') ? elementCoords.left
                    : elementCoords.right

            y = (where == 'bottom') ? elementCoords.bottom
                : (where == 'top') ? elementCoords.top - pickersize.y
                    : elementCoords.top;
        }

        x += offset.x * ((where && where == 'left') ? -1 : 1);
        y += offset.y * ((where && where == 'top') ? -1 : 1);

        if ((x + pickersize.x) > (size.x + scroll.x)) x = (size.x + scroll.x) - pickersize.x;
        if ((y + pickersize.y) > (size.y + scroll.y)) y = (size.y + scroll.y) - pickersize.y;
        if (x < 0) x = 0;
        if (y < 0) y = 0;

        this.picker.setStyles({
            left: x,
            top: y
        });

        if (this.shim) this.shim.position();

        return this;
    },

    setBodySize: function() {
        var bodysize = this.bodysize = this.body.getSize();

        this.slider.setStyles({
            width: 2 * bodysize.x,
            height: bodysize.y
        });

        this.oldContents.setStyles({
            left: bodysize.x,
            width: bodysize.x,
            height: bodysize.y
        });

        this.newContents.setStyles({
            width: bodysize.x,
            height: bodysize.y
        });
    },

    setColumnContent: function(column, content) {
        var columnElement = this.columns[column];
        if (!columnElement) return this;

        var type = typeOf(content);

        if (['string', 'number'].contains(type)) columnElement.set('text', content);
        else columnElement.empty().adopt(content);

        return this;
    },

    setColumnsContent: function(content, fx) {
        var old = this.columns;

        this.columns = this.newColumns;
        this.newColumns = old;

        content.forEach(function(_content, i) {
            this.setColumnContent(i, _content);
        }, this);

        return this.setContent(null, fx);
    },

    setColumns: function(columns) {
        var _columns = this.columns = new Elements, _newColumns = this.newColumns = new Elements;

        for (var i = columns; i--;) {
            _columns.push(new Element('div.column').addClass('column_' + (columns - i)));
            _newColumns.push(new Element('div.column').addClass('column_' + (columns - i)));
        }

        var oldClass = 'column_' + this.options.columns, newClass = 'column_' + columns;

        this.picker.removeClass(oldClass).addClass(newClass);
        this.options.columns = columns;

        return this;
    },

    setContent: function(content, fx) {
        if (content) return this.setColumnsContent([content], fx);

        // swap contents, so we can fill the newContents again and animate
        var old = this.oldContents;

        this.oldContents = this.newContents;
        this.newContents = old;
        this.newContents.empty();
        this.newContents.adopt(this.columns);
        this.setBodySize();

        if (fx) {
            this.fx(fx);
        } else {
            this.slider.setStyle('left', 0);
            this.oldContents.setStyles({left: 0, opacity: 0});
            this.newContents.setStyles({left: 0, opacity: 1});
        }

        return this;
    },

    fx: function(fx) {
        var oldContents = this.oldContents,
            newContents = this.newContents,
            slider = this.slider,
            bodysize = this.bodysize;

        if (fx == 'right') {
            oldContents.setStyles({left: 0, opacity: 1});
            newContents.setStyles({left: bodysize.x, opacity: 1});
            slider.setStyle('left', 0).tween('left', 0, -bodysize.x);
        } else if (fx == 'left') {
            oldContents.setStyles({left: bodysize.x, opacity: 1});
            newContents.setStyles({left: 0, opacity: 1});
            slider.setStyle('left', -bodysize.x).tween('left', -bodysize.x, 0);
        } else if (fx == 'fade') {
            slider.setStyle('left', 0);

            oldContents.setStyle('left', 0).set('tween', {
                duration: this.options.animationDuration / 2
            }).tween('opacity', 1, 0).get('tween').chain(function() {
                oldContents.setStyle('left', bodysize.x);
            });

            newContents.setStyles({opacity: 0, left: 0}).set('tween', {
                duration: this.options.animationDuration
            }).tween('opacity', 0, 1);
        }
    },

    toElement: function() {
        return this.picker;
    },

    setTitle: function(content, fn) {
        if (!fn) fn = Function.from;

        this.titleText.empty().adopt(
            Array.convert(content).map(function(item, i) {
                return typeOf(item) == 'element'
                    ? item
                    : new Element('div.column', {text: fn(item, this.options)}).addClass('column_' + (i + 1));
            }, this)
        );

        return this;
    },

    setTitleEvent: function(fn) {
        this.titleText.removeEvents('click');
        if (fn) this.titleText.addEvent('click', fn);
        this.titleText.setStyle('cursor', fn ? 'pointer' : '');

        return this;
    }
});

/*
---
name: Picker.Attach
description: Adds attach and detach methods to the Picker, to attach it to element events
authors: Arian Stolwijk
requires: [Picker, Core/Element.Event]
provides: Picker.Attach
...
*/

Picker.Attach = new Class({
    Extends: Picker,

    options: {
        togglesOnly: true, // set to false to always make calendar popup on input element, if true, it depends on the toggles elements set.
        showOnInit: false, // overrides the Picker option
        blockKeydown: true
    },

    initialize: function(attachTo, options) {
        this.parent(options);

        this.attachedEvents = [];
        this.attachedElements = [];
        this.toggles = [];
        this.inputs = [];

        var documentEvent = function(event) {
            if (this.attachedElements.contains(event.target)) return;
            this.close();
        }.bind(this);

        var document = this.picker.getDocument().addEvent('click', documentEvent);

        var preventPickerClick = function(event) {
            event.stopPropagation();
            return false;
        };

        this.picker.addEvent('click', preventPickerClick);

        // Support for deprecated toggleElements
        if (this.options.toggleElements) this.options.toggle = document.getElements(this.options.toggleElements);

        this.attach(attachTo, this.options.toggle);
    },

    attach: function(attachTo, toggle) {
        if (typeOf(attachTo) == 'string') attachTo = document.id(attachTo);
        if (typeOf(toggle) == 'string') toggle = document.id(toggle);

        var elements = Array.convert(attachTo),
            toggles = Array.convert(toggle),
            allElements = [].append(elements).combine(toggles),
            self = this;

        var closeEvent = function(event) {
            var stopInput = self.options.blockKeydown && event.type == 'keydown' && !(['tab', 'esc'].contains(event.key)),
                isCloseKey = event.type == 'keydown' && (['tab', 'esc'].contains(event.key)),
                isA = event.target.get('tag') == 'a';

            if (stopInput || isA) event.preventDefault();
            if (isCloseKey || isA) self.close();
        };

        var getOpenEvent = function(element) {
            return function(event) {
                var tag = event.target.get('tag');
                if (tag == 'input' && event.type == 'click' && !element.match(':focus') || (self.opened && self.input == element)) return;
                if (tag == 'a') event.stop();

                self.position(element);
                self.open();
                self.fireEvent('attached', [event, element]);
            };
        };

        var getToggleEvent = function(open, close) {
            return function(event) {
                if (self.opened) close(event);
                else open(event);
            };
        };

        allElements.each(function(element) {
            // The events are already attached!
            if (self.attachedElements.contains(element)) return;

            var events = {},
                tag = element.get('tag'),
                openEvent = getOpenEvent(element),
                // closeEvent does not have a depency on element
                toggleEvent = getToggleEvent(openEvent, closeEvent);

            if (tag == 'input') {
                // Fix in order to use togglers only
                if (!self.options.togglesOnly || !toggles.length) {
                    events = {
                        focus: openEvent,
                        click: openEvent,
                        keydown: closeEvent
                    };
                }
                self.inputs.push(element);
            } else {
                if (toggles.contains(element)) {
                    self.toggles.push(element);
                    events.click = toggleEvent
                } else {
                    events.click = openEvent;
                }
            }

            element.addEvents(events);
            self.attachedElements.push(element);
            self.attachedEvents.push(events);
        });

        return this;
    },

    detach: function(attachTo, toggle) {
        if (typeOf(attachTo) == 'string') attachTo = document.id(attachTo);
        if (typeOf(toggle) == 'string') toggle = document.id(toggle);

        var elements = Array.convert(attachTo),
            toggles = Array.convert(toggle),
            allElements = [].append(elements).combine(toggles),
            self = this;

        if (!allElements.length) allElements = self.attachedElements;

        allElements.each(function(element) {
            var i = self.attachedElements.indexOf(element);
            if (i < 0) return;

            var events = self.attachedEvents[i];
            element.removeEvents(events);

            delete self.attachedEvents[i];
            delete self.attachedElements[i];

            var toggleIndex = self.toggles.indexOf(element);
            if (toggleIndex != -1) delete self.toggles[toggleIndex];

            var inputIndex = self.inputs.indexOf(element);
            if (toggleIndex != -1) delete self.inputs[inputIndex];
        });

        return this;
    },

    destroy: function() {
        this.detach();
        return this.parent();
    }

});

/*
---
name: Picker.Date
description: Creates a DatePicker, can be used for picking years/months/days and time, or all of them
authors: Arian Stolwijk
requires: [Picker, Picker.Attach, Locale.en-US.DatePicker, More/Locale, More/Date]
provides: Picker.Date
...
*/

(function() {
    Picker.Date = new Class({
        Extends: Picker.Attach,

        options: {
            timePicker: false,
            timePickerOnly: false, // deprecated, use onlyView = 'time'
            timeWheelStep: 1, // 10,15,20,30

            yearPicker: true,
            yearsPerPage: 20,

            startDay: 1, // Sunday (0) through Saturday (6) - be aware that this may affect your layout, since the days on the right might have a different margin
            rtl: false,

            startView: 'days', // allowed values: {time, days, months, years}
            openLastView: false,
            pickOnly: false, // 'years', 'months', 'days', 'time'
            canAlwaysGoUp: ['months', 'days'],
            updateAll: false, // whether or not to update all inputs when selecting a date

            weeknumbers: false,
            titleFormat: '%d %B, %Y', // PATCH

            // if you like to use your own translations
            months_abbr: null,
            days_abbr: null,

            years_title: function(date, options) {
                var year = date.get('year');
                return year + '-' + (year + options.yearsPerPage - 1);
            },

            months_title: function(date) {
                return date.get('year');
            },

            days_title: function(date) {
                return date.format('%b %Y');
            },

            time_title: function(date, options) {
                return (options.pickOnly == 'time') ? Locale.get('DatePicker.select_a_time') : date.format(options.titleFormat); /* PATCH */
            }
        },

        initialize: function(attachTo, options) {
            this.parent(attachTo, options);

            this.setOptions(options);
            options = this.options;

            // If we only want to use one picker / backwards compatibility
            ['year', 'month', 'day', 'time'].some(function(what) {
                if (options[what + 'PickerOnly']) {
                    options.pickOnly = what;
                    return true;
                }
                return false;
            });

            if (options.pickOnly) {
                options[options.pickOnly + 'Picker'] = true;
                options.startView = options.pickOnly;
            }

            // backward compatibility for startView
            var newViews = ['days', 'months', 'years'];

            ['month', 'year', 'decades'].some(function(what, i) {
                return (options.startView == what) && (options.startView = newViews[i]);
            });

            options.canAlwaysGoUp = options.canAlwaysGoUp ? Array.convert(options.canAlwaysGoUp) : [];

            // Set the min and max dates as Date objects
            if (options.minDate) {
                if (!(options.minDate instanceof Date)) options.minDate = Date.parse(options.minDate);
                options.minDate.clearTime();
            }

            if (options.maxDate) {
                if (!(options.maxDate instanceof Date)) options.maxDate = Date.parse(options.maxDate);
                options.maxDate.clearTime();
            }

            if (!options.format) {
                options.format = (options.pickOnly != 'time') ? Locale.get('Date.shortDate') : '';
                if (options.timePicker) options.format = (options.format) + (options.format ? ' ' : '') + Locale.get('Date.shortTime');
            }

            // Some link or input has fired an event!
            this.addEvent('attached', function(event, element) {
                // This is where we store the selected date
                if (!this.currentView || !options.openLastView) this.currentView = options.startView;

                this.date = limitDate(new Date(), options.minDate, options.maxDate);

                var tag = element.get('tag'), input;

                if (tag == 'input') {
                    input = element;
                } else {
                    var index = this.toggles.indexOf(element);
                    if (this.inputs[index]) input = this.inputs[index];
                }

                this.getInputDate(input);
                this.input = input;
                this.setColumns(this.originalColumns);
            }.bind(this), true);
        },

        getInputDate: function(input) {
            this.date = new Date();
            if (!input) return;

            var date = Date.parse(input.get('value'));

            if (date == null || !date.isValid()) {
                var storeDate = input.retrieve('datepicker:value');
                if (storeDate) date = Date.parse(storeDate);
            }

            if (date != null && date.isValid()) this.date = date;
        },

        // Control the previous and next elements
        constructPicker: function() {
            this.parent();

            if (!this.options.rtl) {
                this.previous = new Element('div.previous[html=&#171;]').inject(this.header);
                this.next = new Element('div.next[html=&#187;]').inject(this.header);
            } else {
                this.next = new Element('div.previous[html=&#171;]').inject(this.header);
                this.previous = new Element('div.next[html=&#187;]').inject(this.header);
            }
        },

        hidePrevious: function(_next, _show) {
            this[_next ? 'next' : 'previous'].setStyle('display', _show ? 'block' : 'none');
            return this;
        },

        showPrevious: function(_next) {
            return this.hidePrevious(_next, true);
        },

        setPreviousEvent: function(fn, _next) {
            this[_next ? 'next' : 'previous'].removeEvents('click');
            if (fn) this[_next ? 'next' : 'previous'].addEvent('click', fn);

            return this;
        },

        hideNext: function() {
            return this.hidePrevious(true);
        },

        showNext: function() {
            return this.showPrevious(true);
        },

        setNextEvent: function(fn) {
            return this.setPreviousEvent(fn, true);
        },

        setColumns: function(columns, view, date, viewFx) {
            var ret = this.parent(columns), method;

            if ((view || this.currentView)
                && (method = 'render' + (view || this.currentView).capitalize())
                && this[method]
            ) this[method](date || this.date.clone(), viewFx);

            return ret;
        },

        // Render the Pickers
        renderYears: function(date, fx) {
            var options = this.options, pages = options.columns, perPage = options.yearsPerPage,
                _columns = [], _dates = [];

            this.dateElements = [];

            // start neatly at interval (e.g. 1980 instead of 1987)
            date = date.clone().decrement('year', date.get('year') % perPage);

            var iterateDate = date.clone().decrement('year', Math.floor((pages - 1) / 2) * perPage);

            for (var i = pages; i--;) {
                var _date = iterateDate.clone();
                _dates.push(_date);

                _columns.push(renderers.years(
                    timesSelectors.years(options, _date.clone()),
                    options,
                    this.date.clone(),
                    this.dateElements,
                    function(date) {
                        if (options.pickOnly == 'years') this.select(date);
                        else this.renderMonths(date, 'fade');
                        this.date = date;
                    }.bind(this)
                ));

                iterateDate.increment('year', perPage);
            }

            this.setColumnsContent(_columns, fx);
            this.setTitle(_dates, options.years_title);

            // Set limits
            var limitLeft = (options.minDate && date.get('year') <= options.minDate.get('year')),
                limitRight = (options.maxDate && (date.get('year') + options.yearsPerPage) >= options.maxDate.get('year'));

            this[(limitLeft ? 'hide' : 'show') + 'Previous']();
            this[(limitRight ? 'hide' : 'show') + 'Next']();

            this.setPreviousEvent(function() {
                this.renderYears(date.decrement('year', perPage), 'left');
            }.bind(this));

            this.setNextEvent(function() {
                this.renderYears(date.increment('year', perPage), 'right');
            }.bind(this));

            // We can't go up!
            this.setTitleEvent(null);

            this.currentView = 'years';
        },

        renderMonths: function(date, fx) {
            var options = this.options, years = options.columns, _columns = [], _dates = [],
                iterateDate = date.clone().decrement('year', Math.floor((years - 1) / 2));

            this.dateElements = [];

            for (var i = years; i--;) {
                var _date = iterateDate.clone();
                _dates.push(_date);

                _columns.push(renderers.months(
                    timesSelectors.months(options, _date.clone()),
                    options,
                    this.date.clone(),
                    this.dateElements,
                    function(date) {
                        if (options.pickOnly == 'months') this.select(date);
                        else this.renderDays(date, 'fade');
                        this.date = date;
                    }.bind(this)
                ));

                iterateDate.increment('year', 1);
            }

            this.setColumnsContent(_columns, fx);
            this.setTitle(_dates, options.months_title);

            // Set limits
            var year = date.get('year'),
                limitLeft = (options.minDate && year <= options.minDate.get('year')),
                limitRight = (options.maxDate && year >= options.maxDate.get('year'));

            this[(limitLeft ? 'hide' : 'show') + 'Previous']();
            this[(limitRight ? 'hide' : 'show') + 'Next']();

            this.setPreviousEvent(function() {
                this.renderMonths(date.decrement('year', years), 'left');
            }.bind(this));

            this.setNextEvent(function() {
                this.renderMonths(date.increment('year', years), 'right');
            }.bind(this));

            var canGoUp = options.yearPicker && (options.pickOnly != 'months' || options.canAlwaysGoUp.contains('months'));

            var titleEvent = (canGoUp) ? function() {
                this.renderYears(date, 'fade');
            }.bind(this) : null;

            this.setTitleEvent(titleEvent);

            this.currentView = 'months';
        },

        renderDays: function(date, fx) {
            var options = this.options, months = options.columns, _columns = [], _dates = [],
                iterateDate = date.clone().decrement('month', Math.floor((months - 1) / 2));

            this.dateElements = [];

            for (var i = months; i--;) {
                _date = iterateDate.clone();
                _dates.push(_date);

                _columns.push(renderers.days(
                    timesSelectors.days(options, _date.clone()),
                    options,
                    this.date.clone(),
                    this.dateElements,
                    function(date) {
                        if (options.pickOnly == 'days' || !options.timePicker) this.select(date)
                        else this.renderTime(date, 'fade');
                        this.date = date;
                    }.bind(this)
                ));

                iterateDate.increment('month', 1);
            }

            this.setColumnsContent(_columns, fx);
            this.setTitle(_dates, options.days_title);

            var yearmonth = date.format('%Y%m').toInt(),
                limitLeft = (options.minDate && yearmonth <= options.minDate.format('%Y%m')),
                limitRight = (options.maxDate && yearmonth >= options.maxDate.format('%Y%m'));

            this[(limitLeft ? 'hide' : 'show') + 'Previous']();
            this[(limitRight ? 'hide' : 'show') + 'Next']();

            this.setPreviousEvent(function() {
                this.renderDays(date.decrement('month', months), 'left');
            }.bind(this));

            this.setNextEvent(function() {
                this.renderDays(date.increment('month', months), 'right');
            }.bind(this));

            var canGoUp = options.pickOnly != 'days' || options.canAlwaysGoUp.contains('days');

            var titleEvent = (canGoUp) ? function() {
                this.renderMonths(date, 'fade');
            }.bind(this) : null;

            this.setTitleEvent(titleEvent);

            this.currentView = 'days';
        },

        renderTime: function(date, fx) {
            var options = this.options;
            this.setTitle(date, options.time_title);

            var originalColumns = this.originalColumns = options.columns;
            this.currentView = null; // otherwise you'd get crazy recursion

            if (originalColumns != 1) this.setColumns(1);

            this.setContent(renderers.time(
                options,
                date.clone(),
                function(date) {
                    this.select(date);
                }.bind(this)
            ), fx);

            // Hide « and » buttons
            this.hidePrevious()
                .hideNext()
                .setPreviousEvent(null)
                .setNextEvent(null);

            var canGoUp = options.pickOnly != 'time' || options.canAlwaysGoUp.contains('time');

            var titleEvent = (canGoUp) ? function() {
                this.setColumns(originalColumns, 'days', date, 'fade');
            }.bind(this) : null;

            this.setTitleEvent(titleEvent);

            this.currentView = 'time';
        },

        select: function(date, all) {
            this.date = date;

            var formatted = date.format(this.options.format),
                time = date.strftime(),
                inputs = (!this.options.updateAll && !all && this.input) ? [this.input] : this.inputs;

            inputs.each(function(input) {
                input.set('value', formatted).store('datepicker:value', time).fireEvent('change');
            }, this);

            this.fireEvent('select', [date].concat(inputs));
            this.close();

            return this;
        }
    });


    // Renderers only output elements and calculate the limits!
    var timesSelectors = {
        years: function(options, date) {
            var times = [];

            for (var i = 0; i < options.yearsPerPage; i++) {
                times.push(+date);
                date.increment('year', 1);
            }

            return times;
        },

        months: function(options, date) {
            var times = [];
            date.set('month', 0);

            for (var i = 0; i <= 11; i++) {
                times.push(+date);
                date.increment('month', 1);
            }

            return times;
        },

        days: function(options, date) {
            var times = [];
            date.set('date', 1);

            while (date.get('day') != options.startDay) date.set('date', date.get('date') - 1);

            for (var i = 0; i < 42; i++) {
                times.push(+date);
                date.increment('day', 1);
            }

            return times;
        }
    };

    var renderers = {
        years: function(years, options, currentDate, dateElements, fn) {
            var container = new Element('table.years'),
                today = new Date(),
                rows = [],
                element, classes;

            years.each(function(_year, i) {
                var date = new Date(_year), year = date.get('year');

                if (i % 4 === 0) {
                    rows.push(new Element('tr'));
                    rows[rows.length - 1].inject(container)
                }

                classes = '.year.year' + i;

                if (year == today.get('year')) classes += '.today';
                if (year == currentDate.get('year')) classes += '.selected';

                element = new Element('td' + classes, {text: year}).inject(rows[rows.length - 1]);
                dateElements.push({element: element, time: _year});

                if (isUnavailable('year', date, options)) element.addClass('unavailable');
                else element.addEvent('click', fn.pass(date));
            });

            return container;
        },

        months: function(months, options, currentDate, dateElements, fn) {
            var today = new Date(),
                month = today.get('month'),
                thisyear = today.get('year'),
                selectedyear = currentDate.get('year'),
                container = new Element('table.months'),
                monthsAbbr = options.months_abbr || Locale.get('Date.months_abbr'),
                rows = [],
                element, classes;

            months.each(function(_month, i) {
                var date = new Date(_month), year = date.get('year');

                if (i % 3 === 0) {
                    rows.push(new Element('tr'));
                    rows[rows.length - 1].inject(container)
                }

                classes = '.month.month' + (i + 1);

                if (i == month && year == thisyear) classes += '.today';
                if (i == currentDate.get('month') && year == selectedyear) classes += '.selected';

                element = new Element('td' + classes, {text: monthsAbbr[i]}).inject(rows[rows.length - 1]);
                dateElements.push({element: element, time: _month});

                if (isUnavailable('month', date, options)) element.addClass('unavailable');
                else element.addEvent('click', fn.pass(date));
            });

            return container;
        },

        days: function(days, options, currentDate, dateElements, fn) {
            var month = new Date(days[14]).get('month'),
                todayString = new Date().toDateString(),
                currentString = currentDate.toDateString(),
                weeknumbers = options.weeknumbers,
                container = new Element('table.days' + (weeknumbers ? '.weeknumbers' : ''), {
                    role: 'grid', 'aria-labelledby': this.titleID
                }),
                header = new Element('thead').inject(container),
                body = new Element('tbody').inject(container),
                titles = new Element('tr.titles').inject(header),
                localeDaysShort = options.days_abbr || Locale.get('Date.days_abbr'),
                day, classes, element, weekcontainer, dateString,
                where = options.rtl ? 'top' : 'bottom';

            if (weeknumbers) new Element('th.title.day.weeknumber', {
                text: Locale.get('DatePicker.week')
            }).inject(titles);

            for (day = options.startDay; day < (options.startDay + 7); day++) {
                new Element('th.title.day.day' + (day % 7), {
                    text: localeDaysShort[(day % 7)],
                    role: 'columnheader'
                }).inject(titles, where);
            }

            days.each(function(_date, i) {
                var date = new Date(_date);

                if (i % 7 == 0) {
                    weekcontainer = new Element('tr.week.week' + (Math.floor(i / 7))).set('role', 'row').inject(body);

                    if (weeknumbers) new Element('th.day.weeknumber', {
                        text: date.get('week'),
                        scope: 'row',
                        role: 'rowheader'
                    }).inject(weekcontainer);
                }

                dateString = date.toDateString();
                classes = '.day.day' + date.get('day');

                if (dateString == todayString) classes += '.today';
                if (date.get('month') != month) classes += '.otherMonth';

                element = new Element('td' + classes, {
                    text: date.getDate(),
                    role: 'gridcell'
                }).inject(weekcontainer, where);

                if (dateString == currentString) element.addClass('selected').set('aria-selected', 'true');
                else element.set('aria-selected', 'false');

                dateElements.push({element: element, time: _date});

                if (isUnavailable('date', date, options)) element.addClass('unavailable');
                else element.addEvent('click', fn.pass(date.clone()));
            });

            return container;
        },

        time: function(options, date, fn) {
            var container = new Element('div.time'),
                // make sure that the minutes are timeWheelStep * k
                initMinutes = (date.get('minutes') / options.timeWheelStep).round() * options.timeWheelStep

            if (initMinutes >= 60) initMinutes = 0;
            date.set('minutes', initMinutes);

            var hoursInput = new Element('input.hour[type=text]', {
                title: Locale.get('DatePicker.use_mouse_wheel'),
                value: date.format('%H'),
                events: {
                    click: function(event) {
                        event.target.focus();
                        event.stop();
                    },
                    mousewheel: function(event) {
                        event.stop();
                        hoursInput.focus();

                        var value = hoursInput.get('value').toInt();

                        value = (event.wheel > 0) ? ((value < 23) ? value + 1 : 0)
                            : ((value > 0) ? value - 1 : 23)

                        date.set('hours', value);
                        hoursInput.set('value', date.format('%H'));
                    }.bind(this)
                },
                maxlength: 2
            }).inject(container);

            new Element('div.separator[text=:]').inject(container);

            var minutesInput = new Element('input.minutes[type=text]', {
                title: Locale.get('DatePicker.use_mouse_wheel'),
                value: date.format('%M'),
                events: {
                    click: function(event) {
                        event.target.focus();
                        event.stop();
                    },
                    mousewheel: function(event) {
                        event.stop();
                        minutesInput.focus();

                        var value = minutesInput.get('value').toInt();

                        value = (event.wheel > 0) ? ((value < 59) ? (value + options.timeWheelStep) : 0)
                            : ((value > 0) ? (value - options.timeWheelStep) : (60 - options.timeWheelStep));

                        if (value >= 60) value = 0;

                        date.set('minutes', value);
                        minutesInput.set('value', date.format('%M'));
                    }.bind(this)
                },
                maxlength: 2
            }).inject(container);


            new Element('input.ok', {
                'type': 'submit',
                value: Locale.get('DatePicker.time_confirm_button'),
                events: {
                    click: function(event) {
                        event.stop();
                        date.set({
                            hours: hoursInput.get('value').toInt(),
                            minutes: minutesInput.get('value').toInt()
                        });
                        fn(date.clone());
                    }
                }
            }).inject(container);

            return container;
        }
    };

    Picker.Date.defineRenderer = function(name, fn) {
        renderers[name] = fn;
        return this;
    };

    Picker.Date.getRenderer = function(name) {
        return renderers[name];
    }

    var limitDate = function(date, min, max) {
        if (min && date < min) return min;
        if (max && date > max) return max;
        return date;
    };

    var isUnavailable = function(type, date, options) {
        var minDate = options.minDate,
            maxDate = options.maxDate,
            availableDates = options.availableDates,
            year, month, day, ms;

        if (!minDate && !maxDate && !availableDates) return false;
        date.clearTime();

        if (type == 'year') {
            year = date.get('year');

            return (
                (minDate && year < minDate.get('year')) ||
                (maxDate && year > maxDate.get('year')) ||
                (
                    (availableDates != null && !options.invertAvailable) && (
                        availableDates[year] == null ||
                        Object.getLength(availableDates[year]) == 0 ||
                        Object.getLength(
                            Object.filter(availableDates[year], function(days) {
                                return (days.length > 0);
                            })
                        ) == 0
                    )
                )
            );
        }

        if (type == 'month') {
            year = date.get('year');
            month = date.get('month') + 1;
            ms = date.format('%Y%m').toInt();

            return (
                (minDate && ms < minDate.format('%Y%m').toInt()) ||
                (maxDate && ms > maxDate.format('%Y%m').toInt()) ||
                (
                    (availableDates != null && !options.invertAvailable) && (
                        availableDates[year] == null ||
                        availableDates[year][month] == null ||
                        availableDates[year][month].length == 0
                    )
                )
            );
        }

        // type == 'date'
        year = date.get('year');
        month = date.get('month') + 1;
        day = date.get('date');

        var dateAllow = (minDate && date < minDate) || (maxDate && date > maxDate);

        if (availableDates != null) {
            dateAllow = dateAllow
                || availableDates[year] == null
                || availableDates[year][month] == null
                || !availableDates[year][month].contains(day);
            if (options.invertAvailable) dateAllow = !dateAllow;
        }

        return dateAllow;
    };
})();
