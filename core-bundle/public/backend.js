/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./core-bundle/assets/controllers sync recursive \\.js$"
/*!****************************************************!*\
  !*** ./core-bundle/assets/controllers/ sync \.js$ ***!
  \****************************************************/
(module, __unused_webpack_exports, __webpack_require__) {

var map = {
	"./clipboard-controller.js": "./core-bundle/assets/controllers/clipboard-controller.js",
	"./color-scheme-controller.js": "./core-bundle/assets/controllers/color-scheme-controller.js",
	"./image-size-controller.js": "./core-bundle/assets/controllers/image-size-controller.js",
	"./jump-targets-controller.js": "./core-bundle/assets/controllers/jump-targets-controller.js",
	"./limit-height-controller.js": "./core-bundle/assets/controllers/limit-height-controller.js",
	"./metawizard-controller.js": "./core-bundle/assets/controllers/metawizard-controller.js",
	"./scroll-offset-controller.js": "./core-bundle/assets/controllers/scroll-offset-controller.js",
	"./toggle-fieldset-controller.js": "./core-bundle/assets/controllers/toggle-fieldset-controller.js",
	"./toggle-navigation-controller.js": "./core-bundle/assets/controllers/toggle-navigation-controller.js",
	"./toggle-nodes-controller.js": "./core-bundle/assets/controllers/toggle-nodes-controller.js"
};


function webpackContext(req) {
	var id = webpackContextResolve(req);
	return __webpack_require__(id);
}
function webpackContextResolve(req) {
	if(!__webpack_require__.o(map, req)) {
		var e = new Error("Cannot find module '" + req + "'");
		e.code = 'MODULE_NOT_FOUND';
		throw e;
	}
	return map[req];
}
webpackContext.keys = function webpackContextKeys() {
	return Object.keys(map);
};
webpackContext.resolve = webpackContextResolve;
module.exports = webpackContext;
webpackContext.id = "./core-bundle/assets/controllers sync recursive \\.js$";

/***/ },

/***/ "./node_modules/@hotwired/stimulus-webpack-helpers/dist/stimulus-webpack-helpers.js"
/*!******************************************************************************************!*\
  !*** ./node_modules/@hotwired/stimulus-webpack-helpers/dist/stimulus-webpack-helpers.js ***!
  \******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   definitionForModuleAndIdentifier: () => (/* binding */ definitionForModuleAndIdentifier),
/* harmony export */   definitionForModuleWithContextAndKey: () => (/* binding */ definitionForModuleWithContextAndKey),
/* harmony export */   definitionsFromContext: () => (/* binding */ definitionsFromContext),
/* harmony export */   identifierForContextKey: () => (/* binding */ identifierForContextKey)
/* harmony export */ });
/*
Stimulus Webpack Helpers 1.0.0
Copyright © 2021 Basecamp, LLC
 */
function definitionsFromContext(context) {
    return context.keys()
        .map((key) => definitionForModuleWithContextAndKey(context, key))
        .filter((value) => value);
}
function definitionForModuleWithContextAndKey(context, key) {
    const identifier = identifierForContextKey(key);
    if (identifier) {
        return definitionForModuleAndIdentifier(context(key), identifier);
    }
}
function definitionForModuleAndIdentifier(module, identifier) {
    const controllerConstructor = module.default;
    if (typeof controllerConstructor == "function") {
        return { identifier, controllerConstructor };
    }
}
function identifierForContextKey(key) {
    const logicalName = (key.match(/^(?:\.\/)?(.+)(?:[_-]controller\..+?)$/) || [])[1];
    if (logicalName) {
        return logicalName.replace(/_/g, "-").replace(/\//g, "--");
    }
}




/***/ },

/***/ "./node_modules/@hotwired/stimulus/dist/stimulus.js"
/*!**********************************************************!*\
  !*** ./node_modules/@hotwired/stimulus/dist/stimulus.js ***!
  \**********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Application: () => (/* binding */ Application),
/* harmony export */   AttributeObserver: () => (/* binding */ AttributeObserver),
/* harmony export */   Context: () => (/* binding */ Context),
/* harmony export */   Controller: () => (/* binding */ Controller),
/* harmony export */   ElementObserver: () => (/* binding */ ElementObserver),
/* harmony export */   IndexedMultimap: () => (/* binding */ IndexedMultimap),
/* harmony export */   Multimap: () => (/* binding */ Multimap),
/* harmony export */   SelectorObserver: () => (/* binding */ SelectorObserver),
/* harmony export */   StringMapObserver: () => (/* binding */ StringMapObserver),
/* harmony export */   TokenListObserver: () => (/* binding */ TokenListObserver),
/* harmony export */   ValueListObserver: () => (/* binding */ ValueListObserver),
/* harmony export */   add: () => (/* binding */ add),
/* harmony export */   defaultSchema: () => (/* binding */ defaultSchema),
/* harmony export */   del: () => (/* binding */ del),
/* harmony export */   fetch: () => (/* binding */ fetch),
/* harmony export */   prune: () => (/* binding */ prune)
/* harmony export */ });
/*
Stimulus 3.2.1
Copyright © 2023 Basecamp, LLC
 */
class EventListener {
    constructor(eventTarget, eventName, eventOptions) {
        this.eventTarget = eventTarget;
        this.eventName = eventName;
        this.eventOptions = eventOptions;
        this.unorderedBindings = new Set();
    }
    connect() {
        this.eventTarget.addEventListener(this.eventName, this, this.eventOptions);
    }
    disconnect() {
        this.eventTarget.removeEventListener(this.eventName, this, this.eventOptions);
    }
    bindingConnected(binding) {
        this.unorderedBindings.add(binding);
    }
    bindingDisconnected(binding) {
        this.unorderedBindings.delete(binding);
    }
    handleEvent(event) {
        const extendedEvent = extendEvent(event);
        for (const binding of this.bindings) {
            if (extendedEvent.immediatePropagationStopped) {
                break;
            }
            else {
                binding.handleEvent(extendedEvent);
            }
        }
    }
    hasBindings() {
        return this.unorderedBindings.size > 0;
    }
    get bindings() {
        return Array.from(this.unorderedBindings).sort((left, right) => {
            const leftIndex = left.index, rightIndex = right.index;
            return leftIndex < rightIndex ? -1 : leftIndex > rightIndex ? 1 : 0;
        });
    }
}
function extendEvent(event) {
    if ("immediatePropagationStopped" in event) {
        return event;
    }
    else {
        const { stopImmediatePropagation } = event;
        return Object.assign(event, {
            immediatePropagationStopped: false,
            stopImmediatePropagation() {
                this.immediatePropagationStopped = true;
                stopImmediatePropagation.call(this);
            },
        });
    }
}

class Dispatcher {
    constructor(application) {
        this.application = application;
        this.eventListenerMaps = new Map();
        this.started = false;
    }
    start() {
        if (!this.started) {
            this.started = true;
            this.eventListeners.forEach((eventListener) => eventListener.connect());
        }
    }
    stop() {
        if (this.started) {
            this.started = false;
            this.eventListeners.forEach((eventListener) => eventListener.disconnect());
        }
    }
    get eventListeners() {
        return Array.from(this.eventListenerMaps.values()).reduce((listeners, map) => listeners.concat(Array.from(map.values())), []);
    }
    bindingConnected(binding) {
        this.fetchEventListenerForBinding(binding).bindingConnected(binding);
    }
    bindingDisconnected(binding, clearEventListeners = false) {
        this.fetchEventListenerForBinding(binding).bindingDisconnected(binding);
        if (clearEventListeners)
            this.clearEventListenersForBinding(binding);
    }
    handleError(error, message, detail = {}) {
        this.application.handleError(error, `Error ${message}`, detail);
    }
    clearEventListenersForBinding(binding) {
        const eventListener = this.fetchEventListenerForBinding(binding);
        if (!eventListener.hasBindings()) {
            eventListener.disconnect();
            this.removeMappedEventListenerFor(binding);
        }
    }
    removeMappedEventListenerFor(binding) {
        const { eventTarget, eventName, eventOptions } = binding;
        const eventListenerMap = this.fetchEventListenerMapForEventTarget(eventTarget);
        const cacheKey = this.cacheKey(eventName, eventOptions);
        eventListenerMap.delete(cacheKey);
        if (eventListenerMap.size == 0)
            this.eventListenerMaps.delete(eventTarget);
    }
    fetchEventListenerForBinding(binding) {
        const { eventTarget, eventName, eventOptions } = binding;
        return this.fetchEventListener(eventTarget, eventName, eventOptions);
    }
    fetchEventListener(eventTarget, eventName, eventOptions) {
        const eventListenerMap = this.fetchEventListenerMapForEventTarget(eventTarget);
        const cacheKey = this.cacheKey(eventName, eventOptions);
        let eventListener = eventListenerMap.get(cacheKey);
        if (!eventListener) {
            eventListener = this.createEventListener(eventTarget, eventName, eventOptions);
            eventListenerMap.set(cacheKey, eventListener);
        }
        return eventListener;
    }
    createEventListener(eventTarget, eventName, eventOptions) {
        const eventListener = new EventListener(eventTarget, eventName, eventOptions);
        if (this.started) {
            eventListener.connect();
        }
        return eventListener;
    }
    fetchEventListenerMapForEventTarget(eventTarget) {
        let eventListenerMap = this.eventListenerMaps.get(eventTarget);
        if (!eventListenerMap) {
            eventListenerMap = new Map();
            this.eventListenerMaps.set(eventTarget, eventListenerMap);
        }
        return eventListenerMap;
    }
    cacheKey(eventName, eventOptions) {
        const parts = [eventName];
        Object.keys(eventOptions)
            .sort()
            .forEach((key) => {
            parts.push(`${eventOptions[key] ? "" : "!"}${key}`);
        });
        return parts.join(":");
    }
}

const defaultActionDescriptorFilters = {
    stop({ event, value }) {
        if (value)
            event.stopPropagation();
        return true;
    },
    prevent({ event, value }) {
        if (value)
            event.preventDefault();
        return true;
    },
    self({ event, value, element }) {
        if (value) {
            return element === event.target;
        }
        else {
            return true;
        }
    },
};
const descriptorPattern = /^(?:(?:([^.]+?)\+)?(.+?)(?:\.(.+?))?(?:@(window|document))?->)?(.+?)(?:#([^:]+?))(?::(.+))?$/;
function parseActionDescriptorString(descriptorString) {
    const source = descriptorString.trim();
    const matches = source.match(descriptorPattern) || [];
    let eventName = matches[2];
    let keyFilter = matches[3];
    if (keyFilter && !["keydown", "keyup", "keypress"].includes(eventName)) {
        eventName += `.${keyFilter}`;
        keyFilter = "";
    }
    return {
        eventTarget: parseEventTarget(matches[4]),
        eventName,
        eventOptions: matches[7] ? parseEventOptions(matches[7]) : {},
        identifier: matches[5],
        methodName: matches[6],
        keyFilter: matches[1] || keyFilter,
    };
}
function parseEventTarget(eventTargetName) {
    if (eventTargetName == "window") {
        return window;
    }
    else if (eventTargetName == "document") {
        return document;
    }
}
function parseEventOptions(eventOptions) {
    return eventOptions
        .split(":")
        .reduce((options, token) => Object.assign(options, { [token.replace(/^!/, "")]: !/^!/.test(token) }), {});
}
function stringifyEventTarget(eventTarget) {
    if (eventTarget == window) {
        return "window";
    }
    else if (eventTarget == document) {
        return "document";
    }
}

function camelize(value) {
    return value.replace(/(?:[_-])([a-z0-9])/g, (_, char) => char.toUpperCase());
}
function namespaceCamelize(value) {
    return camelize(value.replace(/--/g, "-").replace(/__/g, "_"));
}
function capitalize(value) {
    return value.charAt(0).toUpperCase() + value.slice(1);
}
function dasherize(value) {
    return value.replace(/([A-Z])/g, (_, char) => `-${char.toLowerCase()}`);
}
function tokenize(value) {
    return value.match(/[^\s]+/g) || [];
}

function isSomething(object) {
    return object !== null && object !== undefined;
}
function hasProperty(object, property) {
    return Object.prototype.hasOwnProperty.call(object, property);
}

const allModifiers = ["meta", "ctrl", "alt", "shift"];
class Action {
    constructor(element, index, descriptor, schema) {
        this.element = element;
        this.index = index;
        this.eventTarget = descriptor.eventTarget || element;
        this.eventName = descriptor.eventName || getDefaultEventNameForElement(element) || error("missing event name");
        this.eventOptions = descriptor.eventOptions || {};
        this.identifier = descriptor.identifier || error("missing identifier");
        this.methodName = descriptor.methodName || error("missing method name");
        this.keyFilter = descriptor.keyFilter || "";
        this.schema = schema;
    }
    static forToken(token, schema) {
        return new this(token.element, token.index, parseActionDescriptorString(token.content), schema);
    }
    toString() {
        const eventFilter = this.keyFilter ? `.${this.keyFilter}` : "";
        const eventTarget = this.eventTargetName ? `@${this.eventTargetName}` : "";
        return `${this.eventName}${eventFilter}${eventTarget}->${this.identifier}#${this.methodName}`;
    }
    shouldIgnoreKeyboardEvent(event) {
        if (!this.keyFilter) {
            return false;
        }
        const filters = this.keyFilter.split("+");
        if (this.keyFilterDissatisfied(event, filters)) {
            return true;
        }
        const standardFilter = filters.filter((key) => !allModifiers.includes(key))[0];
        if (!standardFilter) {
            return false;
        }
        if (!hasProperty(this.keyMappings, standardFilter)) {
            error(`contains unknown key filter: ${this.keyFilter}`);
        }
        return this.keyMappings[standardFilter].toLowerCase() !== event.key.toLowerCase();
    }
    shouldIgnoreMouseEvent(event) {
        if (!this.keyFilter) {
            return false;
        }
        const filters = [this.keyFilter];
        if (this.keyFilterDissatisfied(event, filters)) {
            return true;
        }
        return false;
    }
    get params() {
        const params = {};
        const pattern = new RegExp(`^data-${this.identifier}-(.+)-param$`, "i");
        for (const { name, value } of Array.from(this.element.attributes)) {
            const match = name.match(pattern);
            const key = match && match[1];
            if (key) {
                params[camelize(key)] = typecast(value);
            }
        }
        return params;
    }
    get eventTargetName() {
        return stringifyEventTarget(this.eventTarget);
    }
    get keyMappings() {
        return this.schema.keyMappings;
    }
    keyFilterDissatisfied(event, filters) {
        const [meta, ctrl, alt, shift] = allModifiers.map((modifier) => filters.includes(modifier));
        return event.metaKey !== meta || event.ctrlKey !== ctrl || event.altKey !== alt || event.shiftKey !== shift;
    }
}
const defaultEventNames = {
    a: () => "click",
    button: () => "click",
    form: () => "submit",
    details: () => "toggle",
    input: (e) => (e.getAttribute("type") == "submit" ? "click" : "input"),
    select: () => "change",
    textarea: () => "input",
};
function getDefaultEventNameForElement(element) {
    const tagName = element.tagName.toLowerCase();
    if (tagName in defaultEventNames) {
        return defaultEventNames[tagName](element);
    }
}
function error(message) {
    throw new Error(message);
}
function typecast(value) {
    try {
        return JSON.parse(value);
    }
    catch (o_O) {
        return value;
    }
}

class Binding {
    constructor(context, action) {
        this.context = context;
        this.action = action;
    }
    get index() {
        return this.action.index;
    }
    get eventTarget() {
        return this.action.eventTarget;
    }
    get eventOptions() {
        return this.action.eventOptions;
    }
    get identifier() {
        return this.context.identifier;
    }
    handleEvent(event) {
        const actionEvent = this.prepareActionEvent(event);
        if (this.willBeInvokedByEvent(event) && this.applyEventModifiers(actionEvent)) {
            this.invokeWithEvent(actionEvent);
        }
    }
    get eventName() {
        return this.action.eventName;
    }
    get method() {
        const method = this.controller[this.methodName];
        if (typeof method == "function") {
            return method;
        }
        throw new Error(`Action "${this.action}" references undefined method "${this.methodName}"`);
    }
    applyEventModifiers(event) {
        const { element } = this.action;
        const { actionDescriptorFilters } = this.context.application;
        const { controller } = this.context;
        let passes = true;
        for (const [name, value] of Object.entries(this.eventOptions)) {
            if (name in actionDescriptorFilters) {
                const filter = actionDescriptorFilters[name];
                passes = passes && filter({ name, value, event, element, controller });
            }
            else {
                continue;
            }
        }
        return passes;
    }
    prepareActionEvent(event) {
        return Object.assign(event, { params: this.action.params });
    }
    invokeWithEvent(event) {
        const { target, currentTarget } = event;
        try {
            this.method.call(this.controller, event);
            this.context.logDebugActivity(this.methodName, { event, target, currentTarget, action: this.methodName });
        }
        catch (error) {
            const { identifier, controller, element, index } = this;
            const detail = { identifier, controller, element, index, event };
            this.context.handleError(error, `invoking action "${this.action}"`, detail);
        }
    }
    willBeInvokedByEvent(event) {
        const eventTarget = event.target;
        if (event instanceof KeyboardEvent && this.action.shouldIgnoreKeyboardEvent(event)) {
            return false;
        }
        if (event instanceof MouseEvent && this.action.shouldIgnoreMouseEvent(event)) {
            return false;
        }
        if (this.element === eventTarget) {
            return true;
        }
        else if (eventTarget instanceof Element && this.element.contains(eventTarget)) {
            return this.scope.containsElement(eventTarget);
        }
        else {
            return this.scope.containsElement(this.action.element);
        }
    }
    get controller() {
        return this.context.controller;
    }
    get methodName() {
        return this.action.methodName;
    }
    get element() {
        return this.scope.element;
    }
    get scope() {
        return this.context.scope;
    }
}

class ElementObserver {
    constructor(element, delegate) {
        this.mutationObserverInit = { attributes: true, childList: true, subtree: true };
        this.element = element;
        this.started = false;
        this.delegate = delegate;
        this.elements = new Set();
        this.mutationObserver = new MutationObserver((mutations) => this.processMutations(mutations));
    }
    start() {
        if (!this.started) {
            this.started = true;
            this.mutationObserver.observe(this.element, this.mutationObserverInit);
            this.refresh();
        }
    }
    pause(callback) {
        if (this.started) {
            this.mutationObserver.disconnect();
            this.started = false;
        }
        callback();
        if (!this.started) {
            this.mutationObserver.observe(this.element, this.mutationObserverInit);
            this.started = true;
        }
    }
    stop() {
        if (this.started) {
            this.mutationObserver.takeRecords();
            this.mutationObserver.disconnect();
            this.started = false;
        }
    }
    refresh() {
        if (this.started) {
            const matches = new Set(this.matchElementsInTree());
            for (const element of Array.from(this.elements)) {
                if (!matches.has(element)) {
                    this.removeElement(element);
                }
            }
            for (const element of Array.from(matches)) {
                this.addElement(element);
            }
        }
    }
    processMutations(mutations) {
        if (this.started) {
            for (const mutation of mutations) {
                this.processMutation(mutation);
            }
        }
    }
    processMutation(mutation) {
        if (mutation.type == "attributes") {
            this.processAttributeChange(mutation.target, mutation.attributeName);
        }
        else if (mutation.type == "childList") {
            this.processRemovedNodes(mutation.removedNodes);
            this.processAddedNodes(mutation.addedNodes);
        }
    }
    processAttributeChange(element, attributeName) {
        if (this.elements.has(element)) {
            if (this.delegate.elementAttributeChanged && this.matchElement(element)) {
                this.delegate.elementAttributeChanged(element, attributeName);
            }
            else {
                this.removeElement(element);
            }
        }
        else if (this.matchElement(element)) {
            this.addElement(element);
        }
    }
    processRemovedNodes(nodes) {
        for (const node of Array.from(nodes)) {
            const element = this.elementFromNode(node);
            if (element) {
                this.processTree(element, this.removeElement);
            }
        }
    }
    processAddedNodes(nodes) {
        for (const node of Array.from(nodes)) {
            const element = this.elementFromNode(node);
            if (element && this.elementIsActive(element)) {
                this.processTree(element, this.addElement);
            }
        }
    }
    matchElement(element) {
        return this.delegate.matchElement(element);
    }
    matchElementsInTree(tree = this.element) {
        return this.delegate.matchElementsInTree(tree);
    }
    processTree(tree, processor) {
        for (const element of this.matchElementsInTree(tree)) {
            processor.call(this, element);
        }
    }
    elementFromNode(node) {
        if (node.nodeType == Node.ELEMENT_NODE) {
            return node;
        }
    }
    elementIsActive(element) {
        if (element.isConnected != this.element.isConnected) {
            return false;
        }
        else {
            return this.element.contains(element);
        }
    }
    addElement(element) {
        if (!this.elements.has(element)) {
            if (this.elementIsActive(element)) {
                this.elements.add(element);
                if (this.delegate.elementMatched) {
                    this.delegate.elementMatched(element);
                }
            }
        }
    }
    removeElement(element) {
        if (this.elements.has(element)) {
            this.elements.delete(element);
            if (this.delegate.elementUnmatched) {
                this.delegate.elementUnmatched(element);
            }
        }
    }
}

class AttributeObserver {
    constructor(element, attributeName, delegate) {
        this.attributeName = attributeName;
        this.delegate = delegate;
        this.elementObserver = new ElementObserver(element, this);
    }
    get element() {
        return this.elementObserver.element;
    }
    get selector() {
        return `[${this.attributeName}]`;
    }
    start() {
        this.elementObserver.start();
    }
    pause(callback) {
        this.elementObserver.pause(callback);
    }
    stop() {
        this.elementObserver.stop();
    }
    refresh() {
        this.elementObserver.refresh();
    }
    get started() {
        return this.elementObserver.started;
    }
    matchElement(element) {
        return element.hasAttribute(this.attributeName);
    }
    matchElementsInTree(tree) {
        const match = this.matchElement(tree) ? [tree] : [];
        const matches = Array.from(tree.querySelectorAll(this.selector));
        return match.concat(matches);
    }
    elementMatched(element) {
        if (this.delegate.elementMatchedAttribute) {
            this.delegate.elementMatchedAttribute(element, this.attributeName);
        }
    }
    elementUnmatched(element) {
        if (this.delegate.elementUnmatchedAttribute) {
            this.delegate.elementUnmatchedAttribute(element, this.attributeName);
        }
    }
    elementAttributeChanged(element, attributeName) {
        if (this.delegate.elementAttributeValueChanged && this.attributeName == attributeName) {
            this.delegate.elementAttributeValueChanged(element, attributeName);
        }
    }
}

function add(map, key, value) {
    fetch(map, key).add(value);
}
function del(map, key, value) {
    fetch(map, key).delete(value);
    prune(map, key);
}
function fetch(map, key) {
    let values = map.get(key);
    if (!values) {
        values = new Set();
        map.set(key, values);
    }
    return values;
}
function prune(map, key) {
    const values = map.get(key);
    if (values != null && values.size == 0) {
        map.delete(key);
    }
}

class Multimap {
    constructor() {
        this.valuesByKey = new Map();
    }
    get keys() {
        return Array.from(this.valuesByKey.keys());
    }
    get values() {
        const sets = Array.from(this.valuesByKey.values());
        return sets.reduce((values, set) => values.concat(Array.from(set)), []);
    }
    get size() {
        const sets = Array.from(this.valuesByKey.values());
        return sets.reduce((size, set) => size + set.size, 0);
    }
    add(key, value) {
        add(this.valuesByKey, key, value);
    }
    delete(key, value) {
        del(this.valuesByKey, key, value);
    }
    has(key, value) {
        const values = this.valuesByKey.get(key);
        return values != null && values.has(value);
    }
    hasKey(key) {
        return this.valuesByKey.has(key);
    }
    hasValue(value) {
        const sets = Array.from(this.valuesByKey.values());
        return sets.some((set) => set.has(value));
    }
    getValuesForKey(key) {
        const values = this.valuesByKey.get(key);
        return values ? Array.from(values) : [];
    }
    getKeysForValue(value) {
        return Array.from(this.valuesByKey)
            .filter(([_key, values]) => values.has(value))
            .map(([key, _values]) => key);
    }
}

class IndexedMultimap extends Multimap {
    constructor() {
        super();
        this.keysByValue = new Map();
    }
    get values() {
        return Array.from(this.keysByValue.keys());
    }
    add(key, value) {
        super.add(key, value);
        add(this.keysByValue, value, key);
    }
    delete(key, value) {
        super.delete(key, value);
        del(this.keysByValue, value, key);
    }
    hasValue(value) {
        return this.keysByValue.has(value);
    }
    getKeysForValue(value) {
        const set = this.keysByValue.get(value);
        return set ? Array.from(set) : [];
    }
}

class SelectorObserver {
    constructor(element, selector, delegate, details) {
        this._selector = selector;
        this.details = details;
        this.elementObserver = new ElementObserver(element, this);
        this.delegate = delegate;
        this.matchesByElement = new Multimap();
    }
    get started() {
        return this.elementObserver.started;
    }
    get selector() {
        return this._selector;
    }
    set selector(selector) {
        this._selector = selector;
        this.refresh();
    }
    start() {
        this.elementObserver.start();
    }
    pause(callback) {
        this.elementObserver.pause(callback);
    }
    stop() {
        this.elementObserver.stop();
    }
    refresh() {
        this.elementObserver.refresh();
    }
    get element() {
        return this.elementObserver.element;
    }
    matchElement(element) {
        const { selector } = this;
        if (selector) {
            const matches = element.matches(selector);
            if (this.delegate.selectorMatchElement) {
                return matches && this.delegate.selectorMatchElement(element, this.details);
            }
            return matches;
        }
        else {
            return false;
        }
    }
    matchElementsInTree(tree) {
        const { selector } = this;
        if (selector) {
            const match = this.matchElement(tree) ? [tree] : [];
            const matches = Array.from(tree.querySelectorAll(selector)).filter((match) => this.matchElement(match));
            return match.concat(matches);
        }
        else {
            return [];
        }
    }
    elementMatched(element) {
        const { selector } = this;
        if (selector) {
            this.selectorMatched(element, selector);
        }
    }
    elementUnmatched(element) {
        const selectors = this.matchesByElement.getKeysForValue(element);
        for (const selector of selectors) {
            this.selectorUnmatched(element, selector);
        }
    }
    elementAttributeChanged(element, _attributeName) {
        const { selector } = this;
        if (selector) {
            const matches = this.matchElement(element);
            const matchedBefore = this.matchesByElement.has(selector, element);
            if (matches && !matchedBefore) {
                this.selectorMatched(element, selector);
            }
            else if (!matches && matchedBefore) {
                this.selectorUnmatched(element, selector);
            }
        }
    }
    selectorMatched(element, selector) {
        this.delegate.selectorMatched(element, selector, this.details);
        this.matchesByElement.add(selector, element);
    }
    selectorUnmatched(element, selector) {
        this.delegate.selectorUnmatched(element, selector, this.details);
        this.matchesByElement.delete(selector, element);
    }
}

class StringMapObserver {
    constructor(element, delegate) {
        this.element = element;
        this.delegate = delegate;
        this.started = false;
        this.stringMap = new Map();
        this.mutationObserver = new MutationObserver((mutations) => this.processMutations(mutations));
    }
    start() {
        if (!this.started) {
            this.started = true;
            this.mutationObserver.observe(this.element, { attributes: true, attributeOldValue: true });
            this.refresh();
        }
    }
    stop() {
        if (this.started) {
            this.mutationObserver.takeRecords();
            this.mutationObserver.disconnect();
            this.started = false;
        }
    }
    refresh() {
        if (this.started) {
            for (const attributeName of this.knownAttributeNames) {
                this.refreshAttribute(attributeName, null);
            }
        }
    }
    processMutations(mutations) {
        if (this.started) {
            for (const mutation of mutations) {
                this.processMutation(mutation);
            }
        }
    }
    processMutation(mutation) {
        const attributeName = mutation.attributeName;
        if (attributeName) {
            this.refreshAttribute(attributeName, mutation.oldValue);
        }
    }
    refreshAttribute(attributeName, oldValue) {
        const key = this.delegate.getStringMapKeyForAttribute(attributeName);
        if (key != null) {
            if (!this.stringMap.has(attributeName)) {
                this.stringMapKeyAdded(key, attributeName);
            }
            const value = this.element.getAttribute(attributeName);
            if (this.stringMap.get(attributeName) != value) {
                this.stringMapValueChanged(value, key, oldValue);
            }
            if (value == null) {
                const oldValue = this.stringMap.get(attributeName);
                this.stringMap.delete(attributeName);
                if (oldValue)
                    this.stringMapKeyRemoved(key, attributeName, oldValue);
            }
            else {
                this.stringMap.set(attributeName, value);
            }
        }
    }
    stringMapKeyAdded(key, attributeName) {
        if (this.delegate.stringMapKeyAdded) {
            this.delegate.stringMapKeyAdded(key, attributeName);
        }
    }
    stringMapValueChanged(value, key, oldValue) {
        if (this.delegate.stringMapValueChanged) {
            this.delegate.stringMapValueChanged(value, key, oldValue);
        }
    }
    stringMapKeyRemoved(key, attributeName, oldValue) {
        if (this.delegate.stringMapKeyRemoved) {
            this.delegate.stringMapKeyRemoved(key, attributeName, oldValue);
        }
    }
    get knownAttributeNames() {
        return Array.from(new Set(this.currentAttributeNames.concat(this.recordedAttributeNames)));
    }
    get currentAttributeNames() {
        return Array.from(this.element.attributes).map((attribute) => attribute.name);
    }
    get recordedAttributeNames() {
        return Array.from(this.stringMap.keys());
    }
}

class TokenListObserver {
    constructor(element, attributeName, delegate) {
        this.attributeObserver = new AttributeObserver(element, attributeName, this);
        this.delegate = delegate;
        this.tokensByElement = new Multimap();
    }
    get started() {
        return this.attributeObserver.started;
    }
    start() {
        this.attributeObserver.start();
    }
    pause(callback) {
        this.attributeObserver.pause(callback);
    }
    stop() {
        this.attributeObserver.stop();
    }
    refresh() {
        this.attributeObserver.refresh();
    }
    get element() {
        return this.attributeObserver.element;
    }
    get attributeName() {
        return this.attributeObserver.attributeName;
    }
    elementMatchedAttribute(element) {
        this.tokensMatched(this.readTokensForElement(element));
    }
    elementAttributeValueChanged(element) {
        const [unmatchedTokens, matchedTokens] = this.refreshTokensForElement(element);
        this.tokensUnmatched(unmatchedTokens);
        this.tokensMatched(matchedTokens);
    }
    elementUnmatchedAttribute(element) {
        this.tokensUnmatched(this.tokensByElement.getValuesForKey(element));
    }
    tokensMatched(tokens) {
        tokens.forEach((token) => this.tokenMatched(token));
    }
    tokensUnmatched(tokens) {
        tokens.forEach((token) => this.tokenUnmatched(token));
    }
    tokenMatched(token) {
        this.delegate.tokenMatched(token);
        this.tokensByElement.add(token.element, token);
    }
    tokenUnmatched(token) {
        this.delegate.tokenUnmatched(token);
        this.tokensByElement.delete(token.element, token);
    }
    refreshTokensForElement(element) {
        const previousTokens = this.tokensByElement.getValuesForKey(element);
        const currentTokens = this.readTokensForElement(element);
        const firstDifferingIndex = zip(previousTokens, currentTokens).findIndex(([previousToken, currentToken]) => !tokensAreEqual(previousToken, currentToken));
        if (firstDifferingIndex == -1) {
            return [[], []];
        }
        else {
            return [previousTokens.slice(firstDifferingIndex), currentTokens.slice(firstDifferingIndex)];
        }
    }
    readTokensForElement(element) {
        const attributeName = this.attributeName;
        const tokenString = element.getAttribute(attributeName) || "";
        return parseTokenString(tokenString, element, attributeName);
    }
}
function parseTokenString(tokenString, element, attributeName) {
    return tokenString
        .trim()
        .split(/\s+/)
        .filter((content) => content.length)
        .map((content, index) => ({ element, attributeName, content, index }));
}
function zip(left, right) {
    const length = Math.max(left.length, right.length);
    return Array.from({ length }, (_, index) => [left[index], right[index]]);
}
function tokensAreEqual(left, right) {
    return left && right && left.index == right.index && left.content == right.content;
}

class ValueListObserver {
    constructor(element, attributeName, delegate) {
        this.tokenListObserver = new TokenListObserver(element, attributeName, this);
        this.delegate = delegate;
        this.parseResultsByToken = new WeakMap();
        this.valuesByTokenByElement = new WeakMap();
    }
    get started() {
        return this.tokenListObserver.started;
    }
    start() {
        this.tokenListObserver.start();
    }
    stop() {
        this.tokenListObserver.stop();
    }
    refresh() {
        this.tokenListObserver.refresh();
    }
    get element() {
        return this.tokenListObserver.element;
    }
    get attributeName() {
        return this.tokenListObserver.attributeName;
    }
    tokenMatched(token) {
        const { element } = token;
        const { value } = this.fetchParseResultForToken(token);
        if (value) {
            this.fetchValuesByTokenForElement(element).set(token, value);
            this.delegate.elementMatchedValue(element, value);
        }
    }
    tokenUnmatched(token) {
        const { element } = token;
        const { value } = this.fetchParseResultForToken(token);
        if (value) {
            this.fetchValuesByTokenForElement(element).delete(token);
            this.delegate.elementUnmatchedValue(element, value);
        }
    }
    fetchParseResultForToken(token) {
        let parseResult = this.parseResultsByToken.get(token);
        if (!parseResult) {
            parseResult = this.parseToken(token);
            this.parseResultsByToken.set(token, parseResult);
        }
        return parseResult;
    }
    fetchValuesByTokenForElement(element) {
        let valuesByToken = this.valuesByTokenByElement.get(element);
        if (!valuesByToken) {
            valuesByToken = new Map();
            this.valuesByTokenByElement.set(element, valuesByToken);
        }
        return valuesByToken;
    }
    parseToken(token) {
        try {
            const value = this.delegate.parseValueForToken(token);
            return { value };
        }
        catch (error) {
            return { error };
        }
    }
}

class BindingObserver {
    constructor(context, delegate) {
        this.context = context;
        this.delegate = delegate;
        this.bindingsByAction = new Map();
    }
    start() {
        if (!this.valueListObserver) {
            this.valueListObserver = new ValueListObserver(this.element, this.actionAttribute, this);
            this.valueListObserver.start();
        }
    }
    stop() {
        if (this.valueListObserver) {
            this.valueListObserver.stop();
            delete this.valueListObserver;
            this.disconnectAllActions();
        }
    }
    get element() {
        return this.context.element;
    }
    get identifier() {
        return this.context.identifier;
    }
    get actionAttribute() {
        return this.schema.actionAttribute;
    }
    get schema() {
        return this.context.schema;
    }
    get bindings() {
        return Array.from(this.bindingsByAction.values());
    }
    connectAction(action) {
        const binding = new Binding(this.context, action);
        this.bindingsByAction.set(action, binding);
        this.delegate.bindingConnected(binding);
    }
    disconnectAction(action) {
        const binding = this.bindingsByAction.get(action);
        if (binding) {
            this.bindingsByAction.delete(action);
            this.delegate.bindingDisconnected(binding);
        }
    }
    disconnectAllActions() {
        this.bindings.forEach((binding) => this.delegate.bindingDisconnected(binding, true));
        this.bindingsByAction.clear();
    }
    parseValueForToken(token) {
        const action = Action.forToken(token, this.schema);
        if (action.identifier == this.identifier) {
            return action;
        }
    }
    elementMatchedValue(element, action) {
        this.connectAction(action);
    }
    elementUnmatchedValue(element, action) {
        this.disconnectAction(action);
    }
}

class ValueObserver {
    constructor(context, receiver) {
        this.context = context;
        this.receiver = receiver;
        this.stringMapObserver = new StringMapObserver(this.element, this);
        this.valueDescriptorMap = this.controller.valueDescriptorMap;
    }
    start() {
        this.stringMapObserver.start();
        this.invokeChangedCallbacksForDefaultValues();
    }
    stop() {
        this.stringMapObserver.stop();
    }
    get element() {
        return this.context.element;
    }
    get controller() {
        return this.context.controller;
    }
    getStringMapKeyForAttribute(attributeName) {
        if (attributeName in this.valueDescriptorMap) {
            return this.valueDescriptorMap[attributeName].name;
        }
    }
    stringMapKeyAdded(key, attributeName) {
        const descriptor = this.valueDescriptorMap[attributeName];
        if (!this.hasValue(key)) {
            this.invokeChangedCallback(key, descriptor.writer(this.receiver[key]), descriptor.writer(descriptor.defaultValue));
        }
    }
    stringMapValueChanged(value, name, oldValue) {
        const descriptor = this.valueDescriptorNameMap[name];
        if (value === null)
            return;
        if (oldValue === null) {
            oldValue = descriptor.writer(descriptor.defaultValue);
        }
        this.invokeChangedCallback(name, value, oldValue);
    }
    stringMapKeyRemoved(key, attributeName, oldValue) {
        const descriptor = this.valueDescriptorNameMap[key];
        if (this.hasValue(key)) {
            this.invokeChangedCallback(key, descriptor.writer(this.receiver[key]), oldValue);
        }
        else {
            this.invokeChangedCallback(key, descriptor.writer(descriptor.defaultValue), oldValue);
        }
    }
    invokeChangedCallbacksForDefaultValues() {
        for (const { key, name, defaultValue, writer } of this.valueDescriptors) {
            if (defaultValue != undefined && !this.controller.data.has(key)) {
                this.invokeChangedCallback(name, writer(defaultValue), undefined);
            }
        }
    }
    invokeChangedCallback(name, rawValue, rawOldValue) {
        const changedMethodName = `${name}Changed`;
        const changedMethod = this.receiver[changedMethodName];
        if (typeof changedMethod == "function") {
            const descriptor = this.valueDescriptorNameMap[name];
            try {
                const value = descriptor.reader(rawValue);
                let oldValue = rawOldValue;
                if (rawOldValue) {
                    oldValue = descriptor.reader(rawOldValue);
                }
                changedMethod.call(this.receiver, value, oldValue);
            }
            catch (error) {
                if (error instanceof TypeError) {
                    error.message = `Stimulus Value "${this.context.identifier}.${descriptor.name}" - ${error.message}`;
                }
                throw error;
            }
        }
    }
    get valueDescriptors() {
        const { valueDescriptorMap } = this;
        return Object.keys(valueDescriptorMap).map((key) => valueDescriptorMap[key]);
    }
    get valueDescriptorNameMap() {
        const descriptors = {};
        Object.keys(this.valueDescriptorMap).forEach((key) => {
            const descriptor = this.valueDescriptorMap[key];
            descriptors[descriptor.name] = descriptor;
        });
        return descriptors;
    }
    hasValue(attributeName) {
        const descriptor = this.valueDescriptorNameMap[attributeName];
        const hasMethodName = `has${capitalize(descriptor.name)}`;
        return this.receiver[hasMethodName];
    }
}

class TargetObserver {
    constructor(context, delegate) {
        this.context = context;
        this.delegate = delegate;
        this.targetsByName = new Multimap();
    }
    start() {
        if (!this.tokenListObserver) {
            this.tokenListObserver = new TokenListObserver(this.element, this.attributeName, this);
            this.tokenListObserver.start();
        }
    }
    stop() {
        if (this.tokenListObserver) {
            this.disconnectAllTargets();
            this.tokenListObserver.stop();
            delete this.tokenListObserver;
        }
    }
    tokenMatched({ element, content: name }) {
        if (this.scope.containsElement(element)) {
            this.connectTarget(element, name);
        }
    }
    tokenUnmatched({ element, content: name }) {
        this.disconnectTarget(element, name);
    }
    connectTarget(element, name) {
        var _a;
        if (!this.targetsByName.has(name, element)) {
            this.targetsByName.add(name, element);
            (_a = this.tokenListObserver) === null || _a === void 0 ? void 0 : _a.pause(() => this.delegate.targetConnected(element, name));
        }
    }
    disconnectTarget(element, name) {
        var _a;
        if (this.targetsByName.has(name, element)) {
            this.targetsByName.delete(name, element);
            (_a = this.tokenListObserver) === null || _a === void 0 ? void 0 : _a.pause(() => this.delegate.targetDisconnected(element, name));
        }
    }
    disconnectAllTargets() {
        for (const name of this.targetsByName.keys) {
            for (const element of this.targetsByName.getValuesForKey(name)) {
                this.disconnectTarget(element, name);
            }
        }
    }
    get attributeName() {
        return `data-${this.context.identifier}-target`;
    }
    get element() {
        return this.context.element;
    }
    get scope() {
        return this.context.scope;
    }
}

function readInheritableStaticArrayValues(constructor, propertyName) {
    const ancestors = getAncestorsForConstructor(constructor);
    return Array.from(ancestors.reduce((values, constructor) => {
        getOwnStaticArrayValues(constructor, propertyName).forEach((name) => values.add(name));
        return values;
    }, new Set()));
}
function readInheritableStaticObjectPairs(constructor, propertyName) {
    const ancestors = getAncestorsForConstructor(constructor);
    return ancestors.reduce((pairs, constructor) => {
        pairs.push(...getOwnStaticObjectPairs(constructor, propertyName));
        return pairs;
    }, []);
}
function getAncestorsForConstructor(constructor) {
    const ancestors = [];
    while (constructor) {
        ancestors.push(constructor);
        constructor = Object.getPrototypeOf(constructor);
    }
    return ancestors.reverse();
}
function getOwnStaticArrayValues(constructor, propertyName) {
    const definition = constructor[propertyName];
    return Array.isArray(definition) ? definition : [];
}
function getOwnStaticObjectPairs(constructor, propertyName) {
    const definition = constructor[propertyName];
    return definition ? Object.keys(definition).map((key) => [key, definition[key]]) : [];
}

class OutletObserver {
    constructor(context, delegate) {
        this.started = false;
        this.context = context;
        this.delegate = delegate;
        this.outletsByName = new Multimap();
        this.outletElementsByName = new Multimap();
        this.selectorObserverMap = new Map();
        this.attributeObserverMap = new Map();
    }
    start() {
        if (!this.started) {
            this.outletDefinitions.forEach((outletName) => {
                this.setupSelectorObserverForOutlet(outletName);
                this.setupAttributeObserverForOutlet(outletName);
            });
            this.started = true;
            this.dependentContexts.forEach((context) => context.refresh());
        }
    }
    refresh() {
        this.selectorObserverMap.forEach((observer) => observer.refresh());
        this.attributeObserverMap.forEach((observer) => observer.refresh());
    }
    stop() {
        if (this.started) {
            this.started = false;
            this.disconnectAllOutlets();
            this.stopSelectorObservers();
            this.stopAttributeObservers();
        }
    }
    stopSelectorObservers() {
        if (this.selectorObserverMap.size > 0) {
            this.selectorObserverMap.forEach((observer) => observer.stop());
            this.selectorObserverMap.clear();
        }
    }
    stopAttributeObservers() {
        if (this.attributeObserverMap.size > 0) {
            this.attributeObserverMap.forEach((observer) => observer.stop());
            this.attributeObserverMap.clear();
        }
    }
    selectorMatched(element, _selector, { outletName }) {
        const outlet = this.getOutlet(element, outletName);
        if (outlet) {
            this.connectOutlet(outlet, element, outletName);
        }
    }
    selectorUnmatched(element, _selector, { outletName }) {
        const outlet = this.getOutletFromMap(element, outletName);
        if (outlet) {
            this.disconnectOutlet(outlet, element, outletName);
        }
    }
    selectorMatchElement(element, { outletName }) {
        const selector = this.selector(outletName);
        const hasOutlet = this.hasOutlet(element, outletName);
        const hasOutletController = element.matches(`[${this.schema.controllerAttribute}~=${outletName}]`);
        if (selector) {
            return hasOutlet && hasOutletController && element.matches(selector);
        }
        else {
            return false;
        }
    }
    elementMatchedAttribute(_element, attributeName) {
        const outletName = this.getOutletNameFromOutletAttributeName(attributeName);
        if (outletName) {
            this.updateSelectorObserverForOutlet(outletName);
        }
    }
    elementAttributeValueChanged(_element, attributeName) {
        const outletName = this.getOutletNameFromOutletAttributeName(attributeName);
        if (outletName) {
            this.updateSelectorObserverForOutlet(outletName);
        }
    }
    elementUnmatchedAttribute(_element, attributeName) {
        const outletName = this.getOutletNameFromOutletAttributeName(attributeName);
        if (outletName) {
            this.updateSelectorObserverForOutlet(outletName);
        }
    }
    connectOutlet(outlet, element, outletName) {
        var _a;
        if (!this.outletElementsByName.has(outletName, element)) {
            this.outletsByName.add(outletName, outlet);
            this.outletElementsByName.add(outletName, element);
            (_a = this.selectorObserverMap.get(outletName)) === null || _a === void 0 ? void 0 : _a.pause(() => this.delegate.outletConnected(outlet, element, outletName));
        }
    }
    disconnectOutlet(outlet, element, outletName) {
        var _a;
        if (this.outletElementsByName.has(outletName, element)) {
            this.outletsByName.delete(outletName, outlet);
            this.outletElementsByName.delete(outletName, element);
            (_a = this.selectorObserverMap
                .get(outletName)) === null || _a === void 0 ? void 0 : _a.pause(() => this.delegate.outletDisconnected(outlet, element, outletName));
        }
    }
    disconnectAllOutlets() {
        for (const outletName of this.outletElementsByName.keys) {
            for (const element of this.outletElementsByName.getValuesForKey(outletName)) {
                for (const outlet of this.outletsByName.getValuesForKey(outletName)) {
                    this.disconnectOutlet(outlet, element, outletName);
                }
            }
        }
    }
    updateSelectorObserverForOutlet(outletName) {
        const observer = this.selectorObserverMap.get(outletName);
        if (observer) {
            observer.selector = this.selector(outletName);
        }
    }
    setupSelectorObserverForOutlet(outletName) {
        const selector = this.selector(outletName);
        const selectorObserver = new SelectorObserver(document.body, selector, this, { outletName });
        this.selectorObserverMap.set(outletName, selectorObserver);
        selectorObserver.start();
    }
    setupAttributeObserverForOutlet(outletName) {
        const attributeName = this.attributeNameForOutletName(outletName);
        const attributeObserver = new AttributeObserver(this.scope.element, attributeName, this);
        this.attributeObserverMap.set(outletName, attributeObserver);
        attributeObserver.start();
    }
    selector(outletName) {
        return this.scope.outlets.getSelectorForOutletName(outletName);
    }
    attributeNameForOutletName(outletName) {
        return this.scope.schema.outletAttributeForScope(this.identifier, outletName);
    }
    getOutletNameFromOutletAttributeName(attributeName) {
        return this.outletDefinitions.find((outletName) => this.attributeNameForOutletName(outletName) === attributeName);
    }
    get outletDependencies() {
        const dependencies = new Multimap();
        this.router.modules.forEach((module) => {
            const constructor = module.definition.controllerConstructor;
            const outlets = readInheritableStaticArrayValues(constructor, "outlets");
            outlets.forEach((outlet) => dependencies.add(outlet, module.identifier));
        });
        return dependencies;
    }
    get outletDefinitions() {
        return this.outletDependencies.getKeysForValue(this.identifier);
    }
    get dependentControllerIdentifiers() {
        return this.outletDependencies.getValuesForKey(this.identifier);
    }
    get dependentContexts() {
        const identifiers = this.dependentControllerIdentifiers;
        return this.router.contexts.filter((context) => identifiers.includes(context.identifier));
    }
    hasOutlet(element, outletName) {
        return !!this.getOutlet(element, outletName) || !!this.getOutletFromMap(element, outletName);
    }
    getOutlet(element, outletName) {
        return this.application.getControllerForElementAndIdentifier(element, outletName);
    }
    getOutletFromMap(element, outletName) {
        return this.outletsByName.getValuesForKey(outletName).find((outlet) => outlet.element === element);
    }
    get scope() {
        return this.context.scope;
    }
    get schema() {
        return this.context.schema;
    }
    get identifier() {
        return this.context.identifier;
    }
    get application() {
        return this.context.application;
    }
    get router() {
        return this.application.router;
    }
}

class Context {
    constructor(module, scope) {
        this.logDebugActivity = (functionName, detail = {}) => {
            const { identifier, controller, element } = this;
            detail = Object.assign({ identifier, controller, element }, detail);
            this.application.logDebugActivity(this.identifier, functionName, detail);
        };
        this.module = module;
        this.scope = scope;
        this.controller = new module.controllerConstructor(this);
        this.bindingObserver = new BindingObserver(this, this.dispatcher);
        this.valueObserver = new ValueObserver(this, this.controller);
        this.targetObserver = new TargetObserver(this, this);
        this.outletObserver = new OutletObserver(this, this);
        try {
            this.controller.initialize();
            this.logDebugActivity("initialize");
        }
        catch (error) {
            this.handleError(error, "initializing controller");
        }
    }
    connect() {
        this.bindingObserver.start();
        this.valueObserver.start();
        this.targetObserver.start();
        this.outletObserver.start();
        try {
            this.controller.connect();
            this.logDebugActivity("connect");
        }
        catch (error) {
            this.handleError(error, "connecting controller");
        }
    }
    refresh() {
        this.outletObserver.refresh();
    }
    disconnect() {
        try {
            this.controller.disconnect();
            this.logDebugActivity("disconnect");
        }
        catch (error) {
            this.handleError(error, "disconnecting controller");
        }
        this.outletObserver.stop();
        this.targetObserver.stop();
        this.valueObserver.stop();
        this.bindingObserver.stop();
    }
    get application() {
        return this.module.application;
    }
    get identifier() {
        return this.module.identifier;
    }
    get schema() {
        return this.application.schema;
    }
    get dispatcher() {
        return this.application.dispatcher;
    }
    get element() {
        return this.scope.element;
    }
    get parentElement() {
        return this.element.parentElement;
    }
    handleError(error, message, detail = {}) {
        const { identifier, controller, element } = this;
        detail = Object.assign({ identifier, controller, element }, detail);
        this.application.handleError(error, `Error ${message}`, detail);
    }
    targetConnected(element, name) {
        this.invokeControllerMethod(`${name}TargetConnected`, element);
    }
    targetDisconnected(element, name) {
        this.invokeControllerMethod(`${name}TargetDisconnected`, element);
    }
    outletConnected(outlet, element, name) {
        this.invokeControllerMethod(`${namespaceCamelize(name)}OutletConnected`, outlet, element);
    }
    outletDisconnected(outlet, element, name) {
        this.invokeControllerMethod(`${namespaceCamelize(name)}OutletDisconnected`, outlet, element);
    }
    invokeControllerMethod(methodName, ...args) {
        const controller = this.controller;
        if (typeof controller[methodName] == "function") {
            controller[methodName](...args);
        }
    }
}

function bless(constructor) {
    return shadow(constructor, getBlessedProperties(constructor));
}
function shadow(constructor, properties) {
    const shadowConstructor = extend(constructor);
    const shadowProperties = getShadowProperties(constructor.prototype, properties);
    Object.defineProperties(shadowConstructor.prototype, shadowProperties);
    return shadowConstructor;
}
function getBlessedProperties(constructor) {
    const blessings = readInheritableStaticArrayValues(constructor, "blessings");
    return blessings.reduce((blessedProperties, blessing) => {
        const properties = blessing(constructor);
        for (const key in properties) {
            const descriptor = blessedProperties[key] || {};
            blessedProperties[key] = Object.assign(descriptor, properties[key]);
        }
        return blessedProperties;
    }, {});
}
function getShadowProperties(prototype, properties) {
    return getOwnKeys(properties).reduce((shadowProperties, key) => {
        const descriptor = getShadowedDescriptor(prototype, properties, key);
        if (descriptor) {
            Object.assign(shadowProperties, { [key]: descriptor });
        }
        return shadowProperties;
    }, {});
}
function getShadowedDescriptor(prototype, properties, key) {
    const shadowingDescriptor = Object.getOwnPropertyDescriptor(prototype, key);
    const shadowedByValue = shadowingDescriptor && "value" in shadowingDescriptor;
    if (!shadowedByValue) {
        const descriptor = Object.getOwnPropertyDescriptor(properties, key).value;
        if (shadowingDescriptor) {
            descriptor.get = shadowingDescriptor.get || descriptor.get;
            descriptor.set = shadowingDescriptor.set || descriptor.set;
        }
        return descriptor;
    }
}
const getOwnKeys = (() => {
    if (typeof Object.getOwnPropertySymbols == "function") {
        return (object) => [...Object.getOwnPropertyNames(object), ...Object.getOwnPropertySymbols(object)];
    }
    else {
        return Object.getOwnPropertyNames;
    }
})();
const extend = (() => {
    function extendWithReflect(constructor) {
        function extended() {
            return Reflect.construct(constructor, arguments, new.target);
        }
        extended.prototype = Object.create(constructor.prototype, {
            constructor: { value: extended },
        });
        Reflect.setPrototypeOf(extended, constructor);
        return extended;
    }
    function testReflectExtension() {
        const a = function () {
            this.a.call(this);
        };
        const b = extendWithReflect(a);
        b.prototype.a = function () { };
        return new b();
    }
    try {
        testReflectExtension();
        return extendWithReflect;
    }
    catch (error) {
        return (constructor) => class extended extends constructor {
        };
    }
})();

function blessDefinition(definition) {
    return {
        identifier: definition.identifier,
        controllerConstructor: bless(definition.controllerConstructor),
    };
}

class Module {
    constructor(application, definition) {
        this.application = application;
        this.definition = blessDefinition(definition);
        this.contextsByScope = new WeakMap();
        this.connectedContexts = new Set();
    }
    get identifier() {
        return this.definition.identifier;
    }
    get controllerConstructor() {
        return this.definition.controllerConstructor;
    }
    get contexts() {
        return Array.from(this.connectedContexts);
    }
    connectContextForScope(scope) {
        const context = this.fetchContextForScope(scope);
        this.connectedContexts.add(context);
        context.connect();
    }
    disconnectContextForScope(scope) {
        const context = this.contextsByScope.get(scope);
        if (context) {
            this.connectedContexts.delete(context);
            context.disconnect();
        }
    }
    fetchContextForScope(scope) {
        let context = this.contextsByScope.get(scope);
        if (!context) {
            context = new Context(this, scope);
            this.contextsByScope.set(scope, context);
        }
        return context;
    }
}

class ClassMap {
    constructor(scope) {
        this.scope = scope;
    }
    has(name) {
        return this.data.has(this.getDataKey(name));
    }
    get(name) {
        return this.getAll(name)[0];
    }
    getAll(name) {
        const tokenString = this.data.get(this.getDataKey(name)) || "";
        return tokenize(tokenString);
    }
    getAttributeName(name) {
        return this.data.getAttributeNameForKey(this.getDataKey(name));
    }
    getDataKey(name) {
        return `${name}-class`;
    }
    get data() {
        return this.scope.data;
    }
}

class DataMap {
    constructor(scope) {
        this.scope = scope;
    }
    get element() {
        return this.scope.element;
    }
    get identifier() {
        return this.scope.identifier;
    }
    get(key) {
        const name = this.getAttributeNameForKey(key);
        return this.element.getAttribute(name);
    }
    set(key, value) {
        const name = this.getAttributeNameForKey(key);
        this.element.setAttribute(name, value);
        return this.get(key);
    }
    has(key) {
        const name = this.getAttributeNameForKey(key);
        return this.element.hasAttribute(name);
    }
    delete(key) {
        if (this.has(key)) {
            const name = this.getAttributeNameForKey(key);
            this.element.removeAttribute(name);
            return true;
        }
        else {
            return false;
        }
    }
    getAttributeNameForKey(key) {
        return `data-${this.identifier}-${dasherize(key)}`;
    }
}

class Guide {
    constructor(logger) {
        this.warnedKeysByObject = new WeakMap();
        this.logger = logger;
    }
    warn(object, key, message) {
        let warnedKeys = this.warnedKeysByObject.get(object);
        if (!warnedKeys) {
            warnedKeys = new Set();
            this.warnedKeysByObject.set(object, warnedKeys);
        }
        if (!warnedKeys.has(key)) {
            warnedKeys.add(key);
            this.logger.warn(message, object);
        }
    }
}

function attributeValueContainsToken(attributeName, token) {
    return `[${attributeName}~="${token}"]`;
}

class TargetSet {
    constructor(scope) {
        this.scope = scope;
    }
    get element() {
        return this.scope.element;
    }
    get identifier() {
        return this.scope.identifier;
    }
    get schema() {
        return this.scope.schema;
    }
    has(targetName) {
        return this.find(targetName) != null;
    }
    find(...targetNames) {
        return targetNames.reduce((target, targetName) => target || this.findTarget(targetName) || this.findLegacyTarget(targetName), undefined);
    }
    findAll(...targetNames) {
        return targetNames.reduce((targets, targetName) => [
            ...targets,
            ...this.findAllTargets(targetName),
            ...this.findAllLegacyTargets(targetName),
        ], []);
    }
    findTarget(targetName) {
        const selector = this.getSelectorForTargetName(targetName);
        return this.scope.findElement(selector);
    }
    findAllTargets(targetName) {
        const selector = this.getSelectorForTargetName(targetName);
        return this.scope.findAllElements(selector);
    }
    getSelectorForTargetName(targetName) {
        const attributeName = this.schema.targetAttributeForScope(this.identifier);
        return attributeValueContainsToken(attributeName, targetName);
    }
    findLegacyTarget(targetName) {
        const selector = this.getLegacySelectorForTargetName(targetName);
        return this.deprecate(this.scope.findElement(selector), targetName);
    }
    findAllLegacyTargets(targetName) {
        const selector = this.getLegacySelectorForTargetName(targetName);
        return this.scope.findAllElements(selector).map((element) => this.deprecate(element, targetName));
    }
    getLegacySelectorForTargetName(targetName) {
        const targetDescriptor = `${this.identifier}.${targetName}`;
        return attributeValueContainsToken(this.schema.targetAttribute, targetDescriptor);
    }
    deprecate(element, targetName) {
        if (element) {
            const { identifier } = this;
            const attributeName = this.schema.targetAttribute;
            const revisedAttributeName = this.schema.targetAttributeForScope(identifier);
            this.guide.warn(element, `target:${targetName}`, `Please replace ${attributeName}="${identifier}.${targetName}" with ${revisedAttributeName}="${targetName}". ` +
                `The ${attributeName} attribute is deprecated and will be removed in a future version of Stimulus.`);
        }
        return element;
    }
    get guide() {
        return this.scope.guide;
    }
}

class OutletSet {
    constructor(scope, controllerElement) {
        this.scope = scope;
        this.controllerElement = controllerElement;
    }
    get element() {
        return this.scope.element;
    }
    get identifier() {
        return this.scope.identifier;
    }
    get schema() {
        return this.scope.schema;
    }
    has(outletName) {
        return this.find(outletName) != null;
    }
    find(...outletNames) {
        return outletNames.reduce((outlet, outletName) => outlet || this.findOutlet(outletName), undefined);
    }
    findAll(...outletNames) {
        return outletNames.reduce((outlets, outletName) => [...outlets, ...this.findAllOutlets(outletName)], []);
    }
    getSelectorForOutletName(outletName) {
        const attributeName = this.schema.outletAttributeForScope(this.identifier, outletName);
        return this.controllerElement.getAttribute(attributeName);
    }
    findOutlet(outletName) {
        const selector = this.getSelectorForOutletName(outletName);
        if (selector)
            return this.findElement(selector, outletName);
    }
    findAllOutlets(outletName) {
        const selector = this.getSelectorForOutletName(outletName);
        return selector ? this.findAllElements(selector, outletName) : [];
    }
    findElement(selector, outletName) {
        const elements = this.scope.queryElements(selector);
        return elements.filter((element) => this.matchesElement(element, selector, outletName))[0];
    }
    findAllElements(selector, outletName) {
        const elements = this.scope.queryElements(selector);
        return elements.filter((element) => this.matchesElement(element, selector, outletName));
    }
    matchesElement(element, selector, outletName) {
        const controllerAttribute = element.getAttribute(this.scope.schema.controllerAttribute) || "";
        return element.matches(selector) && controllerAttribute.split(" ").includes(outletName);
    }
}

class Scope {
    constructor(schema, element, identifier, logger) {
        this.targets = new TargetSet(this);
        this.classes = new ClassMap(this);
        this.data = new DataMap(this);
        this.containsElement = (element) => {
            return element.closest(this.controllerSelector) === this.element;
        };
        this.schema = schema;
        this.element = element;
        this.identifier = identifier;
        this.guide = new Guide(logger);
        this.outlets = new OutletSet(this.documentScope, element);
    }
    findElement(selector) {
        return this.element.matches(selector) ? this.element : this.queryElements(selector).find(this.containsElement);
    }
    findAllElements(selector) {
        return [
            ...(this.element.matches(selector) ? [this.element] : []),
            ...this.queryElements(selector).filter(this.containsElement),
        ];
    }
    queryElements(selector) {
        return Array.from(this.element.querySelectorAll(selector));
    }
    get controllerSelector() {
        return attributeValueContainsToken(this.schema.controllerAttribute, this.identifier);
    }
    get isDocumentScope() {
        return this.element === document.documentElement;
    }
    get documentScope() {
        return this.isDocumentScope
            ? this
            : new Scope(this.schema, document.documentElement, this.identifier, this.guide.logger);
    }
}

class ScopeObserver {
    constructor(element, schema, delegate) {
        this.element = element;
        this.schema = schema;
        this.delegate = delegate;
        this.valueListObserver = new ValueListObserver(this.element, this.controllerAttribute, this);
        this.scopesByIdentifierByElement = new WeakMap();
        this.scopeReferenceCounts = new WeakMap();
    }
    start() {
        this.valueListObserver.start();
    }
    stop() {
        this.valueListObserver.stop();
    }
    get controllerAttribute() {
        return this.schema.controllerAttribute;
    }
    parseValueForToken(token) {
        const { element, content: identifier } = token;
        return this.parseValueForElementAndIdentifier(element, identifier);
    }
    parseValueForElementAndIdentifier(element, identifier) {
        const scopesByIdentifier = this.fetchScopesByIdentifierForElement(element);
        let scope = scopesByIdentifier.get(identifier);
        if (!scope) {
            scope = this.delegate.createScopeForElementAndIdentifier(element, identifier);
            scopesByIdentifier.set(identifier, scope);
        }
        return scope;
    }
    elementMatchedValue(element, value) {
        const referenceCount = (this.scopeReferenceCounts.get(value) || 0) + 1;
        this.scopeReferenceCounts.set(value, referenceCount);
        if (referenceCount == 1) {
            this.delegate.scopeConnected(value);
        }
    }
    elementUnmatchedValue(element, value) {
        const referenceCount = this.scopeReferenceCounts.get(value);
        if (referenceCount) {
            this.scopeReferenceCounts.set(value, referenceCount - 1);
            if (referenceCount == 1) {
                this.delegate.scopeDisconnected(value);
            }
        }
    }
    fetchScopesByIdentifierForElement(element) {
        let scopesByIdentifier = this.scopesByIdentifierByElement.get(element);
        if (!scopesByIdentifier) {
            scopesByIdentifier = new Map();
            this.scopesByIdentifierByElement.set(element, scopesByIdentifier);
        }
        return scopesByIdentifier;
    }
}

class Router {
    constructor(application) {
        this.application = application;
        this.scopeObserver = new ScopeObserver(this.element, this.schema, this);
        this.scopesByIdentifier = new Multimap();
        this.modulesByIdentifier = new Map();
    }
    get element() {
        return this.application.element;
    }
    get schema() {
        return this.application.schema;
    }
    get logger() {
        return this.application.logger;
    }
    get controllerAttribute() {
        return this.schema.controllerAttribute;
    }
    get modules() {
        return Array.from(this.modulesByIdentifier.values());
    }
    get contexts() {
        return this.modules.reduce((contexts, module) => contexts.concat(module.contexts), []);
    }
    start() {
        this.scopeObserver.start();
    }
    stop() {
        this.scopeObserver.stop();
    }
    loadDefinition(definition) {
        this.unloadIdentifier(definition.identifier);
        const module = new Module(this.application, definition);
        this.connectModule(module);
        const afterLoad = definition.controllerConstructor.afterLoad;
        if (afterLoad) {
            afterLoad.call(definition.controllerConstructor, definition.identifier, this.application);
        }
    }
    unloadIdentifier(identifier) {
        const module = this.modulesByIdentifier.get(identifier);
        if (module) {
            this.disconnectModule(module);
        }
    }
    getContextForElementAndIdentifier(element, identifier) {
        const module = this.modulesByIdentifier.get(identifier);
        if (module) {
            return module.contexts.find((context) => context.element == element);
        }
    }
    proposeToConnectScopeForElementAndIdentifier(element, identifier) {
        const scope = this.scopeObserver.parseValueForElementAndIdentifier(element, identifier);
        if (scope) {
            this.scopeObserver.elementMatchedValue(scope.element, scope);
        }
        else {
            console.error(`Couldn't find or create scope for identifier: "${identifier}" and element:`, element);
        }
    }
    handleError(error, message, detail) {
        this.application.handleError(error, message, detail);
    }
    createScopeForElementAndIdentifier(element, identifier) {
        return new Scope(this.schema, element, identifier, this.logger);
    }
    scopeConnected(scope) {
        this.scopesByIdentifier.add(scope.identifier, scope);
        const module = this.modulesByIdentifier.get(scope.identifier);
        if (module) {
            module.connectContextForScope(scope);
        }
    }
    scopeDisconnected(scope) {
        this.scopesByIdentifier.delete(scope.identifier, scope);
        const module = this.modulesByIdentifier.get(scope.identifier);
        if (module) {
            module.disconnectContextForScope(scope);
        }
    }
    connectModule(module) {
        this.modulesByIdentifier.set(module.identifier, module);
        const scopes = this.scopesByIdentifier.getValuesForKey(module.identifier);
        scopes.forEach((scope) => module.connectContextForScope(scope));
    }
    disconnectModule(module) {
        this.modulesByIdentifier.delete(module.identifier);
        const scopes = this.scopesByIdentifier.getValuesForKey(module.identifier);
        scopes.forEach((scope) => module.disconnectContextForScope(scope));
    }
}

const defaultSchema = {
    controllerAttribute: "data-controller",
    actionAttribute: "data-action",
    targetAttribute: "data-target",
    targetAttributeForScope: (identifier) => `data-${identifier}-target`,
    outletAttributeForScope: (identifier, outlet) => `data-${identifier}-${outlet}-outlet`,
    keyMappings: Object.assign(Object.assign({ enter: "Enter", tab: "Tab", esc: "Escape", space: " ", up: "ArrowUp", down: "ArrowDown", left: "ArrowLeft", right: "ArrowRight", home: "Home", end: "End", page_up: "PageUp", page_down: "PageDown" }, objectFromEntries("abcdefghijklmnopqrstuvwxyz".split("").map((c) => [c, c]))), objectFromEntries("0123456789".split("").map((n) => [n, n]))),
};
function objectFromEntries(array) {
    return array.reduce((memo, [k, v]) => (Object.assign(Object.assign({}, memo), { [k]: v })), {});
}

class Application {
    constructor(element = document.documentElement, schema = defaultSchema) {
        this.logger = console;
        this.debug = false;
        this.logDebugActivity = (identifier, functionName, detail = {}) => {
            if (this.debug) {
                this.logFormattedMessage(identifier, functionName, detail);
            }
        };
        this.element = element;
        this.schema = schema;
        this.dispatcher = new Dispatcher(this);
        this.router = new Router(this);
        this.actionDescriptorFilters = Object.assign({}, defaultActionDescriptorFilters);
    }
    static start(element, schema) {
        const application = new this(element, schema);
        application.start();
        return application;
    }
    async start() {
        await domReady();
        this.logDebugActivity("application", "starting");
        this.dispatcher.start();
        this.router.start();
        this.logDebugActivity("application", "start");
    }
    stop() {
        this.logDebugActivity("application", "stopping");
        this.dispatcher.stop();
        this.router.stop();
        this.logDebugActivity("application", "stop");
    }
    register(identifier, controllerConstructor) {
        this.load({ identifier, controllerConstructor });
    }
    registerActionOption(name, filter) {
        this.actionDescriptorFilters[name] = filter;
    }
    load(head, ...rest) {
        const definitions = Array.isArray(head) ? head : [head, ...rest];
        definitions.forEach((definition) => {
            if (definition.controllerConstructor.shouldLoad) {
                this.router.loadDefinition(definition);
            }
        });
    }
    unload(head, ...rest) {
        const identifiers = Array.isArray(head) ? head : [head, ...rest];
        identifiers.forEach((identifier) => this.router.unloadIdentifier(identifier));
    }
    get controllers() {
        return this.router.contexts.map((context) => context.controller);
    }
    getControllerForElementAndIdentifier(element, identifier) {
        const context = this.router.getContextForElementAndIdentifier(element, identifier);
        return context ? context.controller : null;
    }
    handleError(error, message, detail) {
        var _a;
        this.logger.error(`%s\n\n%o\n\n%o`, message, error, detail);
        (_a = window.onerror) === null || _a === void 0 ? void 0 : _a.call(window, message, "", 0, 0, error);
    }
    logFormattedMessage(identifier, functionName, detail = {}) {
        detail = Object.assign({ application: this }, detail);
        this.logger.groupCollapsed(`${identifier} #${functionName}`);
        this.logger.log("details:", Object.assign({}, detail));
        this.logger.groupEnd();
    }
}
function domReady() {
    return new Promise((resolve) => {
        if (document.readyState == "loading") {
            document.addEventListener("DOMContentLoaded", () => resolve());
        }
        else {
            resolve();
        }
    });
}

function ClassPropertiesBlessing(constructor) {
    const classes = readInheritableStaticArrayValues(constructor, "classes");
    return classes.reduce((properties, classDefinition) => {
        return Object.assign(properties, propertiesForClassDefinition(classDefinition));
    }, {});
}
function propertiesForClassDefinition(key) {
    return {
        [`${key}Class`]: {
            get() {
                const { classes } = this;
                if (classes.has(key)) {
                    return classes.get(key);
                }
                else {
                    const attribute = classes.getAttributeName(key);
                    throw new Error(`Missing attribute "${attribute}"`);
                }
            },
        },
        [`${key}Classes`]: {
            get() {
                return this.classes.getAll(key);
            },
        },
        [`has${capitalize(key)}Class`]: {
            get() {
                return this.classes.has(key);
            },
        },
    };
}

function OutletPropertiesBlessing(constructor) {
    const outlets = readInheritableStaticArrayValues(constructor, "outlets");
    return outlets.reduce((properties, outletDefinition) => {
        return Object.assign(properties, propertiesForOutletDefinition(outletDefinition));
    }, {});
}
function getOutletController(controller, element, identifier) {
    return controller.application.getControllerForElementAndIdentifier(element, identifier);
}
function getControllerAndEnsureConnectedScope(controller, element, outletName) {
    let outletController = getOutletController(controller, element, outletName);
    if (outletController)
        return outletController;
    controller.application.router.proposeToConnectScopeForElementAndIdentifier(element, outletName);
    outletController = getOutletController(controller, element, outletName);
    if (outletController)
        return outletController;
}
function propertiesForOutletDefinition(name) {
    const camelizedName = namespaceCamelize(name);
    return {
        [`${camelizedName}Outlet`]: {
            get() {
                const outletElement = this.outlets.find(name);
                const selector = this.outlets.getSelectorForOutletName(name);
                if (outletElement) {
                    const outletController = getControllerAndEnsureConnectedScope(this, outletElement, name);
                    if (outletController)
                        return outletController;
                    throw new Error(`The provided outlet element is missing an outlet controller "${name}" instance for host controller "${this.identifier}"`);
                }
                throw new Error(`Missing outlet element "${name}" for host controller "${this.identifier}". Stimulus couldn't find a matching outlet element using selector "${selector}".`);
            },
        },
        [`${camelizedName}Outlets`]: {
            get() {
                const outlets = this.outlets.findAll(name);
                if (outlets.length > 0) {
                    return outlets
                        .map((outletElement) => {
                        const outletController = getControllerAndEnsureConnectedScope(this, outletElement, name);
                        if (outletController)
                            return outletController;
                        console.warn(`The provided outlet element is missing an outlet controller "${name}" instance for host controller "${this.identifier}"`, outletElement);
                    })
                        .filter((controller) => controller);
                }
                return [];
            },
        },
        [`${camelizedName}OutletElement`]: {
            get() {
                const outletElement = this.outlets.find(name);
                const selector = this.outlets.getSelectorForOutletName(name);
                if (outletElement) {
                    return outletElement;
                }
                else {
                    throw new Error(`Missing outlet element "${name}" for host controller "${this.identifier}". Stimulus couldn't find a matching outlet element using selector "${selector}".`);
                }
            },
        },
        [`${camelizedName}OutletElements`]: {
            get() {
                return this.outlets.findAll(name);
            },
        },
        [`has${capitalize(camelizedName)}Outlet`]: {
            get() {
                return this.outlets.has(name);
            },
        },
    };
}

function TargetPropertiesBlessing(constructor) {
    const targets = readInheritableStaticArrayValues(constructor, "targets");
    return targets.reduce((properties, targetDefinition) => {
        return Object.assign(properties, propertiesForTargetDefinition(targetDefinition));
    }, {});
}
function propertiesForTargetDefinition(name) {
    return {
        [`${name}Target`]: {
            get() {
                const target = this.targets.find(name);
                if (target) {
                    return target;
                }
                else {
                    throw new Error(`Missing target element "${name}" for "${this.identifier}" controller`);
                }
            },
        },
        [`${name}Targets`]: {
            get() {
                return this.targets.findAll(name);
            },
        },
        [`has${capitalize(name)}Target`]: {
            get() {
                return this.targets.has(name);
            },
        },
    };
}

function ValuePropertiesBlessing(constructor) {
    const valueDefinitionPairs = readInheritableStaticObjectPairs(constructor, "values");
    const propertyDescriptorMap = {
        valueDescriptorMap: {
            get() {
                return valueDefinitionPairs.reduce((result, valueDefinitionPair) => {
                    const valueDescriptor = parseValueDefinitionPair(valueDefinitionPair, this.identifier);
                    const attributeName = this.data.getAttributeNameForKey(valueDescriptor.key);
                    return Object.assign(result, { [attributeName]: valueDescriptor });
                }, {});
            },
        },
    };
    return valueDefinitionPairs.reduce((properties, valueDefinitionPair) => {
        return Object.assign(properties, propertiesForValueDefinitionPair(valueDefinitionPair));
    }, propertyDescriptorMap);
}
function propertiesForValueDefinitionPair(valueDefinitionPair, controller) {
    const definition = parseValueDefinitionPair(valueDefinitionPair, controller);
    const { key, name, reader: read, writer: write } = definition;
    return {
        [name]: {
            get() {
                const value = this.data.get(key);
                if (value !== null) {
                    return read(value);
                }
                else {
                    return definition.defaultValue;
                }
            },
            set(value) {
                if (value === undefined) {
                    this.data.delete(key);
                }
                else {
                    this.data.set(key, write(value));
                }
            },
        },
        [`has${capitalize(name)}`]: {
            get() {
                return this.data.has(key) || definition.hasCustomDefaultValue;
            },
        },
    };
}
function parseValueDefinitionPair([token, typeDefinition], controller) {
    return valueDescriptorForTokenAndTypeDefinition({
        controller,
        token,
        typeDefinition,
    });
}
function parseValueTypeConstant(constant) {
    switch (constant) {
        case Array:
            return "array";
        case Boolean:
            return "boolean";
        case Number:
            return "number";
        case Object:
            return "object";
        case String:
            return "string";
    }
}
function parseValueTypeDefault(defaultValue) {
    switch (typeof defaultValue) {
        case "boolean":
            return "boolean";
        case "number":
            return "number";
        case "string":
            return "string";
    }
    if (Array.isArray(defaultValue))
        return "array";
    if (Object.prototype.toString.call(defaultValue) === "[object Object]")
        return "object";
}
function parseValueTypeObject(payload) {
    const { controller, token, typeObject } = payload;
    const hasType = isSomething(typeObject.type);
    const hasDefault = isSomething(typeObject.default);
    const fullObject = hasType && hasDefault;
    const onlyType = hasType && !hasDefault;
    const onlyDefault = !hasType && hasDefault;
    const typeFromObject = parseValueTypeConstant(typeObject.type);
    const typeFromDefaultValue = parseValueTypeDefault(payload.typeObject.default);
    if (onlyType)
        return typeFromObject;
    if (onlyDefault)
        return typeFromDefaultValue;
    if (typeFromObject !== typeFromDefaultValue) {
        const propertyPath = controller ? `${controller}.${token}` : token;
        throw new Error(`The specified default value for the Stimulus Value "${propertyPath}" must match the defined type "${typeFromObject}". The provided default value of "${typeObject.default}" is of type "${typeFromDefaultValue}".`);
    }
    if (fullObject)
        return typeFromObject;
}
function parseValueTypeDefinition(payload) {
    const { controller, token, typeDefinition } = payload;
    const typeObject = { controller, token, typeObject: typeDefinition };
    const typeFromObject = parseValueTypeObject(typeObject);
    const typeFromDefaultValue = parseValueTypeDefault(typeDefinition);
    const typeFromConstant = parseValueTypeConstant(typeDefinition);
    const type = typeFromObject || typeFromDefaultValue || typeFromConstant;
    if (type)
        return type;
    const propertyPath = controller ? `${controller}.${typeDefinition}` : token;
    throw new Error(`Unknown value type "${propertyPath}" for "${token}" value`);
}
function defaultValueForDefinition(typeDefinition) {
    const constant = parseValueTypeConstant(typeDefinition);
    if (constant)
        return defaultValuesByType[constant];
    const hasDefault = hasProperty(typeDefinition, "default");
    const hasType = hasProperty(typeDefinition, "type");
    const typeObject = typeDefinition;
    if (hasDefault)
        return typeObject.default;
    if (hasType) {
        const { type } = typeObject;
        const constantFromType = parseValueTypeConstant(type);
        if (constantFromType)
            return defaultValuesByType[constantFromType];
    }
    return typeDefinition;
}
function valueDescriptorForTokenAndTypeDefinition(payload) {
    const { token, typeDefinition } = payload;
    const key = `${dasherize(token)}-value`;
    const type = parseValueTypeDefinition(payload);
    return {
        type,
        key,
        name: camelize(key),
        get defaultValue() {
            return defaultValueForDefinition(typeDefinition);
        },
        get hasCustomDefaultValue() {
            return parseValueTypeDefault(typeDefinition) !== undefined;
        },
        reader: readers[type],
        writer: writers[type] || writers.default,
    };
}
const defaultValuesByType = {
    get array() {
        return [];
    },
    boolean: false,
    number: 0,
    get object() {
        return {};
    },
    string: "",
};
const readers = {
    array(value) {
        const array = JSON.parse(value);
        if (!Array.isArray(array)) {
            throw new TypeError(`expected value of type "array" but instead got value "${value}" of type "${parseValueTypeDefault(array)}"`);
        }
        return array;
    },
    boolean(value) {
        return !(value == "0" || String(value).toLowerCase() == "false");
    },
    number(value) {
        return Number(value.replace(/_/g, ""));
    },
    object(value) {
        const object = JSON.parse(value);
        if (object === null || typeof object != "object" || Array.isArray(object)) {
            throw new TypeError(`expected value of type "object" but instead got value "${value}" of type "${parseValueTypeDefault(object)}"`);
        }
        return object;
    },
    string(value) {
        return value;
    },
};
const writers = {
    default: writeString,
    array: writeJSON,
    object: writeJSON,
};
function writeJSON(value) {
    return JSON.stringify(value);
}
function writeString(value) {
    return `${value}`;
}

class Controller {
    constructor(context) {
        this.context = context;
    }
    static get shouldLoad() {
        return true;
    }
    static afterLoad(_identifier, _application) {
        return;
    }
    get application() {
        return this.context.application;
    }
    get scope() {
        return this.context.scope;
    }
    get element() {
        return this.scope.element;
    }
    get identifier() {
        return this.scope.identifier;
    }
    get targets() {
        return this.scope.targets;
    }
    get outlets() {
        return this.scope.outlets;
    }
    get classes() {
        return this.scope.classes;
    }
    get data() {
        return this.scope.data;
    }
    initialize() {
    }
    connect() {
    }
    disconnect() {
    }
    dispatch(eventName, { target = this.element, detail = {}, prefix = this.identifier, bubbles = true, cancelable = true, } = {}) {
        const type = prefix ? `${prefix}:${eventName}` : eventName;
        const event = new CustomEvent(type, { detail, bubbles, cancelable });
        target.dispatchEvent(event);
        return event;
    }
}
Controller.blessings = [
    ClassPropertiesBlessing,
    TargetPropertiesBlessing,
    ValuePropertiesBlessing,
    OutletPropertiesBlessing,
];
Controller.targets = [];
Controller.outlets = [];
Controller.values = {};




/***/ },

/***/ "./core-bundle/assets/controllers/clipboard-controller.js"
/*!****************************************************************!*\
  !*** ./core-bundle/assets/controllers/clipboard-controller.js ***!
  \****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _default)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "write",
    value: function write() {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(this.contentValue)["catch"](this.clipboardFallback.bind(this));
      } else {
        this.clipboardFallback();
      }
    }
  }, {
    key: "clipboardFallback",
    value: function clipboardFallback() {
      var input = document.createElement('input');
      input.value = this.contentValue;
      document.body.appendChild(input);
      input.select();
      input.setSelectionRange(0, 99999);
      document.execCommand('copy');
      document.body.removeChild(input);
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller);
_defineProperty(_default, "values", {
  content: String
});


/***/ },

/***/ "./core-bundle/assets/controllers/color-scheme-controller.js"
/*!*******************************************************************!*\
  !*** ./core-bundle/assets/controllers/color-scheme-controller.js ***!
  \*******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _default)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var prefersDark = function prefersDark() {
  var prefersDark = localStorage.getItem('contao--prefers-dark');
  if (null === prefersDark) {
    return !!window.matchMedia('(prefers-color-scheme: dark)').matches;
  }
  return prefersDark === 'true';
};
var setColorScheme = function setColorScheme() {
  document.documentElement.dataset.colorScheme = prefersDark() ? 'dark' : 'light';
};
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', setColorScheme);
setColorScheme();
var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "initialize",
    value: function initialize() {
      this.toggle = this.toggle.bind(this);
      this.setLabel = this.setLabel.bind(this);
    }
  }, {
    key: "connect",
    value: function connect() {
      this.element.addEventListener('click', this.toggle);
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', this.setLabel);
      this.setLabel();
    }
  }, {
    key: "disconnect",
    value: function disconnect() {
      this.element.removeEventListener('click', this.toggle);
    }
  }, {
    key: "toggle",
    value: function toggle(e) {
      e.preventDefault();
      var isDark = !prefersDark();
      if (isDark === window.matchMedia('(prefers-color-scheme: dark)').matches) {
        localStorage.removeItem('contao--prefers-dark');
      } else {
        localStorage.setItem('contao--prefers-dark', String(isDark));
      }
      setColorScheme();

      // Change the label after the dropdown is hidden
      setTimeout(this.setLabel, 300);
    }
  }, {
    key: "setLabel",
    value: function setLabel() {
      if (!this.hasLabelTarget) {
        return;
      }
      var label = this.i18nValue[prefersDark() ? 'light' : 'dark'];
      this.labelTarget.title = label;
      this.labelTarget.innerText = label;
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller);
_defineProperty(_default, "targets", ['label']);
_defineProperty(_default, "values", {
  i18n: {
    type: Object,
    "default": {
      light: 'Disable dark mode',
      dark: 'Enable dark mode'
    }
  }
});


/***/ },

/***/ "./core-bundle/assets/controllers/image-size-controller.js"
/*!*****************************************************************!*\
  !*** ./core-bundle/assets/controllers/image-size-controller.js ***!
  \*****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _default)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "initialize",
    value: function initialize() {
      this.updateWizard = this.updateWizard.bind(this);
      this.openModal = this.openModal.bind(this);
    }
  }, {
    key: "connect",
    value: function connect() {
      this.select = this.element.querySelector('select');
      this.button = document.createElement('button');
      this.button.type = 'button';
      this.button.title = '';
      this.buttonImage = document.createElement('img');
      this.button.append(this.buttonImage);
      this.element.parentNode.classList.add('wizard');
      this.element.after(this.button);
      this.select.addEventListener('change', this.updateWizard);
      this.button.addEventListener('click', this.openModal);
      this.updateWizard();
    }
  }, {
    key: "disconnect",
    value: function disconnect() {
      this.element.parentNode.classList.remove('wizard');
      this.select.removeEventListener('change', this.updateWizard);
      this.buttonImage.remove();
      this.button.remove();
    }
  }, {
    key: "updateWizard",
    value: function updateWizard() {
      if (this.canEdit()) {
        this.button.title = this.configValue.title;
        this.button.disabled = false;
        this.buttonImage.src = this.configValue.icon;
      } else {
        this.button.title = '';
        this.button.disabled = true;
        this.buttonImage.src = this.configValue.iconDisabled;
      }
    }
  }, {
    key: "openModal",
    value: function openModal() {
      Backend.openModalIframe({
        title: this.configValue.title,
        url: "".concat(this.configValue.href, "&id=").concat(this.select.value)
      });
    }
  }, {
    key: "canEdit",
    value: function canEdit() {
      return this.configValue.ids.includes(Number(this.select.value));
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller);
_defineProperty(_default, "values", {
  config: Object
});


/***/ },

/***/ "./core-bundle/assets/controllers/jump-targets-controller.js"
/*!*******************************************************************!*\
  !*** ./core-bundle/assets/controllers/jump-targets-controller.js ***!
  \*******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _default)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "connect",
    value: function connect() {
      this.rebuildNavigation();
      this.connected = true;
    }
  }, {
    key: "sectionTargetConnected",
    value: function sectionTargetConnected() {
      if (!this.connected) {
        return;
      }
      this.rebuildNavigation();
    }
  }, {
    key: "rebuildNavigation",
    value: function rebuildNavigation() {
      var _this = this;
      if (!this.hasNavigationTarget) {
        return;
      }
      var links = document.createElement('ul');
      this.sectionTargets.forEach(function (el) {
        var action = document.createElement('button');
        action.innerText = el.getAttribute("data-".concat(_this.identifier, "-label-value"));
        action.addEventListener('click', function (event) {
          event.preventDefault();
          _this.dispatch('scrollto', {
            target: el
          });
          el.scrollIntoView();
        });
        var li = document.createElement('li');
        li.append(action);
        links.append(li);
      });
      this.navigationTarget.replaceChildren(links);
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller);
_defineProperty(_default, "targets", ['navigation', 'section']);


/***/ },

/***/ "./core-bundle/assets/controllers/limit-height-controller.js"
/*!*******************************************************************!*\
  !*** ./core-bundle/assets/controllers/limit-height-controller.js ***!
  \*******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _default)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _superPropGet(t, o, e, r) { var p = _get(_getPrototypeOf(1 & r ? t.prototype : t), o, e); return 2 & r && "function" == typeof p ? function (t) { return p.apply(e, t); } : p; }
function _get() { return _get = "undefined" != typeof Reflect && Reflect.get ? Reflect.get.bind() : function (e, t, r) { var p = _superPropBase(e, t); if (p) { var n = Object.getOwnPropertyDescriptor(p, t); return n.get ? n.get.call(arguments.length < 3 ? e : r) : n.value; } }, _get.apply(null, arguments); }
function _superPropBase(t, o) { for (; !{}.hasOwnProperty.call(t, o) && null !== (t = _getPrototypeOf(t));); return t; }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "initialize",
    value: function initialize() {
      _superPropGet(_default, "initialize", this, 3)([]);
      this.togglerMap = new WeakMap();
      this.nextId = 1;
    }
  }, {
    key: "operationTargetConnected",
    value: function operationTargetConnected() {
      this.updateOperation();
    }
  }, {
    key: "nodeTargetConnected",
    value: function nodeTargetConnected(node) {
      var _this = this;
      var style = window.getComputedStyle(node, null);
      var padding = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
      var height = node.clientHeight - padding;

      // Resize the element if it is higher than the maximum height
      if (this.maxValue > height) {
        return;
      }
      if (!node.id) {
        node.id = "limit-height-".concat(this.nextId++);
      }
      node.style.overflow = 'hidden';
      node.style.maxHeight = "".concat(this.maxValue, "px");
      var button = document.createElement('button');
      button.setAttribute('type', 'button');
      button.title = this.expandValue;
      button.innerHTML = '<span>...</span>';
      button.classList.add('unselectable');
      button.setAttribute('aria-expanded', 'false');
      button.setAttribute('aria-controls', node.id);
      button.addEventListener('click', function (event) {
        event.preventDefault();
        _this.toggle(node);
        _this.updateOperation(event);
      });
      var toggler = document.createElement('div');
      toggler.classList.add('limit_toggler');
      toggler.append(button);
      this.togglerMap.set(node, toggler);
      node.append(toggler);
      this.updateOperation();
    }
  }, {
    key: "nodeTargetDisconnected",
    value: function nodeTargetDisconnected(node) {
      if (!this.togglerMap.has(node)) {
        return;
      }
      this.togglerMap.get(node).remove();
      this.togglerMap["delete"](node);
      node.style.overflow = '';
      node.style.maxHeight = '';
    }
  }, {
    key: "toggle",
    value: function toggle(node) {
      if (node.style.maxHeight === '') {
        this.collapse(node);
      } else {
        this.expand(node);
      }
    }
  }, {
    key: "expand",
    value: function expand(node) {
      if (!this.togglerMap.has(node)) {
        return;
      }
      node.style.maxHeight = '';
      var button = this.togglerMap.get(node).querySelector('button');
      button.title = this.collapseValue;
      button.setAttribute('aria-expanded', 'true');
    }
  }, {
    key: "collapse",
    value: function collapse(node) {
      if (!this.togglerMap.has(node)) {
        return;
      }
      node.style.maxHeight = "".concat(this.maxValue, "px");
      var button = this.togglerMap.get(node).querySelector('button');
      button.title = this.expandValue;
      button.setAttribute('aria-expanded', 'false');
    }
  }, {
    key: "toggleAll",
    value: function toggleAll(event) {
      var _this2 = this;
      event.preventDefault();
      var isExpanded = this.hasExpanded() ^ event.altKey;
      this.nodeTargets.forEach(function (node) {
        if (isExpanded) {
          _this2.collapse(node);
        } else {
          _this2.expand(node);
        }
      });
      this.updateOperation(event);
    }
  }, {
    key: "keypress",
    value: function keypress(event) {
      this.updateOperation(event);
    }
  }, {
    key: "updateOperation",
    value: function updateOperation(event) {
      var _this3 = this;
      if (!this.hasOperationTarget) {
        return;
      }
      var hasTogglers = !!this.nodeTargets.find(function (el) {
        return _this3.togglerMap.has(el);
      });
      var expanded = this.hasExpanded();
      this.operationTarget.style.display = hasTogglers ? '' : 'none';
      this.operationTarget.setAttribute('aria-controls', this.nodeTargets.map(function (el) {
        return el.id;
      }).join(' '));
      this.operationTarget.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      if (expanded ^ (event ? event.altKey : false)) {
        this.operationTarget.innerText = this.collapseAllValue;
        this.operationTarget.title = this.collapseAllTitleValue;
      } else {
        this.operationTarget.innerText = this.expandAllValue;
        this.operationTarget.title = this.expandAllTitleValue;
      }
    }
  }, {
    key: "hasExpanded",
    value: function hasExpanded() {
      var _this4 = this;
      return !!this.nodeTargets.find(function (el) {
        return _this4.togglerMap.has(el) && el.style.maxHeight === '';
      });
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller);
_defineProperty(_default, "values", {
  max: Number,
  expand: String,
  collapse: String,
  expandAll: String,
  expandAllTitle: String,
  collapseAll: String,
  collapseAllTitle: String
});
_defineProperty(_default, "targets", ['operation', 'node']);


/***/ },

/***/ "./core-bundle/assets/controllers/metawizard-controller.js"
/*!*****************************************************************!*\
  !*** ./core-bundle/assets/controllers/metawizard-controller.js ***!
  \*****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _default)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "delete",
    value: function _delete() {
      this.inputTargets.forEach(function (input) {
        input.value = '';
      });
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller);
_defineProperty(_default, "targets", ['input']);


/***/ },

/***/ "./core-bundle/assets/controllers/scroll-offset-controller.js"
/*!********************************************************************!*\
  !*** ./core-bundle/assets/controllers/scroll-offset-controller.js ***!
  \********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _default)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "initialize",
    value: function initialize() {
      this.store = this.store.bind(this);
    }
  }, {
    key: "connect",
    value: function connect() {
      if (!this.offset) return;
      window.scrollTo({
        top: this.offset,
        behavior: this.behaviorValue,
        block: this.blockValue
      });
      this.offset = null;
    }
  }, {
    key: "scrollToTargetConnected",
    value: function scrollToTargetConnected() {
      this.scrollToTarget.scrollIntoView({
        behavior: this.behaviorValue,
        block: this.blockValue
      });
    }
  }, {
    key: "autoFocusTargetConnected",
    value: function autoFocusTargetConnected() {
      if (this.offset || this.autoFocus) return;
      var input = this.autoFocusTarget;
      if (input.disabled || input.readonly || !input.offsetWidth || !input.offsetHeight || input.closest('.chzn-search') || input.autocomplete && input.autocomplete !== 'off') {
        return;
      }
      this.autoFocus = true;
      input.focus();
    }
  }, {
    key: "store",
    value: function store() {
      this.offset = this.element.scrollTop;
    }
  }, {
    key: "discard",
    value: function discard() {
      this.offset = null;
    }
  }, {
    key: "offset",
    get: function get() {
      var value = window.sessionStorage.getItem(this.sessionKeyValue);
      return value ? parseInt(value) : null;
    },
    set: function set(value) {
      if (value === null || value === undefined) {
        window.sessionStorage.removeItem(this.sessionKeyValue);
      } else {
        window.sessionStorage.setItem(this.sessionKeyValue, String(value));
      }
    }
  }], [{
    key: "afterLoad",
    value:
    // Backwards compatibility: automatically register the Stimulus controller if the legacy methods are used
    function afterLoad(identifier, application) {
      var loadFallback = function loadFallback() {
        return new Promise(function (resolve, reject) {
          var controller = application.getControllerForElementAndIdentifier(document.documentElement, identifier);
          if (controller) {
            resolve(controller);
            return;
          }
          var controllerAttribute = application.schema.controllerAttribute;
          document.documentElement.setAttribute(controllerAttribute, "".concat(document.documentElement.getAttribute(controllerAttribute) || '', " ").concat(identifier));
          setTimeout(function () {
            var controller = application.getControllerForElementAndIdentifier(document.documentElement, identifier);
            controller && resolve(controller) || reject(controller);
          }, 100);
        });
      };
      if (window.Backend && !window.Backend.initScrollOffset) {
        window.Backend.initScrollOffset = function () {
          console.warn('Backend.initScrollOffset() is deprecated. Please use the Stimulus controller instead.');
          loadFallback();
        };
      }
      if (window.Backend && !window.Backend.getScrollOffset) {
        window.Backend.getScrollOffset = function () {
          console.warn('Backend.getScrollOffset() is deprecated. Please use the Stimulus controller instead.');
          loadFallback().then(function (controller) {
            return controller.discard();
          });
        };
      }
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller);
_defineProperty(_default, "targets", ['scrollTo', 'autoFocus']);
_defineProperty(_default, "values", {
  sessionKey: {
    type: String,
    "default": 'contao_backend_offset'
  },
  behavior: {
    type: String,
    "default": 'instant'
  },
  block: {
    type: String,
    "default": 'center'
  }
});


/***/ },

/***/ "./core-bundle/assets/controllers/toggle-fieldset-controller.js"
/*!**********************************************************************!*\
  !*** ./core-bundle/assets/controllers/toggle-fieldset-controller.js ***!
  \**********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _default)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "connect",
    value: function connect() {
      if (this.element.querySelectorAll('label.error, label.mandatory').length) {
        this.element.classList.remove(this.collapsedClass);
      } else if (this.element.classList.contains('hide')) {
        if (window.console) {
          console.warn("Using class \"hide\" on a fieldset is deprecated and will be removed in Contao 6. Use class \"".concat(this.collapsedClass, "\" instead."));
        }
        this.element.classList.add(this.collapsedClass);
      }
      if (this.element.classList.contains(this.collapsedClass)) {
        this.setAriaExpanded(false);
      } else {
        this.setAriaExpanded(true);
      }
    }
  }, {
    key: "toggle",
    value: function toggle() {
      if (this.element.classList.contains(this.collapsedClass)) {
        this.open();
        this.setAriaExpanded(true);
      } else {
        this.close();
        this.setAriaExpanded(false);
      }
    }
  }, {
    key: "open",
    value: function open() {
      if (!this.element.classList.contains(this.collapsedClass)) {
        return;
      }
      this.element.classList.remove(this.collapsedClass);
      this.storeState(1);
    }
  }, {
    key: "close",
    value: function close() {
      if (this.element.classList.contains(this.collapsedClass)) {
        return;
      }
      var form = this.element.closest('form');
      var input = this.element.querySelectorAll('[required]');
      var collapse = true;
      for (var i = 0; i < input.length; i++) {
        if (!input[i].value) {
          collapse = false;
          break;
        }
      }
      if (!collapse) {
        if (typeof form.checkValidity == 'function') {
          form.querySelector('button[type="submit"]').click();
        }
      } else {
        this.element.classList.add(this.collapsedClass);
        this.storeState(0);
      }
    }
  }, {
    key: "storeState",
    value: function storeState(state) {
      if (!this.hasIdValue || !this.hasTableValue) {
        return;
      }
      fetch(window.location.href, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
          action: 'toggleFieldset',
          id: this.idValue,
          table: this.tableValue,
          state: state
        })
      });
    }
  }, {
    key: "setAriaExpanded",
    value: function setAriaExpanded(state) {
      var button = this.element.querySelector('button');
      if (button) {
        button.ariaExpanded = state;
      }
    }
  }], [{
    key: "afterLoad",
    value: function afterLoad(identifier, application) {
      var addController = function addController(el, id, table) {
        var fs = el.parentNode;
        fs.dataset.controller = "".concat(fs.dataset.controller || '', " ").concat(identifier);
        fs.setAttribute("data-".concat(identifier, "-id-value"), id);
        fs.setAttribute("data-".concat(identifier, "-table-value"), table);
        fs.setAttribute("data-".concat(identifier, "-collapsed-class"), 'collapsed');
        el.setAttribute('tabindex', 0);
        el.setAttribute('data-action', "click->".concat(identifier, "#toggle keydown.enter->").concat(identifier, "#toggle keydown.space->").concat(identifier, "#prevent:prevent keyup.space->").concat(identifier, "#toggle:prevent"));
      };
      var migrateLegacy = function migrateLegacy() {
        document.querySelectorAll('legend[data-toggle-fieldset]').forEach(function (el) {
          if (window.console) {
            console.warn("Using the \"data-toggle-fieldset\" attribute on fieldset legends is deprecated and will be removed in Contao 6. Apply the \"".concat(identifier, "\" Stimulus controller instead."));
          }
          var _JSON$parse = JSON.parse(el.getAttribute('data-toggle-fieldset')),
            id = _JSON$parse.id,
            table = _JSON$parse.table;
          addController(el, id, table);
        });
        AjaxRequest.toggleFieldset = function (el, id, table) {
          var fs = el.parentNode;

          // Already clicked, Stimulus controller was added dynamically
          if (application.getControllerForElementAndIdentifier(fs, identifier)) {
            return;
          }
          if (window.console) {
            console.warn('Using AjaxRequest.toggleFieldset() is deprecated and will be removed in Contao 6. Apply the Stimulus actions instead.');
          }
          addController(el, id, table);

          // Optimistically wait until Stimulus has registered the new controller
          setTimeout(function () {
            application.getControllerForElementAndIdentifier(fs, identifier).toggle();
          }, 100);
        };
      };

      // Called as soon as registered, so DOM may not have been loaded yet
      if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", migrateLegacy);
      } else {
        migrateLegacy();
      }
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller);
_defineProperty(_default, "values", {
  id: String,
  table: String
});
_defineProperty(_default, "classes", ['collapsed']);


/***/ },

/***/ "./core-bundle/assets/controllers/toggle-navigation-controller.js"
/*!************************************************************************!*\
  !*** ./core-bundle/assets/controllers/toggle-navigation-controller.js ***!
  \************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _default)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "toggle",
    value: function toggle(_ref) {
      var currentTarget = _ref.currentTarget,
        category = _ref.params.category;
      var el = currentTarget.parentNode;
      var collapsed = el.classList.toggle(this.collapsedClass);
      currentTarget.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      currentTarget.setAttribute('title', collapsed ? this.expandTitleValue : this.collapseTitleValue);
      this.sendRequest(category, collapsed);
    }
  }, {
    key: "sendRequest",
    value: function sendRequest(category, collapsed) {
      fetch(this.urlValue, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
          action: 'toggleNavigation',
          id: category,
          state: collapsed ? 0 : 1,
          REQUEST_TOKEN: this.requestTokenValue
        })
      });
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller);
_defineProperty(_default, "classes", ['collapsed']);
_defineProperty(_default, "values", {
  url: String,
  requestToken: String,
  expandTitle: String,
  collapseTitle: String
});


/***/ },

/***/ "./core-bundle/assets/controllers/toggle-nodes-controller.js"
/*!*******************************************************************!*\
  !*** ./core-bundle/assets/controllers/toggle-nodes-controller.js ***!
  \*******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _default)
/* harmony export */ });
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _classCallCheck(a, n) { if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function"); }
function _defineProperties(e, r) { for (var t = 0; t < r.length; t++) { var o = r[t]; o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, _toPropertyKey(o.key), o); } }
function _createClass(e, r, t) { return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", { writable: !1 }), e; }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == _typeof(e) || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inherits(t, e) { if ("function" != typeof e && null !== e) throw new TypeError("Super expression must either be null or a function"); t.prototype = Object.create(e && e.prototype, { constructor: { value: t, writable: !0, configurable: !0 } }), Object.defineProperty(t, "prototype", { writable: !1 }), e && _setPrototypeOf(t, e); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }

var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "operationTargetConnected",
    value: function operationTargetConnected() {
      this.updateOperation();
    }
  }, {
    key: "childTargetConnected",
    value: function childTargetConnected() {
      this.updateOperation();
    }
  }, {
    key: "toggle",
    value: function toggle(event) {
      event.preventDefault();
      var el = event.currentTarget;
      this.toggleToggler(el, event.params.id, event.params.level, event.params.folder);
    }
  }, {
    key: "toggleToggler",
    value: function toggleToggler(el, id, level, folder) {
      var item = document.id(id);
      if (item && item.style.display === 'none') {
        this.showChild(item);
        this.expandToggler(el);
        this.updateState(el, id, 1);
      } else if (item) {
        this.hideChild(item);
        this.collapseToggler(el);
        this.updateState(el, id, 0);
      } else {
        this.fetchChild(el, id, level, folder);
      }
      this.updateOperation();
    }
  }, {
    key: "expandToggler",
    value: function expandToggler(el) {
      el.classList.add('foldable--open');
      el.title = this.collapseValue;
    }
  }, {
    key: "collapseToggler",
    value: function collapseToggler(el) {
      el.classList.remove('foldable--open');
      el.title = this.expandValue;
    }
  }, {
    key: "loadToggler",
    value: function loadToggler(el, enabled) {
      el.classList[enabled ? 'add' : 'remove']('foldable--loading');
    }
  }, {
    key: "showChild",
    value: function showChild(item) {
      item.style.display = '';
    }
  }, {
    key: "hideChild",
    value: function hideChild(item) {
      item.style.display = 'none';
    }
  }, {
    key: "fetchChild",
    value: function () {
      var _fetchChild = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee(el, id, level, folder) {
        var url, search, response, txt, li, ul, isFolder, parent, next;
        return _regenerator().w(function (_context) {
          while (1) switch (_context.n) {
            case 0:
              this.loadToggler(el, true);
              url = new URL(location.href);
              search = url.searchParams;
              search.set('ref', this.refererIdValue);
              url.search = search.toString();
              _context.n = 1;
              return fetch(url, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded',
                  'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                  'action': this.loadActionValue,
                  'id': id,
                  'level': level,
                  'folder': folder,
                  'state': 1,
                  'REQUEST_TOKEN': this.requestTokenValue
                })
              });
            case 1:
              response = _context.v;
              if (!response.ok) {
                _context.n = 8;
                break;
              }
              _context.n = 2;
              return response.text();
            case 2:
              txt = _context.v;
              li = document.createElement('li');
              li.id = id;
              li.classList.add('parent');
              li.style.display = 'inline';
              li.setAttribute("data-".concat(this.identifier, "-target"), level === 0 ? 'child rootChild' : 'child');
              ul = document.createElement('ul');
              ul.classList.add('level_' + level);
              ul.innerHTML = txt;
              li.append(ul);
              if (!(this.modeValue === 5)) {
                _context.n = 3;
                break;
              }
              el.closest('li').after(li);
              _context.n = 7;
              break;
            case 3:
              isFolder = false, parent = el.closest('li');
            case 4:
              if (!(typeOf(parent) === 'element' && parent.tagName === 'LI' && (next = parent.nextElementSibling))) {
                _context.n = 6;
                break;
              }
              parent = next;
              if (!parent.classList.contains('tl_folder')) {
                _context.n = 5;
                break;
              }
              isFolder = true;
              return _context.a(3, 6);
            case 5:
              _context.n = 4;
              break;
            case 6:
              if (isFolder) {
                parent.before(li);
              } else {
                parent.after(li);
              }
            case 7:
              window.dispatchEvent(new CustomEvent('structure'));
              this.expandToggler(el);

              // HOOK (see #6752)
              window.fireEvent('ajax_change');
            case 8:
              this.loadToggler(el, false);
            case 9:
              return _context.a(2);
          }
        }, _callee, this);
      }));
      function fetchChild(_x, _x2, _x3, _x4) {
        return _fetchChild.apply(this, arguments);
      }
      return fetchChild;
    }()
  }, {
    key: "toggleAll",
    value: function () {
      var _toggleAll = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2(event) {
        var _this = this;
        var href, promises;
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.n) {
            case 0:
              event.preventDefault();
              href = event.currentTarget.href;
              if (!(this.hasExpandedRoot() ^ (event ? event.altKey : false))) {
                _context2.n = 1;
                break;
              }
              this.updateAllState(href, 0);
              this.toggleTargets.forEach(function (el) {
                return _this.collapseToggler(el);
              });
              this.childTargets.forEach(function (item) {
                return item.style.display = 'none';
              });
              _context2.n = 3;
              break;
            case 1:
              this.childTargets.forEach(function (el) {
                return el.remove();
              });
              this.toggleTargets.forEach(function (el) {
                return _this.loadToggler(el, true);
              });
              _context2.n = 2;
              return this.updateAllState(href, 1);
            case 2:
              promises = [];
              this.toggleTargets.forEach(function (el) {
                promises.push(_this.fetchChild(el, el.getAttribute("data-".concat(_this.identifier, "-id-param")), 0, el.getAttribute("data-".concat(_this.identifier, "-folder-param"))));
              });
              _context2.n = 3;
              return Promise.all(promises);
            case 3:
              this.updateOperation();
            case 4:
              return _context2.a(2);
          }
        }, _callee2, this);
      }));
      function toggleAll(_x5) {
        return _toggleAll.apply(this, arguments);
      }
      return toggleAll;
    }()
  }, {
    key: "keypress",
    value: function keypress(event) {
      this.updateOperation(event);
    }
  }, {
    key: "updateState",
    value: function () {
      var _updateState = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3(el, id, state) {
        return _regenerator().w(function (_context3) {
          while (1) switch (_context3.n) {
            case 0:
              _context3.n = 1;
              return fetch(location.href, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/x-www-form-urlencoded',
                  'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                  'action': this.toggleActionValue,
                  'id': id,
                  'state': state,
                  'REQUEST_TOKEN': this.requestTokenValue
                })
              });
            case 1:
              return _context3.a(2);
          }
        }, _callee3, this);
      }));
      function updateState(_x6, _x7, _x8) {
        return _updateState.apply(this, arguments);
      }
      return updateState;
    }()
  }, {
    key: "updateAllState",
    value: function () {
      var _updateAllState = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee4(href, state) {
        return _regenerator().w(function (_context4) {
          while (1) switch (_context4.n) {
            case 0:
              _context4.n = 1;
              return fetch("".concat(href, "&state=").concat(state));
            case 1:
              return _context4.a(2);
          }
        }, _callee4);
      }));
      function updateAllState(_x9, _x0) {
        return _updateAllState.apply(this, arguments);
      }
      return updateAllState;
    }()
  }, {
    key: "updateOperation",
    value: function updateOperation(event) {
      if (!this.hasOperationTarget) {
        return;
      }
      if (this.hasExpandedRoot() ^ (event ? event.altKey : false)) {
        this.operationTarget.innerText = this.collapseAllValue;
        this.operationTarget.title = this.collapseAllTitleValue;
      } else {
        this.operationTarget.innerText = this.expandAllValue;
        this.operationTarget.title = this.expandAllTitleValue;
      }
    }
  }, {
    key: "hasExpandedRoot",
    value: function hasExpandedRoot() {
      return !!this.rootChildTargets.find(function (el) {
        return el.style.display !== 'none';
      });
    }
  }]);
}(_hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Controller);
_defineProperty(_default, "values", {
  mode: {
    type: Number,
    "default": 5
  },
  toggleAction: String,
  loadAction: String,
  requestToken: String,
  refererId: String,
  expand: String,
  collapse: String,
  expandAll: String,
  expandAllTitle: String,
  collapseAll: String,
  collapseAllTitle: String
});
_defineProperty(_default, "targets", ['operation', 'node', 'toggle', 'child', 'rootChild']);


/***/ },

/***/ "./core-bundle/assets/scripts/core.js"
/*!********************************************!*\
  !*** ./core-bundle/assets/scripts/core.js ***!
  \********************************************/
() {

/**
 * Provide methods to handle Ajax requests.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
window.AjaxRequest = {
  /**
   * Toggle the navigation menu
   *
   * @param {object} el  The DOM element
   * @param {string} id  The ID of the menu item
   * @param {string} url The Ajax URL
   *
   * @returns {boolean}
   */
  toggleNavigation: function toggleNavigation(el, id, url) {
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
        new Request.Contao({
          url: url
        }).post({
          'action': 'toggleNavigation',
          'id': id,
          'state': 1,
          'REQUEST_TOKEN': Contao.request_token
        });
      } else {
        parent.addClass('collapsed');
        $(el).setAttribute('aria-expanded', 'false');
        $(el).setAttribute('title', Contao.lang.expand);
        new Request.Contao({
          url: url
        }).post({
          'action': 'toggleNavigation',
          'id': id,
          'state': 0,
          'REQUEST_TOKEN': Contao.request_token
        });
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
  toggleStructure: function toggleStructure(el, id, level, mode) {
    if (window.console) {
      console.warn('AjaxRequest.toggleStructure() is deprecated. Please use the stimulus controller instead.');
    }
    var item = $(id);
    if (item) {
      if (item.getStyle('display') == 'none') {
        item.setStyle('display', null);
        $(el).addClass('foldable--open');
        $(el).setAttribute('title', Contao.lang.collapse);
        new Request.Contao({
          field: el
        }).post({
          'action': 'toggleStructure',
          'id': id,
          'state': 1,
          'REQUEST_TOKEN': Contao.request_token
        });
      } else {
        item.setStyle('display', 'none');
        $(el).removeClass('foldable--open');
        $(el).setAttribute('title', Contao.lang.expand);
        new Request.Contao({
          field: el
        }).post({
          'action': 'toggleStructure',
          'id': id,
          'state': 0,
          'REQUEST_TOKEN': Contao.request_token
        });
      }
      return false;
    }
    new Request.Contao({
      field: el,
      evalScripts: true,
      onRequest: function onRequest() {
        AjaxRequest.displayBox(Contao.lang.loading + ' …');
      },
      onSuccess: function onSuccess(txt) {
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
        li.getElements('a').each(function (el) {
          el.href = el.href.replace(/&ref=[a-f0-9]+/, '&ref=' + Contao.referer_id);
        });
        $(el).addClass('foldable--open');
        $(el).setAttribute('title', Contao.lang.collapse);
        window.fireEvent('structure');
        AjaxRequest.hideBox();

        // HOOK
        window.fireEvent('ajax_change');
      }
    }).post({
      'action': 'loadStructure',
      'id': id,
      'level': level,
      'state': 1,
      'REQUEST_TOKEN': Contao.request_token
    });
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
  toggleFileManager: function toggleFileManager(el, id, folder, level) {
    if (window.console) {
      console.warn('AjaxRequest.toggleFileManager() is deprecated. Please use the stimulus controller instead.');
    }
    var item = $(id);
    if (item) {
      if (item.getStyle('display') == 'none') {
        item.setStyle('display', null);
        $(el).addClass('foldable--open');
        $(el).setAttribute('title', Contao.lang.collapse);
        new Request.Contao({
          field: el
        }).post({
          'action': 'toggleFileManager',
          'id': id,
          'state': 1,
          'REQUEST_TOKEN': Contao.request_token
        });
      } else {
        item.setStyle('display', 'none');
        $(el).removeClass('foldable--open');
        $(el).setAttribute('title', Contao.lang.expand);
        new Request.Contao({
          field: el
        }).post({
          'action': 'toggleFileManager',
          'id': id,
          'state': 0,
          'REQUEST_TOKEN': Contao.request_token
        });
      }
      return false;
    }
    new Request.Contao({
      field: el,
      evalScripts: true,
      onRequest: function onRequest() {
        AjaxRequest.displayBox(Contao.lang.loading + ' …');
      },
      onSuccess: function onSuccess(txt) {
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
        li.getElements('a').each(function (el) {
          el.href = el.href.replace(/&ref=[a-f0-9]+/, '&ref=' + Contao.referer_id);
        });
        $(el).addClass('foldable--open');
        $(el).setAttribute('title', Contao.lang.collapse);
        AjaxRequest.hideBox();

        // HOOK
        window.fireEvent('ajax_change');
      }
    }).post({
      'action': 'loadFileManager',
      'id': id,
      'level': level,
      'folder': folder,
      'state': 1,
      'REQUEST_TOKEN': Contao.request_token
    });
    return false;
  },
  /**
   * Toggle sub-palettes in edit mode
   *
   * @param {object} el    The DOM element
   * @param {string} id    The ID of the target element
   * @param {string} field The field name
   */
  toggleSubpalette: function toggleSubpalette(el, id, field) {
    var item = $(id);
    if (item) {
      if (!el.value) {
        el.value = 1;
        el.checked = 'checked';
        item.setStyle('display', null);
        item.getElements('[data-required]').each(function (el) {
          el.set('required', '').set('data-required', null);
        });
        new Request.Contao({
          field: el,
          onSuccess: updateVersionNumber
        }).post({
          'action': 'toggleSubpalette',
          'id': id,
          'field': field,
          'state': 1,
          'REQUEST_TOKEN': Contao.request_token
        });
      } else {
        el.value = '';
        el.checked = '';
        item.setStyle('display', 'none');
        item.getElements('[required]').each(function (el) {
          el.set('required', null).set('data-required', '');
        });
        new Request.Contao({
          field: el,
          onSuccess: updateVersionNumber
        }).post({
          'action': 'toggleSubpalette',
          'id': id,
          'field': field,
          'state': 0,
          'REQUEST_TOKEN': Contao.request_token
        });
      }
      return;
    }
    new Request.Contao({
      field: el,
      evalScripts: false,
      onRequest: function onRequest() {
        AjaxRequest.displayBox(Contao.lang.loading + ' …');
      },
      onSuccess: function onSuccess(txt, json) {
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
          document.write = function (str) {
            var src = '';
            str.replace(/<script src="([^"]+)"/i, function (all, match) {
              src = match;
            });
            src && Asset.javascript(src, {
              onLoad: function onLoad() {
                Browser.exec(json.javascript);
              }
            });
          };
          Browser.exec(json.javascript);
        }
        el.value = 1;
        el.checked = 'checked';

        // Update the referer ID
        div.getElements('a').each(function (el) {
          el.href = el.href.replace(/&ref=[a-f0-9]+/, '&ref=' + Contao.referer_id);
        });
        updateVersionNumber(txt);
        AjaxRequest.hideBox();
        window.fireEvent('ajax_change');
      }
    }).post({
      'action': 'toggleSubpalette',
      'id': id,
      'field': field,
      'load': 1,
      'state': 1,
      'REQUEST_TOKEN': Contao.request_token
    });
    function updateVersionNumber(html) {
      var fields = el.form.elements.VERSION_NUMBER || [];
      if (!fields.forEach) {
        fields = [fields];
      }
      fields.forEach(function (field) {
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
  toggleField: function toggleField(el, rowIcon) {
    var img = null,
      images = $(el).getElements('img'),
      published = images[0].get('data-state') == 1,
      div = el.getParent('div'),
      next,
      pa;
    if (rowIcon) {
      // Find the icon depending on the view (tree view, list view, parent view)
      if (div.hasClass('tl_right')) {
        img = div.getPrevious('div').getElements('img');
      } else if (div.hasClass('tl_listing_container')) {
        img = el.getParent('td').getPrevious('td').getFirst('div.list_icon');
        if (img === null) {
          // comments
          img = el.getParent('td').getPrevious('td').getElement('div.cte_type');
        }
        if (img === null) {
          // showColumns
          img = el.getParent('tr').getFirst('td').getElement('div.list_icon_new');
        }
      } else if (next = div.getNext('div')) {
        if (next.hasClass('cte_type')) {
          img = next;
        }
        if (img === null) {
          // newsletter recipients
          img = next.getFirst('div.list_icon');
        }
      }

      // Change the row icon
      if (img !== null) {
        // Tree view
        if (!(img instanceof HTMLElement) && img.forEach) {
          img.forEach(function (img) {
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
              var newSrc = !published ? img.get('data-icon') : img.get('data-icon-disabled');
              if (newSrc) {
                img.src = img.src.includes('/') && !newSrc.includes('/') ? img.src.slice(0, img.src.lastIndexOf('/') + 1) + newSrc : newSrc;
              }
            }
          });
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
    images.forEach(function (image) {
      var newSrc = !published ? image.get('data-icon') : image.get('data-icon-disabled');
      image.src = image.src.includes('/') && !newSrc.includes('/') ? image.src.slice(0, image.src.lastIndexOf('/') + 1) + newSrc : newSrc;
      image.set('data-state', !published ? 1 : 0);
    });
    if (!published && $(el).get('data-title')) {
      el.title = $(el).get('data-title');
    } else if (published && $(el).get('data-title-disabled')) {
      el.title = $(el).get('data-title-disabled');
    }
    new Request.Contao({
      'url': el.href,
      'followRedirects': false
    }).get();

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
  toggleCheckboxGroup: function toggleCheckboxGroup(el, id) {
    var item = $(id);
    if (item) {
      if (item.getStyle('display') == 'none') {
        item.setStyle('display', null);
        $(el).addClass('foldable--open');
        new Request.Contao().post({
          'action': 'toggleCheckboxGroup',
          'id': id,
          'state': 1,
          'REQUEST_TOKEN': Contao.request_token
        });
      } else {
        item.setStyle('display', 'none');
        $(el).removeClass('foldable--open');
        new Request.Contao().post({
          'action': 'toggleCheckboxGroup',
          'id': id,
          'state': 0,
          'REQUEST_TOKEN': Contao.request_token
        });
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
  displayBox: function displayBox(message) {
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
        'display': 'block'
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
        'top': scroll.y + 100 + 'px'
      }
    });
  },
  /**
   * Hide the "loading data" message
   */
  hideBox: function hideBox() {
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
window.Backend = {
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
  openModalWindow: function openModalWindow(width, title, content) {
    new SimpleModal({
      'width': width,
      'hideFooter': true,
      'draggable': false,
      'overlayOpacity': .7,
      'overlayClick': false,
      'onShow': function onShow() {
        document.body.setStyle('overflow', 'hidden');
      },
      'onHide': function onHide() {
        document.body.setStyle('overflow', 'auto');
      }
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
  openModalImage: function openModalImage(options) {
    var _opt$title;
    var opt = options || {},
      maxWidth = (window.getSize().x - 20).toInt();
    if (!opt.width || opt.width > maxWidth) opt.width = Math.min(maxWidth, 900);
    var M = new SimpleModal({
      'width': opt.width,
      'hideFooter': true,
      'draggable': false,
      'overlayOpacity': .7,
      'onShow': function onShow() {
        document.body.setStyle('overflow', 'hidden');
      },
      'onHide': function onHide() {
        document.body.setStyle('overflow', 'auto');
      }
    });
    M.show({
      'title': (_opt$title = opt.title) === null || _opt$title === void 0 ? void 0 : _opt$title.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;'),
      'contents': '<img src="' + opt.url + '" alt="">'
    });
  },
  /**
   * Open an iframe in a modal window
   *
   * @param {object} options An optional options object
   */
  openModalIframe: function openModalIframe(options) {
    var _opt$title2;
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
      'overlayClick': false,
      'onShow': function onShow() {
        document.body.setStyle('overflow', 'hidden');
      },
      'onHide': function onHide() {
        document.body.setStyle('overflow', 'auto');
      }
    });
    M.show({
      'title': (_opt$title2 = opt.title) === null || _opt$title2 === void 0 ? void 0 : _opt$title2.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;'),
      'contents': '<iframe src="' + opt.url + '" width="100%" height="' + opt.height + '" frameborder="0"></iframe>',
      'model': 'modal'
    });
  },
  /**
   * Open a selector page in a modal window
   *
   * @param {object} options An optional options object
   */
  openModalSelector: function openModalSelector(options) {
    var _opt$title3;
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
      'overlayClick': false,
      'onShow': function onShow() {
        document.body.setStyle('overflow', 'hidden');
      },
      'onHide': function onHide() {
        document.body.setStyle('overflow', 'auto');
      }
    });
    M.addButton(Contao.lang.cancel, 'btn', function () {
      if (this.buttons[0].hasClass('btn-disabled')) {
        return;
      }
      this.hide();
    });
    M.addButton(Contao.lang.apply, 'btn primary', function () {
      if (this.buttons[1].hasClass('btn-disabled')) {
        return;
      }
      var frm = window.frames['simple-modal-iframe'],
        val = [],
        ul,
        inp,
        i,
        pickerValue,
        sIndex;
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
      for (i = 0; i < inp.length; i++) {
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
      'title': (_opt$title3 = opt.title) === null || _opt$title3 === void 0 ? void 0 : _opt$title3.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;'),
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
  openModalBrowser: function openModalBrowser(field_name, url, type, win, source) {
    Backend.openModalSelector({
      'id': 'tl_listing',
      'title': win.document.getElement('div.mce-title').get('text'),
      'url': Contao.routes.backend_picker + '?context=' + (type == 'file' ? 'link' : 'file') + '&amp;extras[fieldType]=radio&amp;extras[filesOnly]=true&amp;extras[source]=' + source + '&amp;value=' + url + '&amp;popup=1',
      'callback': function callback(table, value) {
        win.document.getElementById(field_name).value = value.join(',');
      }
    });
  },
  /**
   * Automatically submit a form
   *
   * @param {object} el The DOM element
   */
  autoSubmit: function autoSubmit(el) {
    window.dispatchEvent(new Event('store-scroll-offset'));
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
  vScrollTo: function vScrollTo(offset) {
    window.addEvent('load', function () {
      window.scrollTo(null, parseInt(offset));
    });
  },
  /**
   * Toggle checkboxes
   *
   * @param {object} el   The DOM element
   * @param {string} [id] The ID of the target element
   */
  toggleCheckboxes: function toggleCheckboxes(el, id) {
    var items = $$('input'),
      status = $(el).checked ? 'checked' : '';
    for (var i = 0; i < items.length; i++) {
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
  toggleCheckboxGroup: function toggleCheckboxGroup(el, id) {
    var cls = $(el).className,
      status = $(el).checked ? 'checked' : '';
    if (cls == 'tl_checkbox') {
      var cbx = $(id) ? $$('#' + id + ' .tl_checkbox') : $(el).getParent('fieldset').getElements('.tl_checkbox');
      cbx.each(function (checkbox) {
        checkbox.checked = status;
      });
    } else if (cls == 'tl_tree_checkbox') {
      $$('#' + id + ' .parent .tl_tree_checkbox').each(function (checkbox) {
        checkbox.checked = status;
      });
    }
    window.dispatchEvent(new Event('store-scroll-offset'));
  },
  /**
   * Toggle checkbox elements
   *
   * @param {string} el  The DOM element
   * @param {string} cls The CSS class name
   */
  toggleCheckboxElements: function toggleCheckboxElements(el, cls) {
    var status = $(el).checked ? 'checked' : '';
    $$('.' + cls).each(function (checkbox) {
      if (checkbox.hasClass('tl_checkbox')) {
        checkbox.checked = status;
      }
    });
    window.dispatchEvent(new Event('store-scroll-offset'));
  },
  /**
   * Make parent view items sortable
   *
   * @param {object} ul The DOM element
   *
   * @author Joe Ray Gregory
   * @author Martin Auswöger
   */
  makeParentViewSortable: function makeParentViewSortable(ul) {
    var ds = new Scroller(document.getElement('body'), {
      onChange: function onChange(x, y) {
        this.element.scrollTo(this.element.getScroll().x, y);
      }
    });
    var list = new Sortables(ul, {
      constrain: true,
      opacity: 0.6,
      onStart: function onStart() {
        ds.start();
      },
      onComplete: function onComplete() {
        ds.stop();
      },
      onSort: function onSort(el) {
        var ul = el.getParent('ul'),
          wrapLevel = 0,
          divs,
          i;
        if (!ul) return;
        divs = ul.getChildren('li > div:first-child');
        if (!divs) return;
        for (i = 0; i < divs.length; i++) {
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
          if (divs[i - 1] && divs[i - 1].hasClass('wrapper_start')) {
            divs[i].addClass('indent_first');
          }
          if (divs[i + 1] && divs[i + 1].hasClass('wrapper_stop')) {
            divs[i].addClass('indent_last');
          }
        }
      },
      handle: '.drag-handle'
    });
    list.active = false;
    list.addEvent('start', function () {
      list.active = true;
    });
    list.addEvent('complete', function (el) {
      if (!list.active) return;
      var id,
        pid,
        url = new URL(window.location.href);
      url.searchParams.set('rt', Contao.request_token);
      url.searchParams.set('act', 'cut');
      if (el.getPrevious('li')) {
        id = el.get('id').replace(/li_/, '');
        pid = el.getPrevious('li').get('id').replace(/li_/, '');
        url.searchParams.set('id', id);
        url.searchParams.set('pid', pid);
        url.searchParams.set('mode', 1);
        new Request.Contao({
          'url': url.toString(),
          'followRedirects': false
        }).get();
      } else if (el.getParent('ul')) {
        id = el.get('id').replace(/li_/, '');
        pid = el.getParent('ul').get('id').replace(/ul_/, '');
        url.searchParams.set('id', id);
        url.searchParams.set('pid', pid);
        url.searchParams.set('mode', 2);
        new Request.Contao({
          'url': url.toString(),
          'followRedirects': false
        }).get();
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
  makeMultiSrcSortable: function makeMultiSrcSortable(id, oid, val) {
    var list = new Sortables($(id), {
      constrain: true,
      opacity: 0.6
    }).addEvent('complete', function () {
      var els = [],
        lis = $(id).getChildren('[data-id]'),
        i;
      for (i = 0; i < lis.length; i++) {
        els.push(lis[i].get('data-id'));
      }
      if (oid === val) {
        $(val).value.split(',').forEach(function (j) {
          if (els.indexOf(j) === -1) {
            els.push(j);
          }
        });
      }
      $(oid).value = els.join(',');
    });
    $(id).getElements('.gimage').each(function (el) {
      if (el.hasClass('removable')) {
        new Element('button', {
          type: 'button',
          html: '&times;',
          'class': 'tl_red'
        }).addEvent('click', function () {
          var li = el.getParent('li'),
            did = li.get('data-id');
          $(val).value = $(val).value.split(',').filter(function (j) {
            return j != did;
          }).join(',');
          $(oid).value = $(oid).value.split(',').filter(function (j) {
            return j != did;
          }).join(',');
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
  enableFileTreeDragAndDrop: function enableFileTreeDragAndDrop(ul, options) {
    var ds = new Scroller(document.getElement('body'), {
      onChange: function onChange(x, y) {
        this.element.scrollTo(this.element.getScroll().x, y);
      }
    });
    ul.addEvent('mousedown', function (event) {
      var dragHandle = event.target.hasClass('drag-handle') ? event.target : event.target.getParent('.drag-handle');
      var dragElement = event.target.getParent('.tl_file,.tl_folder');
      if (!dragHandle || !dragElement || event.rightClick) {
        return;
      }
      ds.start();
      ul.addClass('tl_listing_dragging');
      var cloneBase = dragElement.getElements('.tl_left')[0] || dragElement,
        clone = cloneBase.clone(true).inject(ul).addClass('tl_left_dragging'),
        currentHover,
        currentHoverTime,
        expandLink;
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
        onStart: function onStart() {
          clone.setStyle('display', '');
        },
        onEnter: function onEnter(element, droppable) {
          droppable = fixDroppable(droppable);
          droppable.addClass('tl_folder_dropping');
          if (droppable.hasClass('tl_folder') && currentHover !== droppable) {
            currentHover = droppable;
            currentHoverTime = new Date().getTime();
            expandLink = droppable.getElement('a.foldable');
            if (expandLink && !expandLink.hasClass('foldable--open')) {
              // Expand the folder after one second hover time
              setTimeout(function () {
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
        onCancel: function onCancel() {
          currentHover = undefined;
          currentHoverTime = undefined;
          ds.stop();
          clone.destroy();
          window.removeEvent('keyup', onKeyup);
          ul.getElements('.tl_folder_dropping').removeClass('tl_folder_dropping');
          ul.removeClass('tl_listing_dragging');
        },
        onDrop: function onDrop(element, droppable) {
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
          if (id && pid && ((pid + '/').indexOf(id + '/') === 0 || pid + '/' === id.replace(/[^/]+$/, ''))) {
            return;
          }
          window.dispatchEvent(new Event('store-scroll-offset'));
          document.location.href = options.url + '&id=' + encodeURIComponent(id) + '&pid=' + encodeURIComponent(pid);
        },
        onLeave: function onLeave(element, droppable) {
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
  listWizard: function listWizard(id) {
    var ul = $(id),
      makeSortable = function makeSortable(ul) {
        new Sortables(ul, {
          constrain: true,
          opacity: 0.6,
          handle: '.drag-handle'
        });
      },
      _addEventsTo = function addEventsTo(li) {
        var command, clone, input, previous, next;
        li.getElements('button').each(function (bt) {
          if (bt.hasEvent('click')) return;
          command = bt.getProperty('data-command');
          switch (command) {
            case 'copy':
              bt.addEvent('click', function () {
                window.dispatchEvent(new Event('store-scroll-offset'));
                clone = li.clone(true).inject(li, 'before');
                if (input = li.getFirst('input')) {
                  clone.getFirst('input').value = input.value;
                }
                _addEventsTo(clone);
                input.select();
              });
              break;
            case 'delete':
              bt.addEvent('click', function () {
                window.dispatchEvent(new Event('store-scroll-offset'));
                if (ul.getChildren().length > 1) {
                  li.destroy();
                }
              });
              break;
            case null:
              bt.addEvent('keydown', function (e) {
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
    ul.getChildren().each(function (li) {
      _addEventsTo(li);
    });
  },
  /**
   * Table wizard
   *
   * @param {string} id The ID of the target element
   */
  tableWizard: function tableWizard(id) {
    var table = $(id),
      thead = table.getElement('thead'),
      tbody = table.getElement('tbody'),
      _makeSortable = function makeSortable(tbody) {
        var rows = tbody.getChildren(),
          textarea,
          children,
          i,
          j;
        for (i = 0; i < rows.length; i++) {
          children = rows[i].getChildren();
          for (j = 0; j < children.length; j++) {
            if (textarea = children[j].getFirst('textarea')) {
              textarea.name = textarea.name.replace(/\[[0-9]+][[0-9]+]/g, '[' + i + '][' + j + ']');
            }
          }
        }
        new Sortables(tbody, {
          constrain: true,
          opacity: 0.6,
          handle: '.drag-handle',
          onComplete: function onComplete() {
            _makeSortable(tbody);
          }
        });
      },
      _addEventsTo2 = function addEventsTo(tr) {
        var head = thead.getFirst('tr'),
          command,
          textarea,
          current,
          next,
          ntr,
          children,
          index,
          i;
        tr.getElements('button').each(function (bt) {
          if (bt.hasEvent('click')) return;
          command = bt.getProperty('data-command');
          switch (command) {
            case 'rcopy':
              bt.addEvent('click', function () {
                window.dispatchEvent(new Event('store-scroll-offset'));
                ntr = new Element('tr');
                children = tr.getChildren();
                for (i = 0; i < children.length; i++) {
                  next = children[i].clone(true).inject(ntr, 'bottom');
                  if (textarea = children[i].getFirst('textarea')) {
                    next.getFirst('textarea').value = textarea.value;
                  }
                }
                ntr.inject(tr, 'after');
                _addEventsTo2(ntr);
                _makeSortable(tbody);
                ntr.getFirst('td').getFirst('textarea').select();
              });
              break;
            case 'rdelete':
              bt.addEvent('click', function () {
                window.dispatchEvent(new Event('store-scroll-offset'));
                if (tbody.getChildren().length > 1) {
                  tr.destroy();
                }
                _makeSortable(tbody);
              });
              break;
            case 'ccopy':
              bt.addEvent('click', function () {
                window.dispatchEvent(new Event('store-scroll-offset'));
                index = getIndex(bt);
                children = tbody.getChildren();
                for (i = 0; i < children.length; i++) {
                  current = children[i].getChildren()[index];
                  next = current.clone(true).inject(current, 'after');
                  if (textarea = current.getFirst('textarea')) {
                    next.getFirst('textarea').value = textarea.value;
                  }
                  _addEventsTo2(next);
                }
                var headFirst = head.getFirst('td');
                next = headFirst.clone(true).inject(head.getLast('td'), 'before');
                _addEventsTo2(next);
                _makeSortable(tbody);
                children[0].getChildren()[index + 1].getFirst('textarea').select();
              });
              break;
            case 'cmovel':
              bt.addEvent('click', function () {
                window.dispatchEvent(new Event('store-scroll-offset'));
                index = getIndex(bt);
                children = tbody.getChildren();
                if (index > 0) {
                  for (i = 0; i < children.length; i++) {
                    current = children[i].getChildren()[index];
                    current.inject(current.getPrevious(), 'before');
                  }
                } else {
                  for (i = 0; i < children.length; i++) {
                    current = children[i].getChildren()[index];
                    current.inject(children[i].getLast(), 'before');
                  }
                }
                _makeSortable(tbody);
              });
              break;
            case 'cmover':
              bt.addEvent('click', function () {
                window.dispatchEvent(new Event('store-scroll-offset'));
                index = getIndex(bt);
                children = tbody.getChildren();
                if (index < tr.getChildren().length - 2) {
                  for (i = 0; i < children.length; i++) {
                    current = children[i].getChildren()[index];
                    current.inject(current.getNext(), 'after');
                  }
                } else {
                  for (i = 0; i < children.length; i++) {
                    current = children[i].getChildren()[index];
                    current.inject(children[i].getFirst(), 'before');
                  }
                }
                _makeSortable(tbody);
              });
              break;
            case 'cdelete':
              bt.addEvent('click', function () {
                window.dispatchEvent(new Event('store-scroll-offset'));
                index = getIndex(bt);
                children = tbody.getChildren();
                if (tr.getChildren().length > 2) {
                  for (i = 0; i < children.length; i++) {
                    children[i].getChildren()[index].destroy();
                  }
                  head.getFirst('td').destroy();
                }
                _makeSortable(tbody);
              });
              break;
            case null:
              bt.addEvent('keydown', function (e) {
                if (e.event.keyCode == 38) {
                  e.preventDefault();
                  if (ntr = tr.getPrevious('tr')) {
                    tr.inject(ntr, 'before');
                  } else {
                    tr.inject(tbody, 'bottom');
                  }
                  bt.focus();
                  _makeSortable(tbody);
                } else if (e.event.keyCode == 40) {
                  e.preventDefault();
                  if (ntr = tr.getNext('tr')) {
                    tr.inject(ntr, 'after');
                  } else {
                    tr.inject(tbody, 'top');
                  }
                  bt.focus();
                  _makeSortable(tbody);
                }
              });
              break;
          }
        });
      },
      getIndex = function getIndex(bt) {
        var td = $(bt).getParent('td'),
          tr = td.getParent('tr'),
          cols = tr.getChildren(),
          index = 0,
          i;
        for (i = 0; i < cols.length; i++) {
          if (cols[i] == td) {
            break;
          }
          index++;
        }
        return index;
      };
    _makeSortable(tbody);
    thead.getChildren().each(function (tr) {
      _addEventsTo2(tr);
    });
    tbody.getChildren().each(function (tr) {
      _addEventsTo2(tr);
    });
    Backend.tableWizardResize();
  },
  /**
   * Resize the table wizard fields on focus
   *
   * @param {float} [factor] The resize factor
   */
  tableWizardResize: function tableWizardResize(factor) {
    var size = window.localStorage.getItem('contao_table_wizard_cell_size');
    if (factor !== undefined) {
      size = '';
      $$('.tl_tablewizard textarea').each(function (el) {
        el.setStyle('width', (el.getStyle('width').toInt() * factor).round().limit(142, 284));
        el.setStyle('height', (el.getStyle('height').toInt() * factor).round().limit(66, 132));
        if (size == '') {
          size = el.getStyle('width') + '|' + el.getStyle('height');
        }
      });
      window.localStorage.setItem('contao_table_wizard_cell_size', size);
    } else if (size !== null) {
      var chunks = size.split('|');
      $$('.tl_tablewizard textarea').each(function (el) {
        el.setStyle('width', chunks[0]);
        el.setStyle('height', chunks[1]);
      });
    }
  },
  /**
   * Set the width of the table wizard
   */
  tableWizardSetWidth: function tableWizardSetWidth() {
    var wrap = $('tl_tablewizard');
    if (!wrap) return;
    wrap.setStyle('width', Math.round(wrap.getParent('.tl_formbody_edit').getComputedSize().width * 0.96));
  },
  /**
   * Options wizard
   *
   * @param {string} id The ID of the target element
   */
  optionsWizard: function optionsWizard(id) {
    var table = $(id),
      tbody = table.getElement('tbody'),
      _makeSortable2 = function makeSortable(tbody) {
        var rows = tbody.getChildren(),
          children,
          i,
          j,
          input;
        for (i = 0; i < rows.length; i++) {
          children = rows[i].getChildren();
          for (j = 0; j < children.length; j++) {
            if (input = children[j].getFirst('input')) {
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
          onComplete: function onComplete() {
            _makeSortable2(tbody);
          }
        });
      },
      _addEventsTo3 = function addEventsTo(tr) {
        var command, input, next, ntr, children, i;
        tr.getElements('button').each(function (bt) {
          if (bt.hasEvent('click')) return;
          command = bt.getProperty('data-command');
          switch (command) {
            case 'copy':
              bt.addEvent('click', function () {
                window.dispatchEvent(new Event('store-scroll-offset'));
                ntr = new Element('tr');
                children = tr.getChildren();
                for (i = 0; i < children.length; i++) {
                  next = children[i].clone(true).inject(ntr, 'bottom');
                  if (input = children[i].getFirst('input')) {
                    next.getFirst('input').value = input.value;
                    if (input.type == 'checkbox') {
                      next.getFirst('input').checked = input.checked ? 'checked' : '';
                    }
                  }
                }
                ntr.inject(tr, 'after');
                _addEventsTo3(ntr);
                _makeSortable2(tbody);
                ntr.getFirst('td').getFirst('input').select();
              });
              break;
            case 'delete':
              bt.addEvent('click', function () {
                window.dispatchEvent(new Event('store-scroll-offset'));
                if (tbody.getChildren().length > 1) {
                  tr.destroy();
                }
                _makeSortable2(tbody);
              });
              break;
            case null:
              bt.addEvent('keydown', function (e) {
                if (e.event.keyCode == 38) {
                  e.preventDefault();
                  if (ntr = tr.getPrevious('tr')) {
                    tr.inject(ntr, 'before');
                  } else {
                    tr.inject(tbody, 'bottom');
                  }
                  bt.focus();
                  _makeSortable2(tbody);
                } else if (e.event.keyCode == 40) {
                  e.preventDefault();
                  if (ntr = tr.getNext('tr')) {
                    tr.inject(ntr, 'after');
                  } else {
                    tr.inject(tbody, 'top');
                  }
                  bt.focus();
                  _makeSortable2(tbody);
                }
              });
              break;
          }
        });
      };
    _makeSortable2(tbody);
    tbody.getChildren().each(function (tr) {
      _addEventsTo3(tr);
    });
  },
  /**
   * Key/value wizard
   *
   * @param {string} id The ID of the target element
   */
  keyValueWizard: function keyValueWizard(id) {
    var table = $(id),
      tbody = table.getElement('tbody'),
      _makeSortable3 = function makeSortable(tbody) {
        var rows = tbody.getChildren(),
          children,
          i,
          j,
          input;
        for (i = 0; i < rows.length; i++) {
          children = rows[i].getChildren();
          for (j = 0; j < children.length; j++) {
            if (input = children[j].getFirst('input')) {
              input.name = input.name.replace(/\[[0-9]+]/g, '[' + i + ']');
            }
          }
        }
        new Sortables(tbody, {
          constrain: true,
          opacity: 0.6,
          handle: '.drag-handle',
          onComplete: function onComplete() {
            _makeSortable3(tbody);
          }
        });
      },
      _addEventsTo4 = function addEventsTo(tr) {
        var command, input, next, ntr, children, i;
        tr.getElements('button').each(function (bt) {
          if (bt.hasEvent('click')) return;
          command = bt.getProperty('data-command');
          switch (command) {
            case 'copy':
              bt.addEvent('click', function () {
                window.dispatchEvent(new Event('store-scroll-offset'));
                ntr = new Element('tr');
                children = tr.getChildren();
                for (i = 0; i < children.length; i++) {
                  next = children[i].clone(true).inject(ntr, 'bottom');
                  if (input = children[i].getFirst('input')) {
                    next.getFirst().value = input.value;
                  }
                }
                ntr.inject(tr, 'after');
                _addEventsTo4(ntr);
                _makeSortable3(tbody);
                ntr.getFirst('td').getFirst('input').select();
              });
              break;
            case 'delete':
              bt.addEvent('click', function () {
                window.dispatchEvent(new Event('store-scroll-offset'));
                if (tbody.getChildren().length > 1) {
                  tr.destroy();
                }
                _makeSortable3(tbody);
              });
              break;
            case null:
              bt.addEvent('keydown', function (e) {
                if (e.event.keyCode == 38) {
                  e.preventDefault();
                  if (ntr = tr.getPrevious('tr')) {
                    tr.inject(ntr, 'before');
                  } else {
                    tr.inject(tbody, 'bottom');
                  }
                  bt.focus();
                  _makeSortable3(tbody);
                } else if (e.event.keyCode == 40) {
                  e.preventDefault();
                  if (ntr = tr.getNext('tr')) {
                    tr.inject(ntr, 'after');
                  } else {
                    tr.inject(tbody, 'top');
                  }
                  bt.focus();
                  _makeSortable3(tbody);
                }
              });
              break;
          }
        });
      };
    _makeSortable3(tbody);
    tbody.getChildren().each(function (tr) {
      _addEventsTo4(tr);
    });
  },
  /**
   * Checkbox wizard
   *
   * @param {string} id The ID of the target element
   */
  checkboxWizard: function checkboxWizard(id) {
    var container = $(id).getElement('.sortable'),
      makeSortable = function makeSortable(container) {
        new Sortables(container, {
          constrain: true,
          opacity: 0.6,
          handle: '.drag-handle'
        });
      },
      addEventsTo = function addEventsTo(span) {
        var nspan;
        span.getElements('button').each(function (bt) {
          if (bt.hasEvent('click')) return;
          bt.addEvent('keydown', function (e) {
            if (e.event.keyCode == 38) {
              e.preventDefault();
              if (nspan = span.getPrevious('span')) {
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
    container.getChildren().each(function (span) {
      addEventsTo(span);
    });
  },
  /**
   * Update the fields of the imageSize widget upon change
   */
  enableImageSizeWidgets: function enableImageSizeWidgets() {
    $$('.tl_image_size').each(function (el) {
      var select = el.getElement('select'),
        widthInput = el.getChildren('input')[0],
        heightInput = el.getChildren('input')[1],
        update = function update() {
          if (select.get('value') === '' || select.get('value').indexOf('_') === 0 || select.get('value').toInt().toString() === select.get('value')) {
            widthInput.readOnly = true;
            heightInput.readOnly = true;
            var dimensions = $(select.getSelected()[0]).get('text');
            dimensions = dimensions.split('(').length > 1 ? dimensions.split('(').getLast().split(')')[0].split('x') : ['', ''];
            widthInput.set('value', '').set('placeholder', dimensions[0] * 1 || '');
            heightInput.set('value', '').set('placeholder', dimensions[1] * 1 || '');
          } else {
            widthInput.set('placeholder', '');
            heightInput.set('placeholder', '');
            widthInput.readOnly = false;
            heightInput.readOnly = false;
          }
        };
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
  enableToggleSelect: function enableToggleSelect() {
    var container = $('tl_listing'),
      shiftToggle = function shiftToggle(el) {
        thisIndex = checkboxes.indexOf(el);
        startIndex = checkboxes.indexOf(start);
        from = Math.min(thisIndex, startIndex);
        to = Math.max(thisIndex, startIndex);
        status = !!checkboxes[startIndex].checked;
        for (from; from <= to; from++) {
          checkboxes[from].checked = status;
        }
      },
      clickEvent = function clickEvent(e) {
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
      checkboxes = [],
      start,
      thisIndex,
      startIndex,
      status,
      from,
      to;
    if (container) {
      checkboxes = container.getElements('input[type="checkbox"]');
    }

    // Row click
    $$('.toggle_select').each(function (el) {
      var boundEvent = el.retrieve('boundEvent');
      if (boundEvent) {
        el.removeEvent('click', boundEvent);
      }

      // Do not propagate the form field click events
      el.getElements('label,input[type="checkbox"],input[type="radio"]').each(function (i) {
        i.addEvent('click', function (e) {
          e.stopPropagation();
        });
      });
      boundEvent = clickEvent.bind(el);
      el.addEvent('click', boundEvent);
      el.store('boundEvent', boundEvent);
    });

    // Checkbox click
    checkboxes.each(function (el) {
      el.addEvent('click', function (e) {
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
  editPreviewWizard: function editPreviewWizard(el) {
    el = $(el);
    var imageElement = el.getElement('img'),
      inputElements = {},
      isDrawing = false,
      partElement,
      startPos,
      getScale = function getScale() {
        return {
          x: imageElement.getComputedSize().width,
          y: imageElement.getComputedSize().height
        };
      },
      updateImage = function updateImage() {
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
      updateValues = function updateValues() {
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
        Object.each(values, function (value, key) {
          inputElements[key].set('value', value === '' ? '' : Number(value).toFixed(15));
        });
      },
      start = function start(event) {
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
      move = function move(event) {
        if (!isDrawing) {
          return;
        }
        event.preventDefault();
        var imageSize = imageElement.getComputedSize();
        var rect = {
          x: [Math.max(0, Math.min(imageSize.width, startPos.x)), Math.max(0, Math.min(imageSize.width, event.page.x - el.getPosition().x - imageSize.computedLeft))],
          y: [Math.max(0, Math.min(imageSize.height, startPos.y)), Math.max(0, Math.min(imageSize.height, event.page.y - el.getPosition().y - imageSize.computedTop))]
        };
        partElement.setStyles({
          top: Math.min(rect.y[0], rect.y[1]) + imageSize.computedTop + 'px',
          left: Math.min(rect.x[0], rect.x[1]) + imageSize.computedLeft + 'px',
          width: Math.abs(rect.x[0] - rect.x[1]) + 'px',
          height: Math.abs(rect.y[0] - rect.y[1]) + 'px'
        });
        updateValues();
      },
      stop = function stop(event) {
        move(event);
        isDrawing = false;
      },
      init = function init() {
        el.getParent('.tl_tbox,.tl_box').getElements('input[name^="importantPart"]').each(function (input) {
          ['x', 'y', 'width', 'height'].each(function (key) {
            if (input.get('name').substr(13, key.length) === key.capitalize()) {
              inputElements[key] = input = $(input);
            }
          });
        });
        if (Object.getLength(inputElements) !== 4) {
          return;
        }
        Object.each(inputElements, function (input) {
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
      };
    window.addEvent('domready', init);
  },
  /**
   * Enable drag and drop file upload for the file tree
   *
   * @param {object} wrap    The DOM element
   * @param {object} options An optional options object
   */
  enableFileTreeUpload: function enableFileTreeUpload(wrap, options) {
    wrap = $(wrap);
    var fallbackUrl = options.url,
      dzElement = new Element('div', {
        'class': 'dropzone dropzone-filetree',
        html: '<span class="dropzone-previews"></span>'
      }).inject(wrap, 'top'),
      currentHover,
      currentHoverTime,
      expandLink;
    options.previewsContainer = dzElement.getElement('.dropzone-previews');
    options.clickable = false;
    var dz = new Dropzone(wrap, options);
    dz.on('queuecomplete', function () {
      window.location.reload();
    });
    dz.on('dragover', function (event) {
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
        dz.options.url = '' + link.href;
        folder.addClass('tl_folder_dropping');
        if (currentHover !== folder) {
          currentHover = folder;
          currentHoverTime = new Date().getTime();
          expandLink = folder.getElement('a.foldable');
          if (expandLink && !expandLink.hasClass('foldable--open')) {
            // Expand the folder after one second hover time
            setTimeout(function () {
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
      window.dispatchEvent(new Event('store-scroll-offset'));
    });
    dz.on('dragleave', function () {
      wrap.getElements('.tl_folder_dropping').removeClass('tl_folder_dropping');
      currentHover = undefined;
      currentHoverTime = undefined;
    });
  },
  /**
   * Crawl the website
   */
  crawl: function crawl() {
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
    function execRequest() {
      var onlyStatusUpdate = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
      new Request({
        url: window.location.href,
        headers: {
          'Only-Status-Update': onlyStatusUpdate
        },
        onSuccess: function onSuccess(responseText) {
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
window.Theme = {
  /**
   * Check for WebKit
   * @member {boolean}
  	 */
  isWebkit: Browser.chrome || Browser.safari || navigator.userAgent.match(/(?:webkit|khtml)/i),
  /**
   * Stop the propagation of click events of certain elements
   */
  stopClickPropagation: function stopClickPropagation() {
    // Do not propagate the click events of the icons
    $$('.picker_selector').each(function (ul) {
      ul.getElements('a').each(function (el) {
        el.addEvent('click', function (e) {
          e.stopPropagation();
        });
      });
    });

    // Do not propagate the click events of the checkboxes
    $$('.picker_selector,.click2edit').each(function (ul) {
      ul.getElements('input[type="checkbox"]').each(function (el) {
        el.addEvent('click', function (e) {
          e.stopPropagation();
        });
      });
    });
  },
  /**
   * Set up the [Ctrl] + click to edit functionality
   */
  setupCtrlClick: function setupCtrlClick() {
    $$('.click2edit').each(function (el) {
      // Do not propagate the click events of the default buttons (see #5731)
      el.getElements('a').each(function (a) {
        a.addEvent('click', function (e) {
          e.stopPropagation();
        });
      });

      // Set up regular click events on touch devices
      if (Browser.Features.Touch) {
        el.addEvent('click', function () {
          if (!el.getAttribute('data-visited')) {
            el.setAttribute('data-visited', '1');
          } else {
            el.getElements('a').each(function (a) {
              if (a.hasClass('edit')) {
                document.location.href = a.href;
              }
            });
            el.removeAttribute('data-visited');
          }
        });
      } else {
        el.addEvent('click', function (e) {
          var key = Browser.Platform.mac ? e.event.metaKey : e.event.ctrlKey;
          if (!key) return;
          if (e.event.shiftKey) {
            el.getElements('a').each(function (a) {
              if (a.hasClass('children')) {
                document.location.href = a.href;
              }
            });
          } else {
            el.getElements('a').each(function (a) {
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
  setupTextareaResizing: function setupTextareaResizing() {
    $$('.tl_textarea').each(function (el) {
      if (Browser.ie6 || Browser.ie7 || Browser.ie8) return;
      if (el.hasClass('noresize') || el.retrieve('autogrow')) return;

      // Set up the dummy element
      var dummy = new Element('div', {
        html: 'X',
        styles: {
          'position': 'absolute',
          'top': 0,
          'left': '-999em',
          'overflow-x': 'hidden'
        }
      }).setStyles(el.getStyles('font-size', 'font-family', 'width', 'line-height')).inject(document.body);

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
      el.addEvent('input', function () {
        dummy.set('html', this.get('value').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n|\r\n/g, '<br>X'));
        var height = Math.max(line, dummy.getSize().y);
        if (this.clientHeight != height) this.tween('height', height);
      }).set('tween', {
        'duration': 100
      }).setStyle('height', line + 'px');

      // Fire the event
      el.fireEvent('input');
      el.store('autogrow', true);
    });
  },
  /**
   * Set up the menu toggle
   */
  setupMenuToggle: function setupMenuToggle() {
    var burger = $('burger');
    if (!burger) return;
    burger.addEvent('click', function () {
      document.body.toggleClass('show-navigation');
      burger.setAttribute('aria-expanded', document.body.hasClass('show-navigation') ? 'true' : 'false');
    }).addEvent('keydown', function (e) {
      if (e.event.keyCode == 27) {
        document.body.removeClass('show-navigation');
      }
    });
    if (window.matchMedia) {
      var matchMedia = window.matchMedia('(max-width:991px)');
      var setAriaControls = function setAriaControls() {
        if (matchMedia.matches) {
          burger.setAttribute('aria-controls', 'left');
          burger.setAttribute('aria-expanded', document.body.hasClass('show-navigation') ? 'true' : 'false');
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
  setupProfileToggle: function setupProfileToggle() {
    var tmenu = $('tmenu');
    if (!tmenu) return;
    var li = tmenu.getElement('.submenu'),
      button = li.getFirst('span').getFirst('button'),
      menu = li.getFirst('ul');
    if (!li || !button || !menu) return;
    button.setAttribute('aria-controls', 'tmenu__profile');
    button.setAttribute('aria-expanded', 'false');
    menu.id = 'tmenu__profile';
    button.addEvent('click', function (e) {
      if (li.hasClass('active')) {
        li.removeClass('active');
        button.setAttribute('aria-expanded', 'false');
      } else {
        li.addClass('active');
        button.setAttribute('aria-expanded', 'true');
      }
      e.stopPropagation();
    });
    $(document.body).addEvent('click', function () {
      if (li.hasClass('active')) {
        li.removeClass('active');
      }
    });
  },
  /**
   * Set up the split button toggle
   */
  setupSplitButtonToggle: function setupSplitButtonToggle() {
    var toggle = $('sbtog');
    if (!toggle) return;
    var ul = toggle.getParent('.split-button').getElement('ul'),
      tab,
      timer;
    toggle.addEvent('click', function (e) {
      tab = false;
      ul.toggleClass('invisible');
      toggle.toggleClass('active');
      e.stopPropagation();
    });
    $(document.body).addEvent('click', function () {
      tab = false;
      ul.addClass('invisible');
      toggle.removeClass('active');
    });
    $(document.body).addEvent('keydown', function (e) {
      tab = e.event.keyCode == 9;
    });
    [toggle].append(ul.getElements('button')).each(function (el) {
      el.addEvent('focus', function () {
        if (!tab) return;
        ul.removeClass('invisible');
        toggle.addClass('active');
        clearTimeout(timer);
      });
      el.addEvent('blur', function () {
        if (!tab) return;
        timer = setTimeout(function () {
          ul.addClass('invisible');
          toggle.removeClass('active');
        }, 100);
      });
    });
    toggle.set('tabindex', '-1');
  }
};

// Initialize the back end script
window.addEvent('domready', function () {
  $(document.body).addClass('js');

  // Mark touch devices (see #5563)
  if (Browser.Features.Touch) {
    $(document.body).addClass('touch');
  }
  Backend.tableWizardSetWidth();
  Backend.enableImageSizeWidgets();
  Backend.enableToggleSelect();

  // Chosen
  if (Elements.chosen != undefined) {
    $$('select.tl_chosen').chosen();
  }
  Theme.stopClickPropagation();
  Theme.setupCtrlClick();
  Theme.setupTextareaResizing();
  Theme.setupMenuToggle();
  Theme.setupProfileToggle();
  Theme.setupSplitButtonToggle();
});

// Resize the table wizard
window.addEvent('resize', function () {
  Backend.tableWizardSetWidth();
});

// Re-apply certain changes upon ajax_change
window.addEvent('ajax_change', function () {
  Backend.enableImageSizeWidgets();
  Backend.enableToggleSelect();

  // Chosen
  if (Elements.chosen != undefined) {
    $$('select.tl_chosen').filter(function (el) {
      return el.getStyle('display') != 'none';
    }).chosen();
  }
  Theme.stopClickPropagation();
  Theme.setupCtrlClick();
  Theme.setupTextareaResizing();
});

/***/ },

/***/ "./core-bundle/assets/scripts/limit-height.js"
/*!****************************************************!*\
  !*** ./core-bundle/assets/scripts/limit-height.js ***!
  \****************************************************/
() {

window.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('div.limit_height').forEach(function (div) {
    if (window.console) {
      console.warn('Using "limit_height" class on child_record_callback is deprecated. Set a list.sorting.limitHeight in your DCA instead.');
    }
    var parent = div.parentNode.closest('.tl_content');

    // Return if the element is a wrapper
    if (parent && (parent.classList.contains('wrapper_start') || parent.classList.contains('wrapper_stop'))) return;
    var hgt = Number(div.className.replace(/[^0-9]*/, ''));

    // Return if there is no height value
    if (!hgt) return;
    var style = window.getComputedStyle(div, null);
    var padding = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
    var height = div.clientHeight - padding;

    // Do not add the toggle if the preview height is below the max-height
    if (height <= hgt) return;

    // Resize the element if it is higher than the maximum height
    div.style.height = hgt + 'px';
    var button = document.createElement('button');
    button.setAttribute('type', 'button');
    button.title = Contao.lang.expand;
    button.innerHTML = '<span>...</span>';
    button.classList.add('unselectable');
    button.addEventListener('click', function () {
      if (div.style.height == 'auto') {
        div.style.height = hgt + 'px';
        button.title = Contao.lang.expand;
      } else {
        div.style.height = 'auto';
        button.title = Contao.lang.collapse;
      }
    });
    var toggler = document.createElement('div');
    toggler.classList.add('limit_toggler');
    toggler.append(button);
    div.append(toggler);
  });
});

/***/ },

/***/ "./core-bundle/assets/scripts/modulewizard.js"
/*!****************************************************!*\
  !*** ./core-bundle/assets/scripts/modulewizard.js ***!
  \****************************************************/
() {

function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
(function () {
  'use strict';

  var initializedRows = new WeakMap();
  var saveScrollOffsetEvent = new Event('store-scroll-offset');
  var init = function init(row) {
    // Check if this row has already been initialized
    if (initializedRows.has(row)) {
      return;
    }

    // Check if the row has all necessary elements to prevent the mutation observer
    // from initializing the incomplete widget.
    if (!row.querySelector('button.drag-handle')) {
      return;
    }
    initializedRows.set(row, true);
    var tbody = row.closest('tbody');
    var _makeSortable = function makeSortable(tbody) {
      Array.from(tbody.children).forEach(function (tr, i) {
        tr.querySelectorAll('input, select').forEach(function (el) {
          el.name = el.name.replace(/\[[0-9]+]/g, '[' + i + ']');
        });
      });

      // TODO: replace this with a vanilla JS solution
      new Sortables(tbody, {
        constrain: true,
        opacity: 0.6,
        handle: '.drag-handle',
        onComplete: function onComplete() {
          _makeSortable(tbody);
        }
      });
    };
    var _addEventsTo = function addEventsTo(tr) {
      tr.querySelectorAll('button').forEach(function (bt) {
        var command = bt.dataset.command;
        switch (command) {
          case 'copy':
            bt.addEventListener('click', function () {
              window.dispatchEvent(saveScrollOffsetEvent);
              var ntr = tr.cloneNode(true);
              var selects = tr.querySelectorAll('select');
              var nselects = ntr.querySelectorAll('select');
              for (var j = 0; j < selects.length; j++) {
                nselects[j].value = selects[j].value;
              }
              ntr.querySelectorAll('[data-original-title]').forEach(function (el) {
                el.setAttribute('title', el.getAttribute('data-original-title'));
                el.removeAttribute('data-original-title');
              });
              initializedRows.set(ntr, true);
              tr.parentNode.insertBefore(ntr, tr.nextSibling);

              // Remove the ID of the select before initializing Chosen
              var select = ntr.querySelector('select.tl_select');
              select.removeAttribute('id');
              ntr.querySelector('.chzn-container').remove();
              new Chosen(select);
              _addEventsTo(ntr);
              _makeSortable(tbody);
            });
            break;
          case 'delete':
            bt.addEventListener('click', function () {
              window.dispatchEvent(saveScrollOffsetEvent);
              if (tbody.children.length > 1) {
                tr.remove();
              } else {
                // Reset values for last element (#689)
                tr.querySelectorAll('select').forEach(function (select) {
                  select.value = select.children[0].value;
                });
              }
              _makeSortable(tbody);
            });
            break;
          case 'enable':
            bt.addEventListener('click', function () {
              window.dispatchEvent(saveScrollOffsetEvent);
              var cbx = bt.previousElementSibling;
              if (cbx.checked) {
                cbx.checked = '';
              } else {
                cbx.checked = 'checked';
              }
            });
            break;
          default:
            if (bt.classList.contains('drag-handle')) {
              bt.addEventListener('keydown', function (event) {
                if (event.code === 'ArrowUp' || event.keyCode === 38) {
                  event.preventDefault();
                  if (tr.previousElementSibling) {
                    tr.previousElementSibling.insertAdjacentElement('beforebegin', tr);
                  } else {
                    tbody.insertAdjacentElement('beforeend', tr);
                  }
                  bt.focus();
                  _makeSortable(tbody);
                } else if (event.code === 'ArrowDown' || event.keyCode === 40) {
                  event.preventDefault();
                  if (tr.nextElementSibling) {
                    tr.nextElementSibling.insertAdjacentElement('afterend', tr);
                  } else {
                    tbody.insertAdjacentElement('afterbegin', tr);
                  }
                  bt.focus();
                  _makeSortable(tbody);
                }
              });
            }
            break;
        }
      });
      var select = tr.querySelector('td:first-child select');
      if (!select) {
        return;
      }
      var link = tr.querySelector('a.module_link');
      var images = tr.querySelectorAll('img.module_image');
      var updateLink = function updateLink() {
        link.href = link.href.replace(/id=[0-9]+/, 'id=' + select.value);
        if (select.value > 0) {
          link.classList.remove('hidden');
          images.forEach(function (image) {
            image.classList.add('hidden');
          });
        } else {
          link.classList.add('hidden');
          images.forEach(function (image) {
            image.classList.remove('hidden');
          });
        }
      };
      select.addEventListener('change', updateLink);

      // Backwards compatibility with MooTools "Chosen" script that fires non-native change event
      select.addEvent('change', updateLink);
    };
    _makeSortable(tbody);
    _addEventsTo(row);
  };
  document.querySelectorAll('.tl_modulewizard tr').forEach(init);
  new MutationObserver(function (mutationsList) {
    var _iterator = _createForOfIteratorHelper(mutationsList),
      _step;
    try {
      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        var mutation = _step.value;
        if (mutation.type === 'childList') {
          mutation.addedNodes.forEach(function (element) {
            if (element.matches && element.matches('.tl_modulewizard tr, .tl_modulewizard tr *')) {
              init(element.closest('tr'));
            }
          });
        }
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }
  }).observe(document, {
    attributes: false,
    childList: true,
    subtree: true
  });
})();

/***/ },

/***/ "./core-bundle/assets/scripts/mootao.js"
/*!**********************************************!*\
  !*** ./core-bundle/assets/scripts/mootao.js ***!
  \**********************************************/
() {

function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
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

Request.Contao = new Class({
  Extends: Request.JSON,
  options: {
    followRedirects: true
  },
  initialize: function initialize(options) {
    if (!options) {
      options = {};
    }
    if (!options.url && options.field && options.field.form && options.field.form.action) {
      options.url = options.field.form.action;
    }
    if (!options.url) {
      options.url = window.location.href;
    }
    this.parent(options);
  },
  success: function success(text) {
    var url = this.getHeader('X-Ajax-Location'),
      json;
    if (url && this.options.followRedirects) {
      location.replace(url);
      return;
    }

    // Support both plain text and JSON responses
    try {
      json = this.response.json = JSON.decode(text, this.options.secure);
    } catch (e) {
      json = {
        'content': text
      };
    }

    // Empty response
    if (json === null) {
      json = {
        'content': ''
      };
    } else if (_typeof(json) != 'object') {
      json = {
        'content': text
      };
    }

    // Isolate scripts and execute them
    if (json.content != '') {
      json.content = json.content.stripScripts(function (script) {
        json.javascript = script.replace(/<!--|\/\/-->|<!\[CDATA\[\/\/>|<!]]>/g, '');
      });
      if (json.javascript && this.options.evalScripts) {
        Browser.exec(json.javascript);
      }
    }
    this.onSuccess(json.content, json);
  },
  failure: function failure() {
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

Class.refactor(Drag, {
  attach: function attach() {
    this.handles.addEvent('touchstart', this.bound.start);
    return this.previous.apply(this, arguments);
  },
  detach: function detach() {
    this.handles.removeEvent('touchstart', this.bound.start);
    return this.previous.apply(this, arguments);
  },
  start: function start() {
    document.addEvents({
      touchmove: this.bound.check,
      touchend: this.bound.cancel
    });
    this.previous.apply(this, arguments);
  },
  check: function check(event) {
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
  cancel: function cancel() {
    document.removeEvents({
      touchmove: this.bound.check,
      touchend: this.bound.cancel
    });
    return this.previous.apply(this, arguments);
  },
  stop: function stop() {
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

Class.refactor(Sortables, {
  initialize: function initialize(lists, options) {
    options.dragOptions = Object.merge(options.dragOptions || {}, {
      preventDefault: options.dragOptions && options.dragOptions.preventDefault || Browser.Features.Touch
    });
    if (options.dragOptions.unDraggableTags === undefined) {
      options.dragOptions.unDraggableTags = this.options.unDraggableTags.filter(function (tag) {
        return tag != 'button';
      });
    }
    return this.previous.apply(this, arguments);
  },
  addItems: function addItems() {
    Array.flatten(arguments).each(function (element) {
      this.elements.push(element);
      var start = element.retrieve('sortables:start', function (event) {
        this.start.call(this, event, element);
      }.bind(this));
      (this.options.handle ? element.getElement(this.options.handle) || element : element).addEvents({
        mousedown: start,
        touchstart: start
      });
    }, this);
    return this;
  },
  removeItems: function removeItems() {
    return $$(Array.flatten(arguments).map(function (element) {
      this.elements.erase(element);
      var start = element.retrieve('sortables:start');
      (this.options.handle ? element.getElement(this.options.handle) || element : element).removeEvents({
        mousedown: start,
        touchend: start
      });
      return element;
    }, this));
  },
  getClone: function getClone(event, element) {
    if (!this.options.clone) return new Element(element.tagName).inject(document.body);
    if (typeOf(this.options.clone) == 'function') return this.options.clone.call(this, event, element, this.list);
    var clone = this.previous.apply(this, arguments);
    clone.addEvent('touchstart', function (event) {
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

Class.refactor(Request.Queue, {
  // Do not fire the "end" event here
  onComplete: function onComplete() {
    this.fireEvent('complete', arguments);
  },
  // Call resume() instead of runNext()
  onCancel: function onCancel() {
    if (this.options.autoAdvance && !this.error) this.resume();
    this.fireEvent('cancel', arguments);
  },
  // Call resume() instead of runNext() and fire the "end" event
  onSuccess: function onSuccess() {
    if (this.options.autoAdvance && !this.error) this.resume();
    this.fireEvent('success', arguments);
    if (!this.queue.length && !this.isRunning()) this.fireEvent('end');
  },
  // Call resume() instead of runNext() and fire the "end" event
  onFailure: function onFailure() {
    this.error = true;
    if (!this.options.stopOnFailure && this.options.autoAdvance) this.resume();
    this.fireEvent('failure', arguments);
    if (!this.queue.length && !this.isRunning()) this.fireEvent('end');
  },
  // Call resume() instead of runNext()
  onException: function onException() {
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

Contao.SerpPreview = new Class({
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
  shorten: function shorten(str, max) {
    if (str.length <= max) {
      return str;
    }
    return str.substr(0, str.lastIndexOf(' ', max)) + ' …';
  },
  html2string: function html2string(html) {
    return new DOMParser().parseFromString(html, 'text/html').body.textContent.replace(/\[-]/g, '\xAD').replace(/\[nbsp]/g, '\xA0');
  },
  getTinymce: function getTinymce() {
    if (window.tinyMCE && this.options.descriptionFallbackField) {
      return window.tinyMCE.get(this.options.descriptionFallbackField);
    }
  },
  initialize: function initialize() {
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
    titleField && titleField.addEvent('input', function () {
      if (titleField.value) {
        serpTitle.set('text', this.shorten(this.html2string(titleTag.replace(/%s/, titleField.value)).replace(/%%/g, '%'), 64));
      } else if (titleFallbackField && titleFallbackField.value) {
        serpTitle.set('text', this.shorten(this.html2string(titleTag.replace(/%s/, titleFallbackField.value)).replace(/%%/g, '%'), 64));
      } else {
        serpTitle.set('text', '');
      }
    }.bind(this));
    titleFallbackField && titleFallbackField.addEvent('input', function () {
      if (titleField && titleField.value) return;
      serpTitle.set('text', this.shorten(this.html2string(titleTag.replace(/%s/, titleFallbackField.value)).replace(/%%/g, '%'), 64));
    }.bind(this));
    aliasField && aliasField.addEvent('input', function () {
      if (aliasField.value == 'index' && indexEmpty) {
        serpUrl.set('text', this.options.trail);
      } else {
        serpUrl.set('text', this.options.trail + ' › ' + (aliasField.value || this.options.id).replace(/\//g, ' › '));
      }
    }.bind(this));
    descriptionField && descriptionField.addEvent('input', function () {
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
    descriptionFallbackField && descriptionFallbackField.addEvent('input', function () {
      if (descriptionField && descriptionField.value) return;
      serpDescription.set('text', this.shorten(this.html2string(descriptionFallbackField.value), 160));
    }.bind(this));
    setTimeout(function () {
      var editor = this.getTinymce();
      editor && editor.on('keyup', function () {
        if (descriptionField && descriptionField.value) return;
        serpDescription.set('text', this.shorten(this.html2string(window.tinyMCE.activeEditor.getContent()), 160));
      }.bind(this));
    }.bind(this), 4);
  }
});

/***/ },

/***/ "./core-bundle/assets/scripts/sectionwizard.js"
/*!*****************************************************!*\
  !*** ./core-bundle/assets/scripts/sectionwizard.js ***!
  \*****************************************************/
() {

function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
(function () {
  'use strict';

  var initializedRows = new WeakMap();
  var saveScrollOffsetEvent = new Event('store-scroll-offset');
  var init = function init(row) {
    // Check if this row has already been initialized
    if (initializedRows.has(row)) {
      return;
    }

    // Check if the row has all necessary elements to prevent the mutation observer
    // from initializing the incomplete widget.
    if (!row.querySelector('button.drag-handle')) {
      return;
    }
    initializedRows.set(row, true);
    var tbody = row.closest('tbody');
    var _makeSortable = function makeSortable(tbody) {
      Array.from(tbody.children).forEach(function (tr, i) {
        tr.querySelectorAll('input, select').forEach(function (el) {
          el.name = el.name.replace(/\[[0-9]+]/g, '[' + i + ']');
        });
      });

      // TODO: replace this with a vanilla JS solution
      new Sortables(tbody, {
        constrain: true,
        opacity: 0.6,
        handle: '.drag-handle',
        onComplete: function onComplete() {
          _makeSortable(tbody);
        }
      });
    };
    var _addEventsTo = function addEventsTo(tr) {
      tr.querySelectorAll('button').forEach(function (bt) {
        var command = bt.dataset.command;
        switch (command) {
          case 'copy':
            bt.addEventListener('click', function () {
              window.dispatchEvent(saveScrollOffsetEvent);
              var ntr = tr.cloneNode(true);
              var selects = tr.querySelectorAll('select');
              var nselects = ntr.querySelectorAll('select');
              for (var j = 0; j < selects.length; j++) {
                nselects[j].value = selects[j].value;
              }
              tr.parentNode.insertBefore(ntr, tr.nextSibling);
              _addEventsTo(ntr);
              _makeSortable(tbody);
            });
            break;
          case 'delete':
            bt.addEventListener('click', function () {
              window.dispatchEvent(saveScrollOffsetEvent);
              if (tbody.children.length > 1) {
                tr.remove();
              } else {
                // Reset values for last element (#689)
                tr.querySelectorAll('input').forEach(function (input) {
                  input.value = '';
                });
                tr.querySelectorAll('select').forEach(function (select) {
                  select.value = select.children[0].value;
                });
              }
              _makeSortable(tbody);
            });
            break;
          default:
            if (bt.classList.contains('drag-handle')) {
              bt.addEventListener('keydown', function (event) {
                if (event.code === 'ArrowUp' || event.keyCode === 38) {
                  event.preventDefault();
                  if (tr.previousElementSibling) {
                    tr.previousElementSibling.insertAdjacentElement('beforebegin', tr);
                  } else {
                    tbody.insertAdjacentElement('beforeend', tr);
                  }
                  bt.focus();
                  _makeSortable(tbody);
                } else if (event.code === 'ArrowDown' || event.keyCode === 40) {
                  event.preventDefault();
                  if (tr.nextElementSibling) {
                    tr.nextElementSibling.insertAdjacentElement('afterend', tr);
                  } else {
                    tbody.insertAdjacentElement('afterbegin', tr);
                  }
                  bt.focus();
                  _makeSortable(tbody);
                }
              });
            }
            break;
        }
      });
    };
    _makeSortable(tbody);
    _addEventsTo(row);
  };
  document.querySelectorAll('.tl_sectionwizard tr').forEach(init);
  new MutationObserver(function (mutationsList) {
    var _iterator = _createForOfIteratorHelper(mutationsList),
      _step;
    try {
      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        var mutation = _step.value;
        if (mutation.type === 'childList') {
          mutation.addedNodes.forEach(function (element) {
            if (element.matches && element.matches('.tl_sectionwizard tr, .tl_sectionwizard tr *')) {
              init(element.closest('tr'));
            }
          });
        }
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }
  }).observe(document, {
    attributes: false,
    childList: true,
    subtree: true
  });
})();

/***/ },

/***/ "./core-bundle/assets/scripts/tips.js"
/*!********************************************!*\
  !*** ./core-bundle/assets/scripts/tips.js ***!
  \********************************************/
() {

function _createForOfIteratorHelper(r, e) { var t = "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (!t) { if (Array.isArray(r) || (t = _unsupportedIterableToArray(r)) || e && r && "number" == typeof r.length) { t && (r = t); var _n = 0, F = function F() {}; return { s: F, n: function n() { return _n >= r.length ? { done: !0 } : { done: !1, value: r[_n++] }; }, e: function e(r) { throw r; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var o, a = !0, u = !1; return { s: function s() { t = t.call(r); }, n: function n() { var r = t.next(); return a = r.done, r; }, e: function e(r) { u = !0, o = r; }, f: function f() { try { a || null == t["return"] || t["return"](); } finally { if (u) throw o; } } }; }
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
(function () {
  var initialized = [];
  var tip = document.createElement('div');
  tip.setAttribute('role', 'tooltip');
  tip.classList.add('tip');
  tip.style.position = 'absolute';
  tip.style.display = 'none';
  var init = function init(el, x, y, useContent) {
    if (initialized.includes(el)) {
      return;
    }
    initialized.push(el);
    var text, timer;
    ['mouseenter', 'touchend'].forEach(function (event) {
      el.addEventListener(event, function (e) {
        if (useContent) {
          text = el.innerHTML;
        } else {
          var _text;
          text = el.getAttribute('title');
          el.setAttribute('data-original-title', text);
          el.removeAttribute('title');
          text = (_text = text) === null || _text === void 0 ? void 0 : _text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
        }
        if (!text) {
          return;
        }
        clearTimeout(timer);
        tip.style.willChange = 'display,contents';
        timer = setTimeout(function () {
          var position = el.getBoundingClientRect();
          var rtl = getComputedStyle(el).direction === 'rtl';
          var clientWidth = document.html.clientWidth;
          if (rtl && position.x < 200 || !rtl && position.x < clientWidth - 200) {
            tip.style.left = "".concat(window.scrollX + position.left + x, "px");
            tip.style.right = 'auto';
            tip.classList.remove('tip--rtl');
          } else {
            tip.style.left = 'auto';
            tip.style.right = "".concat(clientWidth - window.scrollX - position.right + x, "px");
            tip.classList.add('tip--rtl');
          }
          tip.innerHTML = "<div>".concat(text, "</div>");
          tip.style.top = "".concat(window.scrollY + position.top + y, "px");
          tip.style.display = 'block';
          tip.style.willChange = 'auto';
          if (!tip.parentNode && document.body) {
            document.body.append(tip);
          }
        }, 'mouseenter' === e.type ? 1000 : 0);
      });
    });
    var close = function close(e) {
      if (el.hasAttribute('data-original-title')) {
        if (!el.hasAttribute('title')) {
          el.setAttribute('title', el.getAttribute('data-original-title'));
        }
        el.removeAttribute('data-original-title');
      }
      clearTimeout(timer);
      tip.style.willChange = 'auto';
      if (tip.style.display === 'block') {
        tip.style.willChange = 'display';
        timer = setTimeout(function () {
          tip.style.display = 'none';
          tip.style.willChange = 'auto';
        }, 'mouseleave' === e.type ? 100 : 0);
      }
    };
    el.addEventListener('mouseleave', close);

    // Close tooltip when touching anywhere else
    document.addEventListener('touchstart', function (e) {
      if (el.contains(e.target)) {
        return;
      }
      close(e);
    });
    var action = el.closest('button, a');

    // Hide tooltip when clicking a button (usually an operation icon in a wizard widget)
    if (action) {
      action.addEventListener('click', function () {
        clearTimeout(timer);
        tip.style.display = 'none';
        tip.style.willChange = 'auto';
      });
    }
  };
  function select(node, selector) {
    if (node.matches(selector)) {
      return [node].concat(_toConsumableArray(node.querySelectorAll(selector)));
    }
    return node.querySelectorAll(selector);
  }
  function setup(node) {
    select(node, 'p.tl_tip').forEach(function (el) {
      init(el, 0, 23, true);
    });
    select(node, '#home').forEach(function (el) {
      init(el, 6, 42);
    });
    select(node, '#tmenu a[title]').forEach(function (el) {
      init(el, 0, 42);
    });
    select(node, 'a[title][class^="group-"]').forEach(function (el) {
      init(el, -6, 27);
    });
    select(node, 'a[title].navigation').forEach(function (el) {
      init(el, 25, 32);
    });
    select(node, 'img[title]').forEach(function (el) {
      init(el, -9, el.classList.contains('gimage') ? 60 : 30);
    });
    select(node, 'a[title]').forEach(function (el) {
      if (el.classList.contains('picker-wizard')) {
        init(el, -4, 30);
      } else {
        init(el, -9, 30);
      }
    });
    select(node, 'button[title]').forEach(function (el) {
      if (el.classList.contains('unselectable')) {
        init(el, -4, 20);
      } else {
        init(el, -9, 30);
      }
    });
    ['input[title]', 'time[title]', 'span[title]'].forEach(function (selector) {
      select(node, selector).forEach(function (el) {
        init(el, -9, selector === 'time[title]' || selector === 'span[title]' ? 26 : 30);
      });
    });
  }
  setup(document.documentElement);
  new MutationObserver(function (mutationsList) {
    var _iterator = _createForOfIteratorHelper(mutationsList),
      _step;
    try {
      for (_iterator.s(); !(_step = _iterator.n()).done;) {
        var mutation = _step.value;
        if (mutation.type === 'childList') {
          mutation.addedNodes.forEach(function (element) {
            if (element.matches && element.querySelectorAll) {
              setup(element);
            }
          });
        }
      }
    } catch (err) {
      _iterator.e(err);
    } finally {
      _iterator.f();
    }
  }).observe(document, {
    attributes: false,
    childList: true,
    subtree: true
  });
})();

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Check if module exists (development only)
/******/ 		if (__webpack_modules__[moduleId] === undefined) {
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be in strict mode.
(() => {
"use strict";
/*!***************************************!*\
  !*** ./core-bundle/assets/backend.js ***!
  \***************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @hotwired/stimulus */ "./node_modules/@hotwired/stimulus/dist/stimulus.js");
/* harmony import */ var _hotwired_stimulus_webpack_helpers__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @hotwired/stimulus-webpack-helpers */ "./node_modules/@hotwired/stimulus-webpack-helpers/dist/stimulus-webpack-helpers.js");
/* harmony import */ var _scripts_mootao_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./scripts/mootao.js */ "./core-bundle/assets/scripts/mootao.js");
/* harmony import */ var _scripts_mootao_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_scripts_mootao_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _scripts_core_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./scripts/core.js */ "./core-bundle/assets/scripts/core.js");
/* harmony import */ var _scripts_core_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_scripts_core_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _scripts_limit_height_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./scripts/limit-height.js */ "./core-bundle/assets/scripts/limit-height.js");
/* harmony import */ var _scripts_limit_height_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_scripts_limit_height_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _scripts_modulewizard_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./scripts/modulewizard.js */ "./core-bundle/assets/scripts/modulewizard.js");
/* harmony import */ var _scripts_modulewizard_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_scripts_modulewizard_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _scripts_sectionwizard_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./scripts/sectionwizard.js */ "./core-bundle/assets/scripts/sectionwizard.js");
/* harmony import */ var _scripts_sectionwizard_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_scripts_sectionwizard_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _scripts_tips_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./scripts/tips.js */ "./core-bundle/assets/scripts/tips.js");
/* harmony import */ var _scripts_tips_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_scripts_tips_js__WEBPACK_IMPORTED_MODULE_7__);









// Start the Stimulus application
var application = _hotwired_stimulus__WEBPACK_IMPORTED_MODULE_0__.Application.start();
application.debug = "development" === 'development';

// Register all controllers with `contao--` prefix
var context = __webpack_require__("./core-bundle/assets/controllers sync recursive \\.js$");
application.load(context.keys().map(function (key) {
  var identifier = (0,_hotwired_stimulus_webpack_helpers__WEBPACK_IMPORTED_MODULE_1__.identifierForContextKey)(key);
  if (identifier) {
    return (0,_hotwired_stimulus_webpack_helpers__WEBPACK_IMPORTED_MODULE_1__.definitionForModuleAndIdentifier)(context(key), "contao--".concat(identifier));
  }
}).filter(function (value) {
  return value;
}));
})();

/******/ })()
;
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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O0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxtQkFBbUIsbUJBQW1CO0FBQ3RDO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsZ0JBQWdCLFdBQVc7QUFDM0I7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsZ0JBQWdCLFdBQVc7QUFDM0I7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxnQkFBZ0IsV0FBVztBQUMzQjtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsZ0JBQWdCLFdBQVc7QUFDM0I7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsMERBQTBELDJDQUEyQztBQUNyRztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0Esb0NBQW9DLHdDQUF3QztBQUM1RTtBQUNBO0FBQ0E7QUFDQSx3QkFBd0IsUUFBUTtBQUNoQztBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsZ0JBQWdCLFVBQVU7QUFDMUIsZ0JBQWdCLFFBQVE7QUFDeEI7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsZ0JBQWdCLFVBQVU7QUFDMUIsZ0JBQWdCLFFBQVE7QUFDeEI7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EscUJBQXFCO0FBQ3JCO0FBQ0E7QUFDQSxxQkFBcUI7QUFDckI7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FB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O0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSx1RkFBdUYsWUFBWTtBQUNuRztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSxTQUFTO0FBQ1Q7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBLDBEQUEwRDtBQUMxRCxvQkFBb0Isa0NBQWtDO0FBQ3RELHFDQUFxQyxpQ0FBaUM7QUFDdEU7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQSwyQ0FBMkM7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O0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTs7QUFFQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBOztBQUVBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0EsZ0JBQWdCLCtCQUErQjtBQUMvQztBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7QUFDQTtB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dGhCLEtBQUs7RUFBQSxPQUFLQSxLQUFLO0FBQUEsRUFDOUIsQ0FBQyxDIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vLy4vY29yZS1idW5kbGUvYXNzZXRzL2NvbnRyb2xsZXJzLyBzeW5jIFxcLmpzJCIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQGhvdHdpcmVkL3N0aW11bHVzLXdlYnBhY2staGVscGVycy9kaXN0L3N0aW11bHVzLXdlYnBhY2staGVscGVycy5qcyIsIndlYnBhY2s6Ly8vLi9ub2RlX21vZHVsZXMvQGhvdHdpcmVkL3N0aW11bHVzL2Rpc3Qvc3RpbXVsdXMuanMiLCJ3ZWJwYWNrOi8vLy4vY29yZS1idW5kbGUvYXNzZXRzL2NvbnRyb2xsZXJzL2NsaXBib2FyZC1jb250cm9sbGVyLmpzIiwid2VicGFjazovLy8uL2NvcmUtYnVuZGxlL2Fzc2V0cy9jb250cm9sbGVycy9jb2xvci1zY2hlbWUtY29udHJvbGxlci5qcyIsIndlYnBhY2s6Ly8vLi9jb3JlLWJ1bmRsZS9hc3NldHMvY29udHJvbGxlcnMvaW1hZ2Utc2l6ZS1jb250cm9sbGVyLmpzIiwid2VicGFjazovLy8uL2NvcmUtYnVuZGxlL2Fzc2V0cy9jb250cm9sbGVycy9qdW1wLXRhcmdldHMtY29udHJvbGxlci5qcyIsIndlYnBhY2s6Ly8vLi9jb3JlLWJ1bmRsZS9hc3NldHMvY29udHJvbGxlcnMvbGltaXQtaGVpZ2h0LWNvbnRyb2xsZXIuanMiLCJ3ZWJwYWNrOi8vLy4vY29yZS1idW5kbGUvYXNzZXRzL2NvbnRyb2xsZXJzL21ldGF3aXphcmQtY29udHJvbGxlci5qcyIsIndlYnBhY2s6Ly8vLi9jb3JlLWJ1bmRsZS9hc3NldHMvY29udHJvbGxlcnMvc2Nyb2xsLW9mZnNldC1jb250cm9sbGVyLmpzIiwid2VicGFjazovLy8uL2NvcmUtYnVuZGxlL2Fzc2V0cy9jb250cm9sbGVycy90b2dnbGUtZmllbGRzZXQtY29udHJvbGxlci5qcyIsIndlYnBhY2s6Ly8vLi9jb3JlLWJ1bmRsZS9hc3NldHMvY29udHJvbGxlcnMvdG9nZ2xlLW5hdmlnYXRpb24tY29udHJvbGxlci5qcyIsIndlYnBhY2s6Ly8vLi9jb3JlLWJ1bmRsZS9hc3NldHMvY29udHJvbGxlcnMvdG9nZ2xlLW5vZGVzLWNvbnRyb2xsZXIuanMiLCJ3ZWJwYWNrOi8vLy4vY29yZS1idW5kbGUvYXNzZXRzL3NjcmlwdHMvY29yZS5qcyIsIndlYnBhY2s6Ly8vLi9jb3JlLWJ1bmRsZS9hc3NldHMvc2NyaXB0cy9saW1pdC1oZWlnaHQuanMiLCJ3ZWJwYWNrOi8vLy4vY29yZS1idW5kbGUvYXNzZXRzL3NjcmlwdHMvbW9kdWxld2l6YXJkLmpzIiwid2VicGFjazovLy8uL2NvcmUtYnVuZGxlL2Fzc2V0cy9zY3JpcHRzL21vb3Rhby5qcyIsIndlYnBhY2s6Ly8vLi9jb3JlLWJ1bmRsZS9hc3NldHMvc2NyaXB0cy9zZWN0aW9ud2l6YXJkLmpzIiwid2VicGFjazovLy8uL2NvcmUtYnVuZGxlL2Fzc2V0cy9zY3JpcHRzL3RpcHMuanMiLCJ3ZWJwYWNrOi8vL3dlYnBhY2svYm9vdHN0cmFwIiwid2VicGFjazovLy93ZWJwYWNrL3J1bnRpbWUvY29tcGF0IGdldCBkZWZhdWx0IGV4cG9ydCIsIndlYnBhY2s6Ly8vd2VicGFjay9ydW50aW1lL2RlZmluZSBwcm9wZXJ0eSBnZXR0ZXJzIiwid2VicGFjazovLy93ZWJwYWNrL3J1bnRpbWUvaGFzT3duUHJvcGVydHkgc2hvcnRoYW5kIiwid2VicGFjazovLy93ZWJwYWNrL3J1bnRpbWUvbWFrZSBuYW1lc3BhY2Ugb2JqZWN0Iiwid2VicGFjazovLy8uL2NvcmUtYnVuZGxlL2Fzc2V0cy9iYWNrZW5kLmpzIl0sInNvdXJjZXNDb250ZW50IjpbInZhciBtYXAgPSB7XG5cdFwiLi9jbGlwYm9hcmQtY29udHJvbGxlci5qc1wiOiBcIi4vY29yZS1idW5kbGUvYXNzZXRzL2NvbnRyb2xsZXJzL2NsaXBib2FyZC1jb250cm9sbGVyLmpzXCIsXG5cdFwiLi9jb2xvci1zY2hlbWUtY29udHJvbGxlci5qc1wiOiBcIi4vY29yZS1idW5kbGUvYXNzZXRzL2NvbnRyb2xsZXJzL2NvbG9yLXNjaGVtZS1jb250cm9sbGVyLmpzXCIsXG5cdFwiLi9pbWFnZS1zaXplLWNvbnRyb2xsZXIuanNcIjogXCIuL2NvcmUtYnVuZGxlL2Fzc2V0cy9jb250cm9sbGVycy9pbWFnZS1zaXplLWNvbnRyb2xsZXIuanNcIixcblx0XCIuL2p1bXAtdGFyZ2V0cy1jb250cm9sbGVyLmpzXCI6IFwiLi9jb3JlLWJ1bmRsZS9hc3NldHMvY29udHJvbGxlcnMvanVtcC10YXJnZXRzLWNvbnRyb2xsZXIuanNcIixcblx0XCIuL2xpbWl0LWhlaWdodC1jb250cm9sbGVyLmpzXCI6IFwiLi9jb3JlLWJ1bmRsZS9hc3NldHMvY29udHJvbGxlcnMvbGltaXQtaGVpZ2h0LWNvbnRyb2xsZXIuanNcIixcblx0XCIuL21ldGF3aXphcmQtY29udHJvbGxlci5qc1wiOiBcIi4vY29yZS1idW5kbGUvYXNzZXRzL2NvbnRyb2xsZXJzL21ldGF3aXphcmQtY29udHJvbGxlci5qc1wiLFxuXHRcIi4vc2Nyb2xsLW9mZnNldC1jb250cm9sbGVyLmpzXCI6IFwiLi9jb3JlLWJ1bmRsZS9hc3NldHMvY29udHJvbGxlcnMvc2Nyb2xsLW9mZnNldC1jb250cm9sbGVyLmpzXCIsXG5cdFwiLi90b2dnbGUtZmllbGRzZXQtY29udHJvbGxlci5qc1wiOiBcIi4vY29yZS1idW5kbGUvYXNzZXRzL2NvbnRyb2xsZXJzL3RvZ2dsZS1maWVsZHNldC1jb250cm9sbGVyLmpzXCIsXG5cdFwiLi90b2dnbGUtbmF2aWdhdGlvbi1jb250cm9sbGVyLmpzXCI6IFwiLi9jb3JlLWJ1bmRsZS9hc3NldHMvY29udHJvbGxlcnMvdG9nZ2xlLW5hdmlnYXRpb24tY29udHJvbGxlci5qc1wiLFxuXHRcIi4vdG9nZ2xlLW5vZGVzLWNvbnRyb2xsZXIuanNcIjogXCIuL2NvcmUtYnVuZGxlL2Fzc2V0cy9jb250cm9sbGVycy90b2dnbGUtbm9kZXMtY29udHJvbGxlci5qc1wiXG59O1xuXG5cbmZ1bmN0aW9uIHdlYnBhY2tDb250ZXh0KHJlcSkge1xuXHR2YXIgaWQgPSB3ZWJwYWNrQ29udGV4dFJlc29sdmUocmVxKTtcblx0cmV0dXJuIF9fd2VicGFja19yZXF1aXJlX18oaWQpO1xufVxuZnVuY3Rpb24gd2VicGFja0NvbnRleHRSZXNvbHZlKHJlcSkge1xuXHRpZighX193ZWJwYWNrX3JlcXVpcmVfXy5vKG1hcCwgcmVxKSkge1xuXHRcdHZhciBlID0gbmV3IEVycm9yKFwiQ2Fubm90IGZpbmQgbW9kdWxlICdcIiArIHJlcSArIFwiJ1wiKTtcblx0XHRlLmNvZGUgPSAnTU9EVUxFX05PVF9GT1VORCc7XG5cdFx0dGhyb3cgZTtcblx0fVxuXHRyZXR1cm4gbWFwW3JlcV07XG59XG53ZWJwYWNrQ29udGV4dC5rZXlzID0gZnVuY3Rpb24gd2VicGFja0NvbnRleHRLZXlzKCkge1xuXHRyZXR1cm4gT2JqZWN0LmtleXMobWFwKTtcbn07XG53ZWJwYWNrQ29udGV4dC5yZXNvbHZlID0gd2VicGFja0NvbnRleHRSZXNvbHZlO1xubW9kdWxlLmV4cG9ydHMgPSB3ZWJwYWNrQ29udGV4dDtcbndlYnBhY2tDb250ZXh0LmlkID0gXCIuL2NvcmUtYnVuZGxlL2Fzc2V0cy9jb250cm9sbGVycyBzeW5jIHJlY3Vyc2l2ZSBcXFxcLmpzJFwiOyIsIi8qXG5TdGltdWx1cyBXZWJwYWNrIEhlbHBlcnMgMS4wLjBcbkNvcHlyaWdodCDCqSAyMDIxIEJhc2VjYW1wLCBMTENcbiAqL1xuZnVuY3Rpb24gZGVmaW5pdGlvbnNGcm9tQ29udGV4dChjb250ZXh0KSB7XG4gICAgcmV0dXJuIGNvbnRleHQua2V5cygpXG4gICAgICAgIC5tYXAoKGtleSkgPT4gZGVmaW5pdGlvbkZvck1vZHVsZVdpdGhDb250ZXh0QW5kS2V5KGNvbnRleHQsIGtleSkpXG4gICAgICAgIC5maWx0ZXIoKHZhbHVlKSA9PiB2YWx1ZSk7XG59XG5mdW5jdGlvbiBkZWZpbml0aW9uRm9yTW9kdWxlV2l0aENvbnRleHRBbmRLZXkoY29udGV4dCwga2V5KSB7XG4gICAgY29uc3QgaWRlbnRpZmllciA9IGlkZW50aWZpZXJGb3JDb250ZXh0S2V5KGtleSk7XG4gICAgaWYgKGlkZW50aWZpZXIpIHtcbiAgICAgICAgcmV0dXJuIGRlZmluaXRpb25Gb3JNb2R1bGVBbmRJZGVudGlmaWVyKGNvbnRleHQoa2V5KSwgaWRlbnRpZmllcik7XG4gICAgfVxufVxuZnVuY3Rpb24gZGVmaW5pdGlvbkZvck1vZHVsZUFuZElkZW50aWZpZXIobW9kdWxlLCBpZGVudGlmaWVyKSB7XG4gICAgY29uc3QgY29udHJvbGxlckNvbnN0cnVjdG9yID0gbW9kdWxlLmRlZmF1bHQ7XG4gICAgaWYgKHR5cGVvZiBjb250cm9sbGVyQ29uc3RydWN0b3IgPT0gXCJmdW5jdGlvblwiKSB7XG4gICAgICAgIHJldHVybiB7IGlkZW50aWZpZXIsIGNvbnRyb2xsZXJDb25zdHJ1Y3RvciB9O1xuICAgIH1cbn1cbmZ1bmN0aW9uIGlkZW50aWZpZXJGb3JDb250ZXh0S2V5KGtleSkge1xuICAgIGNvbnN0IGxvZ2ljYWxOYW1lID0gKGtleS5tYXRjaCgvXig/OlxcLlxcLyk/KC4rKSg/OltfLV1jb250cm9sbGVyXFwuLis/KSQvKSB8fCBbXSlbMV07XG4gICAgaWYgKGxvZ2ljYWxOYW1lKSB7XG4gICAgICAgIHJldHVybiBsb2dpY2FsTmFtZS5yZXBsYWNlKC9fL2csIFwiLVwiKS5yZXBsYWNlKC9cXC8vZywgXCItLVwiKTtcbiAgICB9XG59XG5cbmV4cG9ydCB7IGRlZmluaXRpb25Gb3JNb2R1bGVBbmRJZGVudGlmaWVyLCBkZWZpbml0aW9uRm9yTW9kdWxlV2l0aENvbnRleHRBbmRLZXksIGRlZmluaXRpb25zRnJvbUNvbnRleHQsIGlkZW50aWZpZXJGb3JDb250ZXh0S2V5IH07XG4iLCIvKlxuU3RpbXVsdXMgMy4yLjFcbkNvcHlyaWdodCDCqSAyMDIzIEJhc2VjYW1wLCBMTENcbiAqL1xuY2xhc3MgRXZlbnRMaXN0ZW5lciB7XG4gICAgY29uc3RydWN0b3IoZXZlbnRUYXJnZXQsIGV2ZW50TmFtZSwgZXZlbnRPcHRpb25zKSB7XG4gICAgICAgIHRoaXMuZXZlbnRUYXJnZXQgPSBldmVudFRhcmdldDtcbiAgICAgICAgdGhpcy5ldmVudE5hbWUgPSBldmVudE5hbWU7XG4gICAgICAgIHRoaXMuZXZlbnRPcHRpb25zID0gZXZlbnRPcHRpb25zO1xuICAgICAgICB0aGlzLnVub3JkZXJlZEJpbmRpbmdzID0gbmV3IFNldCgpO1xuICAgIH1cbiAgICBjb25uZWN0KCkge1xuICAgICAgICB0aGlzLmV2ZW50VGFyZ2V0LmFkZEV2ZW50TGlzdGVuZXIodGhpcy5ldmVudE5hbWUsIHRoaXMsIHRoaXMuZXZlbnRPcHRpb25zKTtcbiAgICB9XG4gICAgZGlzY29ubmVjdCgpIHtcbiAgICAgICAgdGhpcy5ldmVudFRhcmdldC5yZW1vdmVFdmVudExpc3RlbmVyKHRoaXMuZXZlbnROYW1lLCB0aGlzLCB0aGlzLmV2ZW50T3B0aW9ucyk7XG4gICAgfVxuICAgIGJpbmRpbmdDb25uZWN0ZWQoYmluZGluZykge1xuICAgICAgICB0aGlzLnVub3JkZXJlZEJpbmRpbmdzLmFkZChiaW5kaW5nKTtcbiAgICB9XG4gICAgYmluZGluZ0Rpc2Nvbm5lY3RlZChiaW5kaW5nKSB7XG4gICAgICAgIHRoaXMudW5vcmRlcmVkQmluZGluZ3MuZGVsZXRlKGJpbmRpbmcpO1xuICAgIH1cbiAgICBoYW5kbGVFdmVudChldmVudCkge1xuICAgICAgICBjb25zdCBleHRlbmRlZEV2ZW50ID0gZXh0ZW5kRXZlbnQoZXZlbnQpO1xuICAgICAgICBmb3IgKGNvbnN0IGJpbmRpbmcgb2YgdGhpcy5iaW5kaW5ncykge1xuICAgICAgICAgICAgaWYgKGV4dGVuZGVkRXZlbnQuaW1tZWRpYXRlUHJvcGFnYXRpb25TdG9wcGVkKSB7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICBiaW5kaW5nLmhhbmRsZUV2ZW50KGV4dGVuZGVkRXZlbnQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuICAgIGhhc0JpbmRpbmdzKCkge1xuICAgICAgICByZXR1cm4gdGhpcy51bm9yZGVyZWRCaW5kaW5ncy5zaXplID4gMDtcbiAgICB9XG4gICAgZ2V0IGJpbmRpbmdzKCkge1xuICAgICAgICByZXR1cm4gQXJyYXkuZnJvbSh0aGlzLnVub3JkZXJlZEJpbmRpbmdzKS5zb3J0KChsZWZ0LCByaWdodCkgPT4ge1xuICAgICAgICAgICAgY29uc3QgbGVmdEluZGV4ID0gbGVmdC5pbmRleCwgcmlnaHRJbmRleCA9IHJpZ2h0LmluZGV4O1xuICAgICAgICAgICAgcmV0dXJuIGxlZnRJbmRleCA8IHJpZ2h0SW5kZXggPyAtMSA6IGxlZnRJbmRleCA+IHJpZ2h0SW5kZXggPyAxIDogMDtcbiAgICAgICAgfSk7XG4gICAgfVxufVxuZnVuY3Rpb24gZXh0ZW5kRXZlbnQoZXZlbnQpIHtcbiAgICBpZiAoXCJpbW1lZGlhdGVQcm9wYWdhdGlvblN0b3BwZWRcIiBpbiBldmVudCkge1xuICAgICAgICByZXR1cm4gZXZlbnQ7XG4gICAgfVxuICAgIGVsc2Uge1xuICAgICAgICBjb25zdCB7IHN0b3BJbW1lZGlhdGVQcm9wYWdhdGlvbiB9ID0gZXZlbnQ7XG4gICAgICAgIHJldHVybiBPYmplY3QuYXNzaWduKGV2ZW50LCB7XG4gICAgICAgICAgICBpbW1lZGlhdGVQcm9wYWdhdGlvblN0b3BwZWQ6IGZhbHNlLFxuICAgICAgICAgICAgc3RvcEltbWVkaWF0ZVByb3BhZ2F0aW9uKCkge1xuICAgICAgICAgICAgICAgIHRoaXMuaW1tZWRpYXRlUHJvcGFnYXRpb25TdG9wcGVkID0gdHJ1ZTtcbiAgICAgICAgICAgICAgICBzdG9wSW1tZWRpYXRlUHJvcGFnYXRpb24uY2FsbCh0aGlzKTtcbiAgICAgICAgICAgIH0sXG4gICAgICAgIH0pO1xuICAgIH1cbn1cblxuY2xhc3MgRGlzcGF0Y2hlciB7XG4gICAgY29uc3RydWN0b3IoYXBwbGljYXRpb24pIHtcbiAgICAgICAgdGhpcy5hcHBsaWNhdGlvbiA9IGFwcGxpY2F0aW9uO1xuICAgICAgICB0aGlzLmV2ZW50TGlzdGVuZXJNYXBzID0gbmV3IE1hcCgpO1xuICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICB9XG4gICAgc3RhcnQoKSB7XG4gICAgICAgIGlmICghdGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSB0cnVlO1xuICAgICAgICAgICAgdGhpcy5ldmVudExpc3RlbmVycy5mb3JFYWNoKChldmVudExpc3RlbmVyKSA9PiBldmVudExpc3RlbmVyLmNvbm5lY3QoKSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgICAgICB0aGlzLmV2ZW50TGlzdGVuZXJzLmZvckVhY2goKGV2ZW50TGlzdGVuZXIpID0+IGV2ZW50TGlzdGVuZXIuZGlzY29ubmVjdCgpKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXQgZXZlbnRMaXN0ZW5lcnMoKSB7XG4gICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMuZXZlbnRMaXN0ZW5lck1hcHMudmFsdWVzKCkpLnJlZHVjZSgobGlzdGVuZXJzLCBtYXApID0+IGxpc3RlbmVycy5jb25jYXQoQXJyYXkuZnJvbShtYXAudmFsdWVzKCkpKSwgW10pO1xuICAgIH1cbiAgICBiaW5kaW5nQ29ubmVjdGVkKGJpbmRpbmcpIHtcbiAgICAgICAgdGhpcy5mZXRjaEV2ZW50TGlzdGVuZXJGb3JCaW5kaW5nKGJpbmRpbmcpLmJpbmRpbmdDb25uZWN0ZWQoYmluZGluZyk7XG4gICAgfVxuICAgIGJpbmRpbmdEaXNjb25uZWN0ZWQoYmluZGluZywgY2xlYXJFdmVudExpc3RlbmVycyA9IGZhbHNlKSB7XG4gICAgICAgIHRoaXMuZmV0Y2hFdmVudExpc3RlbmVyRm9yQmluZGluZyhiaW5kaW5nKS5iaW5kaW5nRGlzY29ubmVjdGVkKGJpbmRpbmcpO1xuICAgICAgICBpZiAoY2xlYXJFdmVudExpc3RlbmVycylcbiAgICAgICAgICAgIHRoaXMuY2xlYXJFdmVudExpc3RlbmVyc0ZvckJpbmRpbmcoYmluZGluZyk7XG4gICAgfVxuICAgIGhhbmRsZUVycm9yKGVycm9yLCBtZXNzYWdlLCBkZXRhaWwgPSB7fSkge1xuICAgICAgICB0aGlzLmFwcGxpY2F0aW9uLmhhbmRsZUVycm9yKGVycm9yLCBgRXJyb3IgJHttZXNzYWdlfWAsIGRldGFpbCk7XG4gICAgfVxuICAgIGNsZWFyRXZlbnRMaXN0ZW5lcnNGb3JCaW5kaW5nKGJpbmRpbmcpIHtcbiAgICAgICAgY29uc3QgZXZlbnRMaXN0ZW5lciA9IHRoaXMuZmV0Y2hFdmVudExpc3RlbmVyRm9yQmluZGluZyhiaW5kaW5nKTtcbiAgICAgICAgaWYgKCFldmVudExpc3RlbmVyLmhhc0JpbmRpbmdzKCkpIHtcbiAgICAgICAgICAgIGV2ZW50TGlzdGVuZXIuZGlzY29ubmVjdCgpO1xuICAgICAgICAgICAgdGhpcy5yZW1vdmVNYXBwZWRFdmVudExpc3RlbmVyRm9yKGJpbmRpbmcpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHJlbW92ZU1hcHBlZEV2ZW50TGlzdGVuZXJGb3IoYmluZGluZykge1xuICAgICAgICBjb25zdCB7IGV2ZW50VGFyZ2V0LCBldmVudE5hbWUsIGV2ZW50T3B0aW9ucyB9ID0gYmluZGluZztcbiAgICAgICAgY29uc3QgZXZlbnRMaXN0ZW5lck1hcCA9IHRoaXMuZmV0Y2hFdmVudExpc3RlbmVyTWFwRm9yRXZlbnRUYXJnZXQoZXZlbnRUYXJnZXQpO1xuICAgICAgICBjb25zdCBjYWNoZUtleSA9IHRoaXMuY2FjaGVLZXkoZXZlbnROYW1lLCBldmVudE9wdGlvbnMpO1xuICAgICAgICBldmVudExpc3RlbmVyTWFwLmRlbGV0ZShjYWNoZUtleSk7XG4gICAgICAgIGlmIChldmVudExpc3RlbmVyTWFwLnNpemUgPT0gMClcbiAgICAgICAgICAgIHRoaXMuZXZlbnRMaXN0ZW5lck1hcHMuZGVsZXRlKGV2ZW50VGFyZ2V0KTtcbiAgICB9XG4gICAgZmV0Y2hFdmVudExpc3RlbmVyRm9yQmluZGluZyhiaW5kaW5nKSB7XG4gICAgICAgIGNvbnN0IHsgZXZlbnRUYXJnZXQsIGV2ZW50TmFtZSwgZXZlbnRPcHRpb25zIH0gPSBiaW5kaW5nO1xuICAgICAgICByZXR1cm4gdGhpcy5mZXRjaEV2ZW50TGlzdGVuZXIoZXZlbnRUYXJnZXQsIGV2ZW50TmFtZSwgZXZlbnRPcHRpb25zKTtcbiAgICB9XG4gICAgZmV0Y2hFdmVudExpc3RlbmVyKGV2ZW50VGFyZ2V0LCBldmVudE5hbWUsIGV2ZW50T3B0aW9ucykge1xuICAgICAgICBjb25zdCBldmVudExpc3RlbmVyTWFwID0gdGhpcy5mZXRjaEV2ZW50TGlzdGVuZXJNYXBGb3JFdmVudFRhcmdldChldmVudFRhcmdldCk7XG4gICAgICAgIGNvbnN0IGNhY2hlS2V5ID0gdGhpcy5jYWNoZUtleShldmVudE5hbWUsIGV2ZW50T3B0aW9ucyk7XG4gICAgICAgIGxldCBldmVudExpc3RlbmVyID0gZXZlbnRMaXN0ZW5lck1hcC5nZXQoY2FjaGVLZXkpO1xuICAgICAgICBpZiAoIWV2ZW50TGlzdGVuZXIpIHtcbiAgICAgICAgICAgIGV2ZW50TGlzdGVuZXIgPSB0aGlzLmNyZWF0ZUV2ZW50TGlzdGVuZXIoZXZlbnRUYXJnZXQsIGV2ZW50TmFtZSwgZXZlbnRPcHRpb25zKTtcbiAgICAgICAgICAgIGV2ZW50TGlzdGVuZXJNYXAuc2V0KGNhY2hlS2V5LCBldmVudExpc3RlbmVyKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gZXZlbnRMaXN0ZW5lcjtcbiAgICB9XG4gICAgY3JlYXRlRXZlbnRMaXN0ZW5lcihldmVudFRhcmdldCwgZXZlbnROYW1lLCBldmVudE9wdGlvbnMpIHtcbiAgICAgICAgY29uc3QgZXZlbnRMaXN0ZW5lciA9IG5ldyBFdmVudExpc3RlbmVyKGV2ZW50VGFyZ2V0LCBldmVudE5hbWUsIGV2ZW50T3B0aW9ucyk7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIGV2ZW50TGlzdGVuZXIuY29ubmVjdCgpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBldmVudExpc3RlbmVyO1xuICAgIH1cbiAgICBmZXRjaEV2ZW50TGlzdGVuZXJNYXBGb3JFdmVudFRhcmdldChldmVudFRhcmdldCkge1xuICAgICAgICBsZXQgZXZlbnRMaXN0ZW5lck1hcCA9IHRoaXMuZXZlbnRMaXN0ZW5lck1hcHMuZ2V0KGV2ZW50VGFyZ2V0KTtcbiAgICAgICAgaWYgKCFldmVudExpc3RlbmVyTWFwKSB7XG4gICAgICAgICAgICBldmVudExpc3RlbmVyTWFwID0gbmV3IE1hcCgpO1xuICAgICAgICAgICAgdGhpcy5ldmVudExpc3RlbmVyTWFwcy5zZXQoZXZlbnRUYXJnZXQsIGV2ZW50TGlzdGVuZXJNYXApO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBldmVudExpc3RlbmVyTWFwO1xuICAgIH1cbiAgICBjYWNoZUtleShldmVudE5hbWUsIGV2ZW50T3B0aW9ucykge1xuICAgICAgICBjb25zdCBwYXJ0cyA9IFtldmVudE5hbWVdO1xuICAgICAgICBPYmplY3Qua2V5cyhldmVudE9wdGlvbnMpXG4gICAgICAgICAgICAuc29ydCgpXG4gICAgICAgICAgICAuZm9yRWFjaCgoa2V5KSA9PiB7XG4gICAgICAgICAgICBwYXJ0cy5wdXNoKGAke2V2ZW50T3B0aW9uc1trZXldID8gXCJcIiA6IFwiIVwifSR7a2V5fWApO1xuICAgICAgICB9KTtcbiAgICAgICAgcmV0dXJuIHBhcnRzLmpvaW4oXCI6XCIpO1xuICAgIH1cbn1cblxuY29uc3QgZGVmYXVsdEFjdGlvbkRlc2NyaXB0b3JGaWx0ZXJzID0ge1xuICAgIHN0b3AoeyBldmVudCwgdmFsdWUgfSkge1xuICAgICAgICBpZiAodmFsdWUpXG4gICAgICAgICAgICBldmVudC5zdG9wUHJvcGFnYXRpb24oKTtcbiAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgfSxcbiAgICBwcmV2ZW50KHsgZXZlbnQsIHZhbHVlIH0pIHtcbiAgICAgICAgaWYgKHZhbHVlKVxuICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgfSxcbiAgICBzZWxmKHsgZXZlbnQsIHZhbHVlLCBlbGVtZW50IH0pIHtcbiAgICAgICAgaWYgKHZhbHVlKSB7XG4gICAgICAgICAgICByZXR1cm4gZWxlbWVudCA9PT0gZXZlbnQudGFyZ2V0O1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH1cbiAgICB9LFxufTtcbmNvbnN0IGRlc2NyaXB0b3JQYXR0ZXJuID0gL14oPzooPzooW14uXSs/KVxcKyk/KC4rPykoPzpcXC4oLis/KSk/KD86QCh3aW5kb3d8ZG9jdW1lbnQpKT8tPik/KC4rPykoPzojKFteOl0rPykpKD86OiguKykpPyQvO1xuZnVuY3Rpb24gcGFyc2VBY3Rpb25EZXNjcmlwdG9yU3RyaW5nKGRlc2NyaXB0b3JTdHJpbmcpIHtcbiAgICBjb25zdCBzb3VyY2UgPSBkZXNjcmlwdG9yU3RyaW5nLnRyaW0oKTtcbiAgICBjb25zdCBtYXRjaGVzID0gc291cmNlLm1hdGNoKGRlc2NyaXB0b3JQYXR0ZXJuKSB8fCBbXTtcbiAgICBsZXQgZXZlbnROYW1lID0gbWF0Y2hlc1syXTtcbiAgICBsZXQga2V5RmlsdGVyID0gbWF0Y2hlc1szXTtcbiAgICBpZiAoa2V5RmlsdGVyICYmICFbXCJrZXlkb3duXCIsIFwia2V5dXBcIiwgXCJrZXlwcmVzc1wiXS5pbmNsdWRlcyhldmVudE5hbWUpKSB7XG4gICAgICAgIGV2ZW50TmFtZSArPSBgLiR7a2V5RmlsdGVyfWA7XG4gICAgICAgIGtleUZpbHRlciA9IFwiXCI7XG4gICAgfVxuICAgIHJldHVybiB7XG4gICAgICAgIGV2ZW50VGFyZ2V0OiBwYXJzZUV2ZW50VGFyZ2V0KG1hdGNoZXNbNF0pLFxuICAgICAgICBldmVudE5hbWUsXG4gICAgICAgIGV2ZW50T3B0aW9uczogbWF0Y2hlc1s3XSA/IHBhcnNlRXZlbnRPcHRpb25zKG1hdGNoZXNbN10pIDoge30sXG4gICAgICAgIGlkZW50aWZpZXI6IG1hdGNoZXNbNV0sXG4gICAgICAgIG1ldGhvZE5hbWU6IG1hdGNoZXNbNl0sXG4gICAgICAgIGtleUZpbHRlcjogbWF0Y2hlc1sxXSB8fCBrZXlGaWx0ZXIsXG4gICAgfTtcbn1cbmZ1bmN0aW9uIHBhcnNlRXZlbnRUYXJnZXQoZXZlbnRUYXJnZXROYW1lKSB7XG4gICAgaWYgKGV2ZW50VGFyZ2V0TmFtZSA9PSBcIndpbmRvd1wiKSB7XG4gICAgICAgIHJldHVybiB3aW5kb3c7XG4gICAgfVxuICAgIGVsc2UgaWYgKGV2ZW50VGFyZ2V0TmFtZSA9PSBcImRvY3VtZW50XCIpIHtcbiAgICAgICAgcmV0dXJuIGRvY3VtZW50O1xuICAgIH1cbn1cbmZ1bmN0aW9uIHBhcnNlRXZlbnRPcHRpb25zKGV2ZW50T3B0aW9ucykge1xuICAgIHJldHVybiBldmVudE9wdGlvbnNcbiAgICAgICAgLnNwbGl0KFwiOlwiKVxuICAgICAgICAucmVkdWNlKChvcHRpb25zLCB0b2tlbikgPT4gT2JqZWN0LmFzc2lnbihvcHRpb25zLCB7IFt0b2tlbi5yZXBsYWNlKC9eIS8sIFwiXCIpXTogIS9eIS8udGVzdCh0b2tlbikgfSksIHt9KTtcbn1cbmZ1bmN0aW9uIHN0cmluZ2lmeUV2ZW50VGFyZ2V0KGV2ZW50VGFyZ2V0KSB7XG4gICAgaWYgKGV2ZW50VGFyZ2V0ID09IHdpbmRvdykge1xuICAgICAgICByZXR1cm4gXCJ3aW5kb3dcIjtcbiAgICB9XG4gICAgZWxzZSBpZiAoZXZlbnRUYXJnZXQgPT0gZG9jdW1lbnQpIHtcbiAgICAgICAgcmV0dXJuIFwiZG9jdW1lbnRcIjtcbiAgICB9XG59XG5cbmZ1bmN0aW9uIGNhbWVsaXplKHZhbHVlKSB7XG4gICAgcmV0dXJuIHZhbHVlLnJlcGxhY2UoLyg/OltfLV0pKFthLXowLTldKS9nLCAoXywgY2hhcikgPT4gY2hhci50b1VwcGVyQ2FzZSgpKTtcbn1cbmZ1bmN0aW9uIG5hbWVzcGFjZUNhbWVsaXplKHZhbHVlKSB7XG4gICAgcmV0dXJuIGNhbWVsaXplKHZhbHVlLnJlcGxhY2UoLy0tL2csIFwiLVwiKS5yZXBsYWNlKC9fXy9nLCBcIl9cIikpO1xufVxuZnVuY3Rpb24gY2FwaXRhbGl6ZSh2YWx1ZSkge1xuICAgIHJldHVybiB2YWx1ZS5jaGFyQXQoMCkudG9VcHBlckNhc2UoKSArIHZhbHVlLnNsaWNlKDEpO1xufVxuZnVuY3Rpb24gZGFzaGVyaXplKHZhbHVlKSB7XG4gICAgcmV0dXJuIHZhbHVlLnJlcGxhY2UoLyhbQS1aXSkvZywgKF8sIGNoYXIpID0+IGAtJHtjaGFyLnRvTG93ZXJDYXNlKCl9YCk7XG59XG5mdW5jdGlvbiB0b2tlbml6ZSh2YWx1ZSkge1xuICAgIHJldHVybiB2YWx1ZS5tYXRjaCgvW15cXHNdKy9nKSB8fCBbXTtcbn1cblxuZnVuY3Rpb24gaXNTb21ldGhpbmcob2JqZWN0KSB7XG4gICAgcmV0dXJuIG9iamVjdCAhPT0gbnVsbCAmJiBvYmplY3QgIT09IHVuZGVmaW5lZDtcbn1cbmZ1bmN0aW9uIGhhc1Byb3BlcnR5KG9iamVjdCwgcHJvcGVydHkpIHtcbiAgICByZXR1cm4gT2JqZWN0LnByb3RvdHlwZS5oYXNPd25Qcm9wZXJ0eS5jYWxsKG9iamVjdCwgcHJvcGVydHkpO1xufVxuXG5jb25zdCBhbGxNb2RpZmllcnMgPSBbXCJtZXRhXCIsIFwiY3RybFwiLCBcImFsdFwiLCBcInNoaWZ0XCJdO1xuY2xhc3MgQWN0aW9uIHtcbiAgICBjb25zdHJ1Y3RvcihlbGVtZW50LCBpbmRleCwgZGVzY3JpcHRvciwgc2NoZW1hKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7XG4gICAgICAgIHRoaXMuaW5kZXggPSBpbmRleDtcbiAgICAgICAgdGhpcy5ldmVudFRhcmdldCA9IGRlc2NyaXB0b3IuZXZlbnRUYXJnZXQgfHwgZWxlbWVudDtcbiAgICAgICAgdGhpcy5ldmVudE5hbWUgPSBkZXNjcmlwdG9yLmV2ZW50TmFtZSB8fCBnZXREZWZhdWx0RXZlbnROYW1lRm9yRWxlbWVudChlbGVtZW50KSB8fCBlcnJvcihcIm1pc3NpbmcgZXZlbnQgbmFtZVwiKTtcbiAgICAgICAgdGhpcy5ldmVudE9wdGlvbnMgPSBkZXNjcmlwdG9yLmV2ZW50T3B0aW9ucyB8fCB7fTtcbiAgICAgICAgdGhpcy5pZGVudGlmaWVyID0gZGVzY3JpcHRvci5pZGVudGlmaWVyIHx8IGVycm9yKFwibWlzc2luZyBpZGVudGlmaWVyXCIpO1xuICAgICAgICB0aGlzLm1ldGhvZE5hbWUgPSBkZXNjcmlwdG9yLm1ldGhvZE5hbWUgfHwgZXJyb3IoXCJtaXNzaW5nIG1ldGhvZCBuYW1lXCIpO1xuICAgICAgICB0aGlzLmtleUZpbHRlciA9IGRlc2NyaXB0b3Iua2V5RmlsdGVyIHx8IFwiXCI7XG4gICAgICAgIHRoaXMuc2NoZW1hID0gc2NoZW1hO1xuICAgIH1cbiAgICBzdGF0aWMgZm9yVG9rZW4odG9rZW4sIHNjaGVtYSkge1xuICAgICAgICByZXR1cm4gbmV3IHRoaXModG9rZW4uZWxlbWVudCwgdG9rZW4uaW5kZXgsIHBhcnNlQWN0aW9uRGVzY3JpcHRvclN0cmluZyh0b2tlbi5jb250ZW50KSwgc2NoZW1hKTtcbiAgICB9XG4gICAgdG9TdHJpbmcoKSB7XG4gICAgICAgIGNvbnN0IGV2ZW50RmlsdGVyID0gdGhpcy5rZXlGaWx0ZXIgPyBgLiR7dGhpcy5rZXlGaWx0ZXJ9YCA6IFwiXCI7XG4gICAgICAgIGNvbnN0IGV2ZW50VGFyZ2V0ID0gdGhpcy5ldmVudFRhcmdldE5hbWUgPyBgQCR7dGhpcy5ldmVudFRhcmdldE5hbWV9YCA6IFwiXCI7XG4gICAgICAgIHJldHVybiBgJHt0aGlzLmV2ZW50TmFtZX0ke2V2ZW50RmlsdGVyfSR7ZXZlbnRUYXJnZXR9LT4ke3RoaXMuaWRlbnRpZmllcn0jJHt0aGlzLm1ldGhvZE5hbWV9YDtcbiAgICB9XG4gICAgc2hvdWxkSWdub3JlS2V5Ym9hcmRFdmVudChldmVudCkge1xuICAgICAgICBpZiAoIXRoaXMua2V5RmlsdGVyKSB7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH1cbiAgICAgICAgY29uc3QgZmlsdGVycyA9IHRoaXMua2V5RmlsdGVyLnNwbGl0KFwiK1wiKTtcbiAgICAgICAgaWYgKHRoaXMua2V5RmlsdGVyRGlzc2F0aXNmaWVkKGV2ZW50LCBmaWx0ZXJzKSkge1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH1cbiAgICAgICAgY29uc3Qgc3RhbmRhcmRGaWx0ZXIgPSBmaWx0ZXJzLmZpbHRlcigoa2V5KSA9PiAhYWxsTW9kaWZpZXJzLmluY2x1ZGVzKGtleSkpWzBdO1xuICAgICAgICBpZiAoIXN0YW5kYXJkRmlsdGVyKSB7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH1cbiAgICAgICAgaWYgKCFoYXNQcm9wZXJ0eSh0aGlzLmtleU1hcHBpbmdzLCBzdGFuZGFyZEZpbHRlcikpIHtcbiAgICAgICAgICAgIGVycm9yKGBjb250YWlucyB1bmtub3duIGtleSBmaWx0ZXI6ICR7dGhpcy5rZXlGaWx0ZXJ9YCk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIHRoaXMua2V5TWFwcGluZ3Nbc3RhbmRhcmRGaWx0ZXJdLnRvTG93ZXJDYXNlKCkgIT09IGV2ZW50LmtleS50b0xvd2VyQ2FzZSgpO1xuICAgIH1cbiAgICBzaG91bGRJZ25vcmVNb3VzZUV2ZW50KGV2ZW50KSB7XG4gICAgICAgIGlmICghdGhpcy5rZXlGaWx0ZXIpIHtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuICAgICAgICBjb25zdCBmaWx0ZXJzID0gW3RoaXMua2V5RmlsdGVyXTtcbiAgICAgICAgaWYgKHRoaXMua2V5RmlsdGVyRGlzc2F0aXNmaWVkKGV2ZW50LCBmaWx0ZXJzKSkge1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgIH1cbiAgICBnZXQgcGFyYW1zKCkge1xuICAgICAgICBjb25zdCBwYXJhbXMgPSB7fTtcbiAgICAgICAgY29uc3QgcGF0dGVybiA9IG5ldyBSZWdFeHAoYF5kYXRhLSR7dGhpcy5pZGVudGlmaWVyfS0oLispLXBhcmFtJGAsIFwiaVwiKTtcbiAgICAgICAgZm9yIChjb25zdCB7IG5hbWUsIHZhbHVlIH0gb2YgQXJyYXkuZnJvbSh0aGlzLmVsZW1lbnQuYXR0cmlidXRlcykpIHtcbiAgICAgICAgICAgIGNvbnN0IG1hdGNoID0gbmFtZS5tYXRjaChwYXR0ZXJuKTtcbiAgICAgICAgICAgIGNvbnN0IGtleSA9IG1hdGNoICYmIG1hdGNoWzFdO1xuICAgICAgICAgICAgaWYgKGtleSkge1xuICAgICAgICAgICAgICAgIHBhcmFtc1tjYW1lbGl6ZShrZXkpXSA9IHR5cGVjYXN0KHZhbHVlKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gcGFyYW1zO1xuICAgIH1cbiAgICBnZXQgZXZlbnRUYXJnZXROYW1lKCkge1xuICAgICAgICByZXR1cm4gc3RyaW5naWZ5RXZlbnRUYXJnZXQodGhpcy5ldmVudFRhcmdldCk7XG4gICAgfVxuICAgIGdldCBrZXlNYXBwaW5ncygpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2NoZW1hLmtleU1hcHBpbmdzO1xuICAgIH1cbiAgICBrZXlGaWx0ZXJEaXNzYXRpc2ZpZWQoZXZlbnQsIGZpbHRlcnMpIHtcbiAgICAgICAgY29uc3QgW21ldGEsIGN0cmwsIGFsdCwgc2hpZnRdID0gYWxsTW9kaWZpZXJzLm1hcCgobW9kaWZpZXIpID0+IGZpbHRlcnMuaW5jbHVkZXMobW9kaWZpZXIpKTtcbiAgICAgICAgcmV0dXJuIGV2ZW50Lm1ldGFLZXkgIT09IG1ldGEgfHwgZXZlbnQuY3RybEtleSAhPT0gY3RybCB8fCBldmVudC5hbHRLZXkgIT09IGFsdCB8fCBldmVudC5zaGlmdEtleSAhPT0gc2hpZnQ7XG4gICAgfVxufVxuY29uc3QgZGVmYXVsdEV2ZW50TmFtZXMgPSB7XG4gICAgYTogKCkgPT4gXCJjbGlja1wiLFxuICAgIGJ1dHRvbjogKCkgPT4gXCJjbGlja1wiLFxuICAgIGZvcm06ICgpID0+IFwic3VibWl0XCIsXG4gICAgZGV0YWlsczogKCkgPT4gXCJ0b2dnbGVcIixcbiAgICBpbnB1dDogKGUpID0+IChlLmdldEF0dHJpYnV0ZShcInR5cGVcIikgPT0gXCJzdWJtaXRcIiA/IFwiY2xpY2tcIiA6IFwiaW5wdXRcIiksXG4gICAgc2VsZWN0OiAoKSA9PiBcImNoYW5nZVwiLFxuICAgIHRleHRhcmVhOiAoKSA9PiBcImlucHV0XCIsXG59O1xuZnVuY3Rpb24gZ2V0RGVmYXVsdEV2ZW50TmFtZUZvckVsZW1lbnQoZWxlbWVudCkge1xuICAgIGNvbnN0IHRhZ05hbWUgPSBlbGVtZW50LnRhZ05hbWUudG9Mb3dlckNhc2UoKTtcbiAgICBpZiAodGFnTmFtZSBpbiBkZWZhdWx0RXZlbnROYW1lcykge1xuICAgICAgICByZXR1cm4gZGVmYXVsdEV2ZW50TmFtZXNbdGFnTmFtZV0oZWxlbWVudCk7XG4gICAgfVxufVxuZnVuY3Rpb24gZXJyb3IobWVzc2FnZSkge1xuICAgIHRocm93IG5ldyBFcnJvcihtZXNzYWdlKTtcbn1cbmZ1bmN0aW9uIHR5cGVjYXN0KHZhbHVlKSB7XG4gICAgdHJ5IHtcbiAgICAgICAgcmV0dXJuIEpTT04ucGFyc2UodmFsdWUpO1xuICAgIH1cbiAgICBjYXRjaCAob19PKSB7XG4gICAgICAgIHJldHVybiB2YWx1ZTtcbiAgICB9XG59XG5cbmNsYXNzIEJpbmRpbmcge1xuICAgIGNvbnN0cnVjdG9yKGNvbnRleHQsIGFjdGlvbikge1xuICAgICAgICB0aGlzLmNvbnRleHQgPSBjb250ZXh0O1xuICAgICAgICB0aGlzLmFjdGlvbiA9IGFjdGlvbjtcbiAgICB9XG4gICAgZ2V0IGluZGV4KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5hY3Rpb24uaW5kZXg7XG4gICAgfVxuICAgIGdldCBldmVudFRhcmdldCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuYWN0aW9uLmV2ZW50VGFyZ2V0O1xuICAgIH1cbiAgICBnZXQgZXZlbnRPcHRpb25zKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5hY3Rpb24uZXZlbnRPcHRpb25zO1xuICAgIH1cbiAgICBnZXQgaWRlbnRpZmllcigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuY29udGV4dC5pZGVudGlmaWVyO1xuICAgIH1cbiAgICBoYW5kbGVFdmVudChldmVudCkge1xuICAgICAgICBjb25zdCBhY3Rpb25FdmVudCA9IHRoaXMucHJlcGFyZUFjdGlvbkV2ZW50KGV2ZW50KTtcbiAgICAgICAgaWYgKHRoaXMud2lsbEJlSW52b2tlZEJ5RXZlbnQoZXZlbnQpICYmIHRoaXMuYXBwbHlFdmVudE1vZGlmaWVycyhhY3Rpb25FdmVudCkpIHtcbiAgICAgICAgICAgIHRoaXMuaW52b2tlV2l0aEV2ZW50KGFjdGlvbkV2ZW50KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXQgZXZlbnROYW1lKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5hY3Rpb24uZXZlbnROYW1lO1xuICAgIH1cbiAgICBnZXQgbWV0aG9kKCkge1xuICAgICAgICBjb25zdCBtZXRob2QgPSB0aGlzLmNvbnRyb2xsZXJbdGhpcy5tZXRob2ROYW1lXTtcbiAgICAgICAgaWYgKHR5cGVvZiBtZXRob2QgPT0gXCJmdW5jdGlvblwiKSB7XG4gICAgICAgICAgICByZXR1cm4gbWV0aG9kO1xuICAgICAgICB9XG4gICAgICAgIHRocm93IG5ldyBFcnJvcihgQWN0aW9uIFwiJHt0aGlzLmFjdGlvbn1cIiByZWZlcmVuY2VzIHVuZGVmaW5lZCBtZXRob2QgXCIke3RoaXMubWV0aG9kTmFtZX1cImApO1xuICAgIH1cbiAgICBhcHBseUV2ZW50TW9kaWZpZXJzKGV2ZW50KSB7XG4gICAgICAgIGNvbnN0IHsgZWxlbWVudCB9ID0gdGhpcy5hY3Rpb247XG4gICAgICAgIGNvbnN0IHsgYWN0aW9uRGVzY3JpcHRvckZpbHRlcnMgfSA9IHRoaXMuY29udGV4dC5hcHBsaWNhdGlvbjtcbiAgICAgICAgY29uc3QgeyBjb250cm9sbGVyIH0gPSB0aGlzLmNvbnRleHQ7XG4gICAgICAgIGxldCBwYXNzZXMgPSB0cnVlO1xuICAgICAgICBmb3IgKGNvbnN0IFtuYW1lLCB2YWx1ZV0gb2YgT2JqZWN0LmVudHJpZXModGhpcy5ldmVudE9wdGlvbnMpKSB7XG4gICAgICAgICAgICBpZiAobmFtZSBpbiBhY3Rpb25EZXNjcmlwdG9yRmlsdGVycykge1xuICAgICAgICAgICAgICAgIGNvbnN0IGZpbHRlciA9IGFjdGlvbkRlc2NyaXB0b3JGaWx0ZXJzW25hbWVdO1xuICAgICAgICAgICAgICAgIHBhc3NlcyA9IHBhc3NlcyAmJiBmaWx0ZXIoeyBuYW1lLCB2YWx1ZSwgZXZlbnQsIGVsZW1lbnQsIGNvbnRyb2xsZXIgfSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICBjb250aW51ZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gcGFzc2VzO1xuICAgIH1cbiAgICBwcmVwYXJlQWN0aW9uRXZlbnQoZXZlbnQpIHtcbiAgICAgICAgcmV0dXJuIE9iamVjdC5hc3NpZ24oZXZlbnQsIHsgcGFyYW1zOiB0aGlzLmFjdGlvbi5wYXJhbXMgfSk7XG4gICAgfVxuICAgIGludm9rZVdpdGhFdmVudChldmVudCkge1xuICAgICAgICBjb25zdCB7IHRhcmdldCwgY3VycmVudFRhcmdldCB9ID0gZXZlbnQ7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICB0aGlzLm1ldGhvZC5jYWxsKHRoaXMuY29udHJvbGxlciwgZXZlbnQpO1xuICAgICAgICAgICAgdGhpcy5jb250ZXh0LmxvZ0RlYnVnQWN0aXZpdHkodGhpcy5tZXRob2ROYW1lLCB7IGV2ZW50LCB0YXJnZXQsIGN1cnJlbnRUYXJnZXQsIGFjdGlvbjogdGhpcy5tZXRob2ROYW1lIH0pO1xuICAgICAgICB9XG4gICAgICAgIGNhdGNoIChlcnJvcikge1xuICAgICAgICAgICAgY29uc3QgeyBpZGVudGlmaWVyLCBjb250cm9sbGVyLCBlbGVtZW50LCBpbmRleCB9ID0gdGhpcztcbiAgICAgICAgICAgIGNvbnN0IGRldGFpbCA9IHsgaWRlbnRpZmllciwgY29udHJvbGxlciwgZWxlbWVudCwgaW5kZXgsIGV2ZW50IH07XG4gICAgICAgICAgICB0aGlzLmNvbnRleHQuaGFuZGxlRXJyb3IoZXJyb3IsIGBpbnZva2luZyBhY3Rpb24gXCIke3RoaXMuYWN0aW9ufVwiYCwgZGV0YWlsKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICB3aWxsQmVJbnZva2VkQnlFdmVudChldmVudCkge1xuICAgICAgICBjb25zdCBldmVudFRhcmdldCA9IGV2ZW50LnRhcmdldDtcbiAgICAgICAgaWYgKGV2ZW50IGluc3RhbmNlb2YgS2V5Ym9hcmRFdmVudCAmJiB0aGlzLmFjdGlvbi5zaG91bGRJZ25vcmVLZXlib2FyZEV2ZW50KGV2ZW50KSkge1xuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9XG4gICAgICAgIGlmIChldmVudCBpbnN0YW5jZW9mIE1vdXNlRXZlbnQgJiYgdGhpcy5hY3Rpb24uc2hvdWxkSWdub3JlTW91c2VFdmVudChldmVudCkpIHtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuICAgICAgICBpZiAodGhpcy5lbGVtZW50ID09PSBldmVudFRhcmdldCkge1xuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSBpZiAoZXZlbnRUYXJnZXQgaW5zdGFuY2VvZiBFbGVtZW50ICYmIHRoaXMuZWxlbWVudC5jb250YWlucyhldmVudFRhcmdldCkpIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmNvbnRhaW5zRWxlbWVudChldmVudFRhcmdldCk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5jb250YWluc0VsZW1lbnQodGhpcy5hY3Rpb24uZWxlbWVudCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZ2V0IGNvbnRyb2xsZXIoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuY29udHJvbGxlcjtcbiAgICB9XG4gICAgZ2V0IG1ldGhvZE5hbWUoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmFjdGlvbi5tZXRob2ROYW1lO1xuICAgIH1cbiAgICBnZXQgZWxlbWVudCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuZWxlbWVudDtcbiAgICB9XG4gICAgZ2V0IHNjb3BlKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LnNjb3BlO1xuICAgIH1cbn1cblxuY2xhc3MgRWxlbWVudE9ic2VydmVyIHtcbiAgICBjb25zdHJ1Y3RvcihlbGVtZW50LCBkZWxlZ2F0ZSkge1xuICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXJJbml0ID0geyBhdHRyaWJ1dGVzOiB0cnVlLCBjaGlsZExpc3Q6IHRydWUsIHN1YnRyZWU6IHRydWUgfTtcbiAgICAgICAgdGhpcy5lbGVtZW50ID0gZWxlbWVudDtcbiAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5lbGVtZW50cyA9IG5ldyBTZXQoKTtcbiAgICAgICAgdGhpcy5tdXRhdGlvbk9ic2VydmVyID0gbmV3IE11dGF0aW9uT2JzZXJ2ZXIoKG11dGF0aW9ucykgPT4gdGhpcy5wcm9jZXNzTXV0YXRpb25zKG11dGF0aW9ucykpO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgaWYgKCF0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IHRydWU7XG4gICAgICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIub2JzZXJ2ZSh0aGlzLmVsZW1lbnQsIHRoaXMubXV0YXRpb25PYnNlcnZlckluaXQpO1xuICAgICAgICAgICAgdGhpcy5yZWZyZXNoKCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcGF1c2UoY2FsbGJhY2spIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgdGhpcy5tdXRhdGlvbk9ic2VydmVyLmRpc2Nvbm5lY3QoKTtcbiAgICAgICAgICAgIHRoaXMuc3RhcnRlZCA9IGZhbHNlO1xuICAgICAgICB9XG4gICAgICAgIGNhbGxiYWNrKCk7XG4gICAgICAgIGlmICghdGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIub2JzZXJ2ZSh0aGlzLmVsZW1lbnQsIHRoaXMubXV0YXRpb25PYnNlcnZlckluaXQpO1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gdHJ1ZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzdG9wKCkge1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIudGFrZVJlY29yZHMoKTtcbiAgICAgICAgICAgIHRoaXMubXV0YXRpb25PYnNlcnZlci5kaXNjb25uZWN0KCk7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZWZyZXNoKCkge1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICBjb25zdCBtYXRjaGVzID0gbmV3IFNldCh0aGlzLm1hdGNoRWxlbWVudHNJblRyZWUoKSk7XG4gICAgICAgICAgICBmb3IgKGNvbnN0IGVsZW1lbnQgb2YgQXJyYXkuZnJvbSh0aGlzLmVsZW1lbnRzKSkge1xuICAgICAgICAgICAgICAgIGlmICghbWF0Y2hlcy5oYXMoZWxlbWVudCkpIHtcbiAgICAgICAgICAgICAgICAgICAgdGhpcy5yZW1vdmVFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH1cbiAgICAgICAgICAgIGZvciAoY29uc3QgZWxlbWVudCBvZiBBcnJheS5mcm9tKG1hdGNoZXMpKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5hZGRFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuICAgIHByb2Nlc3NNdXRhdGlvbnMobXV0YXRpb25zKSB7XG4gICAgICAgIGlmICh0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIGZvciAoY29uc3QgbXV0YXRpb24gb2YgbXV0YXRpb25zKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5wcm9jZXNzTXV0YXRpb24obXV0YXRpb24pO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuICAgIHByb2Nlc3NNdXRhdGlvbihtdXRhdGlvbikge1xuICAgICAgICBpZiAobXV0YXRpb24udHlwZSA9PSBcImF0dHJpYnV0ZXNcIikge1xuICAgICAgICAgICAgdGhpcy5wcm9jZXNzQXR0cmlidXRlQ2hhbmdlKG11dGF0aW9uLnRhcmdldCwgbXV0YXRpb24uYXR0cmlidXRlTmFtZSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSBpZiAobXV0YXRpb24udHlwZSA9PSBcImNoaWxkTGlzdFwiKSB7XG4gICAgICAgICAgICB0aGlzLnByb2Nlc3NSZW1vdmVkTm9kZXMobXV0YXRpb24ucmVtb3ZlZE5vZGVzKTtcbiAgICAgICAgICAgIHRoaXMucHJvY2Vzc0FkZGVkTm9kZXMobXV0YXRpb24uYWRkZWROb2Rlcyk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcHJvY2Vzc0F0dHJpYnV0ZUNoYW5nZShlbGVtZW50LCBhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgIGlmICh0aGlzLmVsZW1lbnRzLmhhcyhlbGVtZW50KSkge1xuICAgICAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuZWxlbWVudEF0dHJpYnV0ZUNoYW5nZWQgJiYgdGhpcy5tYXRjaEVsZW1lbnQoZWxlbWVudCkpIHtcbiAgICAgICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmVsZW1lbnRBdHRyaWJ1dGVDaGFuZ2VkKGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICAgICAgdGhpcy5yZW1vdmVFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICAgIGVsc2UgaWYgKHRoaXMubWF0Y2hFbGVtZW50KGVsZW1lbnQpKSB7XG4gICAgICAgICAgICB0aGlzLmFkZEVsZW1lbnQoZWxlbWVudCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcHJvY2Vzc1JlbW92ZWROb2Rlcyhub2Rlcykge1xuICAgICAgICBmb3IgKGNvbnN0IG5vZGUgb2YgQXJyYXkuZnJvbShub2RlcykpIHtcbiAgICAgICAgICAgIGNvbnN0IGVsZW1lbnQgPSB0aGlzLmVsZW1lbnRGcm9tTm9kZShub2RlKTtcbiAgICAgICAgICAgIGlmIChlbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgdGhpcy5wcm9jZXNzVHJlZShlbGVtZW50LCB0aGlzLnJlbW92ZUVsZW1lbnQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuICAgIHByb2Nlc3NBZGRlZE5vZGVzKG5vZGVzKSB7XG4gICAgICAgIGZvciAoY29uc3Qgbm9kZSBvZiBBcnJheS5mcm9tKG5vZGVzKSkge1xuICAgICAgICAgICAgY29uc3QgZWxlbWVudCA9IHRoaXMuZWxlbWVudEZyb21Ob2RlKG5vZGUpO1xuICAgICAgICAgICAgaWYgKGVsZW1lbnQgJiYgdGhpcy5lbGVtZW50SXNBY3RpdmUoZWxlbWVudCkpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnByb2Nlc3NUcmVlKGVsZW1lbnQsIHRoaXMuYWRkRWxlbWVudCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9XG4gICAgbWF0Y2hFbGVtZW50KGVsZW1lbnQpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGVsZWdhdGUubWF0Y2hFbGVtZW50KGVsZW1lbnQpO1xuICAgIH1cbiAgICBtYXRjaEVsZW1lbnRzSW5UcmVlKHRyZWUgPSB0aGlzLmVsZW1lbnQpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGVsZWdhdGUubWF0Y2hFbGVtZW50c0luVHJlZSh0cmVlKTtcbiAgICB9XG4gICAgcHJvY2Vzc1RyZWUodHJlZSwgcHJvY2Vzc29yKSB7XG4gICAgICAgIGZvciAoY29uc3QgZWxlbWVudCBvZiB0aGlzLm1hdGNoRWxlbWVudHNJblRyZWUodHJlZSkpIHtcbiAgICAgICAgICAgIHByb2Nlc3Nvci5jYWxsKHRoaXMsIGVsZW1lbnQpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGVsZW1lbnRGcm9tTm9kZShub2RlKSB7XG4gICAgICAgIGlmIChub2RlLm5vZGVUeXBlID09IE5vZGUuRUxFTUVOVF9OT0RFKSB7XG4gICAgICAgICAgICByZXR1cm4gbm9kZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBlbGVtZW50SXNBY3RpdmUoZWxlbWVudCkge1xuICAgICAgICBpZiAoZWxlbWVudC5pc0Nvbm5lY3RlZCAhPSB0aGlzLmVsZW1lbnQuaXNDb25uZWN0ZWQpIHtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHJldHVybiB0aGlzLmVsZW1lbnQuY29udGFpbnMoZWxlbWVudCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgYWRkRWxlbWVudChlbGVtZW50KSB7XG4gICAgICAgIGlmICghdGhpcy5lbGVtZW50cy5oYXMoZWxlbWVudCkpIHtcbiAgICAgICAgICAgIGlmICh0aGlzLmVsZW1lbnRJc0FjdGl2ZShlbGVtZW50KSkge1xuICAgICAgICAgICAgICAgIHRoaXMuZWxlbWVudHMuYWRkKGVsZW1lbnQpO1xuICAgICAgICAgICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLmVsZW1lbnRNYXRjaGVkKSB7XG4gICAgICAgICAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZWxlbWVudE1hdGNoZWQoZWxlbWVudCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuICAgIHJlbW92ZUVsZW1lbnQoZWxlbWVudCkge1xuICAgICAgICBpZiAodGhpcy5lbGVtZW50cy5oYXMoZWxlbWVudCkpIHtcbiAgICAgICAgICAgIHRoaXMuZWxlbWVudHMuZGVsZXRlKGVsZW1lbnQpO1xuICAgICAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuZWxlbWVudFVubWF0Y2hlZCkge1xuICAgICAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZWxlbWVudFVubWF0Y2hlZChlbGVtZW50KTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH1cbn1cblxuY2xhc3MgQXR0cmlidXRlT2JzZXJ2ZXIge1xuICAgIGNvbnN0cnVjdG9yKGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUsIGRlbGVnYXRlKSB7XG4gICAgICAgIHRoaXMuYXR0cmlidXRlTmFtZSA9IGF0dHJpYnV0ZU5hbWU7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5lbGVtZW50T2JzZXJ2ZXIgPSBuZXcgRWxlbWVudE9ic2VydmVyKGVsZW1lbnQsIHRoaXMpO1xuICAgIH1cbiAgICBnZXQgZWxlbWVudCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZWxlbWVudE9ic2VydmVyLmVsZW1lbnQ7XG4gICAgfVxuICAgIGdldCBzZWxlY3RvcigpIHtcbiAgICAgICAgcmV0dXJuIGBbJHt0aGlzLmF0dHJpYnV0ZU5hbWV9XWA7XG4gICAgfVxuICAgIHN0YXJ0KCkge1xuICAgICAgICB0aGlzLmVsZW1lbnRPYnNlcnZlci5zdGFydCgpO1xuICAgIH1cbiAgICBwYXVzZShjYWxsYmFjaykge1xuICAgICAgICB0aGlzLmVsZW1lbnRPYnNlcnZlci5wYXVzZShjYWxsYmFjayk7XG4gICAgfVxuICAgIHN0b3AoKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudE9ic2VydmVyLnN0b3AoKTtcbiAgICB9XG4gICAgcmVmcmVzaCgpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50T2JzZXJ2ZXIucmVmcmVzaCgpO1xuICAgIH1cbiAgICBnZXQgc3RhcnRlZCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZWxlbWVudE9ic2VydmVyLnN0YXJ0ZWQ7XG4gICAgfVxuICAgIG1hdGNoRWxlbWVudChlbGVtZW50KSB7XG4gICAgICAgIHJldHVybiBlbGVtZW50Lmhhc0F0dHJpYnV0ZSh0aGlzLmF0dHJpYnV0ZU5hbWUpO1xuICAgIH1cbiAgICBtYXRjaEVsZW1lbnRzSW5UcmVlKHRyZWUpIHtcbiAgICAgICAgY29uc3QgbWF0Y2ggPSB0aGlzLm1hdGNoRWxlbWVudCh0cmVlKSA/IFt0cmVlXSA6IFtdO1xuICAgICAgICBjb25zdCBtYXRjaGVzID0gQXJyYXkuZnJvbSh0cmVlLnF1ZXJ5U2VsZWN0b3JBbGwodGhpcy5zZWxlY3RvcikpO1xuICAgICAgICByZXR1cm4gbWF0Y2guY29uY2F0KG1hdGNoZXMpO1xuICAgIH1cbiAgICBlbGVtZW50TWF0Y2hlZChlbGVtZW50KSB7XG4gICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLmVsZW1lbnRNYXRjaGVkQXR0cmlidXRlKSB7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmVsZW1lbnRNYXRjaGVkQXR0cmlidXRlKGVsZW1lbnQsIHRoaXMuYXR0cmlidXRlTmFtZSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZWxlbWVudFVubWF0Y2hlZChlbGVtZW50KSB7XG4gICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLmVsZW1lbnRVbm1hdGNoZWRBdHRyaWJ1dGUpIHtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZWxlbWVudFVubWF0Y2hlZEF0dHJpYnV0ZShlbGVtZW50LCB0aGlzLmF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGVsZW1lbnRBdHRyaWJ1dGVDaGFuZ2VkKGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUpIHtcbiAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuZWxlbWVudEF0dHJpYnV0ZVZhbHVlQ2hhbmdlZCAmJiB0aGlzLmF0dHJpYnV0ZU5hbWUgPT0gYXR0cmlidXRlTmFtZSkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5lbGVtZW50QXR0cmlidXRlVmFsdWVDaGFuZ2VkKGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICB9XG4gICAgfVxufVxuXG5mdW5jdGlvbiBhZGQobWFwLCBrZXksIHZhbHVlKSB7XG4gICAgZmV0Y2gobWFwLCBrZXkpLmFkZCh2YWx1ZSk7XG59XG5mdW5jdGlvbiBkZWwobWFwLCBrZXksIHZhbHVlKSB7XG4gICAgZmV0Y2gobWFwLCBrZXkpLmRlbGV0ZSh2YWx1ZSk7XG4gICAgcHJ1bmUobWFwLCBrZXkpO1xufVxuZnVuY3Rpb24gZmV0Y2gobWFwLCBrZXkpIHtcbiAgICBsZXQgdmFsdWVzID0gbWFwLmdldChrZXkpO1xuICAgIGlmICghdmFsdWVzKSB7XG4gICAgICAgIHZhbHVlcyA9IG5ldyBTZXQoKTtcbiAgICAgICAgbWFwLnNldChrZXksIHZhbHVlcyk7XG4gICAgfVxuICAgIHJldHVybiB2YWx1ZXM7XG59XG5mdW5jdGlvbiBwcnVuZShtYXAsIGtleSkge1xuICAgIGNvbnN0IHZhbHVlcyA9IG1hcC5nZXQoa2V5KTtcbiAgICBpZiAodmFsdWVzICE9IG51bGwgJiYgdmFsdWVzLnNpemUgPT0gMCkge1xuICAgICAgICBtYXAuZGVsZXRlKGtleSk7XG4gICAgfVxufVxuXG5jbGFzcyBNdWx0aW1hcCB7XG4gICAgY29uc3RydWN0b3IoKSB7XG4gICAgICAgIHRoaXMudmFsdWVzQnlLZXkgPSBuZXcgTWFwKCk7XG4gICAgfVxuICAgIGdldCBrZXlzKCkge1xuICAgICAgICByZXR1cm4gQXJyYXkuZnJvbSh0aGlzLnZhbHVlc0J5S2V5LmtleXMoKSk7XG4gICAgfVxuICAgIGdldCB2YWx1ZXMoKSB7XG4gICAgICAgIGNvbnN0IHNldHMgPSBBcnJheS5mcm9tKHRoaXMudmFsdWVzQnlLZXkudmFsdWVzKCkpO1xuICAgICAgICByZXR1cm4gc2V0cy5yZWR1Y2UoKHZhbHVlcywgc2V0KSA9PiB2YWx1ZXMuY29uY2F0KEFycmF5LmZyb20oc2V0KSksIFtdKTtcbiAgICB9XG4gICAgZ2V0IHNpemUoKSB7XG4gICAgICAgIGNvbnN0IHNldHMgPSBBcnJheS5mcm9tKHRoaXMudmFsdWVzQnlLZXkudmFsdWVzKCkpO1xuICAgICAgICByZXR1cm4gc2V0cy5yZWR1Y2UoKHNpemUsIHNldCkgPT4gc2l6ZSArIHNldC5zaXplLCAwKTtcbiAgICB9XG4gICAgYWRkKGtleSwgdmFsdWUpIHtcbiAgICAgICAgYWRkKHRoaXMudmFsdWVzQnlLZXksIGtleSwgdmFsdWUpO1xuICAgIH1cbiAgICBkZWxldGUoa2V5LCB2YWx1ZSkge1xuICAgICAgICBkZWwodGhpcy52YWx1ZXNCeUtleSwga2V5LCB2YWx1ZSk7XG4gICAgfVxuICAgIGhhcyhrZXksIHZhbHVlKSB7XG4gICAgICAgIGNvbnN0IHZhbHVlcyA9IHRoaXMudmFsdWVzQnlLZXkuZ2V0KGtleSk7XG4gICAgICAgIHJldHVybiB2YWx1ZXMgIT0gbnVsbCAmJiB2YWx1ZXMuaGFzKHZhbHVlKTtcbiAgICB9XG4gICAgaGFzS2V5KGtleSkge1xuICAgICAgICByZXR1cm4gdGhpcy52YWx1ZXNCeUtleS5oYXMoa2V5KTtcbiAgICB9XG4gICAgaGFzVmFsdWUodmFsdWUpIHtcbiAgICAgICAgY29uc3Qgc2V0cyA9IEFycmF5LmZyb20odGhpcy52YWx1ZXNCeUtleS52YWx1ZXMoKSk7XG4gICAgICAgIHJldHVybiBzZXRzLnNvbWUoKHNldCkgPT4gc2V0Lmhhcyh2YWx1ZSkpO1xuICAgIH1cbiAgICBnZXRWYWx1ZXNGb3JLZXkoa2V5KSB7XG4gICAgICAgIGNvbnN0IHZhbHVlcyA9IHRoaXMudmFsdWVzQnlLZXkuZ2V0KGtleSk7XG4gICAgICAgIHJldHVybiB2YWx1ZXMgPyBBcnJheS5mcm9tKHZhbHVlcykgOiBbXTtcbiAgICB9XG4gICAgZ2V0S2V5c0ZvclZhbHVlKHZhbHVlKSB7XG4gICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMudmFsdWVzQnlLZXkpXG4gICAgICAgICAgICAuZmlsdGVyKChbX2tleSwgdmFsdWVzXSkgPT4gdmFsdWVzLmhhcyh2YWx1ZSkpXG4gICAgICAgICAgICAubWFwKChba2V5LCBfdmFsdWVzXSkgPT4ga2V5KTtcbiAgICB9XG59XG5cbmNsYXNzIEluZGV4ZWRNdWx0aW1hcCBleHRlbmRzIE11bHRpbWFwIHtcbiAgICBjb25zdHJ1Y3RvcigpIHtcbiAgICAgICAgc3VwZXIoKTtcbiAgICAgICAgdGhpcy5rZXlzQnlWYWx1ZSA9IG5ldyBNYXAoKTtcbiAgICB9XG4gICAgZ2V0IHZhbHVlcygpIHtcbiAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy5rZXlzQnlWYWx1ZS5rZXlzKCkpO1xuICAgIH1cbiAgICBhZGQoa2V5LCB2YWx1ZSkge1xuICAgICAgICBzdXBlci5hZGQoa2V5LCB2YWx1ZSk7XG4gICAgICAgIGFkZCh0aGlzLmtleXNCeVZhbHVlLCB2YWx1ZSwga2V5KTtcbiAgICB9XG4gICAgZGVsZXRlKGtleSwgdmFsdWUpIHtcbiAgICAgICAgc3VwZXIuZGVsZXRlKGtleSwgdmFsdWUpO1xuICAgICAgICBkZWwodGhpcy5rZXlzQnlWYWx1ZSwgdmFsdWUsIGtleSk7XG4gICAgfVxuICAgIGhhc1ZhbHVlKHZhbHVlKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmtleXNCeVZhbHVlLmhhcyh2YWx1ZSk7XG4gICAgfVxuICAgIGdldEtleXNGb3JWYWx1ZSh2YWx1ZSkge1xuICAgICAgICBjb25zdCBzZXQgPSB0aGlzLmtleXNCeVZhbHVlLmdldCh2YWx1ZSk7XG4gICAgICAgIHJldHVybiBzZXQgPyBBcnJheS5mcm9tKHNldCkgOiBbXTtcbiAgICB9XG59XG5cbmNsYXNzIFNlbGVjdG9yT2JzZXJ2ZXIge1xuICAgIGNvbnN0cnVjdG9yKGVsZW1lbnQsIHNlbGVjdG9yLCBkZWxlZ2F0ZSwgZGV0YWlscykge1xuICAgICAgICB0aGlzLl9zZWxlY3RvciA9IHNlbGVjdG9yO1xuICAgICAgICB0aGlzLmRldGFpbHMgPSBkZXRhaWxzO1xuICAgICAgICB0aGlzLmVsZW1lbnRPYnNlcnZlciA9IG5ldyBFbGVtZW50T2JzZXJ2ZXIoZWxlbWVudCwgdGhpcyk7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5tYXRjaGVzQnlFbGVtZW50ID0gbmV3IE11bHRpbWFwKCk7XG4gICAgfVxuICAgIGdldCBzdGFydGVkKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5lbGVtZW50T2JzZXJ2ZXIuc3RhcnRlZDtcbiAgICB9XG4gICAgZ2V0IHNlbGVjdG9yKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5fc2VsZWN0b3I7XG4gICAgfVxuICAgIHNldCBzZWxlY3RvcihzZWxlY3Rvcikge1xuICAgICAgICB0aGlzLl9zZWxlY3RvciA9IHNlbGVjdG9yO1xuICAgICAgICB0aGlzLnJlZnJlc2goKTtcbiAgICB9XG4gICAgc3RhcnQoKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudE9ic2VydmVyLnN0YXJ0KCk7XG4gICAgfVxuICAgIHBhdXNlKGNhbGxiYWNrKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudE9ic2VydmVyLnBhdXNlKGNhbGxiYWNrKTtcbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50T2JzZXJ2ZXIuc3RvcCgpO1xuICAgIH1cbiAgICByZWZyZXNoKCkge1xuICAgICAgICB0aGlzLmVsZW1lbnRPYnNlcnZlci5yZWZyZXNoKCk7XG4gICAgfVxuICAgIGdldCBlbGVtZW50KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5lbGVtZW50T2JzZXJ2ZXIuZWxlbWVudDtcbiAgICB9XG4gICAgbWF0Y2hFbGVtZW50KGVsZW1lbnQpIHtcbiAgICAgICAgY29uc3QgeyBzZWxlY3RvciB9ID0gdGhpcztcbiAgICAgICAgaWYgKHNlbGVjdG9yKSB7XG4gICAgICAgICAgICBjb25zdCBtYXRjaGVzID0gZWxlbWVudC5tYXRjaGVzKHNlbGVjdG9yKTtcbiAgICAgICAgICAgIGlmICh0aGlzLmRlbGVnYXRlLnNlbGVjdG9yTWF0Y2hFbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIG1hdGNoZXMgJiYgdGhpcy5kZWxlZ2F0ZS5zZWxlY3Rvck1hdGNoRWxlbWVudChlbGVtZW50LCB0aGlzLmRldGFpbHMpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuIG1hdGNoZXM7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgIH1cbiAgICB9XG4gICAgbWF0Y2hFbGVtZW50c0luVHJlZSh0cmVlKSB7XG4gICAgICAgIGNvbnN0IHsgc2VsZWN0b3IgfSA9IHRoaXM7XG4gICAgICAgIGlmIChzZWxlY3Rvcikge1xuICAgICAgICAgICAgY29uc3QgbWF0Y2ggPSB0aGlzLm1hdGNoRWxlbWVudCh0cmVlKSA/IFt0cmVlXSA6IFtdO1xuICAgICAgICAgICAgY29uc3QgbWF0Y2hlcyA9IEFycmF5LmZyb20odHJlZS5xdWVyeVNlbGVjdG9yQWxsKHNlbGVjdG9yKSkuZmlsdGVyKChtYXRjaCkgPT4gdGhpcy5tYXRjaEVsZW1lbnQobWF0Y2gpKTtcbiAgICAgICAgICAgIHJldHVybiBtYXRjaC5jb25jYXQobWF0Y2hlcyk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXR1cm4gW107XG4gICAgICAgIH1cbiAgICB9XG4gICAgZWxlbWVudE1hdGNoZWQoZWxlbWVudCkge1xuICAgICAgICBjb25zdCB7IHNlbGVjdG9yIH0gPSB0aGlzO1xuICAgICAgICBpZiAoc2VsZWN0b3IpIHtcbiAgICAgICAgICAgIHRoaXMuc2VsZWN0b3JNYXRjaGVkKGVsZW1lbnQsIHNlbGVjdG9yKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBlbGVtZW50VW5tYXRjaGVkKGVsZW1lbnQpIHtcbiAgICAgICAgY29uc3Qgc2VsZWN0b3JzID0gdGhpcy5tYXRjaGVzQnlFbGVtZW50LmdldEtleXNGb3JWYWx1ZShlbGVtZW50KTtcbiAgICAgICAgZm9yIChjb25zdCBzZWxlY3RvciBvZiBzZWxlY3RvcnMpIHtcbiAgICAgICAgICAgIHRoaXMuc2VsZWN0b3JVbm1hdGNoZWQoZWxlbWVudCwgc2VsZWN0b3IpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGVsZW1lbnRBdHRyaWJ1dGVDaGFuZ2VkKGVsZW1lbnQsIF9hdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgIGNvbnN0IHsgc2VsZWN0b3IgfSA9IHRoaXM7XG4gICAgICAgIGlmIChzZWxlY3Rvcikge1xuICAgICAgICAgICAgY29uc3QgbWF0Y2hlcyA9IHRoaXMubWF0Y2hFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICAgICAgY29uc3QgbWF0Y2hlZEJlZm9yZSA9IHRoaXMubWF0Y2hlc0J5RWxlbWVudC5oYXMoc2VsZWN0b3IsIGVsZW1lbnQpO1xuICAgICAgICAgICAgaWYgKG1hdGNoZXMgJiYgIW1hdGNoZWRCZWZvcmUpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnNlbGVjdG9yTWF0Y2hlZChlbGVtZW50LCBzZWxlY3Rvcik7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIGlmICghbWF0Y2hlcyAmJiBtYXRjaGVkQmVmb3JlKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5zZWxlY3RvclVubWF0Y2hlZChlbGVtZW50LCBzZWxlY3Rvcik7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9XG4gICAgc2VsZWN0b3JNYXRjaGVkKGVsZW1lbnQsIHNlbGVjdG9yKSB7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUuc2VsZWN0b3JNYXRjaGVkKGVsZW1lbnQsIHNlbGVjdG9yLCB0aGlzLmRldGFpbHMpO1xuICAgICAgICB0aGlzLm1hdGNoZXNCeUVsZW1lbnQuYWRkKHNlbGVjdG9yLCBlbGVtZW50KTtcbiAgICB9XG4gICAgc2VsZWN0b3JVbm1hdGNoZWQoZWxlbWVudCwgc2VsZWN0b3IpIHtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZS5zZWxlY3RvclVubWF0Y2hlZChlbGVtZW50LCBzZWxlY3RvciwgdGhpcy5kZXRhaWxzKTtcbiAgICAgICAgdGhpcy5tYXRjaGVzQnlFbGVtZW50LmRlbGV0ZShzZWxlY3RvciwgZWxlbWVudCk7XG4gICAgfVxufVxuXG5jbGFzcyBTdHJpbmdNYXBPYnNlcnZlciB7XG4gICAgY29uc3RydWN0b3IoZWxlbWVudCwgZGVsZWdhdGUpIHtcbiAgICAgICAgdGhpcy5lbGVtZW50ID0gZWxlbWVudDtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgICAgICB0aGlzLnN0YXJ0ZWQgPSBmYWxzZTtcbiAgICAgICAgdGhpcy5zdHJpbmdNYXAgPSBuZXcgTWFwKCk7XG4gICAgICAgIHRoaXMubXV0YXRpb25PYnNlcnZlciA9IG5ldyBNdXRhdGlvbk9ic2VydmVyKChtdXRhdGlvbnMpID0+IHRoaXMucHJvY2Vzc011dGF0aW9ucyhtdXRhdGlvbnMpKTtcbiAgICB9XG4gICAgc3RhcnQoKSB7XG4gICAgICAgIGlmICghdGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICB0aGlzLnN0YXJ0ZWQgPSB0cnVlO1xuICAgICAgICAgICAgdGhpcy5tdXRhdGlvbk9ic2VydmVyLm9ic2VydmUodGhpcy5lbGVtZW50LCB7IGF0dHJpYnV0ZXM6IHRydWUsIGF0dHJpYnV0ZU9sZFZhbHVlOiB0cnVlIH0pO1xuICAgICAgICAgICAgdGhpcy5yZWZyZXNoKCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgdGhpcy5tdXRhdGlvbk9ic2VydmVyLnRha2VSZWNvcmRzKCk7XG4gICAgICAgICAgICB0aGlzLm11dGF0aW9uT2JzZXJ2ZXIuZGlzY29ubmVjdCgpO1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIH1cbiAgICB9XG4gICAgcmVmcmVzaCgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgZm9yIChjb25zdCBhdHRyaWJ1dGVOYW1lIG9mIHRoaXMua25vd25BdHRyaWJ1dGVOYW1lcykge1xuICAgICAgICAgICAgICAgIHRoaXMucmVmcmVzaEF0dHJpYnV0ZShhdHRyaWJ1dGVOYW1lLCBudWxsKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH1cbiAgICBwcm9jZXNzTXV0YXRpb25zKG11dGF0aW9ucykge1xuICAgICAgICBpZiAodGhpcy5zdGFydGVkKSB7XG4gICAgICAgICAgICBmb3IgKGNvbnN0IG11dGF0aW9uIG9mIG11dGF0aW9ucykge1xuICAgICAgICAgICAgICAgIHRoaXMucHJvY2Vzc011dGF0aW9uKG11dGF0aW9uKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH1cbiAgICBwcm9jZXNzTXV0YXRpb24obXV0YXRpb24pIHtcbiAgICAgICAgY29uc3QgYXR0cmlidXRlTmFtZSA9IG11dGF0aW9uLmF0dHJpYnV0ZU5hbWU7XG4gICAgICAgIGlmIChhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgICAgICB0aGlzLnJlZnJlc2hBdHRyaWJ1dGUoYXR0cmlidXRlTmFtZSwgbXV0YXRpb24ub2xkVmFsdWUpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHJlZnJlc2hBdHRyaWJ1dGUoYXR0cmlidXRlTmFtZSwgb2xkVmFsdWUpIHtcbiAgICAgICAgY29uc3Qga2V5ID0gdGhpcy5kZWxlZ2F0ZS5nZXRTdHJpbmdNYXBLZXlGb3JBdHRyaWJ1dGUoYXR0cmlidXRlTmFtZSk7XG4gICAgICAgIGlmIChrZXkgIT0gbnVsbCkge1xuICAgICAgICAgICAgaWYgKCF0aGlzLnN0cmluZ01hcC5oYXMoYXR0cmlidXRlTmFtZSkpIHtcbiAgICAgICAgICAgICAgICB0aGlzLnN0cmluZ01hcEtleUFkZGVkKGtleSwgYXR0cmlidXRlTmFtZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBjb25zdCB2YWx1ZSA9IHRoaXMuZWxlbWVudC5nZXRBdHRyaWJ1dGUoYXR0cmlidXRlTmFtZSk7XG4gICAgICAgICAgICBpZiAodGhpcy5zdHJpbmdNYXAuZ2V0KGF0dHJpYnV0ZU5hbWUpICE9IHZhbHVlKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5zdHJpbmdNYXBWYWx1ZUNoYW5nZWQodmFsdWUsIGtleSwgb2xkVmFsdWUpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgaWYgKHZhbHVlID09IG51bGwpIHtcbiAgICAgICAgICAgICAgICBjb25zdCBvbGRWYWx1ZSA9IHRoaXMuc3RyaW5nTWFwLmdldChhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgICAgICAgICB0aGlzLnN0cmluZ01hcC5kZWxldGUoYXR0cmlidXRlTmFtZSk7XG4gICAgICAgICAgICAgICAgaWYgKG9sZFZhbHVlKVxuICAgICAgICAgICAgICAgICAgICB0aGlzLnN0cmluZ01hcEtleVJlbW92ZWQoa2V5LCBhdHRyaWJ1dGVOYW1lLCBvbGRWYWx1ZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICB0aGlzLnN0cmluZ01hcC5zZXQoYXR0cmlidXRlTmFtZSwgdmFsdWUpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuICAgIHN0cmluZ01hcEtleUFkZGVkKGtleSwgYXR0cmlidXRlTmFtZSkge1xuICAgICAgICBpZiAodGhpcy5kZWxlZ2F0ZS5zdHJpbmdNYXBLZXlBZGRlZCkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5zdHJpbmdNYXBLZXlBZGRlZChrZXksIGF0dHJpYnV0ZU5hbWUpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0cmluZ01hcFZhbHVlQ2hhbmdlZCh2YWx1ZSwga2V5LCBvbGRWYWx1ZSkge1xuICAgICAgICBpZiAodGhpcy5kZWxlZ2F0ZS5zdHJpbmdNYXBWYWx1ZUNoYW5nZWQpIHtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuc3RyaW5nTWFwVmFsdWVDaGFuZ2VkKHZhbHVlLCBrZXksIG9sZFZhbHVlKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzdHJpbmdNYXBLZXlSZW1vdmVkKGtleSwgYXR0cmlidXRlTmFtZSwgb2xkVmFsdWUpIHtcbiAgICAgICAgaWYgKHRoaXMuZGVsZWdhdGUuc3RyaW5nTWFwS2V5UmVtb3ZlZCkge1xuICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5zdHJpbmdNYXBLZXlSZW1vdmVkKGtleSwgYXR0cmlidXRlTmFtZSwgb2xkVmFsdWUpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGdldCBrbm93bkF0dHJpYnV0ZU5hbWVzKCkge1xuICAgICAgICByZXR1cm4gQXJyYXkuZnJvbShuZXcgU2V0KHRoaXMuY3VycmVudEF0dHJpYnV0ZU5hbWVzLmNvbmNhdCh0aGlzLnJlY29yZGVkQXR0cmlidXRlTmFtZXMpKSk7XG4gICAgfVxuICAgIGdldCBjdXJyZW50QXR0cmlidXRlTmFtZXMoKSB7XG4gICAgICAgIHJldHVybiBBcnJheS5mcm9tKHRoaXMuZWxlbWVudC5hdHRyaWJ1dGVzKS5tYXAoKGF0dHJpYnV0ZSkgPT4gYXR0cmlidXRlLm5hbWUpO1xuICAgIH1cbiAgICBnZXQgcmVjb3JkZWRBdHRyaWJ1dGVOYW1lcygpIHtcbiAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy5zdHJpbmdNYXAua2V5cygpKTtcbiAgICB9XG59XG5cbmNsYXNzIFRva2VuTGlzdE9ic2VydmVyIHtcbiAgICBjb25zdHJ1Y3RvcihlbGVtZW50LCBhdHRyaWJ1dGVOYW1lLCBkZWxlZ2F0ZSkge1xuICAgICAgICB0aGlzLmF0dHJpYnV0ZU9ic2VydmVyID0gbmV3IEF0dHJpYnV0ZU9ic2VydmVyKGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUsIHRoaXMpO1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMudG9rZW5zQnlFbGVtZW50ID0gbmV3IE11bHRpbWFwKCk7XG4gICAgfVxuICAgIGdldCBzdGFydGVkKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5hdHRyaWJ1dGVPYnNlcnZlci5zdGFydGVkO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgdGhpcy5hdHRyaWJ1dGVPYnNlcnZlci5zdGFydCgpO1xuICAgIH1cbiAgICBwYXVzZShjYWxsYmFjaykge1xuICAgICAgICB0aGlzLmF0dHJpYnV0ZU9ic2VydmVyLnBhdXNlKGNhbGxiYWNrKTtcbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgdGhpcy5hdHRyaWJ1dGVPYnNlcnZlci5zdG9wKCk7XG4gICAgfVxuICAgIHJlZnJlc2goKSB7XG4gICAgICAgIHRoaXMuYXR0cmlidXRlT2JzZXJ2ZXIucmVmcmVzaCgpO1xuICAgIH1cbiAgICBnZXQgZWxlbWVudCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuYXR0cmlidXRlT2JzZXJ2ZXIuZWxlbWVudDtcbiAgICB9XG4gICAgZ2V0IGF0dHJpYnV0ZU5hbWUoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmF0dHJpYnV0ZU9ic2VydmVyLmF0dHJpYnV0ZU5hbWU7XG4gICAgfVxuICAgIGVsZW1lbnRNYXRjaGVkQXR0cmlidXRlKGVsZW1lbnQpIHtcbiAgICAgICAgdGhpcy50b2tlbnNNYXRjaGVkKHRoaXMucmVhZFRva2Vuc0ZvckVsZW1lbnQoZWxlbWVudCkpO1xuICAgIH1cbiAgICBlbGVtZW50QXR0cmlidXRlVmFsdWVDaGFuZ2VkKGVsZW1lbnQpIHtcbiAgICAgICAgY29uc3QgW3VubWF0Y2hlZFRva2VucywgbWF0Y2hlZFRva2Vuc10gPSB0aGlzLnJlZnJlc2hUb2tlbnNGb3JFbGVtZW50KGVsZW1lbnQpO1xuICAgICAgICB0aGlzLnRva2Vuc1VubWF0Y2hlZCh1bm1hdGNoZWRUb2tlbnMpO1xuICAgICAgICB0aGlzLnRva2Vuc01hdGNoZWQobWF0Y2hlZFRva2Vucyk7XG4gICAgfVxuICAgIGVsZW1lbnRVbm1hdGNoZWRBdHRyaWJ1dGUoZWxlbWVudCkge1xuICAgICAgICB0aGlzLnRva2Vuc1VubWF0Y2hlZCh0aGlzLnRva2Vuc0J5RWxlbWVudC5nZXRWYWx1ZXNGb3JLZXkoZWxlbWVudCkpO1xuICAgIH1cbiAgICB0b2tlbnNNYXRjaGVkKHRva2Vucykge1xuICAgICAgICB0b2tlbnMuZm9yRWFjaCgodG9rZW4pID0+IHRoaXMudG9rZW5NYXRjaGVkKHRva2VuKSk7XG4gICAgfVxuICAgIHRva2Vuc1VubWF0Y2hlZCh0b2tlbnMpIHtcbiAgICAgICAgdG9rZW5zLmZvckVhY2goKHRva2VuKSA9PiB0aGlzLnRva2VuVW5tYXRjaGVkKHRva2VuKSk7XG4gICAgfVxuICAgIHRva2VuTWF0Y2hlZCh0b2tlbikge1xuICAgICAgICB0aGlzLmRlbGVnYXRlLnRva2VuTWF0Y2hlZCh0b2tlbik7XG4gICAgICAgIHRoaXMudG9rZW5zQnlFbGVtZW50LmFkZCh0b2tlbi5lbGVtZW50LCB0b2tlbik7XG4gICAgfVxuICAgIHRva2VuVW5tYXRjaGVkKHRva2VuKSB7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUudG9rZW5Vbm1hdGNoZWQodG9rZW4pO1xuICAgICAgICB0aGlzLnRva2Vuc0J5RWxlbWVudC5kZWxldGUodG9rZW4uZWxlbWVudCwgdG9rZW4pO1xuICAgIH1cbiAgICByZWZyZXNoVG9rZW5zRm9yRWxlbWVudChlbGVtZW50KSB7XG4gICAgICAgIGNvbnN0IHByZXZpb3VzVG9rZW5zID0gdGhpcy50b2tlbnNCeUVsZW1lbnQuZ2V0VmFsdWVzRm9yS2V5KGVsZW1lbnQpO1xuICAgICAgICBjb25zdCBjdXJyZW50VG9rZW5zID0gdGhpcy5yZWFkVG9rZW5zRm9yRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgY29uc3QgZmlyc3REaWZmZXJpbmdJbmRleCA9IHppcChwcmV2aW91c1Rva2VucywgY3VycmVudFRva2VucykuZmluZEluZGV4KChbcHJldmlvdXNUb2tlbiwgY3VycmVudFRva2VuXSkgPT4gIXRva2Vuc0FyZUVxdWFsKHByZXZpb3VzVG9rZW4sIGN1cnJlbnRUb2tlbikpO1xuICAgICAgICBpZiAoZmlyc3REaWZmZXJpbmdJbmRleCA9PSAtMSkge1xuICAgICAgICAgICAgcmV0dXJuIFtbXSwgW11dO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIFtwcmV2aW91c1Rva2Vucy5zbGljZShmaXJzdERpZmZlcmluZ0luZGV4KSwgY3VycmVudFRva2Vucy5zbGljZShmaXJzdERpZmZlcmluZ0luZGV4KV07XG4gICAgICAgIH1cbiAgICB9XG4gICAgcmVhZFRva2Vuc0ZvckVsZW1lbnQoZWxlbWVudCkge1xuICAgICAgICBjb25zdCBhdHRyaWJ1dGVOYW1lID0gdGhpcy5hdHRyaWJ1dGVOYW1lO1xuICAgICAgICBjb25zdCB0b2tlblN0cmluZyA9IGVsZW1lbnQuZ2V0QXR0cmlidXRlKGF0dHJpYnV0ZU5hbWUpIHx8IFwiXCI7XG4gICAgICAgIHJldHVybiBwYXJzZVRva2VuU3RyaW5nKHRva2VuU3RyaW5nLCBlbGVtZW50LCBhdHRyaWJ1dGVOYW1lKTtcbiAgICB9XG59XG5mdW5jdGlvbiBwYXJzZVRva2VuU3RyaW5nKHRva2VuU3RyaW5nLCBlbGVtZW50LCBhdHRyaWJ1dGVOYW1lKSB7XG4gICAgcmV0dXJuIHRva2VuU3RyaW5nXG4gICAgICAgIC50cmltKClcbiAgICAgICAgLnNwbGl0KC9cXHMrLylcbiAgICAgICAgLmZpbHRlcigoY29udGVudCkgPT4gY29udGVudC5sZW5ndGgpXG4gICAgICAgIC5tYXAoKGNvbnRlbnQsIGluZGV4KSA9PiAoeyBlbGVtZW50LCBhdHRyaWJ1dGVOYW1lLCBjb250ZW50LCBpbmRleCB9KSk7XG59XG5mdW5jdGlvbiB6aXAobGVmdCwgcmlnaHQpIHtcbiAgICBjb25zdCBsZW5ndGggPSBNYXRoLm1heChsZWZ0Lmxlbmd0aCwgcmlnaHQubGVuZ3RoKTtcbiAgICByZXR1cm4gQXJyYXkuZnJvbSh7IGxlbmd0aCB9LCAoXywgaW5kZXgpID0+IFtsZWZ0W2luZGV4XSwgcmlnaHRbaW5kZXhdXSk7XG59XG5mdW5jdGlvbiB0b2tlbnNBcmVFcXVhbChsZWZ0LCByaWdodCkge1xuICAgIHJldHVybiBsZWZ0ICYmIHJpZ2h0ICYmIGxlZnQuaW5kZXggPT0gcmlnaHQuaW5kZXggJiYgbGVmdC5jb250ZW50ID09IHJpZ2h0LmNvbnRlbnQ7XG59XG5cbmNsYXNzIFZhbHVlTGlzdE9ic2VydmVyIHtcbiAgICBjb25zdHJ1Y3RvcihlbGVtZW50LCBhdHRyaWJ1dGVOYW1lLCBkZWxlZ2F0ZSkge1xuICAgICAgICB0aGlzLnRva2VuTGlzdE9ic2VydmVyID0gbmV3IFRva2VuTGlzdE9ic2VydmVyKGVsZW1lbnQsIGF0dHJpYnV0ZU5hbWUsIHRoaXMpO1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMucGFyc2VSZXN1bHRzQnlUb2tlbiA9IG5ldyBXZWFrTWFwKCk7XG4gICAgICAgIHRoaXMudmFsdWVzQnlUb2tlbkJ5RWxlbWVudCA9IG5ldyBXZWFrTWFwKCk7XG4gICAgfVxuICAgIGdldCBzdGFydGVkKCkge1xuICAgICAgICByZXR1cm4gdGhpcy50b2tlbkxpc3RPYnNlcnZlci5zdGFydGVkO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgdGhpcy50b2tlbkxpc3RPYnNlcnZlci5zdGFydCgpO1xuICAgIH1cbiAgICBzdG9wKCkge1xuICAgICAgICB0aGlzLnRva2VuTGlzdE9ic2VydmVyLnN0b3AoKTtcbiAgICB9XG4gICAgcmVmcmVzaCgpIHtcbiAgICAgICAgdGhpcy50b2tlbkxpc3RPYnNlcnZlci5yZWZyZXNoKCk7XG4gICAgfVxuICAgIGdldCBlbGVtZW50KCkge1xuICAgICAgICByZXR1cm4gdGhpcy50b2tlbkxpc3RPYnNlcnZlci5lbGVtZW50O1xuICAgIH1cbiAgICBnZXQgYXR0cmlidXRlTmFtZSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMudG9rZW5MaXN0T2JzZXJ2ZXIuYXR0cmlidXRlTmFtZTtcbiAgICB9XG4gICAgdG9rZW5NYXRjaGVkKHRva2VuKSB7XG4gICAgICAgIGNvbnN0IHsgZWxlbWVudCB9ID0gdG9rZW47XG4gICAgICAgIGNvbnN0IHsgdmFsdWUgfSA9IHRoaXMuZmV0Y2hQYXJzZVJlc3VsdEZvclRva2VuKHRva2VuKTtcbiAgICAgICAgaWYgKHZhbHVlKSB7XG4gICAgICAgICAgICB0aGlzLmZldGNoVmFsdWVzQnlUb2tlbkZvckVsZW1lbnQoZWxlbWVudCkuc2V0KHRva2VuLCB2YWx1ZSk7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmVsZW1lbnRNYXRjaGVkVmFsdWUoZWxlbWVudCwgdmFsdWUpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHRva2VuVW5tYXRjaGVkKHRva2VuKSB7XG4gICAgICAgIGNvbnN0IHsgZWxlbWVudCB9ID0gdG9rZW47XG4gICAgICAgIGNvbnN0IHsgdmFsdWUgfSA9IHRoaXMuZmV0Y2hQYXJzZVJlc3VsdEZvclRva2VuKHRva2VuKTtcbiAgICAgICAgaWYgKHZhbHVlKSB7XG4gICAgICAgICAgICB0aGlzLmZldGNoVmFsdWVzQnlUb2tlbkZvckVsZW1lbnQoZWxlbWVudCkuZGVsZXRlKHRva2VuKTtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuZWxlbWVudFVubWF0Y2hlZFZhbHVlKGVsZW1lbnQsIHZhbHVlKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBmZXRjaFBhcnNlUmVzdWx0Rm9yVG9rZW4odG9rZW4pIHtcbiAgICAgICAgbGV0IHBhcnNlUmVzdWx0ID0gdGhpcy5wYXJzZVJlc3VsdHNCeVRva2VuLmdldCh0b2tlbik7XG4gICAgICAgIGlmICghcGFyc2VSZXN1bHQpIHtcbiAgICAgICAgICAgIHBhcnNlUmVzdWx0ID0gdGhpcy5wYXJzZVRva2VuKHRva2VuKTtcbiAgICAgICAgICAgIHRoaXMucGFyc2VSZXN1bHRzQnlUb2tlbi5zZXQodG9rZW4sIHBhcnNlUmVzdWx0KTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gcGFyc2VSZXN1bHQ7XG4gICAgfVxuICAgIGZldGNoVmFsdWVzQnlUb2tlbkZvckVsZW1lbnQoZWxlbWVudCkge1xuICAgICAgICBsZXQgdmFsdWVzQnlUb2tlbiA9IHRoaXMudmFsdWVzQnlUb2tlbkJ5RWxlbWVudC5nZXQoZWxlbWVudCk7XG4gICAgICAgIGlmICghdmFsdWVzQnlUb2tlbikge1xuICAgICAgICAgICAgdmFsdWVzQnlUb2tlbiA9IG5ldyBNYXAoKTtcbiAgICAgICAgICAgIHRoaXMudmFsdWVzQnlUb2tlbkJ5RWxlbWVudC5zZXQoZWxlbWVudCwgdmFsdWVzQnlUb2tlbik7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIHZhbHVlc0J5VG9rZW47XG4gICAgfVxuICAgIHBhcnNlVG9rZW4odG9rZW4pIHtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIGNvbnN0IHZhbHVlID0gdGhpcy5kZWxlZ2F0ZS5wYXJzZVZhbHVlRm9yVG9rZW4odG9rZW4pO1xuICAgICAgICAgICAgcmV0dXJuIHsgdmFsdWUgfTtcbiAgICAgICAgfVxuICAgICAgICBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgIHJldHVybiB7IGVycm9yIH07XG4gICAgICAgIH1cbiAgICB9XG59XG5cbmNsYXNzIEJpbmRpbmdPYnNlcnZlciB7XG4gICAgY29uc3RydWN0b3IoY29udGV4dCwgZGVsZWdhdGUpIHtcbiAgICAgICAgdGhpcy5jb250ZXh0ID0gY29udGV4dDtcbiAgICAgICAgdGhpcy5kZWxlZ2F0ZSA9IGRlbGVnYXRlO1xuICAgICAgICB0aGlzLmJpbmRpbmdzQnlBY3Rpb24gPSBuZXcgTWFwKCk7XG4gICAgfVxuICAgIHN0YXJ0KCkge1xuICAgICAgICBpZiAoIXRoaXMudmFsdWVMaXN0T2JzZXJ2ZXIpIHtcbiAgICAgICAgICAgIHRoaXMudmFsdWVMaXN0T2JzZXJ2ZXIgPSBuZXcgVmFsdWVMaXN0T2JzZXJ2ZXIodGhpcy5lbGVtZW50LCB0aGlzLmFjdGlvbkF0dHJpYnV0ZSwgdGhpcyk7XG4gICAgICAgICAgICB0aGlzLnZhbHVlTGlzdE9ic2VydmVyLnN0YXJ0KCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgaWYgKHRoaXMudmFsdWVMaXN0T2JzZXJ2ZXIpIHtcbiAgICAgICAgICAgIHRoaXMudmFsdWVMaXN0T2JzZXJ2ZXIuc3RvcCgpO1xuICAgICAgICAgICAgZGVsZXRlIHRoaXMudmFsdWVMaXN0T2JzZXJ2ZXI7XG4gICAgICAgICAgICB0aGlzLmRpc2Nvbm5lY3RBbGxBY3Rpb25zKCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZ2V0IGVsZW1lbnQoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuZWxlbWVudDtcbiAgICB9XG4gICAgZ2V0IGlkZW50aWZpZXIoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuaWRlbnRpZmllcjtcbiAgICB9XG4gICAgZ2V0IGFjdGlvbkF0dHJpYnV0ZSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2NoZW1hLmFjdGlvbkF0dHJpYnV0ZTtcbiAgICB9XG4gICAgZ2V0IHNjaGVtYSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuY29udGV4dC5zY2hlbWE7XG4gICAgfVxuICAgIGdldCBiaW5kaW5ncygpIHtcbiAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy5iaW5kaW5nc0J5QWN0aW9uLnZhbHVlcygpKTtcbiAgICB9XG4gICAgY29ubmVjdEFjdGlvbihhY3Rpb24pIHtcbiAgICAgICAgY29uc3QgYmluZGluZyA9IG5ldyBCaW5kaW5nKHRoaXMuY29udGV4dCwgYWN0aW9uKTtcbiAgICAgICAgdGhpcy5iaW5kaW5nc0J5QWN0aW9uLnNldChhY3Rpb24sIGJpbmRpbmcpO1xuICAgICAgICB0aGlzLmRlbGVnYXRlLmJpbmRpbmdDb25uZWN0ZWQoYmluZGluZyk7XG4gICAgfVxuICAgIGRpc2Nvbm5lY3RBY3Rpb24oYWN0aW9uKSB7XG4gICAgICAgIGNvbnN0IGJpbmRpbmcgPSB0aGlzLmJpbmRpbmdzQnlBY3Rpb24uZ2V0KGFjdGlvbik7XG4gICAgICAgIGlmIChiaW5kaW5nKSB7XG4gICAgICAgICAgICB0aGlzLmJpbmRpbmdzQnlBY3Rpb24uZGVsZXRlKGFjdGlvbik7XG4gICAgICAgICAgICB0aGlzLmRlbGVnYXRlLmJpbmRpbmdEaXNjb25uZWN0ZWQoYmluZGluZyk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZGlzY29ubmVjdEFsbEFjdGlvbnMoKSB7XG4gICAgICAgIHRoaXMuYmluZGluZ3MuZm9yRWFjaCgoYmluZGluZykgPT4gdGhpcy5kZWxlZ2F0ZS5iaW5kaW5nRGlzY29ubmVjdGVkKGJpbmRpbmcsIHRydWUpKTtcbiAgICAgICAgdGhpcy5iaW5kaW5nc0J5QWN0aW9uLmNsZWFyKCk7XG4gICAgfVxuICAgIHBhcnNlVmFsdWVGb3JUb2tlbih0b2tlbikge1xuICAgICAgICBjb25zdCBhY3Rpb24gPSBBY3Rpb24uZm9yVG9rZW4odG9rZW4sIHRoaXMuc2NoZW1hKTtcbiAgICAgICAgaWYgKGFjdGlvbi5pZGVudGlmaWVyID09IHRoaXMuaWRlbnRpZmllcikge1xuICAgICAgICAgICAgcmV0dXJuIGFjdGlvbjtcbiAgICAgICAgfVxuICAgIH1cbiAgICBlbGVtZW50TWF0Y2hlZFZhbHVlKGVsZW1lbnQsIGFjdGlvbikge1xuICAgICAgICB0aGlzLmNvbm5lY3RBY3Rpb24oYWN0aW9uKTtcbiAgICB9XG4gICAgZWxlbWVudFVubWF0Y2hlZFZhbHVlKGVsZW1lbnQsIGFjdGlvbikge1xuICAgICAgICB0aGlzLmRpc2Nvbm5lY3RBY3Rpb24oYWN0aW9uKTtcbiAgICB9XG59XG5cbmNsYXNzIFZhbHVlT2JzZXJ2ZXIge1xuICAgIGNvbnN0cnVjdG9yKGNvbnRleHQsIHJlY2VpdmVyKSB7XG4gICAgICAgIHRoaXMuY29udGV4dCA9IGNvbnRleHQ7XG4gICAgICAgIHRoaXMucmVjZWl2ZXIgPSByZWNlaXZlcjtcbiAgICAgICAgdGhpcy5zdHJpbmdNYXBPYnNlcnZlciA9IG5ldyBTdHJpbmdNYXBPYnNlcnZlcih0aGlzLmVsZW1lbnQsIHRoaXMpO1xuICAgICAgICB0aGlzLnZhbHVlRGVzY3JpcHRvck1hcCA9IHRoaXMuY29udHJvbGxlci52YWx1ZURlc2NyaXB0b3JNYXA7XG4gICAgfVxuICAgIHN0YXJ0KCkge1xuICAgICAgICB0aGlzLnN0cmluZ01hcE9ic2VydmVyLnN0YXJ0KCk7XG4gICAgICAgIHRoaXMuaW52b2tlQ2hhbmdlZENhbGxiYWNrc0ZvckRlZmF1bHRWYWx1ZXMoKTtcbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgdGhpcy5zdHJpbmdNYXBPYnNlcnZlci5zdG9wKCk7XG4gICAgfVxuICAgIGdldCBlbGVtZW50KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LmVsZW1lbnQ7XG4gICAgfVxuICAgIGdldCBjb250cm9sbGVyKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LmNvbnRyb2xsZXI7XG4gICAgfVxuICAgIGdldFN0cmluZ01hcEtleUZvckF0dHJpYnV0ZShhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgIGlmIChhdHRyaWJ1dGVOYW1lIGluIHRoaXMudmFsdWVEZXNjcmlwdG9yTWFwKSB7XG4gICAgICAgICAgICByZXR1cm4gdGhpcy52YWx1ZURlc2NyaXB0b3JNYXBbYXR0cmlidXRlTmFtZV0ubmFtZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzdHJpbmdNYXBLZXlBZGRlZChrZXksIGF0dHJpYnV0ZU5hbWUpIHtcbiAgICAgICAgY29uc3QgZGVzY3JpcHRvciA9IHRoaXMudmFsdWVEZXNjcmlwdG9yTWFwW2F0dHJpYnV0ZU5hbWVdO1xuICAgICAgICBpZiAoIXRoaXMuaGFzVmFsdWUoa2V5KSkge1xuICAgICAgICAgICAgdGhpcy5pbnZva2VDaGFuZ2VkQ2FsbGJhY2soa2V5LCBkZXNjcmlwdG9yLndyaXRlcih0aGlzLnJlY2VpdmVyW2tleV0pLCBkZXNjcmlwdG9yLndyaXRlcihkZXNjcmlwdG9yLmRlZmF1bHRWYWx1ZSkpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHN0cmluZ01hcFZhbHVlQ2hhbmdlZCh2YWx1ZSwgbmFtZSwgb2xkVmFsdWUpIHtcbiAgICAgICAgY29uc3QgZGVzY3JpcHRvciA9IHRoaXMudmFsdWVEZXNjcmlwdG9yTmFtZU1hcFtuYW1lXTtcbiAgICAgICAgaWYgKHZhbHVlID09PSBudWxsKVxuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICBpZiAob2xkVmFsdWUgPT09IG51bGwpIHtcbiAgICAgICAgICAgIG9sZFZhbHVlID0gZGVzY3JpcHRvci53cml0ZXIoZGVzY3JpcHRvci5kZWZhdWx0VmFsdWUpO1xuICAgICAgICB9XG4gICAgICAgIHRoaXMuaW52b2tlQ2hhbmdlZENhbGxiYWNrKG5hbWUsIHZhbHVlLCBvbGRWYWx1ZSk7XG4gICAgfVxuICAgIHN0cmluZ01hcEtleVJlbW92ZWQoa2V5LCBhdHRyaWJ1dGVOYW1lLCBvbGRWYWx1ZSkge1xuICAgICAgICBjb25zdCBkZXNjcmlwdG9yID0gdGhpcy52YWx1ZURlc2NyaXB0b3JOYW1lTWFwW2tleV07XG4gICAgICAgIGlmICh0aGlzLmhhc1ZhbHVlKGtleSkpIHtcbiAgICAgICAgICAgIHRoaXMuaW52b2tlQ2hhbmdlZENhbGxiYWNrKGtleSwgZGVzY3JpcHRvci53cml0ZXIodGhpcy5yZWNlaXZlcltrZXldKSwgb2xkVmFsdWUpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5pbnZva2VDaGFuZ2VkQ2FsbGJhY2soa2V5LCBkZXNjcmlwdG9yLndyaXRlcihkZXNjcmlwdG9yLmRlZmF1bHRWYWx1ZSksIG9sZFZhbHVlKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBpbnZva2VDaGFuZ2VkQ2FsbGJhY2tzRm9yRGVmYXVsdFZhbHVlcygpIHtcbiAgICAgICAgZm9yIChjb25zdCB7IGtleSwgbmFtZSwgZGVmYXVsdFZhbHVlLCB3cml0ZXIgfSBvZiB0aGlzLnZhbHVlRGVzY3JpcHRvcnMpIHtcbiAgICAgICAgICAgIGlmIChkZWZhdWx0VmFsdWUgIT0gdW5kZWZpbmVkICYmICF0aGlzLmNvbnRyb2xsZXIuZGF0YS5oYXMoa2V5KSkge1xuICAgICAgICAgICAgICAgIHRoaXMuaW52b2tlQ2hhbmdlZENhbGxiYWNrKG5hbWUsIHdyaXRlcihkZWZhdWx0VmFsdWUpLCB1bmRlZmluZWQpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuICAgIGludm9rZUNoYW5nZWRDYWxsYmFjayhuYW1lLCByYXdWYWx1ZSwgcmF3T2xkVmFsdWUpIHtcbiAgICAgICAgY29uc3QgY2hhbmdlZE1ldGhvZE5hbWUgPSBgJHtuYW1lfUNoYW5nZWRgO1xuICAgICAgICBjb25zdCBjaGFuZ2VkTWV0aG9kID0gdGhpcy5yZWNlaXZlcltjaGFuZ2VkTWV0aG9kTmFtZV07XG4gICAgICAgIGlmICh0eXBlb2YgY2hhbmdlZE1ldGhvZCA9PSBcImZ1bmN0aW9uXCIpIHtcbiAgICAgICAgICAgIGNvbnN0IGRlc2NyaXB0b3IgPSB0aGlzLnZhbHVlRGVzY3JpcHRvck5hbWVNYXBbbmFtZV07XG4gICAgICAgICAgICB0cnkge1xuICAgICAgICAgICAgICAgIGNvbnN0IHZhbHVlID0gZGVzY3JpcHRvci5yZWFkZXIocmF3VmFsdWUpO1xuICAgICAgICAgICAgICAgIGxldCBvbGRWYWx1ZSA9IHJhd09sZFZhbHVlO1xuICAgICAgICAgICAgICAgIGlmIChyYXdPbGRWYWx1ZSkge1xuICAgICAgICAgICAgICAgICAgICBvbGRWYWx1ZSA9IGRlc2NyaXB0b3IucmVhZGVyKHJhd09sZFZhbHVlKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgY2hhbmdlZE1ldGhvZC5jYWxsKHRoaXMucmVjZWl2ZXIsIHZhbHVlLCBvbGRWYWx1ZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgICBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgICAgICBpZiAoZXJyb3IgaW5zdGFuY2VvZiBUeXBlRXJyb3IpIHtcbiAgICAgICAgICAgICAgICAgICAgZXJyb3IubWVzc2FnZSA9IGBTdGltdWx1cyBWYWx1ZSBcIiR7dGhpcy5jb250ZXh0LmlkZW50aWZpZXJ9LiR7ZGVzY3JpcHRvci5uYW1lfVwiIC0gJHtlcnJvci5tZXNzYWdlfWA7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIHRocm93IGVycm9yO1xuICAgICAgICAgICAgfVxuICAgICAgICB9XG4gICAgfVxuICAgIGdldCB2YWx1ZURlc2NyaXB0b3JzKCkge1xuICAgICAgICBjb25zdCB7IHZhbHVlRGVzY3JpcHRvck1hcCB9ID0gdGhpcztcbiAgICAgICAgcmV0dXJuIE9iamVjdC5rZXlzKHZhbHVlRGVzY3JpcHRvck1hcCkubWFwKChrZXkpID0+IHZhbHVlRGVzY3JpcHRvck1hcFtrZXldKTtcbiAgICB9XG4gICAgZ2V0IHZhbHVlRGVzY3JpcHRvck5hbWVNYXAoKSB7XG4gICAgICAgIGNvbnN0IGRlc2NyaXB0b3JzID0ge307XG4gICAgICAgIE9iamVjdC5rZXlzKHRoaXMudmFsdWVEZXNjcmlwdG9yTWFwKS5mb3JFYWNoKChrZXkpID0+IHtcbiAgICAgICAgICAgIGNvbnN0IGRlc2NyaXB0b3IgPSB0aGlzLnZhbHVlRGVzY3JpcHRvck1hcFtrZXldO1xuICAgICAgICAgICAgZGVzY3JpcHRvcnNbZGVzY3JpcHRvci5uYW1lXSA9IGRlc2NyaXB0b3I7XG4gICAgICAgIH0pO1xuICAgICAgICByZXR1cm4gZGVzY3JpcHRvcnM7XG4gICAgfVxuICAgIGhhc1ZhbHVlKGF0dHJpYnV0ZU5hbWUpIHtcbiAgICAgICAgY29uc3QgZGVzY3JpcHRvciA9IHRoaXMudmFsdWVEZXNjcmlwdG9yTmFtZU1hcFthdHRyaWJ1dGVOYW1lXTtcbiAgICAgICAgY29uc3QgaGFzTWV0aG9kTmFtZSA9IGBoYXMke2NhcGl0YWxpemUoZGVzY3JpcHRvci5uYW1lKX1gO1xuICAgICAgICByZXR1cm4gdGhpcy5yZWNlaXZlcltoYXNNZXRob2ROYW1lXTtcbiAgICB9XG59XG5cbmNsYXNzIFRhcmdldE9ic2VydmVyIHtcbiAgICBjb25zdHJ1Y3Rvcihjb250ZXh0LCBkZWxlZ2F0ZSkge1xuICAgICAgICB0aGlzLmNvbnRleHQgPSBjb250ZXh0O1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMudGFyZ2V0c0J5TmFtZSA9IG5ldyBNdWx0aW1hcCgpO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgaWYgKCF0aGlzLnRva2VuTGlzdE9ic2VydmVyKSB7XG4gICAgICAgICAgICB0aGlzLnRva2VuTGlzdE9ic2VydmVyID0gbmV3IFRva2VuTGlzdE9ic2VydmVyKHRoaXMuZWxlbWVudCwgdGhpcy5hdHRyaWJ1dGVOYW1lLCB0aGlzKTtcbiAgICAgICAgICAgIHRoaXMudG9rZW5MaXN0T2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzdG9wKCkge1xuICAgICAgICBpZiAodGhpcy50b2tlbkxpc3RPYnNlcnZlcikge1xuICAgICAgICAgICAgdGhpcy5kaXNjb25uZWN0QWxsVGFyZ2V0cygpO1xuICAgICAgICAgICAgdGhpcy50b2tlbkxpc3RPYnNlcnZlci5zdG9wKCk7XG4gICAgICAgICAgICBkZWxldGUgdGhpcy50b2tlbkxpc3RPYnNlcnZlcjtcbiAgICAgICAgfVxuICAgIH1cbiAgICB0b2tlbk1hdGNoZWQoeyBlbGVtZW50LCBjb250ZW50OiBuYW1lIH0pIHtcbiAgICAgICAgaWYgKHRoaXMuc2NvcGUuY29udGFpbnNFbGVtZW50KGVsZW1lbnQpKSB7XG4gICAgICAgICAgICB0aGlzLmNvbm5lY3RUYXJnZXQoZWxlbWVudCwgbmFtZSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgdG9rZW5Vbm1hdGNoZWQoeyBlbGVtZW50LCBjb250ZW50OiBuYW1lIH0pIHtcbiAgICAgICAgdGhpcy5kaXNjb25uZWN0VGFyZ2V0KGVsZW1lbnQsIG5hbWUpO1xuICAgIH1cbiAgICBjb25uZWN0VGFyZ2V0KGVsZW1lbnQsIG5hbWUpIHtcbiAgICAgICAgdmFyIF9hO1xuICAgICAgICBpZiAoIXRoaXMudGFyZ2V0c0J5TmFtZS5oYXMobmFtZSwgZWxlbWVudCkpIHtcbiAgICAgICAgICAgIHRoaXMudGFyZ2V0c0J5TmFtZS5hZGQobmFtZSwgZWxlbWVudCk7XG4gICAgICAgICAgICAoX2EgPSB0aGlzLnRva2VuTGlzdE9ic2VydmVyKSA9PT0gbnVsbCB8fCBfYSA9PT0gdm9pZCAwID8gdm9pZCAwIDogX2EucGF1c2UoKCkgPT4gdGhpcy5kZWxlZ2F0ZS50YXJnZXRDb25uZWN0ZWQoZWxlbWVudCwgbmFtZSkpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGRpc2Nvbm5lY3RUYXJnZXQoZWxlbWVudCwgbmFtZSkge1xuICAgICAgICB2YXIgX2E7XG4gICAgICAgIGlmICh0aGlzLnRhcmdldHNCeU5hbWUuaGFzKG5hbWUsIGVsZW1lbnQpKSB7XG4gICAgICAgICAgICB0aGlzLnRhcmdldHNCeU5hbWUuZGVsZXRlKG5hbWUsIGVsZW1lbnQpO1xuICAgICAgICAgICAgKF9hID0gdGhpcy50b2tlbkxpc3RPYnNlcnZlcikgPT09IG51bGwgfHwgX2EgPT09IHZvaWQgMCA/IHZvaWQgMCA6IF9hLnBhdXNlKCgpID0+IHRoaXMuZGVsZWdhdGUudGFyZ2V0RGlzY29ubmVjdGVkKGVsZW1lbnQsIG5hbWUpKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBkaXNjb25uZWN0QWxsVGFyZ2V0cygpIHtcbiAgICAgICAgZm9yIChjb25zdCBuYW1lIG9mIHRoaXMudGFyZ2V0c0J5TmFtZS5rZXlzKSB7XG4gICAgICAgICAgICBmb3IgKGNvbnN0IGVsZW1lbnQgb2YgdGhpcy50YXJnZXRzQnlOYW1lLmdldFZhbHVlc0ZvcktleShuYW1lKSkge1xuICAgICAgICAgICAgICAgIHRoaXMuZGlzY29ubmVjdFRhcmdldChlbGVtZW50LCBuYW1lKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXQgYXR0cmlidXRlTmFtZSgpIHtcbiAgICAgICAgcmV0dXJuIGBkYXRhLSR7dGhpcy5jb250ZXh0LmlkZW50aWZpZXJ9LXRhcmdldGA7XG4gICAgfVxuICAgIGdldCBlbGVtZW50KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LmVsZW1lbnQ7XG4gICAgfVxuICAgIGdldCBzY29wZSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuY29udGV4dC5zY29wZTtcbiAgICB9XG59XG5cbmZ1bmN0aW9uIHJlYWRJbmhlcml0YWJsZVN0YXRpY0FycmF5VmFsdWVzKGNvbnN0cnVjdG9yLCBwcm9wZXJ0eU5hbWUpIHtcbiAgICBjb25zdCBhbmNlc3RvcnMgPSBnZXRBbmNlc3RvcnNGb3JDb25zdHJ1Y3Rvcihjb25zdHJ1Y3Rvcik7XG4gICAgcmV0dXJuIEFycmF5LmZyb20oYW5jZXN0b3JzLnJlZHVjZSgodmFsdWVzLCBjb25zdHJ1Y3RvcikgPT4ge1xuICAgICAgICBnZXRPd25TdGF0aWNBcnJheVZhbHVlcyhjb25zdHJ1Y3RvciwgcHJvcGVydHlOYW1lKS5mb3JFYWNoKChuYW1lKSA9PiB2YWx1ZXMuYWRkKG5hbWUpKTtcbiAgICAgICAgcmV0dXJuIHZhbHVlcztcbiAgICB9LCBuZXcgU2V0KCkpKTtcbn1cbmZ1bmN0aW9uIHJlYWRJbmhlcml0YWJsZVN0YXRpY09iamVjdFBhaXJzKGNvbnN0cnVjdG9yLCBwcm9wZXJ0eU5hbWUpIHtcbiAgICBjb25zdCBhbmNlc3RvcnMgPSBnZXRBbmNlc3RvcnNGb3JDb25zdHJ1Y3Rvcihjb25zdHJ1Y3Rvcik7XG4gICAgcmV0dXJuIGFuY2VzdG9ycy5yZWR1Y2UoKHBhaXJzLCBjb25zdHJ1Y3RvcikgPT4ge1xuICAgICAgICBwYWlycy5wdXNoKC4uLmdldE93blN0YXRpY09iamVjdFBhaXJzKGNvbnN0cnVjdG9yLCBwcm9wZXJ0eU5hbWUpKTtcbiAgICAgICAgcmV0dXJuIHBhaXJzO1xuICAgIH0sIFtdKTtcbn1cbmZ1bmN0aW9uIGdldEFuY2VzdG9yc0ZvckNvbnN0cnVjdG9yKGNvbnN0cnVjdG9yKSB7XG4gICAgY29uc3QgYW5jZXN0b3JzID0gW107XG4gICAgd2hpbGUgKGNvbnN0cnVjdG9yKSB7XG4gICAgICAgIGFuY2VzdG9ycy5wdXNoKGNvbnN0cnVjdG9yKTtcbiAgICAgICAgY29uc3RydWN0b3IgPSBPYmplY3QuZ2V0UHJvdG90eXBlT2YoY29uc3RydWN0b3IpO1xuICAgIH1cbiAgICByZXR1cm4gYW5jZXN0b3JzLnJldmVyc2UoKTtcbn1cbmZ1bmN0aW9uIGdldE93blN0YXRpY0FycmF5VmFsdWVzKGNvbnN0cnVjdG9yLCBwcm9wZXJ0eU5hbWUpIHtcbiAgICBjb25zdCBkZWZpbml0aW9uID0gY29uc3RydWN0b3JbcHJvcGVydHlOYW1lXTtcbiAgICByZXR1cm4gQXJyYXkuaXNBcnJheShkZWZpbml0aW9uKSA/IGRlZmluaXRpb24gOiBbXTtcbn1cbmZ1bmN0aW9uIGdldE93blN0YXRpY09iamVjdFBhaXJzKGNvbnN0cnVjdG9yLCBwcm9wZXJ0eU5hbWUpIHtcbiAgICBjb25zdCBkZWZpbml0aW9uID0gY29uc3RydWN0b3JbcHJvcGVydHlOYW1lXTtcbiAgICByZXR1cm4gZGVmaW5pdGlvbiA/IE9iamVjdC5rZXlzKGRlZmluaXRpb24pLm1hcCgoa2V5KSA9PiBba2V5LCBkZWZpbml0aW9uW2tleV1dKSA6IFtdO1xufVxuXG5jbGFzcyBPdXRsZXRPYnNlcnZlciB7XG4gICAgY29uc3RydWN0b3IoY29udGV4dCwgZGVsZWdhdGUpIHtcbiAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgIHRoaXMuY29udGV4dCA9IGNvbnRleHQ7XG4gICAgICAgIHRoaXMuZGVsZWdhdGUgPSBkZWxlZ2F0ZTtcbiAgICAgICAgdGhpcy5vdXRsZXRzQnlOYW1lID0gbmV3IE11bHRpbWFwKCk7XG4gICAgICAgIHRoaXMub3V0bGV0RWxlbWVudHNCeU5hbWUgPSBuZXcgTXVsdGltYXAoKTtcbiAgICAgICAgdGhpcy5zZWxlY3Rvck9ic2VydmVyTWFwID0gbmV3IE1hcCgpO1xuICAgICAgICB0aGlzLmF0dHJpYnV0ZU9ic2VydmVyTWFwID0gbmV3IE1hcCgpO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgaWYgKCF0aGlzLnN0YXJ0ZWQpIHtcbiAgICAgICAgICAgIHRoaXMub3V0bGV0RGVmaW5pdGlvbnMuZm9yRWFjaCgob3V0bGV0TmFtZSkgPT4ge1xuICAgICAgICAgICAgICAgIHRoaXMuc2V0dXBTZWxlY3Rvck9ic2VydmVyRm9yT3V0bGV0KG91dGxldE5hbWUpO1xuICAgICAgICAgICAgICAgIHRoaXMuc2V0dXBBdHRyaWJ1dGVPYnNlcnZlckZvck91dGxldChvdXRsZXROYW1lKTtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gdHJ1ZTtcbiAgICAgICAgICAgIHRoaXMuZGVwZW5kZW50Q29udGV4dHMuZm9yRWFjaCgoY29udGV4dCkgPT4gY29udGV4dC5yZWZyZXNoKCkpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHJlZnJlc2goKSB7XG4gICAgICAgIHRoaXMuc2VsZWN0b3JPYnNlcnZlck1hcC5mb3JFYWNoKChvYnNlcnZlcikgPT4gb2JzZXJ2ZXIucmVmcmVzaCgpKTtcbiAgICAgICAgdGhpcy5hdHRyaWJ1dGVPYnNlcnZlck1hcC5mb3JFYWNoKChvYnNlcnZlcikgPT4gb2JzZXJ2ZXIucmVmcmVzaCgpKTtcbiAgICB9XG4gICAgc3RvcCgpIHtcbiAgICAgICAgaWYgKHRoaXMuc3RhcnRlZCkge1xuICAgICAgICAgICAgdGhpcy5zdGFydGVkID0gZmFsc2U7XG4gICAgICAgICAgICB0aGlzLmRpc2Nvbm5lY3RBbGxPdXRsZXRzKCk7XG4gICAgICAgICAgICB0aGlzLnN0b3BTZWxlY3Rvck9ic2VydmVycygpO1xuICAgICAgICAgICAgdGhpcy5zdG9wQXR0cmlidXRlT2JzZXJ2ZXJzKCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc3RvcFNlbGVjdG9yT2JzZXJ2ZXJzKCkge1xuICAgICAgICBpZiAodGhpcy5zZWxlY3Rvck9ic2VydmVyTWFwLnNpemUgPiAwKSB7XG4gICAgICAgICAgICB0aGlzLnNlbGVjdG9yT2JzZXJ2ZXJNYXAuZm9yRWFjaCgob2JzZXJ2ZXIpID0+IG9ic2VydmVyLnN0b3AoKSk7XG4gICAgICAgICAgICB0aGlzLnNlbGVjdG9yT2JzZXJ2ZXJNYXAuY2xlYXIoKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzdG9wQXR0cmlidXRlT2JzZXJ2ZXJzKCkge1xuICAgICAgICBpZiAodGhpcy5hdHRyaWJ1dGVPYnNlcnZlck1hcC5zaXplID4gMCkge1xuICAgICAgICAgICAgdGhpcy5hdHRyaWJ1dGVPYnNlcnZlck1hcC5mb3JFYWNoKChvYnNlcnZlcikgPT4gb2JzZXJ2ZXIuc3RvcCgpKTtcbiAgICAgICAgICAgIHRoaXMuYXR0cmlidXRlT2JzZXJ2ZXJNYXAuY2xlYXIoKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBzZWxlY3Rvck1hdGNoZWQoZWxlbWVudCwgX3NlbGVjdG9yLCB7IG91dGxldE5hbWUgfSkge1xuICAgICAgICBjb25zdCBvdXRsZXQgPSB0aGlzLmdldE91dGxldChlbGVtZW50LCBvdXRsZXROYW1lKTtcbiAgICAgICAgaWYgKG91dGxldCkge1xuICAgICAgICAgICAgdGhpcy5jb25uZWN0T3V0bGV0KG91dGxldCwgZWxlbWVudCwgb3V0bGV0TmFtZSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc2VsZWN0b3JVbm1hdGNoZWQoZWxlbWVudCwgX3NlbGVjdG9yLCB7IG91dGxldE5hbWUgfSkge1xuICAgICAgICBjb25zdCBvdXRsZXQgPSB0aGlzLmdldE91dGxldEZyb21NYXAoZWxlbWVudCwgb3V0bGV0TmFtZSk7XG4gICAgICAgIGlmIChvdXRsZXQpIHtcbiAgICAgICAgICAgIHRoaXMuZGlzY29ubmVjdE91dGxldChvdXRsZXQsIGVsZW1lbnQsIG91dGxldE5hbWUpO1xuICAgICAgICB9XG4gICAgfVxuICAgIHNlbGVjdG9yTWF0Y2hFbGVtZW50KGVsZW1lbnQsIHsgb3V0bGV0TmFtZSB9KSB7XG4gICAgICAgIGNvbnN0IHNlbGVjdG9yID0gdGhpcy5zZWxlY3RvcihvdXRsZXROYW1lKTtcbiAgICAgICAgY29uc3QgaGFzT3V0bGV0ID0gdGhpcy5oYXNPdXRsZXQoZWxlbWVudCwgb3V0bGV0TmFtZSk7XG4gICAgICAgIGNvbnN0IGhhc091dGxldENvbnRyb2xsZXIgPSBlbGVtZW50Lm1hdGNoZXMoYFske3RoaXMuc2NoZW1hLmNvbnRyb2xsZXJBdHRyaWJ1dGV9fj0ke291dGxldE5hbWV9XWApO1xuICAgICAgICBpZiAoc2VsZWN0b3IpIHtcbiAgICAgICAgICAgIHJldHVybiBoYXNPdXRsZXQgJiYgaGFzT3V0bGV0Q29udHJvbGxlciAmJiBlbGVtZW50Lm1hdGNoZXMoc2VsZWN0b3IpO1xuICAgICAgICB9XG4gICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICB9XG4gICAgfVxuICAgIGVsZW1lbnRNYXRjaGVkQXR0cmlidXRlKF9lbGVtZW50LCBhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgIGNvbnN0IG91dGxldE5hbWUgPSB0aGlzLmdldE91dGxldE5hbWVGcm9tT3V0bGV0QXR0cmlidXRlTmFtZShhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgaWYgKG91dGxldE5hbWUpIHtcbiAgICAgICAgICAgIHRoaXMudXBkYXRlU2VsZWN0b3JPYnNlcnZlckZvck91dGxldChvdXRsZXROYW1lKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBlbGVtZW50QXR0cmlidXRlVmFsdWVDaGFuZ2VkKF9lbGVtZW50LCBhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgIGNvbnN0IG91dGxldE5hbWUgPSB0aGlzLmdldE91dGxldE5hbWVGcm9tT3V0bGV0QXR0cmlidXRlTmFtZShhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgaWYgKG91dGxldE5hbWUpIHtcbiAgICAgICAgICAgIHRoaXMudXBkYXRlU2VsZWN0b3JPYnNlcnZlckZvck91dGxldChvdXRsZXROYW1lKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBlbGVtZW50VW5tYXRjaGVkQXR0cmlidXRlKF9lbGVtZW50LCBhdHRyaWJ1dGVOYW1lKSB7XG4gICAgICAgIGNvbnN0IG91dGxldE5hbWUgPSB0aGlzLmdldE91dGxldE5hbWVGcm9tT3V0bGV0QXR0cmlidXRlTmFtZShhdHRyaWJ1dGVOYW1lKTtcbiAgICAgICAgaWYgKG91dGxldE5hbWUpIHtcbiAgICAgICAgICAgIHRoaXMudXBkYXRlU2VsZWN0b3JPYnNlcnZlckZvck91dGxldChvdXRsZXROYW1lKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBjb25uZWN0T3V0bGV0KG91dGxldCwgZWxlbWVudCwgb3V0bGV0TmFtZSkge1xuICAgICAgICB2YXIgX2E7XG4gICAgICAgIGlmICghdGhpcy5vdXRsZXRFbGVtZW50c0J5TmFtZS5oYXMob3V0bGV0TmFtZSwgZWxlbWVudCkpIHtcbiAgICAgICAgICAgIHRoaXMub3V0bGV0c0J5TmFtZS5hZGQob3V0bGV0TmFtZSwgb3V0bGV0KTtcbiAgICAgICAgICAgIHRoaXMub3V0bGV0RWxlbWVudHNCeU5hbWUuYWRkKG91dGxldE5hbWUsIGVsZW1lbnQpO1xuICAgICAgICAgICAgKF9hID0gdGhpcy5zZWxlY3Rvck9ic2VydmVyTWFwLmdldChvdXRsZXROYW1lKSkgPT09IG51bGwgfHwgX2EgPT09IHZvaWQgMCA/IHZvaWQgMCA6IF9hLnBhdXNlKCgpID0+IHRoaXMuZGVsZWdhdGUub3V0bGV0Q29ubmVjdGVkKG91dGxldCwgZWxlbWVudCwgb3V0bGV0TmFtZSkpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGRpc2Nvbm5lY3RPdXRsZXQob3V0bGV0LCBlbGVtZW50LCBvdXRsZXROYW1lKSB7XG4gICAgICAgIHZhciBfYTtcbiAgICAgICAgaWYgKHRoaXMub3V0bGV0RWxlbWVudHNCeU5hbWUuaGFzKG91dGxldE5hbWUsIGVsZW1lbnQpKSB7XG4gICAgICAgICAgICB0aGlzLm91dGxldHNCeU5hbWUuZGVsZXRlKG91dGxldE5hbWUsIG91dGxldCk7XG4gICAgICAgICAgICB0aGlzLm91dGxldEVsZW1lbnRzQnlOYW1lLmRlbGV0ZShvdXRsZXROYW1lLCBlbGVtZW50KTtcbiAgICAgICAgICAgIChfYSA9IHRoaXMuc2VsZWN0b3JPYnNlcnZlck1hcFxuICAgICAgICAgICAgICAgIC5nZXQob3V0bGV0TmFtZSkpID09PSBudWxsIHx8IF9hID09PSB2b2lkIDAgPyB2b2lkIDAgOiBfYS5wYXVzZSgoKSA9PiB0aGlzLmRlbGVnYXRlLm91dGxldERpc2Nvbm5lY3RlZChvdXRsZXQsIGVsZW1lbnQsIG91dGxldE5hbWUpKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBkaXNjb25uZWN0QWxsT3V0bGV0cygpIHtcbiAgICAgICAgZm9yIChjb25zdCBvdXRsZXROYW1lIG9mIHRoaXMub3V0bGV0RWxlbWVudHNCeU5hbWUua2V5cykge1xuICAgICAgICAgICAgZm9yIChjb25zdCBlbGVtZW50IG9mIHRoaXMub3V0bGV0RWxlbWVudHNCeU5hbWUuZ2V0VmFsdWVzRm9yS2V5KG91dGxldE5hbWUpKSB7XG4gICAgICAgICAgICAgICAgZm9yIChjb25zdCBvdXRsZXQgb2YgdGhpcy5vdXRsZXRzQnlOYW1lLmdldFZhbHVlc0ZvcktleShvdXRsZXROYW1lKSkge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmRpc2Nvbm5lY3RPdXRsZXQob3V0bGV0LCBlbGVtZW50LCBvdXRsZXROYW1lKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9XG4gICAgdXBkYXRlU2VsZWN0b3JPYnNlcnZlckZvck91dGxldChvdXRsZXROYW1lKSB7XG4gICAgICAgIGNvbnN0IG9ic2VydmVyID0gdGhpcy5zZWxlY3Rvck9ic2VydmVyTWFwLmdldChvdXRsZXROYW1lKTtcbiAgICAgICAgaWYgKG9ic2VydmVyKSB7XG4gICAgICAgICAgICBvYnNlcnZlci5zZWxlY3RvciA9IHRoaXMuc2VsZWN0b3Iob3V0bGV0TmFtZSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc2V0dXBTZWxlY3Rvck9ic2VydmVyRm9yT3V0bGV0KG91dGxldE5hbWUpIHtcbiAgICAgICAgY29uc3Qgc2VsZWN0b3IgPSB0aGlzLnNlbGVjdG9yKG91dGxldE5hbWUpO1xuICAgICAgICBjb25zdCBzZWxlY3Rvck9ic2VydmVyID0gbmV3IFNlbGVjdG9yT2JzZXJ2ZXIoZG9jdW1lbnQuYm9keSwgc2VsZWN0b3IsIHRoaXMsIHsgb3V0bGV0TmFtZSB9KTtcbiAgICAgICAgdGhpcy5zZWxlY3Rvck9ic2VydmVyTWFwLnNldChvdXRsZXROYW1lLCBzZWxlY3Rvck9ic2VydmVyKTtcbiAgICAgICAgc2VsZWN0b3JPYnNlcnZlci5zdGFydCgpO1xuICAgIH1cbiAgICBzZXR1cEF0dHJpYnV0ZU9ic2VydmVyRm9yT3V0bGV0KG91dGxldE5hbWUpIHtcbiAgICAgICAgY29uc3QgYXR0cmlidXRlTmFtZSA9IHRoaXMuYXR0cmlidXRlTmFtZUZvck91dGxldE5hbWUob3V0bGV0TmFtZSk7XG4gICAgICAgIGNvbnN0IGF0dHJpYnV0ZU9ic2VydmVyID0gbmV3IEF0dHJpYnV0ZU9ic2VydmVyKHRoaXMuc2NvcGUuZWxlbWVudCwgYXR0cmlidXRlTmFtZSwgdGhpcyk7XG4gICAgICAgIHRoaXMuYXR0cmlidXRlT2JzZXJ2ZXJNYXAuc2V0KG91dGxldE5hbWUsIGF0dHJpYnV0ZU9ic2VydmVyKTtcbiAgICAgICAgYXR0cmlidXRlT2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICB9XG4gICAgc2VsZWN0b3Iob3V0bGV0TmFtZSkge1xuICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5vdXRsZXRzLmdldFNlbGVjdG9yRm9yT3V0bGV0TmFtZShvdXRsZXROYW1lKTtcbiAgICB9XG4gICAgYXR0cmlidXRlTmFtZUZvck91dGxldE5hbWUob3V0bGV0TmFtZSkge1xuICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5zY2hlbWEub3V0bGV0QXR0cmlidXRlRm9yU2NvcGUodGhpcy5pZGVudGlmaWVyLCBvdXRsZXROYW1lKTtcbiAgICB9XG4gICAgZ2V0T3V0bGV0TmFtZUZyb21PdXRsZXRBdHRyaWJ1dGVOYW1lKGF0dHJpYnV0ZU5hbWUpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMub3V0bGV0RGVmaW5pdGlvbnMuZmluZCgob3V0bGV0TmFtZSkgPT4gdGhpcy5hdHRyaWJ1dGVOYW1lRm9yT3V0bGV0TmFtZShvdXRsZXROYW1lKSA9PT0gYXR0cmlidXRlTmFtZSk7XG4gICAgfVxuICAgIGdldCBvdXRsZXREZXBlbmRlbmNpZXMoKSB7XG4gICAgICAgIGNvbnN0IGRlcGVuZGVuY2llcyA9IG5ldyBNdWx0aW1hcCgpO1xuICAgICAgICB0aGlzLnJvdXRlci5tb2R1bGVzLmZvckVhY2goKG1vZHVsZSkgPT4ge1xuICAgICAgICAgICAgY29uc3QgY29uc3RydWN0b3IgPSBtb2R1bGUuZGVmaW5pdGlvbi5jb250cm9sbGVyQ29uc3RydWN0b3I7XG4gICAgICAgICAgICBjb25zdCBvdXRsZXRzID0gcmVhZEluaGVyaXRhYmxlU3RhdGljQXJyYXlWYWx1ZXMoY29uc3RydWN0b3IsIFwib3V0bGV0c1wiKTtcbiAgICAgICAgICAgIG91dGxldHMuZm9yRWFjaCgob3V0bGV0KSA9PiBkZXBlbmRlbmNpZXMuYWRkKG91dGxldCwgbW9kdWxlLmlkZW50aWZpZXIpKTtcbiAgICAgICAgfSk7XG4gICAgICAgIHJldHVybiBkZXBlbmRlbmNpZXM7XG4gICAgfVxuICAgIGdldCBvdXRsZXREZWZpbml0aW9ucygpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMub3V0bGV0RGVwZW5kZW5jaWVzLmdldEtleXNGb3JWYWx1ZSh0aGlzLmlkZW50aWZpZXIpO1xuICAgIH1cbiAgICBnZXQgZGVwZW5kZW50Q29udHJvbGxlcklkZW50aWZpZXJzKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5vdXRsZXREZXBlbmRlbmNpZXMuZ2V0VmFsdWVzRm9yS2V5KHRoaXMuaWRlbnRpZmllcik7XG4gICAgfVxuICAgIGdldCBkZXBlbmRlbnRDb250ZXh0cygpIHtcbiAgICAgICAgY29uc3QgaWRlbnRpZmllcnMgPSB0aGlzLmRlcGVuZGVudENvbnRyb2xsZXJJZGVudGlmaWVycztcbiAgICAgICAgcmV0dXJuIHRoaXMucm91dGVyLmNvbnRleHRzLmZpbHRlcigoY29udGV4dCkgPT4gaWRlbnRpZmllcnMuaW5jbHVkZXMoY29udGV4dC5pZGVudGlmaWVyKSk7XG4gICAgfVxuICAgIGhhc091dGxldChlbGVtZW50LCBvdXRsZXROYW1lKSB7XG4gICAgICAgIHJldHVybiAhIXRoaXMuZ2V0T3V0bGV0KGVsZW1lbnQsIG91dGxldE5hbWUpIHx8ICEhdGhpcy5nZXRPdXRsZXRGcm9tTWFwKGVsZW1lbnQsIG91dGxldE5hbWUpO1xuICAgIH1cbiAgICBnZXRPdXRsZXQoZWxlbWVudCwgb3V0bGV0TmFtZSkge1xuICAgICAgICByZXR1cm4gdGhpcy5hcHBsaWNhdGlvbi5nZXRDb250cm9sbGVyRm9yRWxlbWVudEFuZElkZW50aWZpZXIoZWxlbWVudCwgb3V0bGV0TmFtZSk7XG4gICAgfVxuICAgIGdldE91dGxldEZyb21NYXAoZWxlbWVudCwgb3V0bGV0TmFtZSkge1xuICAgICAgICByZXR1cm4gdGhpcy5vdXRsZXRzQnlOYW1lLmdldFZhbHVlc0ZvcktleShvdXRsZXROYW1lKS5maW5kKChvdXRsZXQpID0+IG91dGxldC5lbGVtZW50ID09PSBlbGVtZW50KTtcbiAgICB9XG4gICAgZ2V0IHNjb3BlKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LnNjb3BlO1xuICAgIH1cbiAgICBnZXQgc2NoZW1hKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LnNjaGVtYTtcbiAgICB9XG4gICAgZ2V0IGlkZW50aWZpZXIoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuaWRlbnRpZmllcjtcbiAgICB9XG4gICAgZ2V0IGFwcGxpY2F0aW9uKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LmFwcGxpY2F0aW9uO1xuICAgIH1cbiAgICBnZXQgcm91dGVyKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5hcHBsaWNhdGlvbi5yb3V0ZXI7XG4gICAgfVxufVxuXG5jbGFzcyBDb250ZXh0IHtcbiAgICBjb25zdHJ1Y3Rvcihtb2R1bGUsIHNjb3BlKSB7XG4gICAgICAgIHRoaXMubG9nRGVidWdBY3Rpdml0eSA9IChmdW5jdGlvbk5hbWUsIGRldGFpbCA9IHt9KSA9PiB7XG4gICAgICAgICAgICBjb25zdCB7IGlkZW50aWZpZXIsIGNvbnRyb2xsZXIsIGVsZW1lbnQgfSA9IHRoaXM7XG4gICAgICAgICAgICBkZXRhaWwgPSBPYmplY3QuYXNzaWduKHsgaWRlbnRpZmllciwgY29udHJvbGxlciwgZWxlbWVudCB9LCBkZXRhaWwpO1xuICAgICAgICAgICAgdGhpcy5hcHBsaWNhdGlvbi5sb2dEZWJ1Z0FjdGl2aXR5KHRoaXMuaWRlbnRpZmllciwgZnVuY3Rpb25OYW1lLCBkZXRhaWwpO1xuICAgICAgICB9O1xuICAgICAgICB0aGlzLm1vZHVsZSA9IG1vZHVsZTtcbiAgICAgICAgdGhpcy5zY29wZSA9IHNjb3BlO1xuICAgICAgICB0aGlzLmNvbnRyb2xsZXIgPSBuZXcgbW9kdWxlLmNvbnRyb2xsZXJDb25zdHJ1Y3Rvcih0aGlzKTtcbiAgICAgICAgdGhpcy5iaW5kaW5nT2JzZXJ2ZXIgPSBuZXcgQmluZGluZ09ic2VydmVyKHRoaXMsIHRoaXMuZGlzcGF0Y2hlcik7XG4gICAgICAgIHRoaXMudmFsdWVPYnNlcnZlciA9IG5ldyBWYWx1ZU9ic2VydmVyKHRoaXMsIHRoaXMuY29udHJvbGxlcik7XG4gICAgICAgIHRoaXMudGFyZ2V0T2JzZXJ2ZXIgPSBuZXcgVGFyZ2V0T2JzZXJ2ZXIodGhpcywgdGhpcyk7XG4gICAgICAgIHRoaXMub3V0bGV0T2JzZXJ2ZXIgPSBuZXcgT3V0bGV0T2JzZXJ2ZXIodGhpcywgdGhpcyk7XG4gICAgICAgIHRyeSB7XG4gICAgICAgICAgICB0aGlzLmNvbnRyb2xsZXIuaW5pdGlhbGl6ZSgpO1xuICAgICAgICAgICAgdGhpcy5sb2dEZWJ1Z0FjdGl2aXR5KFwiaW5pdGlhbGl6ZVwiKTtcbiAgICAgICAgfVxuICAgICAgICBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgIHRoaXMuaGFuZGxlRXJyb3IoZXJyb3IsIFwiaW5pdGlhbGl6aW5nIGNvbnRyb2xsZXJcIik7XG4gICAgICAgIH1cbiAgICB9XG4gICAgY29ubmVjdCgpIHtcbiAgICAgICAgdGhpcy5iaW5kaW5nT2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICAgICAgdGhpcy52YWx1ZU9ic2VydmVyLnN0YXJ0KCk7XG4gICAgICAgIHRoaXMudGFyZ2V0T2JzZXJ2ZXIuc3RhcnQoKTtcbiAgICAgICAgdGhpcy5vdXRsZXRPYnNlcnZlci5zdGFydCgpO1xuICAgICAgICB0cnkge1xuICAgICAgICAgICAgdGhpcy5jb250cm9sbGVyLmNvbm5lY3QoKTtcbiAgICAgICAgICAgIHRoaXMubG9nRGVidWdBY3Rpdml0eShcImNvbm5lY3RcIik7XG4gICAgICAgIH1cbiAgICAgICAgY2F0Y2ggKGVycm9yKSB7XG4gICAgICAgICAgICB0aGlzLmhhbmRsZUVycm9yKGVycm9yLCBcImNvbm5lY3RpbmcgY29udHJvbGxlclwiKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICByZWZyZXNoKCkge1xuICAgICAgICB0aGlzLm91dGxldE9ic2VydmVyLnJlZnJlc2goKTtcbiAgICB9XG4gICAgZGlzY29ubmVjdCgpIHtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIHRoaXMuY29udHJvbGxlci5kaXNjb25uZWN0KCk7XG4gICAgICAgICAgICB0aGlzLmxvZ0RlYnVnQWN0aXZpdHkoXCJkaXNjb25uZWN0XCIpO1xuICAgICAgICB9XG4gICAgICAgIGNhdGNoIChlcnJvcikge1xuICAgICAgICAgICAgdGhpcy5oYW5kbGVFcnJvcihlcnJvciwgXCJkaXNjb25uZWN0aW5nIGNvbnRyb2xsZXJcIik7XG4gICAgICAgIH1cbiAgICAgICAgdGhpcy5vdXRsZXRPYnNlcnZlci5zdG9wKCk7XG4gICAgICAgIHRoaXMudGFyZ2V0T2JzZXJ2ZXIuc3RvcCgpO1xuICAgICAgICB0aGlzLnZhbHVlT2JzZXJ2ZXIuc3RvcCgpO1xuICAgICAgICB0aGlzLmJpbmRpbmdPYnNlcnZlci5zdG9wKCk7XG4gICAgfVxuICAgIGdldCBhcHBsaWNhdGlvbigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMubW9kdWxlLmFwcGxpY2F0aW9uO1xuICAgIH1cbiAgICBnZXQgaWRlbnRpZmllcigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMubW9kdWxlLmlkZW50aWZpZXI7XG4gICAgfVxuICAgIGdldCBzY2hlbWEoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmFwcGxpY2F0aW9uLnNjaGVtYTtcbiAgICB9XG4gICAgZ2V0IGRpc3BhdGNoZXIoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmFwcGxpY2F0aW9uLmRpc3BhdGNoZXI7XG4gICAgfVxuICAgIGdldCBlbGVtZW50KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5lbGVtZW50O1xuICAgIH1cbiAgICBnZXQgcGFyZW50RWxlbWVudCgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZWxlbWVudC5wYXJlbnRFbGVtZW50O1xuICAgIH1cbiAgICBoYW5kbGVFcnJvcihlcnJvciwgbWVzc2FnZSwgZGV0YWlsID0ge30pIHtcbiAgICAgICAgY29uc3QgeyBpZGVudGlmaWVyLCBjb250cm9sbGVyLCBlbGVtZW50IH0gPSB0aGlzO1xuICAgICAgICBkZXRhaWwgPSBPYmplY3QuYXNzaWduKHsgaWRlbnRpZmllciwgY29udHJvbGxlciwgZWxlbWVudCB9LCBkZXRhaWwpO1xuICAgICAgICB0aGlzLmFwcGxpY2F0aW9uLmhhbmRsZUVycm9yKGVycm9yLCBgRXJyb3IgJHttZXNzYWdlfWAsIGRldGFpbCk7XG4gICAgfVxuICAgIHRhcmdldENvbm5lY3RlZChlbGVtZW50LCBuYW1lKSB7XG4gICAgICAgIHRoaXMuaW52b2tlQ29udHJvbGxlck1ldGhvZChgJHtuYW1lfVRhcmdldENvbm5lY3RlZGAsIGVsZW1lbnQpO1xuICAgIH1cbiAgICB0YXJnZXREaXNjb25uZWN0ZWQoZWxlbWVudCwgbmFtZSkge1xuICAgICAgICB0aGlzLmludm9rZUNvbnRyb2xsZXJNZXRob2QoYCR7bmFtZX1UYXJnZXREaXNjb25uZWN0ZWRgLCBlbGVtZW50KTtcbiAgICB9XG4gICAgb3V0bGV0Q29ubmVjdGVkKG91dGxldCwgZWxlbWVudCwgbmFtZSkge1xuICAgICAgICB0aGlzLmludm9rZUNvbnRyb2xsZXJNZXRob2QoYCR7bmFtZXNwYWNlQ2FtZWxpemUobmFtZSl9T3V0bGV0Q29ubmVjdGVkYCwgb3V0bGV0LCBlbGVtZW50KTtcbiAgICB9XG4gICAgb3V0bGV0RGlzY29ubmVjdGVkKG91dGxldCwgZWxlbWVudCwgbmFtZSkge1xuICAgICAgICB0aGlzLmludm9rZUNvbnRyb2xsZXJNZXRob2QoYCR7bmFtZXNwYWNlQ2FtZWxpemUobmFtZSl9T3V0bGV0RGlzY29ubmVjdGVkYCwgb3V0bGV0LCBlbGVtZW50KTtcbiAgICB9XG4gICAgaW52b2tlQ29udHJvbGxlck1ldGhvZChtZXRob2ROYW1lLCAuLi5hcmdzKSB7XG4gICAgICAgIGNvbnN0IGNvbnRyb2xsZXIgPSB0aGlzLmNvbnRyb2xsZXI7XG4gICAgICAgIGlmICh0eXBlb2YgY29udHJvbGxlclttZXRob2ROYW1lXSA9PSBcImZ1bmN0aW9uXCIpIHtcbiAgICAgICAgICAgIGNvbnRyb2xsZXJbbWV0aG9kTmFtZV0oLi4uYXJncyk7XG4gICAgICAgIH1cbiAgICB9XG59XG5cbmZ1bmN0aW9uIGJsZXNzKGNvbnN0cnVjdG9yKSB7XG4gICAgcmV0dXJuIHNoYWRvdyhjb25zdHJ1Y3RvciwgZ2V0Qmxlc3NlZFByb3BlcnRpZXMoY29uc3RydWN0b3IpKTtcbn1cbmZ1bmN0aW9uIHNoYWRvdyhjb25zdHJ1Y3RvciwgcHJvcGVydGllcykge1xuICAgIGNvbnN0IHNoYWRvd0NvbnN0cnVjdG9yID0gZXh0ZW5kKGNvbnN0cnVjdG9yKTtcbiAgICBjb25zdCBzaGFkb3dQcm9wZXJ0aWVzID0gZ2V0U2hhZG93UHJvcGVydGllcyhjb25zdHJ1Y3Rvci5wcm90b3R5cGUsIHByb3BlcnRpZXMpO1xuICAgIE9iamVjdC5kZWZpbmVQcm9wZXJ0aWVzKHNoYWRvd0NvbnN0cnVjdG9yLnByb3RvdHlwZSwgc2hhZG93UHJvcGVydGllcyk7XG4gICAgcmV0dXJuIHNoYWRvd0NvbnN0cnVjdG9yO1xufVxuZnVuY3Rpb24gZ2V0Qmxlc3NlZFByb3BlcnRpZXMoY29uc3RydWN0b3IpIHtcbiAgICBjb25zdCBibGVzc2luZ3MgPSByZWFkSW5oZXJpdGFibGVTdGF0aWNBcnJheVZhbHVlcyhjb25zdHJ1Y3RvciwgXCJibGVzc2luZ3NcIik7XG4gICAgcmV0dXJuIGJsZXNzaW5ncy5yZWR1Y2UoKGJsZXNzZWRQcm9wZXJ0aWVzLCBibGVzc2luZykgPT4ge1xuICAgICAgICBjb25zdCBwcm9wZXJ0aWVzID0gYmxlc3NpbmcoY29uc3RydWN0b3IpO1xuICAgICAgICBmb3IgKGNvbnN0IGtleSBpbiBwcm9wZXJ0aWVzKSB7XG4gICAgICAgICAgICBjb25zdCBkZXNjcmlwdG9yID0gYmxlc3NlZFByb3BlcnRpZXNba2V5XSB8fCB7fTtcbiAgICAgICAgICAgIGJsZXNzZWRQcm9wZXJ0aWVzW2tleV0gPSBPYmplY3QuYXNzaWduKGRlc2NyaXB0b3IsIHByb3BlcnRpZXNba2V5XSk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGJsZXNzZWRQcm9wZXJ0aWVzO1xuICAgIH0sIHt9KTtcbn1cbmZ1bmN0aW9uIGdldFNoYWRvd1Byb3BlcnRpZXMocHJvdG90eXBlLCBwcm9wZXJ0aWVzKSB7XG4gICAgcmV0dXJuIGdldE93bktleXMocHJvcGVydGllcykucmVkdWNlKChzaGFkb3dQcm9wZXJ0aWVzLCBrZXkpID0+IHtcbiAgICAgICAgY29uc3QgZGVzY3JpcHRvciA9IGdldFNoYWRvd2VkRGVzY3JpcHRvcihwcm90b3R5cGUsIHByb3BlcnRpZXMsIGtleSk7XG4gICAgICAgIGlmIChkZXNjcmlwdG9yKSB7XG4gICAgICAgICAgICBPYmplY3QuYXNzaWduKHNoYWRvd1Byb3BlcnRpZXMsIHsgW2tleV06IGRlc2NyaXB0b3IgfSk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIHNoYWRvd1Byb3BlcnRpZXM7XG4gICAgfSwge30pO1xufVxuZnVuY3Rpb24gZ2V0U2hhZG93ZWREZXNjcmlwdG9yKHByb3RvdHlwZSwgcHJvcGVydGllcywga2V5KSB7XG4gICAgY29uc3Qgc2hhZG93aW5nRGVzY3JpcHRvciA9IE9iamVjdC5nZXRPd25Qcm9wZXJ0eURlc2NyaXB0b3IocHJvdG90eXBlLCBrZXkpO1xuICAgIGNvbnN0IHNoYWRvd2VkQnlWYWx1ZSA9IHNoYWRvd2luZ0Rlc2NyaXB0b3IgJiYgXCJ2YWx1ZVwiIGluIHNoYWRvd2luZ0Rlc2NyaXB0b3I7XG4gICAgaWYgKCFzaGFkb3dlZEJ5VmFsdWUpIHtcbiAgICAgICAgY29uc3QgZGVzY3JpcHRvciA9IE9iamVjdC5nZXRPd25Qcm9wZXJ0eURlc2NyaXB0b3IocHJvcGVydGllcywga2V5KS52YWx1ZTtcbiAgICAgICAgaWYgKHNoYWRvd2luZ0Rlc2NyaXB0b3IpIHtcbiAgICAgICAgICAgIGRlc2NyaXB0b3IuZ2V0ID0gc2hhZG93aW5nRGVzY3JpcHRvci5nZXQgfHwgZGVzY3JpcHRvci5nZXQ7XG4gICAgICAgICAgICBkZXNjcmlwdG9yLnNldCA9IHNoYWRvd2luZ0Rlc2NyaXB0b3Iuc2V0IHx8IGRlc2NyaXB0b3Iuc2V0O1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBkZXNjcmlwdG9yO1xuICAgIH1cbn1cbmNvbnN0IGdldE93bktleXMgPSAoKCkgPT4ge1xuICAgIGlmICh0eXBlb2YgT2JqZWN0LmdldE93blByb3BlcnR5U3ltYm9scyA9PSBcImZ1bmN0aW9uXCIpIHtcbiAgICAgICAgcmV0dXJuIChvYmplY3QpID0+IFsuLi5PYmplY3QuZ2V0T3duUHJvcGVydHlOYW1lcyhvYmplY3QpLCAuLi5PYmplY3QuZ2V0T3duUHJvcGVydHlTeW1ib2xzKG9iamVjdCldO1xuICAgIH1cbiAgICBlbHNlIHtcbiAgICAgICAgcmV0dXJuIE9iamVjdC5nZXRPd25Qcm9wZXJ0eU5hbWVzO1xuICAgIH1cbn0pKCk7XG5jb25zdCBleHRlbmQgPSAoKCkgPT4ge1xuICAgIGZ1bmN0aW9uIGV4dGVuZFdpdGhSZWZsZWN0KGNvbnN0cnVjdG9yKSB7XG4gICAgICAgIGZ1bmN0aW9uIGV4dGVuZGVkKCkge1xuICAgICAgICAgICAgcmV0dXJuIFJlZmxlY3QuY29uc3RydWN0KGNvbnN0cnVjdG9yLCBhcmd1bWVudHMsIG5ldy50YXJnZXQpO1xuICAgICAgICB9XG4gICAgICAgIGV4dGVuZGVkLnByb3RvdHlwZSA9IE9iamVjdC5jcmVhdGUoY29uc3RydWN0b3IucHJvdG90eXBlLCB7XG4gICAgICAgICAgICBjb25zdHJ1Y3RvcjogeyB2YWx1ZTogZXh0ZW5kZWQgfSxcbiAgICAgICAgfSk7XG4gICAgICAgIFJlZmxlY3Quc2V0UHJvdG90eXBlT2YoZXh0ZW5kZWQsIGNvbnN0cnVjdG9yKTtcbiAgICAgICAgcmV0dXJuIGV4dGVuZGVkO1xuICAgIH1cbiAgICBmdW5jdGlvbiB0ZXN0UmVmbGVjdEV4dGVuc2lvbigpIHtcbiAgICAgICAgY29uc3QgYSA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgIHRoaXMuYS5jYWxsKHRoaXMpO1xuICAgICAgICB9O1xuICAgICAgICBjb25zdCBiID0gZXh0ZW5kV2l0aFJlZmxlY3QoYSk7XG4gICAgICAgIGIucHJvdG90eXBlLmEgPSBmdW5jdGlvbiAoKSB7IH07XG4gICAgICAgIHJldHVybiBuZXcgYigpO1xuICAgIH1cbiAgICB0cnkge1xuICAgICAgICB0ZXN0UmVmbGVjdEV4dGVuc2lvbigpO1xuICAgICAgICByZXR1cm4gZXh0ZW5kV2l0aFJlZmxlY3Q7XG4gICAgfVxuICAgIGNhdGNoIChlcnJvcikge1xuICAgICAgICByZXR1cm4gKGNvbnN0cnVjdG9yKSA9PiBjbGFzcyBleHRlbmRlZCBleHRlbmRzIGNvbnN0cnVjdG9yIHtcbiAgICAgICAgfTtcbiAgICB9XG59KSgpO1xuXG5mdW5jdGlvbiBibGVzc0RlZmluaXRpb24oZGVmaW5pdGlvbikge1xuICAgIHJldHVybiB7XG4gICAgICAgIGlkZW50aWZpZXI6IGRlZmluaXRpb24uaWRlbnRpZmllcixcbiAgICAgICAgY29udHJvbGxlckNvbnN0cnVjdG9yOiBibGVzcyhkZWZpbml0aW9uLmNvbnRyb2xsZXJDb25zdHJ1Y3RvciksXG4gICAgfTtcbn1cblxuY2xhc3MgTW9kdWxlIHtcbiAgICBjb25zdHJ1Y3RvcihhcHBsaWNhdGlvbiwgZGVmaW5pdGlvbikge1xuICAgICAgICB0aGlzLmFwcGxpY2F0aW9uID0gYXBwbGljYXRpb247XG4gICAgICAgIHRoaXMuZGVmaW5pdGlvbiA9IGJsZXNzRGVmaW5pdGlvbihkZWZpbml0aW9uKTtcbiAgICAgICAgdGhpcy5jb250ZXh0c0J5U2NvcGUgPSBuZXcgV2Vha01hcCgpO1xuICAgICAgICB0aGlzLmNvbm5lY3RlZENvbnRleHRzID0gbmV3IFNldCgpO1xuICAgIH1cbiAgICBnZXQgaWRlbnRpZmllcigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGVmaW5pdGlvbi5pZGVudGlmaWVyO1xuICAgIH1cbiAgICBnZXQgY29udHJvbGxlckNvbnN0cnVjdG9yKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5kZWZpbml0aW9uLmNvbnRyb2xsZXJDb25zdHJ1Y3RvcjtcbiAgICB9XG4gICAgZ2V0IGNvbnRleHRzKCkge1xuICAgICAgICByZXR1cm4gQXJyYXkuZnJvbSh0aGlzLmNvbm5lY3RlZENvbnRleHRzKTtcbiAgICB9XG4gICAgY29ubmVjdENvbnRleHRGb3JTY29wZShzY29wZSkge1xuICAgICAgICBjb25zdCBjb250ZXh0ID0gdGhpcy5mZXRjaENvbnRleHRGb3JTY29wZShzY29wZSk7XG4gICAgICAgIHRoaXMuY29ubmVjdGVkQ29udGV4dHMuYWRkKGNvbnRleHQpO1xuICAgICAgICBjb250ZXh0LmNvbm5lY3QoKTtcbiAgICB9XG4gICAgZGlzY29ubmVjdENvbnRleHRGb3JTY29wZShzY29wZSkge1xuICAgICAgICBjb25zdCBjb250ZXh0ID0gdGhpcy5jb250ZXh0c0J5U2NvcGUuZ2V0KHNjb3BlKTtcbiAgICAgICAgaWYgKGNvbnRleHQpIHtcbiAgICAgICAgICAgIHRoaXMuY29ubmVjdGVkQ29udGV4dHMuZGVsZXRlKGNvbnRleHQpO1xuICAgICAgICAgICAgY29udGV4dC5kaXNjb25uZWN0KCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgZmV0Y2hDb250ZXh0Rm9yU2NvcGUoc2NvcGUpIHtcbiAgICAgICAgbGV0IGNvbnRleHQgPSB0aGlzLmNvbnRleHRzQnlTY29wZS5nZXQoc2NvcGUpO1xuICAgICAgICBpZiAoIWNvbnRleHQpIHtcbiAgICAgICAgICAgIGNvbnRleHQgPSBuZXcgQ29udGV4dCh0aGlzLCBzY29wZSk7XG4gICAgICAgICAgICB0aGlzLmNvbnRleHRzQnlTY29wZS5zZXQoc2NvcGUsIGNvbnRleHQpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBjb250ZXh0O1xuICAgIH1cbn1cblxuY2xhc3MgQ2xhc3NNYXAge1xuICAgIGNvbnN0cnVjdG9yKHNjb3BlKSB7XG4gICAgICAgIHRoaXMuc2NvcGUgPSBzY29wZTtcbiAgICB9XG4gICAgaGFzKG5hbWUpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGF0YS5oYXModGhpcy5nZXREYXRhS2V5KG5hbWUpKTtcbiAgICB9XG4gICAgZ2V0KG5hbWUpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZ2V0QWxsKG5hbWUpWzBdO1xuICAgIH1cbiAgICBnZXRBbGwobmFtZSkge1xuICAgICAgICBjb25zdCB0b2tlblN0cmluZyA9IHRoaXMuZGF0YS5nZXQodGhpcy5nZXREYXRhS2V5KG5hbWUpKSB8fCBcIlwiO1xuICAgICAgICByZXR1cm4gdG9rZW5pemUodG9rZW5TdHJpbmcpO1xuICAgIH1cbiAgICBnZXRBdHRyaWJ1dGVOYW1lKG5hbWUpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGF0YS5nZXRBdHRyaWJ1dGVOYW1lRm9yS2V5KHRoaXMuZ2V0RGF0YUtleShuYW1lKSk7XG4gICAgfVxuICAgIGdldERhdGFLZXkobmFtZSkge1xuICAgICAgICByZXR1cm4gYCR7bmFtZX0tY2xhc3NgO1xuICAgIH1cbiAgICBnZXQgZGF0YSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuZGF0YTtcbiAgICB9XG59XG5cbmNsYXNzIERhdGFNYXAge1xuICAgIGNvbnN0cnVjdG9yKHNjb3BlKSB7XG4gICAgICAgIHRoaXMuc2NvcGUgPSBzY29wZTtcbiAgICB9XG4gICAgZ2V0IGVsZW1lbnQoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmVsZW1lbnQ7XG4gICAgfVxuICAgIGdldCBpZGVudGlmaWVyKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5pZGVudGlmaWVyO1xuICAgIH1cbiAgICBnZXQoa2V5KSB7XG4gICAgICAgIGNvbnN0IG5hbWUgPSB0aGlzLmdldEF0dHJpYnV0ZU5hbWVGb3JLZXkoa2V5KTtcbiAgICAgICAgcmV0dXJuIHRoaXMuZWxlbWVudC5nZXRBdHRyaWJ1dGUobmFtZSk7XG4gICAgfVxuICAgIHNldChrZXksIHZhbHVlKSB7XG4gICAgICAgIGNvbnN0IG5hbWUgPSB0aGlzLmdldEF0dHJpYnV0ZU5hbWVGb3JLZXkoa2V5KTtcbiAgICAgICAgdGhpcy5lbGVtZW50LnNldEF0dHJpYnV0ZShuYW1lLCB2YWx1ZSk7XG4gICAgICAgIHJldHVybiB0aGlzLmdldChrZXkpO1xuICAgIH1cbiAgICBoYXMoa2V5KSB7XG4gICAgICAgIGNvbnN0IG5hbWUgPSB0aGlzLmdldEF0dHJpYnV0ZU5hbWVGb3JLZXkoa2V5KTtcbiAgICAgICAgcmV0dXJuIHRoaXMuZWxlbWVudC5oYXNBdHRyaWJ1dGUobmFtZSk7XG4gICAgfVxuICAgIGRlbGV0ZShrZXkpIHtcbiAgICAgICAgaWYgKHRoaXMuaGFzKGtleSkpIHtcbiAgICAgICAgICAgIGNvbnN0IG5hbWUgPSB0aGlzLmdldEF0dHJpYnV0ZU5hbWVGb3JLZXkoa2V5KTtcbiAgICAgICAgICAgIHRoaXMuZWxlbWVudC5yZW1vdmVBdHRyaWJ1dGUobmFtZSk7XG4gICAgICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICAgICAgfVxuICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXRBdHRyaWJ1dGVOYW1lRm9yS2V5KGtleSkge1xuICAgICAgICByZXR1cm4gYGRhdGEtJHt0aGlzLmlkZW50aWZpZXJ9LSR7ZGFzaGVyaXplKGtleSl9YDtcbiAgICB9XG59XG5cbmNsYXNzIEd1aWRlIHtcbiAgICBjb25zdHJ1Y3Rvcihsb2dnZXIpIHtcbiAgICAgICAgdGhpcy53YXJuZWRLZXlzQnlPYmplY3QgPSBuZXcgV2Vha01hcCgpO1xuICAgICAgICB0aGlzLmxvZ2dlciA9IGxvZ2dlcjtcbiAgICB9XG4gICAgd2FybihvYmplY3QsIGtleSwgbWVzc2FnZSkge1xuICAgICAgICBsZXQgd2FybmVkS2V5cyA9IHRoaXMud2FybmVkS2V5c0J5T2JqZWN0LmdldChvYmplY3QpO1xuICAgICAgICBpZiAoIXdhcm5lZEtleXMpIHtcbiAgICAgICAgICAgIHdhcm5lZEtleXMgPSBuZXcgU2V0KCk7XG4gICAgICAgICAgICB0aGlzLndhcm5lZEtleXNCeU9iamVjdC5zZXQob2JqZWN0LCB3YXJuZWRLZXlzKTtcbiAgICAgICAgfVxuICAgICAgICBpZiAoIXdhcm5lZEtleXMuaGFzKGtleSkpIHtcbiAgICAgICAgICAgIHdhcm5lZEtleXMuYWRkKGtleSk7XG4gICAgICAgICAgICB0aGlzLmxvZ2dlci53YXJuKG1lc3NhZ2UsIG9iamVjdCk7XG4gICAgICAgIH1cbiAgICB9XG59XG5cbmZ1bmN0aW9uIGF0dHJpYnV0ZVZhbHVlQ29udGFpbnNUb2tlbihhdHRyaWJ1dGVOYW1lLCB0b2tlbikge1xuICAgIHJldHVybiBgWyR7YXR0cmlidXRlTmFtZX1+PVwiJHt0b2tlbn1cIl1gO1xufVxuXG5jbGFzcyBUYXJnZXRTZXQge1xuICAgIGNvbnN0cnVjdG9yKHNjb3BlKSB7XG4gICAgICAgIHRoaXMuc2NvcGUgPSBzY29wZTtcbiAgICB9XG4gICAgZ2V0IGVsZW1lbnQoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmVsZW1lbnQ7XG4gICAgfVxuICAgIGdldCBpZGVudGlmaWVyKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5pZGVudGlmaWVyO1xuICAgIH1cbiAgICBnZXQgc2NoZW1hKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5zY2hlbWE7XG4gICAgfVxuICAgIGhhcyh0YXJnZXROYW1lKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmZpbmQodGFyZ2V0TmFtZSkgIT0gbnVsbDtcbiAgICB9XG4gICAgZmluZCguLi50YXJnZXROYW1lcykge1xuICAgICAgICByZXR1cm4gdGFyZ2V0TmFtZXMucmVkdWNlKCh0YXJnZXQsIHRhcmdldE5hbWUpID0+IHRhcmdldCB8fCB0aGlzLmZpbmRUYXJnZXQodGFyZ2V0TmFtZSkgfHwgdGhpcy5maW5kTGVnYWN5VGFyZ2V0KHRhcmdldE5hbWUpLCB1bmRlZmluZWQpO1xuICAgIH1cbiAgICBmaW5kQWxsKC4uLnRhcmdldE5hbWVzKSB7XG4gICAgICAgIHJldHVybiB0YXJnZXROYW1lcy5yZWR1Y2UoKHRhcmdldHMsIHRhcmdldE5hbWUpID0+IFtcbiAgICAgICAgICAgIC4uLnRhcmdldHMsXG4gICAgICAgICAgICAuLi50aGlzLmZpbmRBbGxUYXJnZXRzKHRhcmdldE5hbWUpLFxuICAgICAgICAgICAgLi4udGhpcy5maW5kQWxsTGVnYWN5VGFyZ2V0cyh0YXJnZXROYW1lKSxcbiAgICAgICAgXSwgW10pO1xuICAgIH1cbiAgICBmaW5kVGFyZ2V0KHRhcmdldE5hbWUpIHtcbiAgICAgICAgY29uc3Qgc2VsZWN0b3IgPSB0aGlzLmdldFNlbGVjdG9yRm9yVGFyZ2V0TmFtZSh0YXJnZXROYW1lKTtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuZmluZEVsZW1lbnQoc2VsZWN0b3IpO1xuICAgIH1cbiAgICBmaW5kQWxsVGFyZ2V0cyh0YXJnZXROYW1lKSB7XG4gICAgICAgIGNvbnN0IHNlbGVjdG9yID0gdGhpcy5nZXRTZWxlY3RvckZvclRhcmdldE5hbWUodGFyZ2V0TmFtZSk7XG4gICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmZpbmRBbGxFbGVtZW50cyhzZWxlY3Rvcik7XG4gICAgfVxuICAgIGdldFNlbGVjdG9yRm9yVGFyZ2V0TmFtZSh0YXJnZXROYW1lKSB7XG4gICAgICAgIGNvbnN0IGF0dHJpYnV0ZU5hbWUgPSB0aGlzLnNjaGVtYS50YXJnZXRBdHRyaWJ1dGVGb3JTY29wZSh0aGlzLmlkZW50aWZpZXIpO1xuICAgICAgICByZXR1cm4gYXR0cmlidXRlVmFsdWVDb250YWluc1Rva2VuKGF0dHJpYnV0ZU5hbWUsIHRhcmdldE5hbWUpO1xuICAgIH1cbiAgICBmaW5kTGVnYWN5VGFyZ2V0KHRhcmdldE5hbWUpIHtcbiAgICAgICAgY29uc3Qgc2VsZWN0b3IgPSB0aGlzLmdldExlZ2FjeVNlbGVjdG9yRm9yVGFyZ2V0TmFtZSh0YXJnZXROYW1lKTtcbiAgICAgICAgcmV0dXJuIHRoaXMuZGVwcmVjYXRlKHRoaXMuc2NvcGUuZmluZEVsZW1lbnQoc2VsZWN0b3IpLCB0YXJnZXROYW1lKTtcbiAgICB9XG4gICAgZmluZEFsbExlZ2FjeVRhcmdldHModGFyZ2V0TmFtZSkge1xuICAgICAgICBjb25zdCBzZWxlY3RvciA9IHRoaXMuZ2V0TGVnYWN5U2VsZWN0b3JGb3JUYXJnZXROYW1lKHRhcmdldE5hbWUpO1xuICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5maW5kQWxsRWxlbWVudHMoc2VsZWN0b3IpLm1hcCgoZWxlbWVudCkgPT4gdGhpcy5kZXByZWNhdGUoZWxlbWVudCwgdGFyZ2V0TmFtZSkpO1xuICAgIH1cbiAgICBnZXRMZWdhY3lTZWxlY3RvckZvclRhcmdldE5hbWUodGFyZ2V0TmFtZSkge1xuICAgICAgICBjb25zdCB0YXJnZXREZXNjcmlwdG9yID0gYCR7dGhpcy5pZGVudGlmaWVyfS4ke3RhcmdldE5hbWV9YDtcbiAgICAgICAgcmV0dXJuIGF0dHJpYnV0ZVZhbHVlQ29udGFpbnNUb2tlbih0aGlzLnNjaGVtYS50YXJnZXRBdHRyaWJ1dGUsIHRhcmdldERlc2NyaXB0b3IpO1xuICAgIH1cbiAgICBkZXByZWNhdGUoZWxlbWVudCwgdGFyZ2V0TmFtZSkge1xuICAgICAgICBpZiAoZWxlbWVudCkge1xuICAgICAgICAgICAgY29uc3QgeyBpZGVudGlmaWVyIH0gPSB0aGlzO1xuICAgICAgICAgICAgY29uc3QgYXR0cmlidXRlTmFtZSA9IHRoaXMuc2NoZW1hLnRhcmdldEF0dHJpYnV0ZTtcbiAgICAgICAgICAgIGNvbnN0IHJldmlzZWRBdHRyaWJ1dGVOYW1lID0gdGhpcy5zY2hlbWEudGFyZ2V0QXR0cmlidXRlRm9yU2NvcGUoaWRlbnRpZmllcik7XG4gICAgICAgICAgICB0aGlzLmd1aWRlLndhcm4oZWxlbWVudCwgYHRhcmdldDoke3RhcmdldE5hbWV9YCwgYFBsZWFzZSByZXBsYWNlICR7YXR0cmlidXRlTmFtZX09XCIke2lkZW50aWZpZXJ9LiR7dGFyZ2V0TmFtZX1cIiB3aXRoICR7cmV2aXNlZEF0dHJpYnV0ZU5hbWV9PVwiJHt0YXJnZXROYW1lfVwiLiBgICtcbiAgICAgICAgICAgICAgICBgVGhlICR7YXR0cmlidXRlTmFtZX0gYXR0cmlidXRlIGlzIGRlcHJlY2F0ZWQgYW5kIHdpbGwgYmUgcmVtb3ZlZCBpbiBhIGZ1dHVyZSB2ZXJzaW9uIG9mIFN0aW11bHVzLmApO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBlbGVtZW50O1xuICAgIH1cbiAgICBnZXQgZ3VpZGUoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmd1aWRlO1xuICAgIH1cbn1cblxuY2xhc3MgT3V0bGV0U2V0IHtcbiAgICBjb25zdHJ1Y3RvcihzY29wZSwgY29udHJvbGxlckVsZW1lbnQpIHtcbiAgICAgICAgdGhpcy5zY29wZSA9IHNjb3BlO1xuICAgICAgICB0aGlzLmNvbnRyb2xsZXJFbGVtZW50ID0gY29udHJvbGxlckVsZW1lbnQ7XG4gICAgfVxuICAgIGdldCBlbGVtZW50KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5lbGVtZW50O1xuICAgIH1cbiAgICBnZXQgaWRlbnRpZmllcigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuaWRlbnRpZmllcjtcbiAgICB9XG4gICAgZ2V0IHNjaGVtYSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuc2NoZW1hO1xuICAgIH1cbiAgICBoYXMob3V0bGV0TmFtZSkge1xuICAgICAgICByZXR1cm4gdGhpcy5maW5kKG91dGxldE5hbWUpICE9IG51bGw7XG4gICAgfVxuICAgIGZpbmQoLi4ub3V0bGV0TmFtZXMpIHtcbiAgICAgICAgcmV0dXJuIG91dGxldE5hbWVzLnJlZHVjZSgob3V0bGV0LCBvdXRsZXROYW1lKSA9PiBvdXRsZXQgfHwgdGhpcy5maW5kT3V0bGV0KG91dGxldE5hbWUpLCB1bmRlZmluZWQpO1xuICAgIH1cbiAgICBmaW5kQWxsKC4uLm91dGxldE5hbWVzKSB7XG4gICAgICAgIHJldHVybiBvdXRsZXROYW1lcy5yZWR1Y2UoKG91dGxldHMsIG91dGxldE5hbWUpID0+IFsuLi5vdXRsZXRzLCAuLi50aGlzLmZpbmRBbGxPdXRsZXRzKG91dGxldE5hbWUpXSwgW10pO1xuICAgIH1cbiAgICBnZXRTZWxlY3RvckZvck91dGxldE5hbWUob3V0bGV0TmFtZSkge1xuICAgICAgICBjb25zdCBhdHRyaWJ1dGVOYW1lID0gdGhpcy5zY2hlbWEub3V0bGV0QXR0cmlidXRlRm9yU2NvcGUodGhpcy5pZGVudGlmaWVyLCBvdXRsZXROYW1lKTtcbiAgICAgICAgcmV0dXJuIHRoaXMuY29udHJvbGxlckVsZW1lbnQuZ2V0QXR0cmlidXRlKGF0dHJpYnV0ZU5hbWUpO1xuICAgIH1cbiAgICBmaW5kT3V0bGV0KG91dGxldE5hbWUpIHtcbiAgICAgICAgY29uc3Qgc2VsZWN0b3IgPSB0aGlzLmdldFNlbGVjdG9yRm9yT3V0bGV0TmFtZShvdXRsZXROYW1lKTtcbiAgICAgICAgaWYgKHNlbGVjdG9yKVxuICAgICAgICAgICAgcmV0dXJuIHRoaXMuZmluZEVsZW1lbnQoc2VsZWN0b3IsIG91dGxldE5hbWUpO1xuICAgIH1cbiAgICBmaW5kQWxsT3V0bGV0cyhvdXRsZXROYW1lKSB7XG4gICAgICAgIGNvbnN0IHNlbGVjdG9yID0gdGhpcy5nZXRTZWxlY3RvckZvck91dGxldE5hbWUob3V0bGV0TmFtZSk7XG4gICAgICAgIHJldHVybiBzZWxlY3RvciA/IHRoaXMuZmluZEFsbEVsZW1lbnRzKHNlbGVjdG9yLCBvdXRsZXROYW1lKSA6IFtdO1xuICAgIH1cbiAgICBmaW5kRWxlbWVudChzZWxlY3Rvciwgb3V0bGV0TmFtZSkge1xuICAgICAgICBjb25zdCBlbGVtZW50cyA9IHRoaXMuc2NvcGUucXVlcnlFbGVtZW50cyhzZWxlY3Rvcik7XG4gICAgICAgIHJldHVybiBlbGVtZW50cy5maWx0ZXIoKGVsZW1lbnQpID0+IHRoaXMubWF0Y2hlc0VsZW1lbnQoZWxlbWVudCwgc2VsZWN0b3IsIG91dGxldE5hbWUpKVswXTtcbiAgICB9XG4gICAgZmluZEFsbEVsZW1lbnRzKHNlbGVjdG9yLCBvdXRsZXROYW1lKSB7XG4gICAgICAgIGNvbnN0IGVsZW1lbnRzID0gdGhpcy5zY29wZS5xdWVyeUVsZW1lbnRzKHNlbGVjdG9yKTtcbiAgICAgICAgcmV0dXJuIGVsZW1lbnRzLmZpbHRlcigoZWxlbWVudCkgPT4gdGhpcy5tYXRjaGVzRWxlbWVudChlbGVtZW50LCBzZWxlY3Rvciwgb3V0bGV0TmFtZSkpO1xuICAgIH1cbiAgICBtYXRjaGVzRWxlbWVudChlbGVtZW50LCBzZWxlY3Rvciwgb3V0bGV0TmFtZSkge1xuICAgICAgICBjb25zdCBjb250cm9sbGVyQXR0cmlidXRlID0gZWxlbWVudC5nZXRBdHRyaWJ1dGUodGhpcy5zY29wZS5zY2hlbWEuY29udHJvbGxlckF0dHJpYnV0ZSkgfHwgXCJcIjtcbiAgICAgICAgcmV0dXJuIGVsZW1lbnQubWF0Y2hlcyhzZWxlY3RvcikgJiYgY29udHJvbGxlckF0dHJpYnV0ZS5zcGxpdChcIiBcIikuaW5jbHVkZXMob3V0bGV0TmFtZSk7XG4gICAgfVxufVxuXG5jbGFzcyBTY29wZSB7XG4gICAgY29uc3RydWN0b3Ioc2NoZW1hLCBlbGVtZW50LCBpZGVudGlmaWVyLCBsb2dnZXIpIHtcbiAgICAgICAgdGhpcy50YXJnZXRzID0gbmV3IFRhcmdldFNldCh0aGlzKTtcbiAgICAgICAgdGhpcy5jbGFzc2VzID0gbmV3IENsYXNzTWFwKHRoaXMpO1xuICAgICAgICB0aGlzLmRhdGEgPSBuZXcgRGF0YU1hcCh0aGlzKTtcbiAgICAgICAgdGhpcy5jb250YWluc0VsZW1lbnQgPSAoZWxlbWVudCkgPT4ge1xuICAgICAgICAgICAgcmV0dXJuIGVsZW1lbnQuY2xvc2VzdCh0aGlzLmNvbnRyb2xsZXJTZWxlY3RvcikgPT09IHRoaXMuZWxlbWVudDtcbiAgICAgICAgfTtcbiAgICAgICAgdGhpcy5zY2hlbWEgPSBzY2hlbWE7XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7XG4gICAgICAgIHRoaXMuaWRlbnRpZmllciA9IGlkZW50aWZpZXI7XG4gICAgICAgIHRoaXMuZ3VpZGUgPSBuZXcgR3VpZGUobG9nZ2VyKTtcbiAgICAgICAgdGhpcy5vdXRsZXRzID0gbmV3IE91dGxldFNldCh0aGlzLmRvY3VtZW50U2NvcGUsIGVsZW1lbnQpO1xuICAgIH1cbiAgICBmaW5kRWxlbWVudChzZWxlY3Rvcikge1xuICAgICAgICByZXR1cm4gdGhpcy5lbGVtZW50Lm1hdGNoZXMoc2VsZWN0b3IpID8gdGhpcy5lbGVtZW50IDogdGhpcy5xdWVyeUVsZW1lbnRzKHNlbGVjdG9yKS5maW5kKHRoaXMuY29udGFpbnNFbGVtZW50KTtcbiAgICB9XG4gICAgZmluZEFsbEVsZW1lbnRzKHNlbGVjdG9yKSB7XG4gICAgICAgIHJldHVybiBbXG4gICAgICAgICAgICAuLi4odGhpcy5lbGVtZW50Lm1hdGNoZXMoc2VsZWN0b3IpID8gW3RoaXMuZWxlbWVudF0gOiBbXSksXG4gICAgICAgICAgICAuLi50aGlzLnF1ZXJ5RWxlbWVudHMoc2VsZWN0b3IpLmZpbHRlcih0aGlzLmNvbnRhaW5zRWxlbWVudCksXG4gICAgICAgIF07XG4gICAgfVxuICAgIHF1ZXJ5RWxlbWVudHMoc2VsZWN0b3IpIHtcbiAgICAgICAgcmV0dXJuIEFycmF5LmZyb20odGhpcy5lbGVtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoc2VsZWN0b3IpKTtcbiAgICB9XG4gICAgZ2V0IGNvbnRyb2xsZXJTZWxlY3RvcigpIHtcbiAgICAgICAgcmV0dXJuIGF0dHJpYnV0ZVZhbHVlQ29udGFpbnNUb2tlbih0aGlzLnNjaGVtYS5jb250cm9sbGVyQXR0cmlidXRlLCB0aGlzLmlkZW50aWZpZXIpO1xuICAgIH1cbiAgICBnZXQgaXNEb2N1bWVudFNjb3BlKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5lbGVtZW50ID09PSBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQ7XG4gICAgfVxuICAgIGdldCBkb2N1bWVudFNjb3BlKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5pc0RvY3VtZW50U2NvcGVcbiAgICAgICAgICAgID8gdGhpc1xuICAgICAgICAgICAgOiBuZXcgU2NvcGUodGhpcy5zY2hlbWEsIGRvY3VtZW50LmRvY3VtZW50RWxlbWVudCwgdGhpcy5pZGVudGlmaWVyLCB0aGlzLmd1aWRlLmxvZ2dlcik7XG4gICAgfVxufVxuXG5jbGFzcyBTY29wZU9ic2VydmVyIHtcbiAgICBjb25zdHJ1Y3RvcihlbGVtZW50LCBzY2hlbWEsIGRlbGVnYXRlKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7XG4gICAgICAgIHRoaXMuc2NoZW1hID0gc2NoZW1hO1xuICAgICAgICB0aGlzLmRlbGVnYXRlID0gZGVsZWdhdGU7XG4gICAgICAgIHRoaXMudmFsdWVMaXN0T2JzZXJ2ZXIgPSBuZXcgVmFsdWVMaXN0T2JzZXJ2ZXIodGhpcy5lbGVtZW50LCB0aGlzLmNvbnRyb2xsZXJBdHRyaWJ1dGUsIHRoaXMpO1xuICAgICAgICB0aGlzLnNjb3Blc0J5SWRlbnRpZmllckJ5RWxlbWVudCA9IG5ldyBXZWFrTWFwKCk7XG4gICAgICAgIHRoaXMuc2NvcGVSZWZlcmVuY2VDb3VudHMgPSBuZXcgV2Vha01hcCgpO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgdGhpcy52YWx1ZUxpc3RPYnNlcnZlci5zdGFydCgpO1xuICAgIH1cbiAgICBzdG9wKCkge1xuICAgICAgICB0aGlzLnZhbHVlTGlzdE9ic2VydmVyLnN0b3AoKTtcbiAgICB9XG4gICAgZ2V0IGNvbnRyb2xsZXJBdHRyaWJ1dGUoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnNjaGVtYS5jb250cm9sbGVyQXR0cmlidXRlO1xuICAgIH1cbiAgICBwYXJzZVZhbHVlRm9yVG9rZW4odG9rZW4pIHtcbiAgICAgICAgY29uc3QgeyBlbGVtZW50LCBjb250ZW50OiBpZGVudGlmaWVyIH0gPSB0b2tlbjtcbiAgICAgICAgcmV0dXJuIHRoaXMucGFyc2VWYWx1ZUZvckVsZW1lbnRBbmRJZGVudGlmaWVyKGVsZW1lbnQsIGlkZW50aWZpZXIpO1xuICAgIH1cbiAgICBwYXJzZVZhbHVlRm9yRWxlbWVudEFuZElkZW50aWZpZXIoZWxlbWVudCwgaWRlbnRpZmllcikge1xuICAgICAgICBjb25zdCBzY29wZXNCeUlkZW50aWZpZXIgPSB0aGlzLmZldGNoU2NvcGVzQnlJZGVudGlmaWVyRm9yRWxlbWVudChlbGVtZW50KTtcbiAgICAgICAgbGV0IHNjb3BlID0gc2NvcGVzQnlJZGVudGlmaWVyLmdldChpZGVudGlmaWVyKTtcbiAgICAgICAgaWYgKCFzY29wZSkge1xuICAgICAgICAgICAgc2NvcGUgPSB0aGlzLmRlbGVnYXRlLmNyZWF0ZVNjb3BlRm9yRWxlbWVudEFuZElkZW50aWZpZXIoZWxlbWVudCwgaWRlbnRpZmllcik7XG4gICAgICAgICAgICBzY29wZXNCeUlkZW50aWZpZXIuc2V0KGlkZW50aWZpZXIsIHNjb3BlKTtcbiAgICAgICAgfVxuICAgICAgICByZXR1cm4gc2NvcGU7XG4gICAgfVxuICAgIGVsZW1lbnRNYXRjaGVkVmFsdWUoZWxlbWVudCwgdmFsdWUpIHtcbiAgICAgICAgY29uc3QgcmVmZXJlbmNlQ291bnQgPSAodGhpcy5zY29wZVJlZmVyZW5jZUNvdW50cy5nZXQodmFsdWUpIHx8IDApICsgMTtcbiAgICAgICAgdGhpcy5zY29wZVJlZmVyZW5jZUNvdW50cy5zZXQodmFsdWUsIHJlZmVyZW5jZUNvdW50KTtcbiAgICAgICAgaWYgKHJlZmVyZW5jZUNvdW50ID09IDEpIHtcbiAgICAgICAgICAgIHRoaXMuZGVsZWdhdGUuc2NvcGVDb25uZWN0ZWQodmFsdWUpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGVsZW1lbnRVbm1hdGNoZWRWYWx1ZShlbGVtZW50LCB2YWx1ZSkge1xuICAgICAgICBjb25zdCByZWZlcmVuY2VDb3VudCA9IHRoaXMuc2NvcGVSZWZlcmVuY2VDb3VudHMuZ2V0KHZhbHVlKTtcbiAgICAgICAgaWYgKHJlZmVyZW5jZUNvdW50KSB7XG4gICAgICAgICAgICB0aGlzLnNjb3BlUmVmZXJlbmNlQ291bnRzLnNldCh2YWx1ZSwgcmVmZXJlbmNlQ291bnQgLSAxKTtcbiAgICAgICAgICAgIGlmIChyZWZlcmVuY2VDb3VudCA9PSAxKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5kZWxlZ2F0ZS5zY29wZURpc2Nvbm5lY3RlZCh2YWx1ZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9XG4gICAgZmV0Y2hTY29wZXNCeUlkZW50aWZpZXJGb3JFbGVtZW50KGVsZW1lbnQpIHtcbiAgICAgICAgbGV0IHNjb3Blc0J5SWRlbnRpZmllciA9IHRoaXMuc2NvcGVzQnlJZGVudGlmaWVyQnlFbGVtZW50LmdldChlbGVtZW50KTtcbiAgICAgICAgaWYgKCFzY29wZXNCeUlkZW50aWZpZXIpIHtcbiAgICAgICAgICAgIHNjb3Blc0J5SWRlbnRpZmllciA9IG5ldyBNYXAoKTtcbiAgICAgICAgICAgIHRoaXMuc2NvcGVzQnlJZGVudGlmaWVyQnlFbGVtZW50LnNldChlbGVtZW50LCBzY29wZXNCeUlkZW50aWZpZXIpO1xuICAgICAgICB9XG4gICAgICAgIHJldHVybiBzY29wZXNCeUlkZW50aWZpZXI7XG4gICAgfVxufVxuXG5jbGFzcyBSb3V0ZXIge1xuICAgIGNvbnN0cnVjdG9yKGFwcGxpY2F0aW9uKSB7XG4gICAgICAgIHRoaXMuYXBwbGljYXRpb24gPSBhcHBsaWNhdGlvbjtcbiAgICAgICAgdGhpcy5zY29wZU9ic2VydmVyID0gbmV3IFNjb3BlT2JzZXJ2ZXIodGhpcy5lbGVtZW50LCB0aGlzLnNjaGVtYSwgdGhpcyk7XG4gICAgICAgIHRoaXMuc2NvcGVzQnlJZGVudGlmaWVyID0gbmV3IE11bHRpbWFwKCk7XG4gICAgICAgIHRoaXMubW9kdWxlc0J5SWRlbnRpZmllciA9IG5ldyBNYXAoKTtcbiAgICB9XG4gICAgZ2V0IGVsZW1lbnQoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmFwcGxpY2F0aW9uLmVsZW1lbnQ7XG4gICAgfVxuICAgIGdldCBzY2hlbWEoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmFwcGxpY2F0aW9uLnNjaGVtYTtcbiAgICB9XG4gICAgZ2V0IGxvZ2dlcigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuYXBwbGljYXRpb24ubG9nZ2VyO1xuICAgIH1cbiAgICBnZXQgY29udHJvbGxlckF0dHJpYnV0ZSgpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2NoZW1hLmNvbnRyb2xsZXJBdHRyaWJ1dGU7XG4gICAgfVxuICAgIGdldCBtb2R1bGVzKCkge1xuICAgICAgICByZXR1cm4gQXJyYXkuZnJvbSh0aGlzLm1vZHVsZXNCeUlkZW50aWZpZXIudmFsdWVzKCkpO1xuICAgIH1cbiAgICBnZXQgY29udGV4dHMoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLm1vZHVsZXMucmVkdWNlKChjb250ZXh0cywgbW9kdWxlKSA9PiBjb250ZXh0cy5jb25jYXQobW9kdWxlLmNvbnRleHRzKSwgW10pO1xuICAgIH1cbiAgICBzdGFydCgpIHtcbiAgICAgICAgdGhpcy5zY29wZU9ic2VydmVyLnN0YXJ0KCk7XG4gICAgfVxuICAgIHN0b3AoKSB7XG4gICAgICAgIHRoaXMuc2NvcGVPYnNlcnZlci5zdG9wKCk7XG4gICAgfVxuICAgIGxvYWREZWZpbml0aW9uKGRlZmluaXRpb24pIHtcbiAgICAgICAgdGhpcy51bmxvYWRJZGVudGlmaWVyKGRlZmluaXRpb24uaWRlbnRpZmllcik7XG4gICAgICAgIGNvbnN0IG1vZHVsZSA9IG5ldyBNb2R1bGUodGhpcy5hcHBsaWNhdGlvbiwgZGVmaW5pdGlvbik7XG4gICAgICAgIHRoaXMuY29ubmVjdE1vZHVsZShtb2R1bGUpO1xuICAgICAgICBjb25zdCBhZnRlckxvYWQgPSBkZWZpbml0aW9uLmNvbnRyb2xsZXJDb25zdHJ1Y3Rvci5hZnRlckxvYWQ7XG4gICAgICAgIGlmIChhZnRlckxvYWQpIHtcbiAgICAgICAgICAgIGFmdGVyTG9hZC5jYWxsKGRlZmluaXRpb24uY29udHJvbGxlckNvbnN0cnVjdG9yLCBkZWZpbml0aW9uLmlkZW50aWZpZXIsIHRoaXMuYXBwbGljYXRpb24pO1xuICAgICAgICB9XG4gICAgfVxuICAgIHVubG9hZElkZW50aWZpZXIoaWRlbnRpZmllcikge1xuICAgICAgICBjb25zdCBtb2R1bGUgPSB0aGlzLm1vZHVsZXNCeUlkZW50aWZpZXIuZ2V0KGlkZW50aWZpZXIpO1xuICAgICAgICBpZiAobW9kdWxlKSB7XG4gICAgICAgICAgICB0aGlzLmRpc2Nvbm5lY3RNb2R1bGUobW9kdWxlKTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBnZXRDb250ZXh0Rm9yRWxlbWVudEFuZElkZW50aWZpZXIoZWxlbWVudCwgaWRlbnRpZmllcikge1xuICAgICAgICBjb25zdCBtb2R1bGUgPSB0aGlzLm1vZHVsZXNCeUlkZW50aWZpZXIuZ2V0KGlkZW50aWZpZXIpO1xuICAgICAgICBpZiAobW9kdWxlKSB7XG4gICAgICAgICAgICByZXR1cm4gbW9kdWxlLmNvbnRleHRzLmZpbmQoKGNvbnRleHQpID0+IGNvbnRleHQuZWxlbWVudCA9PSBlbGVtZW50KTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBwcm9wb3NlVG9Db25uZWN0U2NvcGVGb3JFbGVtZW50QW5kSWRlbnRpZmllcihlbGVtZW50LCBpZGVudGlmaWVyKSB7XG4gICAgICAgIGNvbnN0IHNjb3BlID0gdGhpcy5zY29wZU9ic2VydmVyLnBhcnNlVmFsdWVGb3JFbGVtZW50QW5kSWRlbnRpZmllcihlbGVtZW50LCBpZGVudGlmaWVyKTtcbiAgICAgICAgaWYgKHNjb3BlKSB7XG4gICAgICAgICAgICB0aGlzLnNjb3BlT2JzZXJ2ZXIuZWxlbWVudE1hdGNoZWRWYWx1ZShzY29wZS5lbGVtZW50LCBzY29wZSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICBjb25zb2xlLmVycm9yKGBDb3VsZG4ndCBmaW5kIG9yIGNyZWF0ZSBzY29wZSBmb3IgaWRlbnRpZmllcjogXCIke2lkZW50aWZpZXJ9XCIgYW5kIGVsZW1lbnQ6YCwgZWxlbWVudCk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgaGFuZGxlRXJyb3IoZXJyb3IsIG1lc3NhZ2UsIGRldGFpbCkge1xuICAgICAgICB0aGlzLmFwcGxpY2F0aW9uLmhhbmRsZUVycm9yKGVycm9yLCBtZXNzYWdlLCBkZXRhaWwpO1xuICAgIH1cbiAgICBjcmVhdGVTY29wZUZvckVsZW1lbnRBbmRJZGVudGlmaWVyKGVsZW1lbnQsIGlkZW50aWZpZXIpIHtcbiAgICAgICAgcmV0dXJuIG5ldyBTY29wZSh0aGlzLnNjaGVtYSwgZWxlbWVudCwgaWRlbnRpZmllciwgdGhpcy5sb2dnZXIpO1xuICAgIH1cbiAgICBzY29wZUNvbm5lY3RlZChzY29wZSkge1xuICAgICAgICB0aGlzLnNjb3Blc0J5SWRlbnRpZmllci5hZGQoc2NvcGUuaWRlbnRpZmllciwgc2NvcGUpO1xuICAgICAgICBjb25zdCBtb2R1bGUgPSB0aGlzLm1vZHVsZXNCeUlkZW50aWZpZXIuZ2V0KHNjb3BlLmlkZW50aWZpZXIpO1xuICAgICAgICBpZiAobW9kdWxlKSB7XG4gICAgICAgICAgICBtb2R1bGUuY29ubmVjdENvbnRleHRGb3JTY29wZShzY29wZSk7XG4gICAgICAgIH1cbiAgICB9XG4gICAgc2NvcGVEaXNjb25uZWN0ZWQoc2NvcGUpIHtcbiAgICAgICAgdGhpcy5zY29wZXNCeUlkZW50aWZpZXIuZGVsZXRlKHNjb3BlLmlkZW50aWZpZXIsIHNjb3BlKTtcbiAgICAgICAgY29uc3QgbW9kdWxlID0gdGhpcy5tb2R1bGVzQnlJZGVudGlmaWVyLmdldChzY29wZS5pZGVudGlmaWVyKTtcbiAgICAgICAgaWYgKG1vZHVsZSkge1xuICAgICAgICAgICAgbW9kdWxlLmRpc2Nvbm5lY3RDb250ZXh0Rm9yU2NvcGUoc2NvcGUpO1xuICAgICAgICB9XG4gICAgfVxuICAgIGNvbm5lY3RNb2R1bGUobW9kdWxlKSB7XG4gICAgICAgIHRoaXMubW9kdWxlc0J5SWRlbnRpZmllci5zZXQobW9kdWxlLmlkZW50aWZpZXIsIG1vZHVsZSk7XG4gICAgICAgIGNvbnN0IHNjb3BlcyA9IHRoaXMuc2NvcGVzQnlJZGVudGlmaWVyLmdldFZhbHVlc0ZvcktleShtb2R1bGUuaWRlbnRpZmllcik7XG4gICAgICAgIHNjb3Blcy5mb3JFYWNoKChzY29wZSkgPT4gbW9kdWxlLmNvbm5lY3RDb250ZXh0Rm9yU2NvcGUoc2NvcGUpKTtcbiAgICB9XG4gICAgZGlzY29ubmVjdE1vZHVsZShtb2R1bGUpIHtcbiAgICAgICAgdGhpcy5tb2R1bGVzQnlJZGVudGlmaWVyLmRlbGV0ZShtb2R1bGUuaWRlbnRpZmllcik7XG4gICAgICAgIGNvbnN0IHNjb3BlcyA9IHRoaXMuc2NvcGVzQnlJZGVudGlmaWVyLmdldFZhbHVlc0ZvcktleShtb2R1bGUuaWRlbnRpZmllcik7XG4gICAgICAgIHNjb3Blcy5mb3JFYWNoKChzY29wZSkgPT4gbW9kdWxlLmRpc2Nvbm5lY3RDb250ZXh0Rm9yU2NvcGUoc2NvcGUpKTtcbiAgICB9XG59XG5cbmNvbnN0IGRlZmF1bHRTY2hlbWEgPSB7XG4gICAgY29udHJvbGxlckF0dHJpYnV0ZTogXCJkYXRhLWNvbnRyb2xsZXJcIixcbiAgICBhY3Rpb25BdHRyaWJ1dGU6IFwiZGF0YS1hY3Rpb25cIixcbiAgICB0YXJnZXRBdHRyaWJ1dGU6IFwiZGF0YS10YXJnZXRcIixcbiAgICB0YXJnZXRBdHRyaWJ1dGVGb3JTY29wZTogKGlkZW50aWZpZXIpID0+IGBkYXRhLSR7aWRlbnRpZmllcn0tdGFyZ2V0YCxcbiAgICBvdXRsZXRBdHRyaWJ1dGVGb3JTY29wZTogKGlkZW50aWZpZXIsIG91dGxldCkgPT4gYGRhdGEtJHtpZGVudGlmaWVyfS0ke291dGxldH0tb3V0bGV0YCxcbiAgICBrZXlNYXBwaW5nczogT2JqZWN0LmFzc2lnbihPYmplY3QuYXNzaWduKHsgZW50ZXI6IFwiRW50ZXJcIiwgdGFiOiBcIlRhYlwiLCBlc2M6IFwiRXNjYXBlXCIsIHNwYWNlOiBcIiBcIiwgdXA6IFwiQXJyb3dVcFwiLCBkb3duOiBcIkFycm93RG93blwiLCBsZWZ0OiBcIkFycm93TGVmdFwiLCByaWdodDogXCJBcnJvd1JpZ2h0XCIsIGhvbWU6IFwiSG9tZVwiLCBlbmQ6IFwiRW5kXCIsIHBhZ2VfdXA6IFwiUGFnZVVwXCIsIHBhZ2VfZG93bjogXCJQYWdlRG93blwiIH0sIG9iamVjdEZyb21FbnRyaWVzKFwiYWJjZGVmZ2hpamtsbW5vcHFyc3R1dnd4eXpcIi5zcGxpdChcIlwiKS5tYXAoKGMpID0+IFtjLCBjXSkpKSwgb2JqZWN0RnJvbUVudHJpZXMoXCIwMTIzNDU2Nzg5XCIuc3BsaXQoXCJcIikubWFwKChuKSA9PiBbbiwgbl0pKSksXG59O1xuZnVuY3Rpb24gb2JqZWN0RnJvbUVudHJpZXMoYXJyYXkpIHtcbiAgICByZXR1cm4gYXJyYXkucmVkdWNlKChtZW1vLCBbaywgdl0pID0+IChPYmplY3QuYXNzaWduKE9iamVjdC5hc3NpZ24oe30sIG1lbW8pLCB7IFtrXTogdiB9KSksIHt9KTtcbn1cblxuY2xhc3MgQXBwbGljYXRpb24ge1xuICAgIGNvbnN0cnVjdG9yKGVsZW1lbnQgPSBkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQsIHNjaGVtYSA9IGRlZmF1bHRTY2hlbWEpIHtcbiAgICAgICAgdGhpcy5sb2dnZXIgPSBjb25zb2xlO1xuICAgICAgICB0aGlzLmRlYnVnID0gZmFsc2U7XG4gICAgICAgIHRoaXMubG9nRGVidWdBY3Rpdml0eSA9IChpZGVudGlmaWVyLCBmdW5jdGlvbk5hbWUsIGRldGFpbCA9IHt9KSA9PiB7XG4gICAgICAgICAgICBpZiAodGhpcy5kZWJ1Zykge1xuICAgICAgICAgICAgICAgIHRoaXMubG9nRm9ybWF0dGVkTWVzc2FnZShpZGVudGlmaWVyLCBmdW5jdGlvbk5hbWUsIGRldGFpbCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH07XG4gICAgICAgIHRoaXMuZWxlbWVudCA9IGVsZW1lbnQ7XG4gICAgICAgIHRoaXMuc2NoZW1hID0gc2NoZW1hO1xuICAgICAgICB0aGlzLmRpc3BhdGNoZXIgPSBuZXcgRGlzcGF0Y2hlcih0aGlzKTtcbiAgICAgICAgdGhpcy5yb3V0ZXIgPSBuZXcgUm91dGVyKHRoaXMpO1xuICAgICAgICB0aGlzLmFjdGlvbkRlc2NyaXB0b3JGaWx0ZXJzID0gT2JqZWN0LmFzc2lnbih7fSwgZGVmYXVsdEFjdGlvbkRlc2NyaXB0b3JGaWx0ZXJzKTtcbiAgICB9XG4gICAgc3RhdGljIHN0YXJ0KGVsZW1lbnQsIHNjaGVtYSkge1xuICAgICAgICBjb25zdCBhcHBsaWNhdGlvbiA9IG5ldyB0aGlzKGVsZW1lbnQsIHNjaGVtYSk7XG4gICAgICAgIGFwcGxpY2F0aW9uLnN0YXJ0KCk7XG4gICAgICAgIHJldHVybiBhcHBsaWNhdGlvbjtcbiAgICB9XG4gICAgYXN5bmMgc3RhcnQoKSB7XG4gICAgICAgIGF3YWl0IGRvbVJlYWR5KCk7XG4gICAgICAgIHRoaXMubG9nRGVidWdBY3Rpdml0eShcImFwcGxpY2F0aW9uXCIsIFwic3RhcnRpbmdcIik7XG4gICAgICAgIHRoaXMuZGlzcGF0Y2hlci5zdGFydCgpO1xuICAgICAgICB0aGlzLnJvdXRlci5zdGFydCgpO1xuICAgICAgICB0aGlzLmxvZ0RlYnVnQWN0aXZpdHkoXCJhcHBsaWNhdGlvblwiLCBcInN0YXJ0XCIpO1xuICAgIH1cbiAgICBzdG9wKCkge1xuICAgICAgICB0aGlzLmxvZ0RlYnVnQWN0aXZpdHkoXCJhcHBsaWNhdGlvblwiLCBcInN0b3BwaW5nXCIpO1xuICAgICAgICB0aGlzLmRpc3BhdGNoZXIuc3RvcCgpO1xuICAgICAgICB0aGlzLnJvdXRlci5zdG9wKCk7XG4gICAgICAgIHRoaXMubG9nRGVidWdBY3Rpdml0eShcImFwcGxpY2F0aW9uXCIsIFwic3RvcFwiKTtcbiAgICB9XG4gICAgcmVnaXN0ZXIoaWRlbnRpZmllciwgY29udHJvbGxlckNvbnN0cnVjdG9yKSB7XG4gICAgICAgIHRoaXMubG9hZCh7IGlkZW50aWZpZXIsIGNvbnRyb2xsZXJDb25zdHJ1Y3RvciB9KTtcbiAgICB9XG4gICAgcmVnaXN0ZXJBY3Rpb25PcHRpb24obmFtZSwgZmlsdGVyKSB7XG4gICAgICAgIHRoaXMuYWN0aW9uRGVzY3JpcHRvckZpbHRlcnNbbmFtZV0gPSBmaWx0ZXI7XG4gICAgfVxuICAgIGxvYWQoaGVhZCwgLi4ucmVzdCkge1xuICAgICAgICBjb25zdCBkZWZpbml0aW9ucyA9IEFycmF5LmlzQXJyYXkoaGVhZCkgPyBoZWFkIDogW2hlYWQsIC4uLnJlc3RdO1xuICAgICAgICBkZWZpbml0aW9ucy5mb3JFYWNoKChkZWZpbml0aW9uKSA9PiB7XG4gICAgICAgICAgICBpZiAoZGVmaW5pdGlvbi5jb250cm9sbGVyQ29uc3RydWN0b3Iuc2hvdWxkTG9hZCkge1xuICAgICAgICAgICAgICAgIHRoaXMucm91dGVyLmxvYWREZWZpbml0aW9uKGRlZmluaXRpb24pO1xuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcbiAgICB9XG4gICAgdW5sb2FkKGhlYWQsIC4uLnJlc3QpIHtcbiAgICAgICAgY29uc3QgaWRlbnRpZmllcnMgPSBBcnJheS5pc0FycmF5KGhlYWQpID8gaGVhZCA6IFtoZWFkLCAuLi5yZXN0XTtcbiAgICAgICAgaWRlbnRpZmllcnMuZm9yRWFjaCgoaWRlbnRpZmllcikgPT4gdGhpcy5yb3V0ZXIudW5sb2FkSWRlbnRpZmllcihpZGVudGlmaWVyKSk7XG4gICAgfVxuICAgIGdldCBjb250cm9sbGVycygpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMucm91dGVyLmNvbnRleHRzLm1hcCgoY29udGV4dCkgPT4gY29udGV4dC5jb250cm9sbGVyKTtcbiAgICB9XG4gICAgZ2V0Q29udHJvbGxlckZvckVsZW1lbnRBbmRJZGVudGlmaWVyKGVsZW1lbnQsIGlkZW50aWZpZXIpIHtcbiAgICAgICAgY29uc3QgY29udGV4dCA9IHRoaXMucm91dGVyLmdldENvbnRleHRGb3JFbGVtZW50QW5kSWRlbnRpZmllcihlbGVtZW50LCBpZGVudGlmaWVyKTtcbiAgICAgICAgcmV0dXJuIGNvbnRleHQgPyBjb250ZXh0LmNvbnRyb2xsZXIgOiBudWxsO1xuICAgIH1cbiAgICBoYW5kbGVFcnJvcihlcnJvciwgbWVzc2FnZSwgZGV0YWlsKSB7XG4gICAgICAgIHZhciBfYTtcbiAgICAgICAgdGhpcy5sb2dnZXIuZXJyb3IoYCVzXFxuXFxuJW9cXG5cXG4lb2AsIG1lc3NhZ2UsIGVycm9yLCBkZXRhaWwpO1xuICAgICAgICAoX2EgPSB3aW5kb3cub25lcnJvcikgPT09IG51bGwgfHwgX2EgPT09IHZvaWQgMCA/IHZvaWQgMCA6IF9hLmNhbGwod2luZG93LCBtZXNzYWdlLCBcIlwiLCAwLCAwLCBlcnJvcik7XG4gICAgfVxuICAgIGxvZ0Zvcm1hdHRlZE1lc3NhZ2UoaWRlbnRpZmllciwgZnVuY3Rpb25OYW1lLCBkZXRhaWwgPSB7fSkge1xuICAgICAgICBkZXRhaWwgPSBPYmplY3QuYXNzaWduKHsgYXBwbGljYXRpb246IHRoaXMgfSwgZGV0YWlsKTtcbiAgICAgICAgdGhpcy5sb2dnZXIuZ3JvdXBDb2xsYXBzZWQoYCR7aWRlbnRpZmllcn0gIyR7ZnVuY3Rpb25OYW1lfWApO1xuICAgICAgICB0aGlzLmxvZ2dlci5sb2coXCJkZXRhaWxzOlwiLCBPYmplY3QuYXNzaWduKHt9LCBkZXRhaWwpKTtcbiAgICAgICAgdGhpcy5sb2dnZXIuZ3JvdXBFbmQoKTtcbiAgICB9XG59XG5mdW5jdGlvbiBkb21SZWFkeSgpIHtcbiAgICByZXR1cm4gbmV3IFByb21pc2UoKHJlc29sdmUpID0+IHtcbiAgICAgICAgaWYgKGRvY3VtZW50LnJlYWR5U3RhdGUgPT0gXCJsb2FkaW5nXCIpIHtcbiAgICAgICAgICAgIGRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoXCJET01Db250ZW50TG9hZGVkXCIsICgpID0+IHJlc29sdmUoKSk7XG4gICAgICAgIH1cbiAgICAgICAgZWxzZSB7XG4gICAgICAgICAgICByZXNvbHZlKCk7XG4gICAgICAgIH1cbiAgICB9KTtcbn1cblxuZnVuY3Rpb24gQ2xhc3NQcm9wZXJ0aWVzQmxlc3NpbmcoY29uc3RydWN0b3IpIHtcbiAgICBjb25zdCBjbGFzc2VzID0gcmVhZEluaGVyaXRhYmxlU3RhdGljQXJyYXlWYWx1ZXMoY29uc3RydWN0b3IsIFwiY2xhc3Nlc1wiKTtcbiAgICByZXR1cm4gY2xhc3Nlcy5yZWR1Y2UoKHByb3BlcnRpZXMsIGNsYXNzRGVmaW5pdGlvbikgPT4ge1xuICAgICAgICByZXR1cm4gT2JqZWN0LmFzc2lnbihwcm9wZXJ0aWVzLCBwcm9wZXJ0aWVzRm9yQ2xhc3NEZWZpbml0aW9uKGNsYXNzRGVmaW5pdGlvbikpO1xuICAgIH0sIHt9KTtcbn1cbmZ1bmN0aW9uIHByb3BlcnRpZXNGb3JDbGFzc0RlZmluaXRpb24oa2V5KSB7XG4gICAgcmV0dXJuIHtcbiAgICAgICAgW2Ake2tleX1DbGFzc2BdOiB7XG4gICAgICAgICAgICBnZXQoKSB7XG4gICAgICAgICAgICAgICAgY29uc3QgeyBjbGFzc2VzIH0gPSB0aGlzO1xuICAgICAgICAgICAgICAgIGlmIChjbGFzc2VzLmhhcyhrZXkpKSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBjbGFzc2VzLmdldChrZXkpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgY29uc3QgYXR0cmlidXRlID0gY2xhc3Nlcy5nZXRBdHRyaWJ1dGVOYW1lKGtleSk7XG4gICAgICAgICAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihgTWlzc2luZyBhdHRyaWJ1dGUgXCIke2F0dHJpYnV0ZX1cImApO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0sXG4gICAgICAgIH0sXG4gICAgICAgIFtgJHtrZXl9Q2xhc3Nlc2BdOiB7XG4gICAgICAgICAgICBnZXQoKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMuY2xhc3Nlcy5nZXRBbGwoa2V5KTtcbiAgICAgICAgICAgIH0sXG4gICAgICAgIH0sXG4gICAgICAgIFtgaGFzJHtjYXBpdGFsaXplKGtleSl9Q2xhc3NgXToge1xuICAgICAgICAgICAgZ2V0KCkge1xuICAgICAgICAgICAgICAgIHJldHVybiB0aGlzLmNsYXNzZXMuaGFzKGtleSk7XG4gICAgICAgICAgICB9LFxuICAgICAgICB9LFxuICAgIH07XG59XG5cbmZ1bmN0aW9uIE91dGxldFByb3BlcnRpZXNCbGVzc2luZyhjb25zdHJ1Y3Rvcikge1xuICAgIGNvbnN0IG91dGxldHMgPSByZWFkSW5oZXJpdGFibGVTdGF0aWNBcnJheVZhbHVlcyhjb25zdHJ1Y3RvciwgXCJvdXRsZXRzXCIpO1xuICAgIHJldHVybiBvdXRsZXRzLnJlZHVjZSgocHJvcGVydGllcywgb3V0bGV0RGVmaW5pdGlvbikgPT4ge1xuICAgICAgICByZXR1cm4gT2JqZWN0LmFzc2lnbihwcm9wZXJ0aWVzLCBwcm9wZXJ0aWVzRm9yT3V0bGV0RGVmaW5pdGlvbihvdXRsZXREZWZpbml0aW9uKSk7XG4gICAgfSwge30pO1xufVxuZnVuY3Rpb24gZ2V0T3V0bGV0Q29udHJvbGxlcihjb250cm9sbGVyLCBlbGVtZW50LCBpZGVudGlmaWVyKSB7XG4gICAgcmV0dXJuIGNvbnRyb2xsZXIuYXBwbGljYXRpb24uZ2V0Q29udHJvbGxlckZvckVsZW1lbnRBbmRJZGVudGlmaWVyKGVsZW1lbnQsIGlkZW50aWZpZXIpO1xufVxuZnVuY3Rpb24gZ2V0Q29udHJvbGxlckFuZEVuc3VyZUNvbm5lY3RlZFNjb3BlKGNvbnRyb2xsZXIsIGVsZW1lbnQsIG91dGxldE5hbWUpIHtcbiAgICBsZXQgb3V0bGV0Q29udHJvbGxlciA9IGdldE91dGxldENvbnRyb2xsZXIoY29udHJvbGxlciwgZWxlbWVudCwgb3V0bGV0TmFtZSk7XG4gICAgaWYgKG91dGxldENvbnRyb2xsZXIpXG4gICAgICAgIHJldHVybiBvdXRsZXRDb250cm9sbGVyO1xuICAgIGNvbnRyb2xsZXIuYXBwbGljYXRpb24ucm91dGVyLnByb3Bvc2VUb0Nvbm5lY3RTY29wZUZvckVsZW1lbnRBbmRJZGVudGlmaWVyKGVsZW1lbnQsIG91dGxldE5hbWUpO1xuICAgIG91dGxldENvbnRyb2xsZXIgPSBnZXRPdXRsZXRDb250cm9sbGVyKGNvbnRyb2xsZXIsIGVsZW1lbnQsIG91dGxldE5hbWUpO1xuICAgIGlmIChvdXRsZXRDb250cm9sbGVyKVxuICAgICAgICByZXR1cm4gb3V0bGV0Q29udHJvbGxlcjtcbn1cbmZ1bmN0aW9uIHByb3BlcnRpZXNGb3JPdXRsZXREZWZpbml0aW9uKG5hbWUpIHtcbiAgICBjb25zdCBjYW1lbGl6ZWROYW1lID0gbmFtZXNwYWNlQ2FtZWxpemUobmFtZSk7XG4gICAgcmV0dXJuIHtcbiAgICAgICAgW2Ake2NhbWVsaXplZE5hbWV9T3V0bGV0YF06IHtcbiAgICAgICAgICAgIGdldCgpIHtcbiAgICAgICAgICAgICAgICBjb25zdCBvdXRsZXRFbGVtZW50ID0gdGhpcy5vdXRsZXRzLmZpbmQobmFtZSk7XG4gICAgICAgICAgICAgICAgY29uc3Qgc2VsZWN0b3IgPSB0aGlzLm91dGxldHMuZ2V0U2VsZWN0b3JGb3JPdXRsZXROYW1lKG5hbWUpO1xuICAgICAgICAgICAgICAgIGlmIChvdXRsZXRFbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgICAgIGNvbnN0IG91dGxldENvbnRyb2xsZXIgPSBnZXRDb250cm9sbGVyQW5kRW5zdXJlQ29ubmVjdGVkU2NvcGUodGhpcywgb3V0bGV0RWxlbWVudCwgbmFtZSk7XG4gICAgICAgICAgICAgICAgICAgIGlmIChvdXRsZXRDb250cm9sbGVyKVxuICAgICAgICAgICAgICAgICAgICAgICAgcmV0dXJuIG91dGxldENvbnRyb2xsZXI7XG4gICAgICAgICAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihgVGhlIHByb3ZpZGVkIG91dGxldCBlbGVtZW50IGlzIG1pc3NpbmcgYW4gb3V0bGV0IGNvbnRyb2xsZXIgXCIke25hbWV9XCIgaW5zdGFuY2UgZm9yIGhvc3QgY29udHJvbGxlciBcIiR7dGhpcy5pZGVudGlmaWVyfVwiYCk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIHRocm93IG5ldyBFcnJvcihgTWlzc2luZyBvdXRsZXQgZWxlbWVudCBcIiR7bmFtZX1cIiBmb3IgaG9zdCBjb250cm9sbGVyIFwiJHt0aGlzLmlkZW50aWZpZXJ9XCIuIFN0aW11bHVzIGNvdWxkbid0IGZpbmQgYSBtYXRjaGluZyBvdXRsZXQgZWxlbWVudCB1c2luZyBzZWxlY3RvciBcIiR7c2VsZWN0b3J9XCIuYCk7XG4gICAgICAgICAgICB9LFxuICAgICAgICB9LFxuICAgICAgICBbYCR7Y2FtZWxpemVkTmFtZX1PdXRsZXRzYF06IHtcbiAgICAgICAgICAgIGdldCgpIHtcbiAgICAgICAgICAgICAgICBjb25zdCBvdXRsZXRzID0gdGhpcy5vdXRsZXRzLmZpbmRBbGwobmFtZSk7XG4gICAgICAgICAgICAgICAgaWYgKG91dGxldHMubGVuZ3RoID4gMCkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm4gb3V0bGV0c1xuICAgICAgICAgICAgICAgICAgICAgICAgLm1hcCgob3V0bGV0RWxlbWVudCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgY29uc3Qgb3V0bGV0Q29udHJvbGxlciA9IGdldENvbnRyb2xsZXJBbmRFbnN1cmVDb25uZWN0ZWRTY29wZSh0aGlzLCBvdXRsZXRFbGVtZW50LCBuYW1lKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmIChvdXRsZXRDb250cm9sbGVyKVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHJldHVybiBvdXRsZXRDb250cm9sbGVyO1xuICAgICAgICAgICAgICAgICAgICAgICAgY29uc29sZS53YXJuKGBUaGUgcHJvdmlkZWQgb3V0bGV0IGVsZW1lbnQgaXMgbWlzc2luZyBhbiBvdXRsZXQgY29udHJvbGxlciBcIiR7bmFtZX1cIiBpbnN0YW5jZSBmb3IgaG9zdCBjb250cm9sbGVyIFwiJHt0aGlzLmlkZW50aWZpZXJ9XCJgLCBvdXRsZXRFbGVtZW50KTtcbiAgICAgICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgICAgICAgICAgICAgIC5maWx0ZXIoKGNvbnRyb2xsZXIpID0+IGNvbnRyb2xsZXIpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICByZXR1cm4gW107XG4gICAgICAgICAgICB9LFxuICAgICAgICB9LFxuICAgICAgICBbYCR7Y2FtZWxpemVkTmFtZX1PdXRsZXRFbGVtZW50YF06IHtcbiAgICAgICAgICAgIGdldCgpIHtcbiAgICAgICAgICAgICAgICBjb25zdCBvdXRsZXRFbGVtZW50ID0gdGhpcy5vdXRsZXRzLmZpbmQobmFtZSk7XG4gICAgICAgICAgICAgICAgY29uc3Qgc2VsZWN0b3IgPSB0aGlzLm91dGxldHMuZ2V0U2VsZWN0b3JGb3JPdXRsZXROYW1lKG5hbWUpO1xuICAgICAgICAgICAgICAgIGlmIChvdXRsZXRFbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiBvdXRsZXRFbGVtZW50O1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgdGhyb3cgbmV3IEVycm9yKGBNaXNzaW5nIG91dGxldCBlbGVtZW50IFwiJHtuYW1lfVwiIGZvciBob3N0IGNvbnRyb2xsZXIgXCIke3RoaXMuaWRlbnRpZmllcn1cIi4gU3RpbXVsdXMgY291bGRuJ3QgZmluZCBhIG1hdGNoaW5nIG91dGxldCBlbGVtZW50IHVzaW5nIHNlbGVjdG9yIFwiJHtzZWxlY3Rvcn1cIi5gKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9LFxuICAgICAgICB9LFxuICAgICAgICBbYCR7Y2FtZWxpemVkTmFtZX1PdXRsZXRFbGVtZW50c2BdOiB7XG4gICAgICAgICAgICBnZXQoKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMub3V0bGV0cy5maW5kQWxsKG5hbWUpO1xuICAgICAgICAgICAgfSxcbiAgICAgICAgfSxcbiAgICAgICAgW2BoYXMke2NhcGl0YWxpemUoY2FtZWxpemVkTmFtZSl9T3V0bGV0YF06IHtcbiAgICAgICAgICAgIGdldCgpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gdGhpcy5vdXRsZXRzLmhhcyhuYW1lKTtcbiAgICAgICAgICAgIH0sXG4gICAgICAgIH0sXG4gICAgfTtcbn1cblxuZnVuY3Rpb24gVGFyZ2V0UHJvcGVydGllc0JsZXNzaW5nKGNvbnN0cnVjdG9yKSB7XG4gICAgY29uc3QgdGFyZ2V0cyA9IHJlYWRJbmhlcml0YWJsZVN0YXRpY0FycmF5VmFsdWVzKGNvbnN0cnVjdG9yLCBcInRhcmdldHNcIik7XG4gICAgcmV0dXJuIHRhcmdldHMucmVkdWNlKChwcm9wZXJ0aWVzLCB0YXJnZXREZWZpbml0aW9uKSA9PiB7XG4gICAgICAgIHJldHVybiBPYmplY3QuYXNzaWduKHByb3BlcnRpZXMsIHByb3BlcnRpZXNGb3JUYXJnZXREZWZpbml0aW9uKHRhcmdldERlZmluaXRpb24pKTtcbiAgICB9LCB7fSk7XG59XG5mdW5jdGlvbiBwcm9wZXJ0aWVzRm9yVGFyZ2V0RGVmaW5pdGlvbihuYW1lKSB7XG4gICAgcmV0dXJuIHtcbiAgICAgICAgW2Ake25hbWV9VGFyZ2V0YF06IHtcbiAgICAgICAgICAgIGdldCgpIHtcbiAgICAgICAgICAgICAgICBjb25zdCB0YXJnZXQgPSB0aGlzLnRhcmdldHMuZmluZChuYW1lKTtcbiAgICAgICAgICAgICAgICBpZiAodGFyZ2V0KSB7XG4gICAgICAgICAgICAgICAgICAgIHJldHVybiB0YXJnZXQ7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICB0aHJvdyBuZXcgRXJyb3IoYE1pc3NpbmcgdGFyZ2V0IGVsZW1lbnQgXCIke25hbWV9XCIgZm9yIFwiJHt0aGlzLmlkZW50aWZpZXJ9XCIgY29udHJvbGxlcmApO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0sXG4gICAgICAgIH0sXG4gICAgICAgIFtgJHtuYW1lfVRhcmdldHNgXToge1xuICAgICAgICAgICAgZ2V0KCkge1xuICAgICAgICAgICAgICAgIHJldHVybiB0aGlzLnRhcmdldHMuZmluZEFsbChuYW1lKTtcbiAgICAgICAgICAgIH0sXG4gICAgICAgIH0sXG4gICAgICAgIFtgaGFzJHtjYXBpdGFsaXplKG5hbWUpfVRhcmdldGBdOiB7XG4gICAgICAgICAgICBnZXQoKSB7XG4gICAgICAgICAgICAgICAgcmV0dXJuIHRoaXMudGFyZ2V0cy5oYXMobmFtZSk7XG4gICAgICAgICAgICB9LFxuICAgICAgICB9LFxuICAgIH07XG59XG5cbmZ1bmN0aW9uIFZhbHVlUHJvcGVydGllc0JsZXNzaW5nKGNvbnN0cnVjdG9yKSB7XG4gICAgY29uc3QgdmFsdWVEZWZpbml0aW9uUGFpcnMgPSByZWFkSW5oZXJpdGFibGVTdGF0aWNPYmplY3RQYWlycyhjb25zdHJ1Y3RvciwgXCJ2YWx1ZXNcIik7XG4gICAgY29uc3QgcHJvcGVydHlEZXNjcmlwdG9yTWFwID0ge1xuICAgICAgICB2YWx1ZURlc2NyaXB0b3JNYXA6IHtcbiAgICAgICAgICAgIGdldCgpIHtcbiAgICAgICAgICAgICAgICByZXR1cm4gdmFsdWVEZWZpbml0aW9uUGFpcnMucmVkdWNlKChyZXN1bHQsIHZhbHVlRGVmaW5pdGlvblBhaXIpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgY29uc3QgdmFsdWVEZXNjcmlwdG9yID0gcGFyc2VWYWx1ZURlZmluaXRpb25QYWlyKHZhbHVlRGVmaW5pdGlvblBhaXIsIHRoaXMuaWRlbnRpZmllcik7XG4gICAgICAgICAgICAgICAgICAgIGNvbnN0IGF0dHJpYnV0ZU5hbWUgPSB0aGlzLmRhdGEuZ2V0QXR0cmlidXRlTmFtZUZvcktleSh2YWx1ZURlc2NyaXB0b3Iua2V5KTtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIE9iamVjdC5hc3NpZ24ocmVzdWx0LCB7IFthdHRyaWJ1dGVOYW1lXTogdmFsdWVEZXNjcmlwdG9yIH0pO1xuICAgICAgICAgICAgICAgIH0sIHt9KTtcbiAgICAgICAgICAgIH0sXG4gICAgICAgIH0sXG4gICAgfTtcbiAgICByZXR1cm4gdmFsdWVEZWZpbml0aW9uUGFpcnMucmVkdWNlKChwcm9wZXJ0aWVzLCB2YWx1ZURlZmluaXRpb25QYWlyKSA9PiB7XG4gICAgICAgIHJldHVybiBPYmplY3QuYXNzaWduKHByb3BlcnRpZXMsIHByb3BlcnRpZXNGb3JWYWx1ZURlZmluaXRpb25QYWlyKHZhbHVlRGVmaW5pdGlvblBhaXIpKTtcbiAgICB9LCBwcm9wZXJ0eURlc2NyaXB0b3JNYXApO1xufVxuZnVuY3Rpb24gcHJvcGVydGllc0ZvclZhbHVlRGVmaW5pdGlvblBhaXIodmFsdWVEZWZpbml0aW9uUGFpciwgY29udHJvbGxlcikge1xuICAgIGNvbnN0IGRlZmluaXRpb24gPSBwYXJzZVZhbHVlRGVmaW5pdGlvblBhaXIodmFsdWVEZWZpbml0aW9uUGFpciwgY29udHJvbGxlcik7XG4gICAgY29uc3QgeyBrZXksIG5hbWUsIHJlYWRlcjogcmVhZCwgd3JpdGVyOiB3cml0ZSB9ID0gZGVmaW5pdGlvbjtcbiAgICByZXR1cm4ge1xuICAgICAgICBbbmFtZV06IHtcbiAgICAgICAgICAgIGdldCgpIHtcbiAgICAgICAgICAgICAgICBjb25zdCB2YWx1ZSA9IHRoaXMuZGF0YS5nZXQoa2V5KTtcbiAgICAgICAgICAgICAgICBpZiAodmFsdWUgIT09IG51bGwpIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIHJlYWQodmFsdWUpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuIGRlZmluaXRpb24uZGVmYXVsdFZhbHVlO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0sXG4gICAgICAgICAgICBzZXQodmFsdWUpIHtcbiAgICAgICAgICAgICAgICBpZiAodmFsdWUgPT09IHVuZGVmaW5lZCkge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmRhdGEuZGVsZXRlKGtleSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICB0aGlzLmRhdGEuc2V0KGtleSwgd3JpdGUodmFsdWUpKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9LFxuICAgICAgICB9LFxuICAgICAgICBbYGhhcyR7Y2FwaXRhbGl6ZShuYW1lKX1gXToge1xuICAgICAgICAgICAgZ2V0KCkge1xuICAgICAgICAgICAgICAgIHJldHVybiB0aGlzLmRhdGEuaGFzKGtleSkgfHwgZGVmaW5pdGlvbi5oYXNDdXN0b21EZWZhdWx0VmFsdWU7XG4gICAgICAgICAgICB9LFxuICAgICAgICB9LFxuICAgIH07XG59XG5mdW5jdGlvbiBwYXJzZVZhbHVlRGVmaW5pdGlvblBhaXIoW3Rva2VuLCB0eXBlRGVmaW5pdGlvbl0sIGNvbnRyb2xsZXIpIHtcbiAgICByZXR1cm4gdmFsdWVEZXNjcmlwdG9yRm9yVG9rZW5BbmRUeXBlRGVmaW5pdGlvbih7XG4gICAgICAgIGNvbnRyb2xsZXIsXG4gICAgICAgIHRva2VuLFxuICAgICAgICB0eXBlRGVmaW5pdGlvbixcbiAgICB9KTtcbn1cbmZ1bmN0aW9uIHBhcnNlVmFsdWVUeXBlQ29uc3RhbnQoY29uc3RhbnQpIHtcbiAgICBzd2l0Y2ggKGNvbnN0YW50KSB7XG4gICAgICAgIGNhc2UgQXJyYXk6XG4gICAgICAgICAgICByZXR1cm4gXCJhcnJheVwiO1xuICAgICAgICBjYXNlIEJvb2xlYW46XG4gICAgICAgICAgICByZXR1cm4gXCJib29sZWFuXCI7XG4gICAgICAgIGNhc2UgTnVtYmVyOlxuICAgICAgICAgICAgcmV0dXJuIFwibnVtYmVyXCI7XG4gICAgICAgIGNhc2UgT2JqZWN0OlxuICAgICAgICAgICAgcmV0dXJuIFwib2JqZWN0XCI7XG4gICAgICAgIGNhc2UgU3RyaW5nOlxuICAgICAgICAgICAgcmV0dXJuIFwic3RyaW5nXCI7XG4gICAgfVxufVxuZnVuY3Rpb24gcGFyc2VWYWx1ZVR5cGVEZWZhdWx0KGRlZmF1bHRWYWx1ZSkge1xuICAgIHN3aXRjaCAodHlwZW9mIGRlZmF1bHRWYWx1ZSkge1xuICAgICAgICBjYXNlIFwiYm9vbGVhblwiOlxuICAgICAgICAgICAgcmV0dXJuIFwiYm9vbGVhblwiO1xuICAgICAgICBjYXNlIFwibnVtYmVyXCI6XG4gICAgICAgICAgICByZXR1cm4gXCJudW1iZXJcIjtcbiAgICAgICAgY2FzZSBcInN0cmluZ1wiOlxuICAgICAgICAgICAgcmV0dXJuIFwic3RyaW5nXCI7XG4gICAgfVxuICAgIGlmIChBcnJheS5pc0FycmF5KGRlZmF1bHRWYWx1ZSkpXG4gICAgICAgIHJldHVybiBcImFycmF5XCI7XG4gICAgaWYgKE9iamVjdC5wcm90b3R5cGUudG9TdHJpbmcuY2FsbChkZWZhdWx0VmFsdWUpID09PSBcIltvYmplY3QgT2JqZWN0XVwiKVxuICAgICAgICByZXR1cm4gXCJvYmplY3RcIjtcbn1cbmZ1bmN0aW9uIHBhcnNlVmFsdWVUeXBlT2JqZWN0KHBheWxvYWQpIHtcbiAgICBjb25zdCB7IGNvbnRyb2xsZXIsIHRva2VuLCB0eXBlT2JqZWN0IH0gPSBwYXlsb2FkO1xuICAgIGNvbnN0IGhhc1R5cGUgPSBpc1NvbWV0aGluZyh0eXBlT2JqZWN0LnR5cGUpO1xuICAgIGNvbnN0IGhhc0RlZmF1bHQgPSBpc1NvbWV0aGluZyh0eXBlT2JqZWN0LmRlZmF1bHQpO1xuICAgIGNvbnN0IGZ1bGxPYmplY3QgPSBoYXNUeXBlICYmIGhhc0RlZmF1bHQ7XG4gICAgY29uc3Qgb25seVR5cGUgPSBoYXNUeXBlICYmICFoYXNEZWZhdWx0O1xuICAgIGNvbnN0IG9ubHlEZWZhdWx0ID0gIWhhc1R5cGUgJiYgaGFzRGVmYXVsdDtcbiAgICBjb25zdCB0eXBlRnJvbU9iamVjdCA9IHBhcnNlVmFsdWVUeXBlQ29uc3RhbnQodHlwZU9iamVjdC50eXBlKTtcbiAgICBjb25zdCB0eXBlRnJvbURlZmF1bHRWYWx1ZSA9IHBhcnNlVmFsdWVUeXBlRGVmYXVsdChwYXlsb2FkLnR5cGVPYmplY3QuZGVmYXVsdCk7XG4gICAgaWYgKG9ubHlUeXBlKVxuICAgICAgICByZXR1cm4gdHlwZUZyb21PYmplY3Q7XG4gICAgaWYgKG9ubHlEZWZhdWx0KVxuICAgICAgICByZXR1cm4gdHlwZUZyb21EZWZhdWx0VmFsdWU7XG4gICAgaWYgKHR5cGVGcm9tT2JqZWN0ICE9PSB0eXBlRnJvbURlZmF1bHRWYWx1ZSkge1xuICAgICAgICBjb25zdCBwcm9wZXJ0eVBhdGggPSBjb250cm9sbGVyID8gYCR7Y29udHJvbGxlcn0uJHt0b2tlbn1gIDogdG9rZW47XG4gICAgICAgIHRocm93IG5ldyBFcnJvcihgVGhlIHNwZWNpZmllZCBkZWZhdWx0IHZhbHVlIGZvciB0aGUgU3RpbXVsdXMgVmFsdWUgXCIke3Byb3BlcnR5UGF0aH1cIiBtdXN0IG1hdGNoIHRoZSBkZWZpbmVkIHR5cGUgXCIke3R5cGVGcm9tT2JqZWN0fVwiLiBUaGUgcHJvdmlkZWQgZGVmYXVsdCB2YWx1ZSBvZiBcIiR7dHlwZU9iamVjdC5kZWZhdWx0fVwiIGlzIG9mIHR5cGUgXCIke3R5cGVGcm9tRGVmYXVsdFZhbHVlfVwiLmApO1xuICAgIH1cbiAgICBpZiAoZnVsbE9iamVjdClcbiAgICAgICAgcmV0dXJuIHR5cGVGcm9tT2JqZWN0O1xufVxuZnVuY3Rpb24gcGFyc2VWYWx1ZVR5cGVEZWZpbml0aW9uKHBheWxvYWQpIHtcbiAgICBjb25zdCB7IGNvbnRyb2xsZXIsIHRva2VuLCB0eXBlRGVmaW5pdGlvbiB9ID0gcGF5bG9hZDtcbiAgICBjb25zdCB0eXBlT2JqZWN0ID0geyBjb250cm9sbGVyLCB0b2tlbiwgdHlwZU9iamVjdDogdHlwZURlZmluaXRpb24gfTtcbiAgICBjb25zdCB0eXBlRnJvbU9iamVjdCA9IHBhcnNlVmFsdWVUeXBlT2JqZWN0KHR5cGVPYmplY3QpO1xuICAgIGNvbnN0IHR5cGVGcm9tRGVmYXVsdFZhbHVlID0gcGFyc2VWYWx1ZVR5cGVEZWZhdWx0KHR5cGVEZWZpbml0aW9uKTtcbiAgICBjb25zdCB0eXBlRnJvbUNvbnN0YW50ID0gcGFyc2VWYWx1ZVR5cGVDb25zdGFudCh0eXBlRGVmaW5pdGlvbik7XG4gICAgY29uc3QgdHlwZSA9IHR5cGVGcm9tT2JqZWN0IHx8IHR5cGVGcm9tRGVmYXVsdFZhbHVlIHx8IHR5cGVGcm9tQ29uc3RhbnQ7XG4gICAgaWYgKHR5cGUpXG4gICAgICAgIHJldHVybiB0eXBlO1xuICAgIGNvbnN0IHByb3BlcnR5UGF0aCA9IGNvbnRyb2xsZXIgPyBgJHtjb250cm9sbGVyfS4ke3R5cGVEZWZpbml0aW9ufWAgOiB0b2tlbjtcbiAgICB0aHJvdyBuZXcgRXJyb3IoYFVua25vd24gdmFsdWUgdHlwZSBcIiR7cHJvcGVydHlQYXRofVwiIGZvciBcIiR7dG9rZW59XCIgdmFsdWVgKTtcbn1cbmZ1bmN0aW9uIGRlZmF1bHRWYWx1ZUZvckRlZmluaXRpb24odHlwZURlZmluaXRpb24pIHtcbiAgICBjb25zdCBjb25zdGFudCA9IHBhcnNlVmFsdWVUeXBlQ29uc3RhbnQodHlwZURlZmluaXRpb24pO1xuICAgIGlmIChjb25zdGFudClcbiAgICAgICAgcmV0dXJuIGRlZmF1bHRWYWx1ZXNCeVR5cGVbY29uc3RhbnRdO1xuICAgIGNvbnN0IGhhc0RlZmF1bHQgPSBoYXNQcm9wZXJ0eSh0eXBlRGVmaW5pdGlvbiwgXCJkZWZhdWx0XCIpO1xuICAgIGNvbnN0IGhhc1R5cGUgPSBoYXNQcm9wZXJ0eSh0eXBlRGVmaW5pdGlvbiwgXCJ0eXBlXCIpO1xuICAgIGNvbnN0IHR5cGVPYmplY3QgPSB0eXBlRGVmaW5pdGlvbjtcbiAgICBpZiAoaGFzRGVmYXVsdClcbiAgICAgICAgcmV0dXJuIHR5cGVPYmplY3QuZGVmYXVsdDtcbiAgICBpZiAoaGFzVHlwZSkge1xuICAgICAgICBjb25zdCB7IHR5cGUgfSA9IHR5cGVPYmplY3Q7XG4gICAgICAgIGNvbnN0IGNvbnN0YW50RnJvbVR5cGUgPSBwYXJzZVZhbHVlVHlwZUNvbnN0YW50KHR5cGUpO1xuICAgICAgICBpZiAoY29uc3RhbnRGcm9tVHlwZSlcbiAgICAgICAgICAgIHJldHVybiBkZWZhdWx0VmFsdWVzQnlUeXBlW2NvbnN0YW50RnJvbVR5cGVdO1xuICAgIH1cbiAgICByZXR1cm4gdHlwZURlZmluaXRpb247XG59XG5mdW5jdGlvbiB2YWx1ZURlc2NyaXB0b3JGb3JUb2tlbkFuZFR5cGVEZWZpbml0aW9uKHBheWxvYWQpIHtcbiAgICBjb25zdCB7IHRva2VuLCB0eXBlRGVmaW5pdGlvbiB9ID0gcGF5bG9hZDtcbiAgICBjb25zdCBrZXkgPSBgJHtkYXNoZXJpemUodG9rZW4pfS12YWx1ZWA7XG4gICAgY29uc3QgdHlwZSA9IHBhcnNlVmFsdWVUeXBlRGVmaW5pdGlvbihwYXlsb2FkKTtcbiAgICByZXR1cm4ge1xuICAgICAgICB0eXBlLFxuICAgICAgICBrZXksXG4gICAgICAgIG5hbWU6IGNhbWVsaXplKGtleSksXG4gICAgICAgIGdldCBkZWZhdWx0VmFsdWUoKSB7XG4gICAgICAgICAgICByZXR1cm4gZGVmYXVsdFZhbHVlRm9yRGVmaW5pdGlvbih0eXBlRGVmaW5pdGlvbik7XG4gICAgICAgIH0sXG4gICAgICAgIGdldCBoYXNDdXN0b21EZWZhdWx0VmFsdWUoKSB7XG4gICAgICAgICAgICByZXR1cm4gcGFyc2VWYWx1ZVR5cGVEZWZhdWx0KHR5cGVEZWZpbml0aW9uKSAhPT0gdW5kZWZpbmVkO1xuICAgICAgICB9LFxuICAgICAgICByZWFkZXI6IHJlYWRlcnNbdHlwZV0sXG4gICAgICAgIHdyaXRlcjogd3JpdGVyc1t0eXBlXSB8fCB3cml0ZXJzLmRlZmF1bHQsXG4gICAgfTtcbn1cbmNvbnN0IGRlZmF1bHRWYWx1ZXNCeVR5cGUgPSB7XG4gICAgZ2V0IGFycmF5KCkge1xuICAgICAgICByZXR1cm4gW107XG4gICAgfSxcbiAgICBib29sZWFuOiBmYWxzZSxcbiAgICBudW1iZXI6IDAsXG4gICAgZ2V0IG9iamVjdCgpIHtcbiAgICAgICAgcmV0dXJuIHt9O1xuICAgIH0sXG4gICAgc3RyaW5nOiBcIlwiLFxufTtcbmNvbnN0IHJlYWRlcnMgPSB7XG4gICAgYXJyYXkodmFsdWUpIHtcbiAgICAgICAgY29uc3QgYXJyYXkgPSBKU09OLnBhcnNlKHZhbHVlKTtcbiAgICAgICAgaWYgKCFBcnJheS5pc0FycmF5KGFycmF5KSkge1xuICAgICAgICAgICAgdGhyb3cgbmV3IFR5cGVFcnJvcihgZXhwZWN0ZWQgdmFsdWUgb2YgdHlwZSBcImFycmF5XCIgYnV0IGluc3RlYWQgZ290IHZhbHVlIFwiJHt2YWx1ZX1cIiBvZiB0eXBlIFwiJHtwYXJzZVZhbHVlVHlwZURlZmF1bHQoYXJyYXkpfVwiYCk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIGFycmF5O1xuICAgIH0sXG4gICAgYm9vbGVhbih2YWx1ZSkge1xuICAgICAgICByZXR1cm4gISh2YWx1ZSA9PSBcIjBcIiB8fCBTdHJpbmcodmFsdWUpLnRvTG93ZXJDYXNlKCkgPT0gXCJmYWxzZVwiKTtcbiAgICB9LFxuICAgIG51bWJlcih2YWx1ZSkge1xuICAgICAgICByZXR1cm4gTnVtYmVyKHZhbHVlLnJlcGxhY2UoL18vZywgXCJcIikpO1xuICAgIH0sXG4gICAgb2JqZWN0KHZhbHVlKSB7XG4gICAgICAgIGNvbnN0IG9iamVjdCA9IEpTT04ucGFyc2UodmFsdWUpO1xuICAgICAgICBpZiAob2JqZWN0ID09PSBudWxsIHx8IHR5cGVvZiBvYmplY3QgIT0gXCJvYmplY3RcIiB8fCBBcnJheS5pc0FycmF5KG9iamVjdCkpIHtcbiAgICAgICAgICAgIHRocm93IG5ldyBUeXBlRXJyb3IoYGV4cGVjdGVkIHZhbHVlIG9mIHR5cGUgXCJvYmplY3RcIiBidXQgaW5zdGVhZCBnb3QgdmFsdWUgXCIke3ZhbHVlfVwiIG9mIHR5cGUgXCIke3BhcnNlVmFsdWVUeXBlRGVmYXVsdChvYmplY3QpfVwiYCk7XG4gICAgICAgIH1cbiAgICAgICAgcmV0dXJuIG9iamVjdDtcbiAgICB9LFxuICAgIHN0cmluZyh2YWx1ZSkge1xuICAgICAgICByZXR1cm4gdmFsdWU7XG4gICAgfSxcbn07XG5jb25zdCB3cml0ZXJzID0ge1xuICAgIGRlZmF1bHQ6IHdyaXRlU3RyaW5nLFxuICAgIGFycmF5OiB3cml0ZUpTT04sXG4gICAgb2JqZWN0OiB3cml0ZUpTT04sXG59O1xuZnVuY3Rpb24gd3JpdGVKU09OKHZhbHVlKSB7XG4gICAgcmV0dXJuIEpTT04uc3RyaW5naWZ5KHZhbHVlKTtcbn1cbmZ1bmN0aW9uIHdyaXRlU3RyaW5nKHZhbHVlKSB7XG4gICAgcmV0dXJuIGAke3ZhbHVlfWA7XG59XG5cbmNsYXNzIENvbnRyb2xsZXIge1xuICAgIGNvbnN0cnVjdG9yKGNvbnRleHQpIHtcbiAgICAgICAgdGhpcy5jb250ZXh0ID0gY29udGV4dDtcbiAgICB9XG4gICAgc3RhdGljIGdldCBzaG91bGRMb2FkKCkge1xuICAgICAgICByZXR1cm4gdHJ1ZTtcbiAgICB9XG4gICAgc3RhdGljIGFmdGVyTG9hZChfaWRlbnRpZmllciwgX2FwcGxpY2F0aW9uKSB7XG4gICAgICAgIHJldHVybjtcbiAgICB9XG4gICAgZ2V0IGFwcGxpY2F0aW9uKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5jb250ZXh0LmFwcGxpY2F0aW9uO1xuICAgIH1cbiAgICBnZXQgc2NvcGUoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLmNvbnRleHQuc2NvcGU7XG4gICAgfVxuICAgIGdldCBlbGVtZW50KCkge1xuICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5lbGVtZW50O1xuICAgIH1cbiAgICBnZXQgaWRlbnRpZmllcigpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuaWRlbnRpZmllcjtcbiAgICB9XG4gICAgZ2V0IHRhcmdldHMoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnNjb3BlLnRhcmdldHM7XG4gICAgfVxuICAgIGdldCBvdXRsZXRzKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5zY29wZS5vdXRsZXRzO1xuICAgIH1cbiAgICBnZXQgY2xhc3NlcygpIHtcbiAgICAgICAgcmV0dXJuIHRoaXMuc2NvcGUuY2xhc3NlcztcbiAgICB9XG4gICAgZ2V0IGRhdGEoKSB7XG4gICAgICAgIHJldHVybiB0aGlzLnNjb3BlLmRhdGE7XG4gICAgfVxuICAgIGluaXRpYWxpemUoKSB7XG4gICAgfVxuICAgIGNvbm5lY3QoKSB7XG4gICAgfVxuICAgIGRpc2Nvbm5lY3QoKSB7XG4gICAgfVxuICAgIGRpc3BhdGNoKGV2ZW50TmFtZSwgeyB0YXJnZXQgPSB0aGlzLmVsZW1lbnQsIGRldGFpbCA9IHt9LCBwcmVmaXggPSB0aGlzLmlkZW50aWZpZXIsIGJ1YmJsZXMgPSB0cnVlLCBjYW5jZWxhYmxlID0gdHJ1ZSwgfSA9IHt9KSB7XG4gICAgICAgIGNvbnN0IHR5cGUgPSBwcmVmaXggPyBgJHtwcmVmaXh9OiR7ZXZlbnROYW1lfWAgOiBldmVudE5hbWU7XG4gICAgICAgIGNvbnN0IGV2ZW50ID0gbmV3IEN1c3RvbUV2ZW50KHR5cGUsIHsgZGV0YWlsLCBidWJibGVzLCBjYW5jZWxhYmxlIH0pO1xuICAgICAgICB0YXJnZXQuZGlzcGF0Y2hFdmVudChldmVudCk7XG4gICAgICAgIHJldHVybiBldmVudDtcbiAgICB9XG59XG5Db250cm9sbGVyLmJsZXNzaW5ncyA9IFtcbiAgICBDbGFzc1Byb3BlcnRpZXNCbGVzc2luZyxcbiAgICBUYXJnZXRQcm9wZXJ0aWVzQmxlc3NpbmcsXG4gICAgVmFsdWVQcm9wZXJ0aWVzQmxlc3NpbmcsXG4gICAgT3V0bGV0UHJvcGVydGllc0JsZXNzaW5nLFxuXTtcbkNvbnRyb2xsZXIudGFyZ2V0cyA9IFtdO1xuQ29udHJvbGxlci5vdXRsZXRzID0gW107XG5Db250cm9sbGVyLnZhbHVlcyA9IHt9O1xuXG5leHBvcnQgeyBBcHBsaWNhdGlvbiwgQXR0cmlidXRlT2JzZXJ2ZXIsIENvbnRleHQsIENvbnRyb2xsZXIsIEVsZW1lbnRPYnNlcnZlciwgSW5kZXhlZE11bHRpbWFwLCBNdWx0aW1hcCwgU2VsZWN0b3JPYnNlcnZlciwgU3RyaW5nTWFwT2JzZXJ2ZXIsIFRva2VuTGlzdE9ic2VydmVyLCBWYWx1ZUxpc3RPYnNlcnZlciwgYWRkLCBkZWZhdWx0U2NoZW1hLCBkZWwsIGZldGNoLCBwcnVuZSB9O1xuIiwiaW1wb3J0IHsgQ29udHJvbGxlciB9IGZyb20gJ0Bob3R3aXJlZC9zdGltdWx1cyc7XG5cbmV4cG9ydCBkZWZhdWx0IGNsYXNzIGV4dGVuZHMgQ29udHJvbGxlciB7XG4gICAgc3RhdGljIHZhbHVlcyA9IHtcbiAgICAgICAgY29udGVudDogU3RyaW5nXG4gICAgfVxuXG4gICAgd3JpdGUgKCkge1xuICAgICAgICBpZiAobmF2aWdhdG9yLmNsaXBib2FyZCAmJiBuYXZpZ2F0b3IuY2xpcGJvYXJkLndyaXRlVGV4dCkge1xuICAgICAgICAgICAgbmF2aWdhdG9yLmNsaXBib2FyZC53cml0ZVRleHQodGhpcy5jb250ZW50VmFsdWUpLmNhdGNoKHRoaXMuY2xpcGJvYXJkRmFsbGJhY2suYmluZCh0aGlzKSk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICB0aGlzLmNsaXBib2FyZEZhbGxiYWNrKCk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICBjbGlwYm9hcmRGYWxsYmFjayAgKCkge1xuICAgICAgICBjb25zdCBpbnB1dCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2lucHV0Jyk7XG4gICAgICAgIGlucHV0LnZhbHVlID0gdGhpcy5jb250ZW50VmFsdWU7XG4gICAgICAgIGRvY3VtZW50LmJvZHkuYXBwZW5kQ2hpbGQoaW5wdXQpO1xuICAgICAgICBpbnB1dC5zZWxlY3QoKTtcbiAgICAgICAgaW5wdXQuc2V0U2VsZWN0aW9uUmFuZ2UoMCwgOTk5OTkpO1xuICAgICAgICBkb2N1bWVudC5leGVjQ29tbWFuZCgnY29weScpO1xuICAgICAgICBkb2N1bWVudC5ib2R5LnJlbW92ZUNoaWxkKGlucHV0KTtcbiAgICB9XG59XG4iLCJpbXBvcnQgeyBDb250cm9sbGVyIH0gZnJvbSAnQGhvdHdpcmVkL3N0aW11bHVzJztcblxuY29uc3QgcHJlZmVyc0RhcmsgPSAoKSA9PiB7XG4gICAgY29uc3QgcHJlZmVyc0RhcmsgPSBsb2NhbFN0b3JhZ2UuZ2V0SXRlbSgnY29udGFvLS1wcmVmZXJzLWRhcmsnKTtcblxuICAgIGlmIChudWxsID09PSBwcmVmZXJzRGFyaykge1xuICAgICAgICByZXR1cm4gISF3aW5kb3cubWF0Y2hNZWRpYSgnKHByZWZlcnMtY29sb3Itc2NoZW1lOiBkYXJrKScpLm1hdGNoZXM7XG4gICAgfVxuXG4gICAgcmV0dXJuIHByZWZlcnNEYXJrID09PSAndHJ1ZSc7XG59XG5cbmNvbnN0IHNldENvbG9yU2NoZW1lID0gKCkgPT4ge1xuICAgIGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5kYXRhc2V0LmNvbG9yU2NoZW1lID0gcHJlZmVyc0RhcmsoKSA/ICdkYXJrJyA6ICdsaWdodCc7XG59O1xuXG53aW5kb3cubWF0Y2hNZWRpYSgnKHByZWZlcnMtY29sb3Itc2NoZW1lOiBkYXJrKScpLmFkZEV2ZW50TGlzdGVuZXIoJ2NoYW5nZScsIHNldENvbG9yU2NoZW1lKTtcbnNldENvbG9yU2NoZW1lKCk7XG5cbmV4cG9ydCBkZWZhdWx0IGNsYXNzIGV4dGVuZHMgQ29udHJvbGxlciB7XG4gICAgc3RhdGljIHRhcmdldHMgPSBbJ2xhYmVsJ107XG5cbiAgICBzdGF0aWMgdmFsdWVzID0ge1xuICAgICAgICBpMThuOiB7XG4gICAgICAgICAgICB0eXBlOiBPYmplY3QsXG4gICAgICAgICAgICBkZWZhdWx0OiB7IGxpZ2h0OiAnRGlzYWJsZSBkYXJrIG1vZGUnLCBkYXJrOiAnRW5hYmxlIGRhcmsgbW9kZScgfVxuICAgICAgICB9XG4gICAgfTtcblxuICAgIGluaXRpYWxpemUgKCkge1xuICAgICAgICB0aGlzLnRvZ2dsZSA9IHRoaXMudG9nZ2xlLmJpbmQodGhpcyk7XG4gICAgICAgIHRoaXMuc2V0TGFiZWwgPSB0aGlzLnNldExhYmVsLmJpbmQodGhpcyk7XG4gICAgfVxuXG4gICAgY29ubmVjdCAoKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIHRoaXMudG9nZ2xlKTtcblxuICAgICAgICB3aW5kb3cubWF0Y2hNZWRpYSgnKHByZWZlcnMtY29sb3Itc2NoZW1lOiBkYXJrKScpLmFkZEV2ZW50TGlzdGVuZXIoJ2NoYW5nZScsIHRoaXMuc2V0TGFiZWwpO1xuICAgICAgICB0aGlzLnNldExhYmVsKCk7XG4gICAgfVxuXG4gICAgZGlzY29ubmVjdCAoKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudC5yZW1vdmVFdmVudExpc3RlbmVyKCdjbGljaycsIHRoaXMudG9nZ2xlKTtcbiAgICB9XG5cbiAgICB0b2dnbGUgKGUpIHtcbiAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuXG4gICAgICAgIGNvbnN0IGlzRGFyayA9ICFwcmVmZXJzRGFyaygpO1xuXG4gICAgICAgIGlmIChpc0RhcmsgPT09IHdpbmRvdy5tYXRjaE1lZGlhKCcocHJlZmVycy1jb2xvci1zY2hlbWU6IGRhcmspJykubWF0Y2hlcykge1xuICAgICAgICAgICAgbG9jYWxTdG9yYWdlLnJlbW92ZUl0ZW0oJ2NvbnRhby0tcHJlZmVycy1kYXJrJyk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICBsb2NhbFN0b3JhZ2Uuc2V0SXRlbSgnY29udGFvLS1wcmVmZXJzLWRhcmsnLCBTdHJpbmcoaXNEYXJrKSk7XG4gICAgICAgIH1cblxuICAgICAgICBzZXRDb2xvclNjaGVtZSgpO1xuXG4gICAgICAgIC8vIENoYW5nZSB0aGUgbGFiZWwgYWZ0ZXIgdGhlIGRyb3Bkb3duIGlzIGhpZGRlblxuICAgICAgICBzZXRUaW1lb3V0KHRoaXMuc2V0TGFiZWwsIDMwMCk7XG4gICAgfVxuXG4gICAgc2V0TGFiZWwgKCkge1xuICAgICAgICBpZiAoIXRoaXMuaGFzTGFiZWxUYXJnZXQpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIGNvbnN0IGxhYmVsID0gdGhpcy5pMThuVmFsdWVbcHJlZmVyc0RhcmsoKSA/ICdsaWdodCcgOiAnZGFyayddO1xuXG4gICAgICAgIHRoaXMubGFiZWxUYXJnZXQudGl0bGUgPSBsYWJlbDtcbiAgICAgICAgdGhpcy5sYWJlbFRhcmdldC5pbm5lclRleHQgPSBsYWJlbDtcbiAgICB9XG59XG4iLCJpbXBvcnQgeyBDb250cm9sbGVyIH0gZnJvbSAnQGhvdHdpcmVkL3N0aW11bHVzJztcblxuZXhwb3J0IGRlZmF1bHQgY2xhc3MgZXh0ZW5kcyBDb250cm9sbGVyIHtcbiAgICBzdGF0aWMgdmFsdWVzID0ge1xuICAgICAgICBjb25maWc6IE9iamVjdCxcbiAgICB9XG5cbiAgICBpbml0aWFsaXplICgpIHtcbiAgICAgICAgdGhpcy51cGRhdGVXaXphcmQgPSB0aGlzLnVwZGF0ZVdpemFyZC5iaW5kKHRoaXMpO1xuICAgICAgICB0aGlzLm9wZW5Nb2RhbCA9IHRoaXMub3Blbk1vZGFsLmJpbmQodGhpcyk7XG4gICAgfVxuXG4gICAgY29ubmVjdCAoKSB7XG4gICAgICAgIHRoaXMuc2VsZWN0ID0gdGhpcy5lbGVtZW50LnF1ZXJ5U2VsZWN0b3IoJ3NlbGVjdCcpO1xuICAgICAgICB0aGlzLmJ1dHRvbiA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2J1dHRvbicpO1xuICAgICAgICB0aGlzLmJ1dHRvbi50eXBlID0gJ2J1dHRvbic7XG4gICAgICAgIHRoaXMuYnV0dG9uLnRpdGxlID0gJyc7XG4gICAgICAgIHRoaXMuYnV0dG9uSW1hZ2UgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdpbWcnKTtcbiAgICAgICAgdGhpcy5idXR0b24uYXBwZW5kKHRoaXMuYnV0dG9uSW1hZ2UpO1xuICAgICAgICB0aGlzLmVsZW1lbnQucGFyZW50Tm9kZS5jbGFzc0xpc3QuYWRkKCd3aXphcmQnKTtcbiAgICAgICAgdGhpcy5lbGVtZW50LmFmdGVyKHRoaXMuYnV0dG9uKTtcblxuICAgICAgICB0aGlzLnNlbGVjdC5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCB0aGlzLnVwZGF0ZVdpemFyZCk7XG4gICAgICAgIHRoaXMuYnV0dG9uLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgdGhpcy5vcGVuTW9kYWwpO1xuXG4gICAgICAgIHRoaXMudXBkYXRlV2l6YXJkKCk7XG4gICAgfVxuXG4gICAgZGlzY29ubmVjdCAoKSB7XG4gICAgICAgIHRoaXMuZWxlbWVudC5wYXJlbnROb2RlLmNsYXNzTGlzdC5yZW1vdmUoJ3dpemFyZCcpO1xuICAgICAgICB0aGlzLnNlbGVjdC5yZW1vdmVFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCB0aGlzLnVwZGF0ZVdpemFyZCk7XG4gICAgICAgIHRoaXMuYnV0dG9uSW1hZ2UucmVtb3ZlKCk7XG4gICAgICAgIHRoaXMuYnV0dG9uLnJlbW92ZSgpO1xuICAgIH1cblxuICAgIHVwZGF0ZVdpemFyZCAoKSB7XG4gICAgICAgIGlmICh0aGlzLmNhbkVkaXQoKSkge1xuICAgICAgICAgICAgdGhpcy5idXR0b24udGl0bGUgPSB0aGlzLmNvbmZpZ1ZhbHVlLnRpdGxlO1xuICAgICAgICAgICAgdGhpcy5idXR0b24uZGlzYWJsZWQgPSBmYWxzZTtcbiAgICAgICAgICAgIHRoaXMuYnV0dG9uSW1hZ2Uuc3JjID0gdGhpcy5jb25maWdWYWx1ZS5pY29uO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5idXR0b24udGl0bGUgPSAnJztcbiAgICAgICAgICAgIHRoaXMuYnV0dG9uLmRpc2FibGVkID0gdHJ1ZTtcbiAgICAgICAgICAgIHRoaXMuYnV0dG9uSW1hZ2Uuc3JjID0gdGhpcy5jb25maWdWYWx1ZS5pY29uRGlzYWJsZWQ7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICBvcGVuTW9kYWwgKCkge1xuICAgICAgICBCYWNrZW5kLm9wZW5Nb2RhbElmcmFtZSh7XG4gICAgICAgICAgICB0aXRsZTogdGhpcy5jb25maWdWYWx1ZS50aXRsZSxcbiAgICAgICAgICAgIHVybDogYCR7IHRoaXMuY29uZmlnVmFsdWUuaHJlZiB9JmlkPSR7IHRoaXMuc2VsZWN0LnZhbHVlIH1gXG4gICAgICAgIH0pO1xuICAgIH1cblxuICAgIGNhbkVkaXQgKCkge1xuICAgICAgICByZXR1cm4gdGhpcy5jb25maWdWYWx1ZS5pZHMuaW5jbHVkZXMoTnVtYmVyKHRoaXMuc2VsZWN0LnZhbHVlKSk7XG4gICAgfVxufVxuIiwiaW1wb3J0IHsgQ29udHJvbGxlciB9IGZyb20gJ0Bob3R3aXJlZC9zdGltdWx1cyc7XG5cbmV4cG9ydCBkZWZhdWx0IGNsYXNzIGV4dGVuZHMgQ29udHJvbGxlciB7XG4gICAgc3RhdGljIHRhcmdldHMgPSBbJ25hdmlnYXRpb24nLCAnc2VjdGlvbiddO1xuXG4gICAgY29ubmVjdCAoKSB7XG4gICAgICAgIHRoaXMucmVidWlsZE5hdmlnYXRpb24oKTtcbiAgICAgICAgdGhpcy5jb25uZWN0ZWQgPSB0cnVlO1xuICAgIH1cblxuICAgIHNlY3Rpb25UYXJnZXRDb25uZWN0ZWQgKCkge1xuICAgICAgICBpZiAoIXRoaXMuY29ubmVjdGVkKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICB0aGlzLnJlYnVpbGROYXZpZ2F0aW9uKCk7XG4gICAgfVxuXG4gICAgcmVidWlsZE5hdmlnYXRpb24gKCkge1xuICAgICAgICBpZiAoIXRoaXMuaGFzTmF2aWdhdGlvblRhcmdldCkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgY29uc3QgbGlua3MgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCd1bCcpO1xuXG4gICAgICAgIHRoaXMuc2VjdGlvblRhcmdldHMuZm9yRWFjaCgoZWwpID0+IHtcbiAgICAgICAgICAgIGNvbnN0IGFjdGlvbiA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2J1dHRvbicpO1xuICAgICAgICAgICAgYWN0aW9uLmlubmVyVGV4dCA9IGVsLmdldEF0dHJpYnV0ZShgZGF0YS0ke3RoaXMuaWRlbnRpZmllcn0tbGFiZWwtdmFsdWVgKTtcblxuICAgICAgICAgICAgYWN0aW9uLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgKGV2ZW50KSA9PiB7XG4gICAgICAgICAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgICAgICAgICB0aGlzLmRpc3BhdGNoKCdzY3JvbGx0bycsIHsgdGFyZ2V0OiBlbCB9KTtcbiAgICAgICAgICAgICAgICBlbC5zY3JvbGxJbnRvVmlldygpO1xuICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgIGNvbnN0IGxpID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnbGknKTtcbiAgICAgICAgICAgIGxpLmFwcGVuZChhY3Rpb24pO1xuXG4gICAgICAgICAgICBsaW5rcy5hcHBlbmQobGkpO1xuICAgICAgICB9KTtcblxuICAgICAgICB0aGlzLm5hdmlnYXRpb25UYXJnZXQucmVwbGFjZUNoaWxkcmVuKGxpbmtzKTtcbiAgICB9XG59XG4iLCJpbXBvcnQgeyBDb250cm9sbGVyIH0gZnJvbSAnQGhvdHdpcmVkL3N0aW11bHVzJztcblxuZXhwb3J0IGRlZmF1bHQgY2xhc3MgZXh0ZW5kcyBDb250cm9sbGVyIHtcbiAgICBzdGF0aWMgdmFsdWVzID0ge1xuICAgICAgICBtYXg6IE51bWJlcixcbiAgICAgICAgZXhwYW5kOiBTdHJpbmcsXG4gICAgICAgIGNvbGxhcHNlOiBTdHJpbmcsXG4gICAgICAgIGV4cGFuZEFsbDogU3RyaW5nLFxuICAgICAgICBleHBhbmRBbGxUaXRsZTogU3RyaW5nLFxuICAgICAgICBjb2xsYXBzZUFsbDogU3RyaW5nLFxuICAgICAgICBjb2xsYXBzZUFsbFRpdGxlOiBTdHJpbmcsXG4gICAgfVxuXG4gICAgc3RhdGljIHRhcmdldHMgPSBbJ29wZXJhdGlvbicsICdub2RlJ107XG5cbiAgICBpbml0aWFsaXplICgpIHtcbiAgICAgICAgc3VwZXIuaW5pdGlhbGl6ZSgpO1xuICAgICAgICB0aGlzLnRvZ2dsZXJNYXAgPSBuZXcgV2Vha01hcCgpO1xuICAgICAgICB0aGlzLm5leHRJZCA9IDE7XG4gICAgfVxuXG4gICAgb3BlcmF0aW9uVGFyZ2V0Q29ubmVjdGVkICgpIHtcbiAgICAgICAgdGhpcy51cGRhdGVPcGVyYXRpb24oKTtcbiAgICB9XG5cbiAgICBub2RlVGFyZ2V0Q29ubmVjdGVkIChub2RlKSB7XG4gICAgICAgIGNvbnN0IHN0eWxlID0gd2luZG93LmdldENvbXB1dGVkU3R5bGUobm9kZSwgbnVsbCk7XG4gICAgICAgIGNvbnN0IHBhZGRpbmcgPSBwYXJzZUZsb2F0KHN0eWxlLnBhZGRpbmdUb3ApICsgcGFyc2VGbG9hdChzdHlsZS5wYWRkaW5nQm90dG9tKTtcbiAgICAgICAgY29uc3QgaGVpZ2h0ID0gbm9kZS5jbGllbnRIZWlnaHQgLSBwYWRkaW5nO1xuXG4gICAgICAgIC8vIFJlc2l6ZSB0aGUgZWxlbWVudCBpZiBpdCBpcyBoaWdoZXIgdGhhbiB0aGUgbWF4aW11bSBoZWlnaHRcbiAgICAgICAgaWYgKHRoaXMubWF4VmFsdWUgPiBoZWlnaHQpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmICghbm9kZS5pZCkge1xuICAgICAgICAgICAgbm9kZS5pZCA9IGBsaW1pdC1oZWlnaHQtJHt0aGlzLm5leHRJZCsrfWA7XG4gICAgICAgIH1cblxuICAgICAgICBub2RlLnN0eWxlLm92ZXJmbG93ID0gJ2hpZGRlbic7XG4gICAgICAgIG5vZGUuc3R5bGUubWF4SGVpZ2h0ID0gYCR7dGhpcy5tYXhWYWx1ZX1weGA7XG5cbiAgICAgICAgY29uc3QgYnV0dG9uID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnYnV0dG9uJyk7XG4gICAgICAgIGJ1dHRvbi5zZXRBdHRyaWJ1dGUoJ3R5cGUnLCAnYnV0dG9uJyk7XG4gICAgICAgIGJ1dHRvbi50aXRsZSA9IHRoaXMuZXhwYW5kVmFsdWU7XG4gICAgICAgIGJ1dHRvbi5pbm5lckhUTUwgPSAnPHNwYW4+Li4uPC9zcGFuPic7XG4gICAgICAgIGJ1dHRvbi5jbGFzc0xpc3QuYWRkKCd1bnNlbGVjdGFibGUnKTtcbiAgICAgICAgYnV0dG9uLnNldEF0dHJpYnV0ZSgnYXJpYS1leHBhbmRlZCcsICdmYWxzZScpO1xuICAgICAgICBidXR0b24uc2V0QXR0cmlidXRlKCdhcmlhLWNvbnRyb2xzJywgbm9kZS5pZCk7XG5cbiAgICAgICAgYnV0dG9uLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgKGV2ZW50KSA9PiB7XG4gICAgICAgICAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgdGhpcy50b2dnbGUobm9kZSk7XG4gICAgICAgICAgICB0aGlzLnVwZGF0ZU9wZXJhdGlvbihldmVudCk7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIGNvbnN0IHRvZ2dsZXIgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdkaXYnKTtcbiAgICAgICAgdG9nZ2xlci5jbGFzc0xpc3QuYWRkKCdsaW1pdF90b2dnbGVyJyk7XG4gICAgICAgIHRvZ2dsZXIuYXBwZW5kKGJ1dHRvbik7XG5cbiAgICAgICAgdGhpcy50b2dnbGVyTWFwLnNldChub2RlLCB0b2dnbGVyKTtcblxuICAgICAgICBub2RlLmFwcGVuZCh0b2dnbGVyKTtcbiAgICAgICAgdGhpcy51cGRhdGVPcGVyYXRpb24oKTtcbiAgICB9XG5cbiAgICBub2RlVGFyZ2V0RGlzY29ubmVjdGVkIChub2RlKSB7XG4gICAgICAgIGlmICghdGhpcy50b2dnbGVyTWFwLmhhcyhub2RlKSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgdGhpcy50b2dnbGVyTWFwLmdldChub2RlKS5yZW1vdmUoKTtcbiAgICAgICAgdGhpcy50b2dnbGVyTWFwLmRlbGV0ZShub2RlKTtcbiAgICAgICAgbm9kZS5zdHlsZS5vdmVyZmxvdyA9ICcnO1xuICAgICAgICBub2RlLnN0eWxlLm1heEhlaWdodCA9ICcnO1xuICAgIH1cblxuICAgIHRvZ2dsZSAobm9kZSkge1xuICAgICAgICBpZiAobm9kZS5zdHlsZS5tYXhIZWlnaHQgPT09ICcnKSB7XG4gICAgICAgICAgICB0aGlzLmNvbGxhcHNlKG5vZGUpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5leHBhbmQobm9kZSk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICBleHBhbmQgKG5vZGUpIHtcbiAgICAgICAgaWYgKCF0aGlzLnRvZ2dsZXJNYXAuaGFzKG5vZGUpKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICBub2RlLnN0eWxlLm1heEhlaWdodCA9ICcnO1xuICAgICAgICBjb25zdCBidXR0b24gPSB0aGlzLnRvZ2dsZXJNYXAuZ2V0KG5vZGUpLnF1ZXJ5U2VsZWN0b3IoJ2J1dHRvbicpO1xuICAgICAgICBidXR0b24udGl0bGUgPSB0aGlzLmNvbGxhcHNlVmFsdWU7XG4gICAgICAgIGJ1dHRvbi5zZXRBdHRyaWJ1dGUoJ2FyaWEtZXhwYW5kZWQnLCAndHJ1ZScpO1xuICAgIH1cblxuICAgIGNvbGxhcHNlIChub2RlKSB7XG4gICAgICAgIGlmICghdGhpcy50b2dnbGVyTWFwLmhhcyhub2RlKSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgbm9kZS5zdHlsZS5tYXhIZWlnaHQgPSBgJHt0aGlzLm1heFZhbHVlfXB4YDtcbiAgICAgICAgY29uc3QgYnV0dG9uID0gdGhpcy50b2dnbGVyTWFwLmdldChub2RlKS5xdWVyeVNlbGVjdG9yKCdidXR0b24nKTtcbiAgICAgICAgYnV0dG9uLnRpdGxlID0gdGhpcy5leHBhbmRWYWx1ZTtcbiAgICAgICAgYnV0dG9uLnNldEF0dHJpYnV0ZSgnYXJpYS1leHBhbmRlZCcsICdmYWxzZScpO1xuICAgIH1cblxuICAgIHRvZ2dsZUFsbCAoZXZlbnQpIHtcbiAgICAgICAgZXZlbnQucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgY29uc3QgaXNFeHBhbmRlZCA9IHRoaXMuaGFzRXhwYW5kZWQoKSBeIGV2ZW50LmFsdEtleTtcblxuICAgICAgICB0aGlzLm5vZGVUYXJnZXRzLmZvckVhY2goKG5vZGUpID0+IHtcbiAgICAgICAgICAgIGlmIChpc0V4cGFuZGVkKSB7XG4gICAgICAgICAgICAgICAgdGhpcy5jb2xsYXBzZShub2RlKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgdGhpcy5leHBhbmQobm9kZSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuXG4gICAgICAgIHRoaXMudXBkYXRlT3BlcmF0aW9uKGV2ZW50KTtcbiAgICB9XG5cbiAgICBrZXlwcmVzcyAoZXZlbnQpIHtcbiAgICAgICAgdGhpcy51cGRhdGVPcGVyYXRpb24oZXZlbnQpO1xuICAgIH1cblxuICAgIHVwZGF0ZU9wZXJhdGlvbiAoZXZlbnQpIHtcbiAgICAgICAgaWYgKCF0aGlzLmhhc09wZXJhdGlvblRhcmdldCkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgY29uc3QgaGFzVG9nZ2xlcnMgPSAhIXRoaXMubm9kZVRhcmdldHMuZmluZCgoZWwpID0+IHRoaXMudG9nZ2xlck1hcC5oYXMoZWwpKTtcbiAgICAgICAgY29uc3QgZXhwYW5kZWQgPSB0aGlzLmhhc0V4cGFuZGVkKCk7XG5cbiAgICAgICAgdGhpcy5vcGVyYXRpb25UYXJnZXQuc3R5bGUuZGlzcGxheSA9IGhhc1RvZ2dsZXJzID8gJycgOiAnbm9uZSc7XG4gICAgICAgIHRoaXMub3BlcmF0aW9uVGFyZ2V0LnNldEF0dHJpYnV0ZSgnYXJpYS1jb250cm9scycsIHRoaXMubm9kZVRhcmdldHMubWFwKChlbCkgPT4gZWwuaWQpLmpvaW4oJyAnKSk7XG4gICAgICAgIHRoaXMub3BlcmF0aW9uVGFyZ2V0LnNldEF0dHJpYnV0ZSgnYXJpYS1leHBhbmRlZCcsIGV4cGFuZGVkID8gJ3RydWUnIDogJ2ZhbHNlJyk7XG5cbiAgICAgICAgaWYgKGV4cGFuZGVkIF4gKGV2ZW50ID8gZXZlbnQuYWx0S2V5IDogZmFsc2UpKSB7XG4gICAgICAgICAgICB0aGlzLm9wZXJhdGlvblRhcmdldC5pbm5lclRleHQgPSB0aGlzLmNvbGxhcHNlQWxsVmFsdWU7XG4gICAgICAgICAgICB0aGlzLm9wZXJhdGlvblRhcmdldC50aXRsZSA9IHRoaXMuY29sbGFwc2VBbGxUaXRsZVZhbHVlO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5vcGVyYXRpb25UYXJnZXQuaW5uZXJUZXh0ID0gdGhpcy5leHBhbmRBbGxWYWx1ZTtcbiAgICAgICAgICAgIHRoaXMub3BlcmF0aW9uVGFyZ2V0LnRpdGxlID0gdGhpcy5leHBhbmRBbGxUaXRsZVZhbHVlO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgaGFzRXhwYW5kZWQgKCkge1xuICAgICAgICByZXR1cm4gISF0aGlzLm5vZGVUYXJnZXRzLmZpbmQoKGVsKSA9PiB0aGlzLnRvZ2dsZXJNYXAuaGFzKGVsKSAmJiBlbC5zdHlsZS5tYXhIZWlnaHQgPT09ICcnKTtcbiAgICB9XG59XG4iLCJpbXBvcnQgeyBDb250cm9sbGVyIH0gZnJvbSAnQGhvdHdpcmVkL3N0aW11bHVzJztcblxuZXhwb3J0IGRlZmF1bHQgY2xhc3MgZXh0ZW5kcyBDb250cm9sbGVyIHtcbiAgICBzdGF0aWMgdGFyZ2V0cyA9IFsnaW5wdXQnXTtcblxuICAgIGRlbGV0ZSAoKSB7XG4gICAgICAgIHRoaXMuaW5wdXRUYXJnZXRzLmZvckVhY2goKGlucHV0KSA9PiB7XG4gICAgICAgICAgICBpbnB1dC52YWx1ZSA9ICcnO1xuICAgICAgICB9KVxuICAgIH1cbn1cbiIsImltcG9ydCB7IENvbnRyb2xsZXIgfSBmcm9tICdAaG90d2lyZWQvc3RpbXVsdXMnXG5cbmV4cG9ydCBkZWZhdWx0IGNsYXNzIGV4dGVuZHMgQ29udHJvbGxlciB7XG4gICAgc3RhdGljIHRhcmdldHMgPSBbJ3Njcm9sbFRvJywgJ2F1dG9Gb2N1cyddO1xuXG4gICAgc3RhdGljIHZhbHVlcyA9IHtcbiAgICAgICAgc2Vzc2lvbktleToge1xuICAgICAgICAgICAgdHlwZTogU3RyaW5nLFxuICAgICAgICAgICAgZGVmYXVsdDogJ2NvbnRhb19iYWNrZW5kX29mZnNldCdcbiAgICAgICAgfSxcbiAgICAgICAgYmVoYXZpb3I6IHtcbiAgICAgICAgICAgIHR5cGU6IFN0cmluZyxcbiAgICAgICAgICAgIGRlZmF1bHQ6ICdpbnN0YW50J1xuICAgICAgICB9LFxuICAgICAgICBibG9jazoge1xuICAgICAgICAgICAgdHlwZTogU3RyaW5nLFxuICAgICAgICAgICAgZGVmYXVsdDogJ2NlbnRlcidcbiAgICAgICAgfVxuICAgIH07XG5cbiAgICAvLyBCYWNrd2FyZHMgY29tcGF0aWJpbGl0eTogYXV0b21hdGljYWxseSByZWdpc3RlciB0aGUgU3RpbXVsdXMgY29udHJvbGxlciBpZiB0aGUgbGVnYWN5IG1ldGhvZHMgYXJlIHVzZWRcbiAgICBzdGF0aWMgYWZ0ZXJMb2FkKGlkZW50aWZpZXIsIGFwcGxpY2F0aW9uKSB7XG4gICAgICAgIGNvbnN0IGxvYWRGYWxsYmFjayA9ICgpID0+IHtcbiAgICAgICAgICAgIHJldHVybiBuZXcgUHJvbWlzZSgocmVzb2x2ZSwgcmVqZWN0KSA9PiB7XG4gICAgICAgICAgICAgICAgY29uc3QgY29udHJvbGxlciA9IGFwcGxpY2F0aW9uLmdldENvbnRyb2xsZXJGb3JFbGVtZW50QW5kSWRlbnRpZmllcihkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQsIGlkZW50aWZpZXIpO1xuXG4gICAgICAgICAgICAgICAgaWYgKGNvbnRyb2xsZXIpIHtcbiAgICAgICAgICAgICAgICAgICAgcmVzb2x2ZShjb250cm9sbGVyKTtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgIGNvbnN0IHsgY29udHJvbGxlckF0dHJpYnV0ZSB9ID0gYXBwbGljYXRpb24uc2NoZW1hO1xuICAgICAgICAgICAgICAgIGRvY3VtZW50LmRvY3VtZW50RWxlbWVudC5zZXRBdHRyaWJ1dGUoY29udHJvbGxlckF0dHJpYnV0ZSwgYCR7ZG9jdW1lbnQuZG9jdW1lbnRFbGVtZW50LmdldEF0dHJpYnV0ZShjb250cm9sbGVyQXR0cmlidXRlKSB8fCAnJ30gJHsgaWRlbnRpZmllciB9YCk7XG5cbiAgICAgICAgICAgICAgICBzZXRUaW1lb3V0KCgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgY29uc3QgY29udHJvbGxlciA9IGFwcGxpY2F0aW9uLmdldENvbnRyb2xsZXJGb3JFbGVtZW50QW5kSWRlbnRpZmllcihkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQsIGlkZW50aWZpZXIpO1xuICAgICAgICAgICAgICAgICAgICBjb250cm9sbGVyICYmIHJlc29sdmUoY29udHJvbGxlcikgfHwgcmVqZWN0KGNvbnRyb2xsZXIpO1xuICAgICAgICAgICAgICAgIH0sIDEwMCk7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmICh3aW5kb3cuQmFja2VuZCAmJiAhd2luZG93LkJhY2tlbmQuaW5pdFNjcm9sbE9mZnNldCkge1xuICAgICAgICAgICAgd2luZG93LkJhY2tlbmQuaW5pdFNjcm9sbE9mZnNldCA9ICgpID0+IHtcbiAgICAgICAgICAgICAgICBjb25zb2xlLndhcm4oJ0JhY2tlbmQuaW5pdFNjcm9sbE9mZnNldCgpIGlzIGRlcHJlY2F0ZWQuIFBsZWFzZSB1c2UgdGhlIFN0aW11bHVzIGNvbnRyb2xsZXIgaW5zdGVhZC4nKTtcbiAgICAgICAgICAgICAgICBsb2FkRmFsbGJhY2soKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIGlmICh3aW5kb3cuQmFja2VuZCAmJiAhd2luZG93LkJhY2tlbmQuZ2V0U2Nyb2xsT2Zmc2V0KSB7XG4gICAgICAgICAgICB3aW5kb3cuQmFja2VuZC5nZXRTY3JvbGxPZmZzZXQgPSAoKSA9PiB7XG4gICAgICAgICAgICAgICAgY29uc29sZS53YXJuKCdCYWNrZW5kLmdldFNjcm9sbE9mZnNldCgpIGlzIGRlcHJlY2F0ZWQuIFBsZWFzZSB1c2UgdGhlIFN0aW11bHVzIGNvbnRyb2xsZXIgaW5zdGVhZC4nKTtcbiAgICAgICAgICAgICAgICBsb2FkRmFsbGJhY2soKS50aGVuKChjb250cm9sbGVyKSA9PiBjb250cm9sbGVyLmRpc2NhcmQoKSk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICBpbml0aWFsaXplICgpIHtcbiAgICAgICAgdGhpcy5zdG9yZSA9IHRoaXMuc3RvcmUuYmluZCh0aGlzKTtcbiAgICB9XG5cbiAgICBjb25uZWN0ICgpIHtcbiAgICAgICAgaWYgKCF0aGlzLm9mZnNldCkgcmV0dXJuO1xuXG4gICAgICAgIHdpbmRvdy5zY3JvbGxUbyh7XG4gICAgICAgICAgICB0b3A6IHRoaXMub2Zmc2V0LFxuICAgICAgICAgICAgYmVoYXZpb3I6IHRoaXMuYmVoYXZpb3JWYWx1ZSxcbiAgICAgICAgICAgIGJsb2NrOiB0aGlzLmJsb2NrVmFsdWVcbiAgICAgICAgfSk7XG5cbiAgICAgICAgdGhpcy5vZmZzZXQgPSBudWxsO1xuICAgIH1cblxuICAgIHNjcm9sbFRvVGFyZ2V0Q29ubmVjdGVkKCkge1xuICAgICAgICB0aGlzLnNjcm9sbFRvVGFyZ2V0LnNjcm9sbEludG9WaWV3KHtcbiAgICAgICAgICAgIGJlaGF2aW9yOiB0aGlzLmJlaGF2aW9yVmFsdWUsXG4gICAgICAgICAgICBibG9jazogdGhpcy5ibG9ja1ZhbHVlXG4gICAgICAgIH0pO1xuICAgIH1cblxuICAgIGF1dG9Gb2N1c1RhcmdldENvbm5lY3RlZCgpIHtcbiAgICAgICAgaWYgKHRoaXMub2Zmc2V0IHx8IHRoaXMuYXV0b0ZvY3VzKSByZXR1cm47XG5cbiAgICAgICAgY29uc3QgaW5wdXQgPSB0aGlzLmF1dG9Gb2N1c1RhcmdldDtcblxuICAgICAgICBpZiAoXG4gICAgICAgICAgICBpbnB1dC5kaXNhYmxlZCB8fCBpbnB1dC5yZWFkb25seVxuICAgICAgICAgICAgfHwgIWlucHV0Lm9mZnNldFdpZHRoIHx8ICFpbnB1dC5vZmZzZXRIZWlnaHRcbiAgICAgICAgICAgIHx8IGlucHV0LmNsb3Nlc3QoJy5jaHpuLXNlYXJjaCcpXG4gICAgICAgICAgICB8fCBpbnB1dC5hdXRvY29tcGxldGUgJiYgaW5wdXQuYXV0b2NvbXBsZXRlICE9PSAnb2ZmJ1xuICAgICAgICApIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIHRoaXMuYXV0b0ZvY3VzID0gdHJ1ZTtcbiAgICAgICAgaW5wdXQuZm9jdXMoKTtcbiAgICB9XG5cbiAgICBzdG9yZSAoKSB7XG4gICAgICAgIHRoaXMub2Zmc2V0ID0gdGhpcy5lbGVtZW50LnNjcm9sbFRvcDtcbiAgICB9XG5cbiAgICBkaXNjYXJkICgpIHtcbiAgICAgICAgdGhpcy5vZmZzZXQgPSBudWxsO1xuICAgIH1cblxuICAgIGdldCBvZmZzZXQgKCkge1xuICAgICAgICBjb25zdCB2YWx1ZSA9IHdpbmRvdy5zZXNzaW9uU3RvcmFnZS5nZXRJdGVtKHRoaXMuc2Vzc2lvbktleVZhbHVlKTtcblxuICAgICAgICByZXR1cm4gdmFsdWUgPyBwYXJzZUludCh2YWx1ZSkgOiBudWxsO1xuICAgIH1cblxuICAgIHNldCBvZmZzZXQgKHZhbHVlKSB7XG4gICAgICAgIGlmICh2YWx1ZSA9PT0gbnVsbCB8fCB2YWx1ZSA9PT0gdW5kZWZpbmVkKSB7XG4gICAgICAgICAgICB3aW5kb3cuc2Vzc2lvblN0b3JhZ2UucmVtb3ZlSXRlbSh0aGlzLnNlc3Npb25LZXlWYWx1ZSk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICB3aW5kb3cuc2Vzc2lvblN0b3JhZ2Uuc2V0SXRlbSh0aGlzLnNlc3Npb25LZXlWYWx1ZSwgU3RyaW5nKHZhbHVlKSk7XG4gICAgICAgIH1cbiAgICB9XG59XG4iLCJpbXBvcnQgeyBDb250cm9sbGVyIH0gZnJvbSAnQGhvdHdpcmVkL3N0aW11bHVzJztcblxuZXhwb3J0IGRlZmF1bHQgY2xhc3MgZXh0ZW5kcyBDb250cm9sbGVyIHtcbiAgICBzdGF0aWMgdmFsdWVzID0ge1xuICAgICAgICBpZDogU3RyaW5nLFxuICAgICAgICB0YWJsZTogU3RyaW5nLFxuICAgIH1cblxuICAgIHN0YXRpYyBjbGFzc2VzID0gWydjb2xsYXBzZWQnXTtcblxuICAgIHN0YXRpYyBhZnRlckxvYWQgKGlkZW50aWZpZXIsIGFwcGxpY2F0aW9uKSB7XG4gICAgICAgIGNvbnN0IGFkZENvbnRyb2xsZXIgPSAoZWwsIGlkLCB0YWJsZSkgPT4ge1xuICAgICAgICAgICAgY29uc3QgZnMgPSBlbC5wYXJlbnROb2RlO1xuXG4gICAgICAgICAgICBmcy5kYXRhc2V0LmNvbnRyb2xsZXIgPSBgJHtmcy5kYXRhc2V0LmNvbnRyb2xsZXIgfHwgJyd9ICR7aWRlbnRpZmllcn1gO1xuICAgICAgICAgICAgZnMuc2V0QXR0cmlidXRlKGBkYXRhLSR7aWRlbnRpZmllcn0taWQtdmFsdWVgLCBpZCk7XG4gICAgICAgICAgICBmcy5zZXRBdHRyaWJ1dGUoYGRhdGEtJHtpZGVudGlmaWVyfS10YWJsZS12YWx1ZWAsIHRhYmxlKTtcbiAgICAgICAgICAgIGZzLnNldEF0dHJpYnV0ZShgZGF0YS0ke2lkZW50aWZpZXJ9LWNvbGxhcHNlZC1jbGFzc2AsICdjb2xsYXBzZWQnKTtcbiAgICAgICAgICAgIGVsLnNldEF0dHJpYnV0ZSgndGFiaW5kZXgnLCAwKTtcbiAgICAgICAgICAgIGVsLnNldEF0dHJpYnV0ZSgnZGF0YS1hY3Rpb24nLCBgY2xpY2stPiR7aWRlbnRpZmllcn0jdG9nZ2xlIGtleWRvd24uZW50ZXItPiR7aWRlbnRpZmllcn0jdG9nZ2xlIGtleWRvd24uc3BhY2UtPiR7aWRlbnRpZmllcn0jcHJldmVudDpwcmV2ZW50IGtleXVwLnNwYWNlLT4ke2lkZW50aWZpZXJ9I3RvZ2dsZTpwcmV2ZW50YCk7XG4gICAgICAgIH1cblxuICAgICAgICBjb25zdCBtaWdyYXRlTGVnYWN5ID0gKCkgPT4ge1xuICAgICAgICAgICAgZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbCgnbGVnZW5kW2RhdGEtdG9nZ2xlLWZpZWxkc2V0XScpLmZvckVhY2goZnVuY3Rpb24oZWwpIHtcbiAgICAgICAgICAgICAgICBpZiAod2luZG93LmNvbnNvbGUpIHtcbiAgICAgICAgICAgICAgICAgICAgY29uc29sZS53YXJuKGBVc2luZyB0aGUgXCJkYXRhLXRvZ2dsZS1maWVsZHNldFwiIGF0dHJpYnV0ZSBvbiBmaWVsZHNldCBsZWdlbmRzIGlzIGRlcHJlY2F0ZWQgYW5kIHdpbGwgYmUgcmVtb3ZlZCBpbiBDb250YW8gNi4gQXBwbHkgdGhlIFwiJHtpZGVudGlmaWVyfVwiIFN0aW11bHVzIGNvbnRyb2xsZXIgaW5zdGVhZC5gKTtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBjb25zdCB7IGlkLCB0YWJsZSB9ID0gSlNPTi5wYXJzZShlbC5nZXRBdHRyaWJ1dGUoJ2RhdGEtdG9nZ2xlLWZpZWxkc2V0JykpO1xuICAgICAgICAgICAgICAgIGFkZENvbnRyb2xsZXIoZWwsIGlkLCB0YWJsZSk7XG4gICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgQWpheFJlcXVlc3QudG9nZ2xlRmllbGRzZXQgPSAoZWwsIGlkLCB0YWJsZSkgPT4ge1xuICAgICAgICAgICAgICAgIGNvbnN0IGZzID0gZWwucGFyZW50Tm9kZTtcblxuICAgICAgICAgICAgICAgIC8vIEFscmVhZHkgY2xpY2tlZCwgU3RpbXVsdXMgY29udHJvbGxlciB3YXMgYWRkZWQgZHluYW1pY2FsbHlcbiAgICAgICAgICAgICAgICBpZiAoYXBwbGljYXRpb24uZ2V0Q29udHJvbGxlckZvckVsZW1lbnRBbmRJZGVudGlmaWVyKGZzLCBpZGVudGlmaWVyKSkge1xuICAgICAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgaWYgKHdpbmRvdy5jb25zb2xlKSB7XG4gICAgICAgICAgICAgICAgICAgIGNvbnNvbGUud2FybignVXNpbmcgQWpheFJlcXVlc3QudG9nZ2xlRmllbGRzZXQoKSBpcyBkZXByZWNhdGVkIGFuZCB3aWxsIGJlIHJlbW92ZWQgaW4gQ29udGFvIDYuIEFwcGx5IHRoZSBTdGltdWx1cyBhY3Rpb25zIGluc3RlYWQuJyk7XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgYWRkQ29udHJvbGxlcihlbCwgaWQsIHRhYmxlKTtcblxuICAgICAgICAgICAgICAgIC8vIE9wdGltaXN0aWNhbGx5IHdhaXQgdW50aWwgU3RpbXVsdXMgaGFzIHJlZ2lzdGVyZWQgdGhlIG5ldyBjb250cm9sbGVyXG4gICAgICAgICAgICAgICAgc2V0VGltZW91dCgoKSA9PiB7IGFwcGxpY2F0aW9uLmdldENvbnRyb2xsZXJGb3JFbGVtZW50QW5kSWRlbnRpZmllcihmcywgaWRlbnRpZmllcikudG9nZ2xlKCk7IH0sIDEwMCk7XG4gICAgICAgICAgICB9O1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gQ2FsbGVkIGFzIHNvb24gYXMgcmVnaXN0ZXJlZCwgc28gRE9NIG1heSBub3QgaGF2ZSBiZWVuIGxvYWRlZCB5ZXRcbiAgICAgICAgaWYgKGRvY3VtZW50LnJlYWR5U3RhdGUgPT09IFwibG9hZGluZ1wiKSB7XG4gICAgICAgICAgICBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKFwiRE9NQ29udGVudExvYWRlZFwiLCBtaWdyYXRlTGVnYWN5KTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIG1pZ3JhdGVMZWdhY3koKTtcbiAgICAgICAgfVxuICAgIH1cblxuICAgIGNvbm5lY3QgKCkge1xuICAgICAgICBpZiAodGhpcy5lbGVtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJ2xhYmVsLmVycm9yLCBsYWJlbC5tYW5kYXRvcnknKS5sZW5ndGgpIHtcbiAgICAgICAgICAgIHRoaXMuZWxlbWVudC5jbGFzc0xpc3QucmVtb3ZlKHRoaXMuY29sbGFwc2VkQ2xhc3MpO1xuICAgICAgICB9IGVsc2UgaWYgKHRoaXMuZWxlbWVudC5jbGFzc0xpc3QuY29udGFpbnMoJ2hpZGUnKSkge1xuICAgICAgICAgICAgaWYgKHdpbmRvdy5jb25zb2xlKSB7XG4gICAgICAgICAgICAgICAgY29uc29sZS53YXJuKGBVc2luZyBjbGFzcyBcImhpZGVcIiBvbiBhIGZpZWxkc2V0IGlzIGRlcHJlY2F0ZWQgYW5kIHdpbGwgYmUgcmVtb3ZlZCBpbiBDb250YW8gNi4gVXNlIGNsYXNzIFwiJHt0aGlzLmNvbGxhcHNlZENsYXNzfVwiIGluc3RlYWQuYCk7XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIHRoaXMuZWxlbWVudC5jbGFzc0xpc3QuYWRkKHRoaXMuY29sbGFwc2VkQ2xhc3MpO1xuICAgICAgICB9XG5cbiAgICAgICAgaWYgKHRoaXMuZWxlbWVudC5jbGFzc0xpc3QuY29udGFpbnModGhpcy5jb2xsYXBzZWRDbGFzcykpIHtcbiAgICAgICAgICAgIHRoaXMuc2V0QXJpYUV4cGFuZGVkKGZhbHNlKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHRoaXMuc2V0QXJpYUV4cGFuZGVkKHRydWUpO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgdG9nZ2xlICgpIHtcbiAgICAgICAgaWYgKHRoaXMuZWxlbWVudC5jbGFzc0xpc3QuY29udGFpbnModGhpcy5jb2xsYXBzZWRDbGFzcykpIHtcbiAgICAgICAgICAgIHRoaXMub3BlbigpO1xuICAgICAgICAgICAgdGhpcy5zZXRBcmlhRXhwYW5kZWQodHJ1ZSk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICB0aGlzLmNsb3NlKCk7XG4gICAgICAgICAgICB0aGlzLnNldEFyaWFFeHBhbmRlZChmYWxzZSk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICBvcGVuICgpIHtcbiAgICAgICAgaWYgKCF0aGlzLmVsZW1lbnQuY2xhc3NMaXN0LmNvbnRhaW5zKHRoaXMuY29sbGFwc2VkQ2xhc3MpKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICB0aGlzLmVsZW1lbnQuY2xhc3NMaXN0LnJlbW92ZSh0aGlzLmNvbGxhcHNlZENsYXNzKTtcbiAgICAgICAgdGhpcy5zdG9yZVN0YXRlKDEpO1xuICAgIH1cblxuICAgIGNsb3NlICgpIHtcbiAgICAgICAgaWYgKHRoaXMuZWxlbWVudC5jbGFzc0xpc3QuY29udGFpbnModGhpcy5jb2xsYXBzZWRDbGFzcykpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIGNvbnN0IGZvcm0gPSB0aGlzLmVsZW1lbnQuY2xvc2VzdCgnZm9ybScpO1xuICAgICAgICBjb25zdCBpbnB1dCA9IHRoaXMuZWxlbWVudC5xdWVyeVNlbGVjdG9yQWxsKCdbcmVxdWlyZWRdJyk7XG5cbiAgICAgICAgbGV0IGNvbGxhcHNlID0gdHJ1ZTtcbiAgICAgICAgZm9yIChsZXQgaSA9IDA7IGkgPCBpbnB1dC5sZW5ndGg7IGkrKykge1xuICAgICAgICAgICAgaWYgKCFpbnB1dFtpXS52YWx1ZSkge1xuICAgICAgICAgICAgICAgIGNvbGxhcHNlID0gZmFsc2U7XG4gICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICB9XG4gICAgICAgIH1cblxuICAgICAgICBpZiAoIWNvbGxhcHNlKSB7XG4gICAgICAgICAgICBpZiAodHlwZW9mKGZvcm0uY2hlY2tWYWxpZGl0eSkgPT0gJ2Z1bmN0aW9uJykge1xuICAgICAgICAgICAgICAgIGZvcm0ucXVlcnlTZWxlY3RvcignYnV0dG9uW3R5cGU9XCJzdWJtaXRcIl0nKS5jbGljaygpO1xuICAgICAgICAgICAgfVxuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5lbGVtZW50LmNsYXNzTGlzdC5hZGQodGhpcy5jb2xsYXBzZWRDbGFzcyk7XG4gICAgICAgICAgICB0aGlzLnN0b3JlU3RhdGUoMCk7XG4gICAgICAgIH1cbiAgICB9XG5cbiAgICBzdG9yZVN0YXRlIChzdGF0ZSkge1xuICAgICAgICBpZiAoIXRoaXMuaGFzSWRWYWx1ZSB8fCAhdGhpcy5oYXNUYWJsZVZhbHVlKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICBmZXRjaCh3aW5kb3cubG9jYXRpb24uaHJlZiwge1xuICAgICAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgICAgICBoZWFkZXJzOiB7XG4gICAgICAgICAgICAgICAgJ1gtUmVxdWVzdGVkLVdpdGgnOiAnWE1MSHR0cFJlcXVlc3QnXG4gICAgICAgICAgICB9LFxuICAgICAgICAgICAgYm9keTogbmV3IFVSTFNlYXJjaFBhcmFtcyh7XG4gICAgICAgICAgICAgICAgYWN0aW9uOiAndG9nZ2xlRmllbGRzZXQnLFxuICAgICAgICAgICAgICAgIGlkOiB0aGlzLmlkVmFsdWUsXG4gICAgICAgICAgICAgICAgdGFibGU6IHRoaXMudGFibGVWYWx1ZSxcbiAgICAgICAgICAgICAgICBzdGF0ZTogc3RhdGUsXG4gICAgICAgICAgICB9KVxuICAgICAgICB9KTtcbiAgICB9XG5cbiAgICBzZXRBcmlhRXhwYW5kZWQoc3RhdGUpIHtcbiAgICAgICAgY29uc3QgYnV0dG9uID0gdGhpcy5lbGVtZW50LnF1ZXJ5U2VsZWN0b3IoJ2J1dHRvbicpO1xuXG4gICAgICAgIGlmIChidXR0b24pIHtcbiAgICAgICAgICAgIGJ1dHRvbi5hcmlhRXhwYW5kZWQgPSBzdGF0ZTtcbiAgICAgICAgfVxuICAgIH1cbn1cbiIsImltcG9ydCB7IENvbnRyb2xsZXIgfSBmcm9tICdAaG90d2lyZWQvc3RpbXVsdXMnO1xuXG5leHBvcnQgZGVmYXVsdCBjbGFzcyBleHRlbmRzIENvbnRyb2xsZXIge1xuICAgIHN0YXRpYyBjbGFzc2VzID0gWydjb2xsYXBzZWQnXVxuXG4gICAgc3RhdGljIHZhbHVlcyA9IHtcbiAgICAgICAgdXJsOiBTdHJpbmcsXG4gICAgICAgIHJlcXVlc3RUb2tlbjogU3RyaW5nLFxuICAgICAgICBleHBhbmRUaXRsZTogU3RyaW5nLFxuICAgICAgICBjb2xsYXBzZVRpdGxlOiBTdHJpbmcsXG4gICAgfVxuXG4gICAgdG9nZ2xlICh7IGN1cnJlbnRUYXJnZXQsIHBhcmFtczogeyBjYXRlZ29yeSB9fSkge1xuICAgICAgICBjb25zdCBlbCA9IGN1cnJlbnRUYXJnZXQucGFyZW50Tm9kZTtcbiAgICAgICAgY29uc3QgY29sbGFwc2VkID0gZWwuY2xhc3NMaXN0LnRvZ2dsZSh0aGlzLmNvbGxhcHNlZENsYXNzKTtcblxuICAgICAgICBjdXJyZW50VGFyZ2V0LnNldEF0dHJpYnV0ZSgnYXJpYS1leHBhbmRlZCcsIGNvbGxhcHNlZCA/ICdmYWxzZScgOiAndHJ1ZScpO1xuICAgICAgICBjdXJyZW50VGFyZ2V0LnNldEF0dHJpYnV0ZSgndGl0bGUnLCBjb2xsYXBzZWQgPyB0aGlzLmV4cGFuZFRpdGxlVmFsdWUgOiB0aGlzLmNvbGxhcHNlVGl0bGVWYWx1ZSk7XG5cbiAgICAgICAgdGhpcy5zZW5kUmVxdWVzdChjYXRlZ29yeSwgY29sbGFwc2VkKTtcbiAgICB9XG5cbiAgICBzZW5kUmVxdWVzdCAoY2F0ZWdvcnksIGNvbGxhcHNlZCkge1xuICAgICAgICBmZXRjaCh0aGlzLnVybFZhbHVlLCB7XG4gICAgICAgICAgICBtZXRob2Q6ICdQT1NUJyxcbiAgICAgICAgICAgIGhlYWRlcnM6IHtcbiAgICAgICAgICAgICAgICAnWC1SZXF1ZXN0ZWQtV2l0aCc6ICdYTUxIdHRwUmVxdWVzdCdcbiAgICAgICAgICAgIH0sXG4gICAgICAgICAgICBib2R5OiBuZXcgVVJMU2VhcmNoUGFyYW1zKHtcbiAgICAgICAgICAgICAgICBhY3Rpb246ICd0b2dnbGVOYXZpZ2F0aW9uJyxcbiAgICAgICAgICAgICAgICBpZDogY2F0ZWdvcnksXG4gICAgICAgICAgICAgICAgc3RhdGU6IGNvbGxhcHNlZCA/IDAgOiAxLFxuICAgICAgICAgICAgICAgIFJFUVVFU1RfVE9LRU46IHRoaXMucmVxdWVzdFRva2VuVmFsdWVcbiAgICAgICAgICAgIH0pXG4gICAgICAgIH0pO1xuICAgIH1cbn1cbiIsImltcG9ydCB7IENvbnRyb2xsZXIgfSBmcm9tICdAaG90d2lyZWQvc3RpbXVsdXMnO1xuXG5leHBvcnQgZGVmYXVsdCBjbGFzcyBleHRlbmRzIENvbnRyb2xsZXIge1xuICAgIHN0YXRpYyB2YWx1ZXMgPSB7XG4gICAgICAgIG1vZGU6IHtcbiAgICAgICAgICAgIHR5cGU6IE51bWJlcixcbiAgICAgICAgICAgIGRlZmF1bHQ6IDVcbiAgICAgICAgfSxcbiAgICAgICAgdG9nZ2xlQWN0aW9uOiBTdHJpbmcsXG4gICAgICAgIGxvYWRBY3Rpb246IFN0cmluZyxcbiAgICAgICAgcmVxdWVzdFRva2VuOiBTdHJpbmcsXG4gICAgICAgIHJlZmVyZXJJZDogU3RyaW5nLFxuICAgICAgICBleHBhbmQ6IFN0cmluZyxcbiAgICAgICAgY29sbGFwc2U6IFN0cmluZyxcbiAgICAgICAgZXhwYW5kQWxsOiBTdHJpbmcsXG4gICAgICAgIGV4cGFuZEFsbFRpdGxlOiBTdHJpbmcsXG4gICAgICAgIGNvbGxhcHNlQWxsOiBTdHJpbmcsXG4gICAgICAgIGNvbGxhcHNlQWxsVGl0bGU6IFN0cmluZyxcbiAgICB9XG5cbiAgICBzdGF0aWMgdGFyZ2V0cyA9IFsnb3BlcmF0aW9uJywgJ25vZGUnLCAndG9nZ2xlJywgJ2NoaWxkJywgJ3Jvb3RDaGlsZCddO1xuXG4gICAgb3BlcmF0aW9uVGFyZ2V0Q29ubmVjdGVkICgpIHtcbiAgICAgICAgdGhpcy51cGRhdGVPcGVyYXRpb24oKTtcbiAgICB9XG5cbiAgICBjaGlsZFRhcmdldENvbm5lY3RlZCAoKSB7XG4gICAgICAgIHRoaXMudXBkYXRlT3BlcmF0aW9uKCk7XG4gICAgfVxuXG4gICAgdG9nZ2xlIChldmVudCkge1xuICAgICAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuXG4gICAgICAgIGNvbnN0IGVsID0gZXZlbnQuY3VycmVudFRhcmdldDtcbiAgICAgICAgdGhpcy50b2dnbGVUb2dnbGVyKGVsLCBldmVudC5wYXJhbXMuaWQsIGV2ZW50LnBhcmFtcy5sZXZlbCwgZXZlbnQucGFyYW1zLmZvbGRlcik7XG4gICAgfVxuXG4gICAgdG9nZ2xlVG9nZ2xlciAoZWwsIGlkLCBsZXZlbCwgZm9sZGVyKSB7XG4gICAgICAgIGNvbnN0IGl0ZW0gPSBkb2N1bWVudC5pZChpZCk7XG5cbiAgICAgICAgaWYgKGl0ZW0gJiYgaXRlbS5zdHlsZS5kaXNwbGF5ID09PSAnbm9uZScpIHtcbiAgICAgICAgICAgIHRoaXMuc2hvd0NoaWxkKGl0ZW0pO1xuICAgICAgICAgICAgdGhpcy5leHBhbmRUb2dnbGVyKGVsKTtcbiAgICAgICAgICAgIHRoaXMudXBkYXRlU3RhdGUoZWwsIGlkLCAxKTtcbiAgICAgICAgfSBlbHNlIGlmIChpdGVtKSB7XG4gICAgICAgICAgICB0aGlzLmhpZGVDaGlsZChpdGVtKTtcbiAgICAgICAgICAgIHRoaXMuY29sbGFwc2VUb2dnbGVyKGVsKTtcbiAgICAgICAgICAgIHRoaXMudXBkYXRlU3RhdGUoZWwsIGlkLCAwKTtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIHRoaXMuZmV0Y2hDaGlsZChlbCwgaWQsIGxldmVsLCBmb2xkZXIpO1xuICAgICAgICB9XG5cbiAgICAgICAgdGhpcy51cGRhdGVPcGVyYXRpb24oKTtcbiAgICB9XG5cbiAgICBleHBhbmRUb2dnbGVyIChlbCkge1xuICAgICAgICBlbC5jbGFzc0xpc3QuYWRkKCdmb2xkYWJsZS0tb3BlbicpO1xuICAgICAgICBlbC50aXRsZSA9IHRoaXMuY29sbGFwc2VWYWx1ZTtcbiAgICB9XG5cbiAgICBjb2xsYXBzZVRvZ2dsZXIgKGVsKSB7XG4gICAgICAgIGVsLmNsYXNzTGlzdC5yZW1vdmUoJ2ZvbGRhYmxlLS1vcGVuJyk7XG4gICAgICAgIGVsLnRpdGxlID0gdGhpcy5leHBhbmRWYWx1ZTtcbiAgICB9XG5cbiAgICBsb2FkVG9nZ2xlciAoZWwsIGVuYWJsZWQpIHtcbiAgICAgICAgZWwuY2xhc3NMaXN0W2VuYWJsZWQgPyAnYWRkJyA6ICdyZW1vdmUnXSgnZm9sZGFibGUtLWxvYWRpbmcnKTtcbiAgICB9XG5cbiAgICBzaG93Q2hpbGQgKGl0ZW0pIHtcbiAgICAgICAgaXRlbS5zdHlsZS5kaXNwbGF5ID0gJyc7XG4gICAgfVxuXG4gICAgaGlkZUNoaWxkIChpdGVtKSB7XG4gICAgICAgIGl0ZW0uc3R5bGUuZGlzcGxheSA9ICdub25lJztcbiAgICB9XG5cbiAgICBhc3luYyBmZXRjaENoaWxkIChlbCwgaWQsIGxldmVsLCBmb2xkZXIpIHtcbiAgICAgICAgdGhpcy5sb2FkVG9nZ2xlcihlbCwgdHJ1ZSk7XG5cbiAgICAgICAgY29uc3QgdXJsID0gbmV3IFVSTChsb2NhdGlvbi5ocmVmKTtcbiAgICAgICAgY29uc3Qgc2VhcmNoID0gdXJsLnNlYXJjaFBhcmFtcztcbiAgICAgICAgc2VhcmNoLnNldCgncmVmJywgdGhpcy5yZWZlcmVySWRWYWx1ZSk7XG4gICAgICAgIHVybC5zZWFyY2ggPSBzZWFyY2gudG9TdHJpbmcoKTtcblxuICAgICAgICBjb25zdCByZXNwb25zZSA9IGF3YWl0IGZldGNoKHVybCwge1xuICAgICAgICAgICAgbWV0aG9kOiAnUE9TVCcsXG4gICAgICAgICAgICBoZWFkZXJzOiB7XG4gICAgICAgICAgICAgICAgJ0NvbnRlbnQtVHlwZSc6ICdhcHBsaWNhdGlvbi94LXd3dy1mb3JtLXVybGVuY29kZWQnLFxuICAgICAgICAgICAgICAgICdYLVJlcXVlc3RlZC1XaXRoJzogJ1hNTEh0dHBSZXF1ZXN0J1xuICAgICAgICAgICAgfSxcbiAgICAgICAgICAgIGJvZHk6IG5ldyBVUkxTZWFyY2hQYXJhbXMoe1xuICAgICAgICAgICAgICAgICdhY3Rpb24nOiB0aGlzLmxvYWRBY3Rpb25WYWx1ZSxcbiAgICAgICAgICAgICAgICAnaWQnOiBpZCxcbiAgICAgICAgICAgICAgICAnbGV2ZWwnOiBsZXZlbCxcbiAgICAgICAgICAgICAgICAnZm9sZGVyJzogZm9sZGVyLFxuICAgICAgICAgICAgICAgICdzdGF0ZSc6IDEsXG4gICAgICAgICAgICAgICAgJ1JFUVVFU1RfVE9LRU4nOiB0aGlzLnJlcXVlc3RUb2tlblZhbHVlXG4gICAgICAgICAgICB9KVxuICAgICAgICB9KTtcblxuICAgICAgICBpZiAocmVzcG9uc2Uub2spIHtcbiAgICAgICAgICAgIGNvbnN0IHR4dCA9IGF3YWl0IHJlc3BvbnNlLnRleHQoKTtcblxuICAgICAgICAgICAgY29uc3QgbGkgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdsaScpO1xuICAgICAgICAgICAgbGkuaWQgPSBpZDtcbiAgICAgICAgICAgIGxpLmNsYXNzTGlzdC5hZGQoJ3BhcmVudCcpO1xuICAgICAgICAgICAgbGkuc3R5bGUuZGlzcGxheSA9ICdpbmxpbmUnO1xuICAgICAgICAgICAgbGkuc2V0QXR0cmlidXRlKGBkYXRhLSR7dGhpcy5pZGVudGlmaWVyfS10YXJnZXRgLCBsZXZlbCA9PT0gMCA/ICdjaGlsZCByb290Q2hpbGQnIDogJ2NoaWxkJyk7XG5cbiAgICAgICAgICAgIGNvbnN0IHVsID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgndWwnKTtcbiAgICAgICAgICAgIHVsLmNsYXNzTGlzdC5hZGQoJ2xldmVsXycgKyBsZXZlbCk7XG4gICAgICAgICAgICB1bC5pbm5lckhUTUwgPSB0eHQ7XG4gICAgICAgICAgICBsaS5hcHBlbmQodWwpO1xuXG4gICAgICAgICAgICBpZiAodGhpcy5tb2RlVmFsdWUgPT09IDUpIHtcbiAgICAgICAgICAgICAgICBlbC5jbG9zZXN0KCdsaScpLmFmdGVyKGxpKTtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgbGV0IGlzRm9sZGVyID0gZmFsc2UsXG4gICAgICAgICAgICAgICAgICAgIHBhcmVudCA9IGVsLmNsb3Nlc3QoJ2xpJyksXG4gICAgICAgICAgICAgICAgICAgIG5leHQ7XG5cbiAgICAgICAgICAgICAgICB3aGlsZSAodHlwZU9mKHBhcmVudCkgPT09ICdlbGVtZW50JyAmJiBwYXJlbnQudGFnTmFtZSA9PT0gJ0xJJyAmJiAobmV4dCA9IHBhcmVudC5uZXh0RWxlbWVudFNpYmxpbmcpKSB7XG4gICAgICAgICAgICAgICAgICAgIHBhcmVudCA9IG5leHQ7XG4gICAgICAgICAgICAgICAgICAgIGlmIChwYXJlbnQuY2xhc3NMaXN0LmNvbnRhaW5zKCd0bF9mb2xkZXInKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgaXNGb2xkZXIgPSB0cnVlO1xuICAgICAgICAgICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBpZiAoaXNGb2xkZXIpIHtcbiAgICAgICAgICAgICAgICAgICAgcGFyZW50LmJlZm9yZShsaSk7XG4gICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgcGFyZW50LmFmdGVyKGxpKTtcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG5cbiAgICAgICAgICAgIHdpbmRvdy5kaXNwYXRjaEV2ZW50KG5ldyBDdXN0b21FdmVudCgnc3RydWN0dXJlJykpO1xuICAgICAgICAgICAgdGhpcy5leHBhbmRUb2dnbGVyKGVsKTtcblxuICAgICAgICAgICAgLy8gSE9PSyAoc2VlICM2NzUyKVxuICAgICAgICAgICAgd2luZG93LmZpcmVFdmVudCgnYWpheF9jaGFuZ2UnKTtcbiAgICAgICAgfVxuXG4gICAgICAgIHRoaXMubG9hZFRvZ2dsZXIoZWwsIGZhbHNlKTtcbiAgICB9XG5cbiAgICBhc3luYyB0b2dnbGVBbGwgKGV2ZW50KSB7XG4gICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG5cbiAgICAgICAgY29uc3QgaHJlZiA9IGV2ZW50LmN1cnJlbnRUYXJnZXQuaHJlZjtcblxuICAgICAgICBpZiAodGhpcy5oYXNFeHBhbmRlZFJvb3QoKSBeIChldmVudCA/IGV2ZW50LmFsdEtleSA6IGZhbHNlKSkge1xuICAgICAgICAgICAgdGhpcy51cGRhdGVBbGxTdGF0ZShocmVmLCAwKTtcbiAgICAgICAgICAgIHRoaXMudG9nZ2xlVGFyZ2V0cy5mb3JFYWNoKChlbCkgPT4gdGhpcy5jb2xsYXBzZVRvZ2dsZXIoZWwpKTtcbiAgICAgICAgICAgIHRoaXMuY2hpbGRUYXJnZXRzLmZvckVhY2goKGl0ZW0pID0+IGl0ZW0uc3R5bGUuZGlzcGxheSA9ICdub25lJyk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICB0aGlzLmNoaWxkVGFyZ2V0cy5mb3JFYWNoKChlbCkgPT4gZWwucmVtb3ZlKCkpO1xuICAgICAgICAgICAgdGhpcy50b2dnbGVUYXJnZXRzLmZvckVhY2goKGVsKSA9PiB0aGlzLmxvYWRUb2dnbGVyKGVsLCB0cnVlKSk7XG5cbiAgICAgICAgICAgIGF3YWl0IHRoaXMudXBkYXRlQWxsU3RhdGUoaHJlZiwgMSk7XG4gICAgICAgICAgICBjb25zdCBwcm9taXNlcyA9IFtdO1xuXG4gICAgICAgICAgICB0aGlzLnRvZ2dsZVRhcmdldHMuZm9yRWFjaCgoZWwpID0+IHtcbiAgICAgICAgICAgICAgICBwcm9taXNlcy5wdXNoKHRoaXMuZmV0Y2hDaGlsZChcbiAgICAgICAgICAgICAgICAgICAgZWwsXG4gICAgICAgICAgICAgICAgICAgIGVsLmdldEF0dHJpYnV0ZShgZGF0YS0ke3RoaXMuaWRlbnRpZmllcn0taWQtcGFyYW1gKSxcbiAgICAgICAgICAgICAgICAgICAgMCxcbiAgICAgICAgICAgICAgICAgICAgZWwuZ2V0QXR0cmlidXRlKGBkYXRhLSR7dGhpcy5pZGVudGlmaWVyfS1mb2xkZXItcGFyYW1gKVxuICAgICAgICAgICAgICAgICkpO1xuICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgIGF3YWl0IFByb21pc2UuYWxsKHByb21pc2VzKTtcbiAgICAgICAgfVxuXG4gICAgICAgIHRoaXMudXBkYXRlT3BlcmF0aW9uKCk7XG4gICAgfVxuXG4gICAga2V5cHJlc3MgKGV2ZW50KSB7XG4gICAgICAgIHRoaXMudXBkYXRlT3BlcmF0aW9uKGV2ZW50KTtcbiAgICB9XG5cbiAgICBhc3luYyB1cGRhdGVTdGF0ZSAoZWwsIGlkLCBzdGF0ZSkge1xuICAgICAgICBhd2FpdCBmZXRjaChsb2NhdGlvbi5ocmVmLCB7XG4gICAgICAgICAgICBtZXRob2Q6ICdQT1NUJyxcbiAgICAgICAgICAgIGhlYWRlcnM6IHtcbiAgICAgICAgICAgICAgICAnQ29udGVudC1UeXBlJzogJ2FwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZCcsXG4gICAgICAgICAgICAgICAgJ1gtUmVxdWVzdGVkLVdpdGgnOiAnWE1MSHR0cFJlcXVlc3QnXG4gICAgICAgICAgICB9LFxuICAgICAgICAgICAgYm9keTogbmV3IFVSTFNlYXJjaFBhcmFtcyh7XG4gICAgICAgICAgICAgICAgJ2FjdGlvbic6IHRoaXMudG9nZ2xlQWN0aW9uVmFsdWUsXG4gICAgICAgICAgICAgICAgJ2lkJzogaWQsXG4gICAgICAgICAgICAgICAgJ3N0YXRlJzogc3RhdGUsXG4gICAgICAgICAgICAgICAgJ1JFUVVFU1RfVE9LRU4nOiB0aGlzLnJlcXVlc3RUb2tlblZhbHVlXG4gICAgICAgICAgICB9KVxuICAgICAgICB9KTtcbiAgICB9XG5cbiAgICBhc3luYyB1cGRhdGVBbGxTdGF0ZSAoaHJlZiwgc3RhdGUpIHtcbiAgICAgICAgYXdhaXQgZmV0Y2goYCR7aHJlZn0mc3RhdGU9JHtzdGF0ZX1gKTtcbiAgICB9XG5cbiAgICB1cGRhdGVPcGVyYXRpb24gKGV2ZW50KSB7XG4gICAgICAgIGlmICghdGhpcy5oYXNPcGVyYXRpb25UYXJnZXQpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIGlmICh0aGlzLmhhc0V4cGFuZGVkUm9vdCgpIF4gKGV2ZW50ID8gZXZlbnQuYWx0S2V5IDogZmFsc2UpKSB7XG4gICAgICAgICAgICB0aGlzLm9wZXJhdGlvblRhcmdldC5pbm5lclRleHQgPSB0aGlzLmNvbGxhcHNlQWxsVmFsdWU7XG4gICAgICAgICAgICB0aGlzLm9wZXJhdGlvblRhcmdldC50aXRsZSA9IHRoaXMuY29sbGFwc2VBbGxUaXRsZVZhbHVlO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgdGhpcy5vcGVyYXRpb25UYXJnZXQuaW5uZXJUZXh0ID0gdGhpcy5leHBhbmRBbGxWYWx1ZTtcbiAgICAgICAgICAgIHRoaXMub3BlcmF0aW9uVGFyZ2V0LnRpdGxlID0gdGhpcy5leHBhbmRBbGxUaXRsZVZhbHVlO1xuICAgICAgICB9XG4gICAgfVxuXG4gICAgaGFzRXhwYW5kZWRSb290ICgpIHtcbiAgICAgICAgcmV0dXJuICEhdGhpcy5yb290Q2hpbGRUYXJnZXRzLmZpbmQoKGVsKSA9PiBlbC5zdHlsZS5kaXNwbGF5ICE9PSAnbm9uZScpXG4gICAgfVxufVxuIiwiLyoqXG4gKiBQcm92aWRlIG1ldGhvZHMgdG8gaGFuZGxlIEFqYXggcmVxdWVzdHMuXG4gKlxuICogQGF1dGhvciBMZW8gRmV5ZXIgPGh0dHBzOi8vZ2l0aHViLmNvbS9sZW9mZXllcj5cbiAqL1xud2luZG93LkFqYXhSZXF1ZXN0ID1cbntcblx0LyoqXG5cdCAqIFRvZ2dsZSB0aGUgbmF2aWdhdGlvbiBtZW51XG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBlbCAgVGhlIERPTSBlbGVtZW50XG5cdCAqIEBwYXJhbSB7c3RyaW5nfSBpZCAgVGhlIElEIG9mIHRoZSBtZW51IGl0ZW1cblx0ICogQHBhcmFtIHtzdHJpbmd9IHVybCBUaGUgQWpheCBVUkxcblx0ICpcblx0ICogQHJldHVybnMge2Jvb2xlYW59XG5cdCAqL1xuXHR0b2dnbGVOYXZpZ2F0aW9uOiBmdW5jdGlvbihlbCwgaWQsIHVybCkge1xuXHRcdGlmICh3aW5kb3cuY29uc29sZSkge1xuXHRcdFx0Y29uc29sZS53YXJuKCdBamF4UmVxdWVzdC50b2dnbGVOYXZpZ2F0aW9uKCkgaXMgZGVwcmVjYXRlZC4gUGxlYXNlIHVzZSB0aGUgc3RpbXVsdXMgY29udHJvbGxlciBpbnN0ZWFkLicpO1xuXHRcdH1cblxuXHRcdHZhciBpdGVtID0gJChpZCksXG5cdFx0XHRwYXJlbnQgPSAkKGVsKS5nZXRQYXJlbnQoJ2xpJyk7XG5cblx0XHRpZiAoaXRlbSkge1xuXHRcdFx0aWYgKHBhcmVudC5oYXNDbGFzcygnY29sbGFwc2VkJykpIHtcblx0XHRcdFx0cGFyZW50LnJlbW92ZUNsYXNzKCdjb2xsYXBzZWQnKTtcblx0XHRcdFx0JChlbCkuc2V0QXR0cmlidXRlKCdhcmlhLWV4cGFuZGVkJywgJ3RydWUnKTtcblx0XHRcdFx0JChlbCkuc2V0QXR0cmlidXRlKCd0aXRsZScsIENvbnRhby5sYW5nLmNvbGxhcHNlKTtcblx0XHRcdFx0bmV3IFJlcXVlc3QuQ29udGFvKHsgdXJsOiB1cmwgfSkucG9zdCh7J2FjdGlvbic6J3RvZ2dsZU5hdmlnYXRpb24nLCAnaWQnOmlkLCAnc3RhdGUnOjEsICdSRVFVRVNUX1RPS0VOJzpDb250YW8ucmVxdWVzdF90b2tlbn0pO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0cGFyZW50LmFkZENsYXNzKCdjb2xsYXBzZWQnKTtcblx0XHRcdFx0JChlbCkuc2V0QXR0cmlidXRlKCdhcmlhLWV4cGFuZGVkJywgJ2ZhbHNlJyk7XG5cdFx0XHRcdCQoZWwpLnNldEF0dHJpYnV0ZSgndGl0bGUnLCBDb250YW8ubGFuZy5leHBhbmQpO1xuXHRcdFx0XHRuZXcgUmVxdWVzdC5Db250YW8oeyB1cmw6IHVybCB9KS5wb3N0KHsnYWN0aW9uJzondG9nZ2xlTmF2aWdhdGlvbicsICdpZCc6aWQsICdzdGF0ZSc6MCwgJ1JFUVVFU1RfVE9LRU4nOkNvbnRhby5yZXF1ZXN0X3Rva2VufSk7XG5cdFx0XHR9XG5cdFx0XHRyZXR1cm4gZmFsc2U7XG5cdFx0fVxuXG5cdFx0cmV0dXJuIGZhbHNlO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBUb2dnbGUgdGhlIHBhZ2UgdHJlZVxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZWwgICAgVGhlIERPTSBlbGVtZW50XG5cdCAqIEBwYXJhbSB7c3RyaW5nfSBpZCAgICBUaGUgSUQgb2YgdGhlIHRhcmdldCBlbGVtZW50XG5cdCAqIEBwYXJhbSB7aW50fSAgICBsZXZlbCBUaGUgaW5kZW50YXRpb24gbGV2ZWxcblx0ICogQHBhcmFtIHtpbnR9ICAgIG1vZGUgIFRoZSBpbnNlcnQgbW9kZVxuXHQgKlxuXHQgKiBAcmV0dXJucyB7Ym9vbGVhbn1cblx0ICovXG5cdHRvZ2dsZVN0cnVjdHVyZTogZnVuY3Rpb24oZWwsIGlkLCBsZXZlbCwgbW9kZSkge1xuXHRcdGlmICh3aW5kb3cuY29uc29sZSkge1xuXHRcdFx0Y29uc29sZS53YXJuKCdBamF4UmVxdWVzdC50b2dnbGVTdHJ1Y3R1cmUoKSBpcyBkZXByZWNhdGVkLiBQbGVhc2UgdXNlIHRoZSBzdGltdWx1cyBjb250cm9sbGVyIGluc3RlYWQuJyk7XG5cdFx0fVxuXG5cdFx0dmFyIGl0ZW0gPSAkKGlkKTtcblxuXHRcdGlmIChpdGVtKSB7XG5cdFx0XHRpZiAoaXRlbS5nZXRTdHlsZSgnZGlzcGxheScpID09ICdub25lJykge1xuXHRcdFx0XHRpdGVtLnNldFN0eWxlKCdkaXNwbGF5JywgbnVsbCk7XG5cblx0XHRcdFx0JChlbCkuYWRkQ2xhc3MoJ2ZvbGRhYmxlLS1vcGVuJyk7XG5cdFx0XHRcdCQoZWwpLnNldEF0dHJpYnV0ZSgndGl0bGUnLCBDb250YW8ubGFuZy5jb2xsYXBzZSk7XG5cblx0XHRcdFx0bmV3IFJlcXVlc3QuQ29udGFvKHtmaWVsZDplbH0pLnBvc3QoeydhY3Rpb24nOid0b2dnbGVTdHJ1Y3R1cmUnLCAnaWQnOmlkLCAnc3RhdGUnOjEsICdSRVFVRVNUX1RPS0VOJzpDb250YW8ucmVxdWVzdF90b2tlbn0pO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0aXRlbS5zZXRTdHlsZSgnZGlzcGxheScsICdub25lJyk7XG5cblx0XHRcdFx0JChlbCkucmVtb3ZlQ2xhc3MoJ2ZvbGRhYmxlLS1vcGVuJyk7XG5cdFx0XHRcdCQoZWwpLnNldEF0dHJpYnV0ZSgndGl0bGUnLCBDb250YW8ubGFuZy5leHBhbmQpO1xuXG5cdFx0XHRcdG5ldyBSZXF1ZXN0LkNvbnRhbyh7ZmllbGQ6ZWx9KS5wb3N0KHsnYWN0aW9uJzondG9nZ2xlU3RydWN0dXJlJywgJ2lkJzppZCwgJ3N0YXRlJzowLCAnUkVRVUVTVF9UT0tFTic6Q29udGFvLnJlcXVlc3RfdG9rZW59KTtcblx0XHRcdH1cblx0XHRcdHJldHVybiBmYWxzZTtcblx0XHR9XG5cblx0XHRuZXcgUmVxdWVzdC5Db250YW8oe1xuXHRcdFx0ZmllbGQ6IGVsLFxuXHRcdFx0ZXZhbFNjcmlwdHM6IHRydWUsXG5cdFx0XHRvblJlcXVlc3Q6IGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRBamF4UmVxdWVzdC5kaXNwbGF5Qm94KENvbnRhby5sYW5nLmxvYWRpbmcgKyAnIOKApicpO1xuXHRcdFx0fSxcblx0XHRcdG9uU3VjY2VzczogZnVuY3Rpb24odHh0KSB7XG5cdFx0XHRcdHZhciBsaSA9IG5ldyBFbGVtZW50KCdsaScsIHtcblx0XHRcdFx0XHQnaWQnOiBpZCxcblx0XHRcdFx0XHQnY2xhc3MnOiAncGFyZW50Jyxcblx0XHRcdFx0XHQnc3R5bGVzJzoge1xuXHRcdFx0XHRcdFx0J2Rpc3BsYXknOiAnaW5saW5lJ1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSk7XG5cblx0XHRcdFx0bmV3IEVsZW1lbnQoJ3VsJywge1xuXHRcdFx0XHRcdCdjbGFzcyc6ICdsZXZlbF8nICsgbGV2ZWwsXG5cdFx0XHRcdFx0J2h0bWwnOiB0eHRcblx0XHRcdFx0fSkuaW5qZWN0KGxpLCAnYm90dG9tJyk7XG5cblx0XHRcdFx0aWYgKG1vZGUgPT0gNSkge1xuXHRcdFx0XHRcdGxpLmluamVjdCgkKGVsKS5nZXRQYXJlbnQoJ2xpJyksICdhZnRlcicpO1xuXHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdHZhciBmb2xkZXIgPSBmYWxzZSxcblx0XHRcdFx0XHRcdHBhcmVudCA9ICQoZWwpLmdldFBhcmVudCgnbGknKSxcblx0XHRcdFx0XHRcdG5leHQ7XG5cblx0XHRcdFx0XHR3aGlsZSAodHlwZU9mKHBhcmVudCkgPT0gJ2VsZW1lbnQnICYmIChuZXh0ID0gcGFyZW50LmdldE5leHQoJ2xpJykpKSB7XG5cdFx0XHRcdFx0XHRwYXJlbnQgPSBuZXh0O1xuXHRcdFx0XHRcdFx0aWYgKHBhcmVudC5oYXNDbGFzcygndGxfZm9sZGVyJykpIHtcblx0XHRcdFx0XHRcdFx0Zm9sZGVyID0gdHJ1ZTtcblx0XHRcdFx0XHRcdFx0YnJlYWs7XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0aWYgKGZvbGRlcikge1xuXHRcdFx0XHRcdFx0bGkuaW5qZWN0KHBhcmVudCwgJ2JlZm9yZScpO1xuXHRcdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0XHRsaS5pbmplY3QocGFyZW50LCAnYWZ0ZXInKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblxuXHRcdFx0XHQvLyBVcGRhdGUgdGhlIHJlZmVyZXIgSURcblx0XHRcdFx0bGkuZ2V0RWxlbWVudHMoJ2EnKS5lYWNoKGZ1bmN0aW9uKGVsKSB7XG5cdFx0XHRcdFx0ZWwuaHJlZiA9IGVsLmhyZWYucmVwbGFjZSgvJnJlZj1bYS1mMC05XSsvLCAnJnJlZj0nICsgQ29udGFvLnJlZmVyZXJfaWQpO1xuXHRcdFx0XHR9KTtcblxuXHRcdFx0XHQkKGVsKS5hZGRDbGFzcygnZm9sZGFibGUtLW9wZW4nKTtcblx0XHRcdFx0JChlbCkuc2V0QXR0cmlidXRlKCd0aXRsZScsIENvbnRhby5sYW5nLmNvbGxhcHNlKTtcblxuXHRcdFx0XHR3aW5kb3cuZmlyZUV2ZW50KCdzdHJ1Y3R1cmUnKTtcblx0XHRcdFx0QWpheFJlcXVlc3QuaGlkZUJveCgpO1xuXG5cdFx0XHRcdC8vIEhPT0tcblx0XHRcdFx0d2luZG93LmZpcmVFdmVudCgnYWpheF9jaGFuZ2UnKTtcbiAgIFx0XHRcdH1cblx0XHR9KS5wb3N0KHsnYWN0aW9uJzonbG9hZFN0cnVjdHVyZScsICdpZCc6aWQsICdsZXZlbCc6bGV2ZWwsICdzdGF0ZSc6MSwgJ1JFUVVFU1RfVE9LRU4nOkNvbnRhby5yZXF1ZXN0X3Rva2VufSk7XG5cblx0XHRyZXR1cm4gZmFsc2U7XG5cdH0sXG5cblx0LyoqXG5cdCAqIFRvZ2dsZSB0aGUgZmlsZSB0cmVlXG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBlbCAgICAgVGhlIERPTSBlbGVtZW50XG5cdCAqIEBwYXJhbSB7c3RyaW5nfSBpZCAgICAgVGhlIElEIG9mIHRoZSB0YXJnZXQgZWxlbWVudFxuXHQgKiBAcGFyYW0ge3N0cmluZ30gZm9sZGVyIFRoZSBmb2xkZXIncyBwYXRoXG5cdCAqIEBwYXJhbSB7aW50fSAgICBsZXZlbCAgVGhlIGluZGVudGF0aW9uIGxldmVsXG5cdCAqXG5cdCAqIEByZXR1cm5zIHtib29sZWFufVxuXHQgKi9cblx0dG9nZ2xlRmlsZU1hbmFnZXI6IGZ1bmN0aW9uKGVsLCBpZCwgZm9sZGVyLCBsZXZlbCkge1xuXHRcdGlmICh3aW5kb3cuY29uc29sZSkge1xuXHRcdFx0Y29uc29sZS53YXJuKCdBamF4UmVxdWVzdC50b2dnbGVGaWxlTWFuYWdlcigpIGlzIGRlcHJlY2F0ZWQuIFBsZWFzZSB1c2UgdGhlIHN0aW11bHVzIGNvbnRyb2xsZXIgaW5zdGVhZC4nKTtcblx0XHR9XG5cblx0XHR2YXIgaXRlbSA9ICQoaWQpO1xuXG5cdFx0aWYgKGl0ZW0pIHtcblx0XHRcdGlmIChpdGVtLmdldFN0eWxlKCdkaXNwbGF5JykgPT0gJ25vbmUnKSB7XG5cdFx0XHRcdGl0ZW0uc2V0U3R5bGUoJ2Rpc3BsYXknLCBudWxsKTtcblxuXHRcdFx0XHQkKGVsKS5hZGRDbGFzcygnZm9sZGFibGUtLW9wZW4nKTtcblx0XHRcdFx0JChlbCkuc2V0QXR0cmlidXRlKCd0aXRsZScsIENvbnRhby5sYW5nLmNvbGxhcHNlKTtcblxuXHRcdFx0XHRuZXcgUmVxdWVzdC5Db250YW8oe2ZpZWxkOmVsfSkucG9zdCh7J2FjdGlvbic6J3RvZ2dsZUZpbGVNYW5hZ2VyJywgJ2lkJzppZCwgJ3N0YXRlJzoxLCAnUkVRVUVTVF9UT0tFTic6Q29udGFvLnJlcXVlc3RfdG9rZW59KTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdGl0ZW0uc2V0U3R5bGUoJ2Rpc3BsYXknLCAnbm9uZScpO1xuXG5cdFx0XHRcdCQoZWwpLnJlbW92ZUNsYXNzKCdmb2xkYWJsZS0tb3BlbicpO1xuXHRcdFx0XHQkKGVsKS5zZXRBdHRyaWJ1dGUoJ3RpdGxlJywgQ29udGFvLmxhbmcuZXhwYW5kKTtcblxuXHRcdFx0XHRuZXcgUmVxdWVzdC5Db250YW8oe2ZpZWxkOmVsfSkucG9zdCh7J2FjdGlvbic6J3RvZ2dsZUZpbGVNYW5hZ2VyJywgJ2lkJzppZCwgJ3N0YXRlJzowLCAnUkVRVUVTVF9UT0tFTic6Q29udGFvLnJlcXVlc3RfdG9rZW59KTtcblx0XHRcdH1cblx0XHRcdHJldHVybiBmYWxzZTtcblx0XHR9XG5cblx0XHRuZXcgUmVxdWVzdC5Db250YW8oe1xuXHRcdFx0ZmllbGQ6IGVsLFxuXHRcdFx0ZXZhbFNjcmlwdHM6IHRydWUsXG5cdFx0XHRvblJlcXVlc3Q6IGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRBamF4UmVxdWVzdC5kaXNwbGF5Qm94KENvbnRhby5sYW5nLmxvYWRpbmcgKyAnIOKApicpO1xuXHRcdFx0fSxcblx0XHRcdG9uU3VjY2VzczogZnVuY3Rpb24odHh0KSB7XG5cdFx0XHRcdHZhciBsaSA9IG5ldyBFbGVtZW50KCdsaScsIHtcblx0XHRcdFx0XHQnaWQnOiBpZCxcblx0XHRcdFx0XHQnY2xhc3MnOiAncGFyZW50Jyxcblx0XHRcdFx0XHQnc3R5bGVzJzoge1xuXHRcdFx0XHRcdFx0J2Rpc3BsYXknOiAnaW5saW5lJ1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSk7XG5cblx0XHRcdFx0bmV3IEVsZW1lbnQoJ3VsJywge1xuXHRcdFx0XHRcdCdjbGFzcyc6ICdsZXZlbF8nICsgbGV2ZWwsXG5cdFx0XHRcdFx0J2h0bWwnOiB0eHRcblx0XHRcdFx0fSkuaW5qZWN0KGxpLCAnYm90dG9tJyk7XG5cblx0XHRcdFx0bGkuaW5qZWN0KCQoZWwpLmdldFBhcmVudCgnbGknKSwgJ2FmdGVyJyk7XG5cblx0XHRcdFx0Ly8gVXBkYXRlIHRoZSByZWZlcmVyIElEXG5cdFx0XHRcdGxpLmdldEVsZW1lbnRzKCdhJykuZWFjaChmdW5jdGlvbihlbCkge1xuXHRcdFx0XHRcdGVsLmhyZWYgPSBlbC5ocmVmLnJlcGxhY2UoLyZyZWY9W2EtZjAtOV0rLywgJyZyZWY9JyArIENvbnRhby5yZWZlcmVyX2lkKTtcblx0XHRcdFx0fSk7XG5cblx0XHRcdFx0JChlbCkuYWRkQ2xhc3MoJ2ZvbGRhYmxlLS1vcGVuJyk7XG5cdFx0XHRcdCQoZWwpLnNldEF0dHJpYnV0ZSgndGl0bGUnLCBDb250YW8ubGFuZy5jb2xsYXBzZSk7XG5cblx0XHRcdFx0QWpheFJlcXVlc3QuaGlkZUJveCgpO1xuXG5cdFx0XHRcdC8vIEhPT0tcblx0XHRcdFx0d2luZG93LmZpcmVFdmVudCgnYWpheF9jaGFuZ2UnKTtcbiAgIFx0XHRcdH1cblx0XHR9KS5wb3N0KHsnYWN0aW9uJzonbG9hZEZpbGVNYW5hZ2VyJywgJ2lkJzppZCwgJ2xldmVsJzpsZXZlbCwgJ2ZvbGRlcic6Zm9sZGVyLCAnc3RhdGUnOjEsICdSRVFVRVNUX1RPS0VOJzpDb250YW8ucmVxdWVzdF90b2tlbn0pO1xuXG5cdFx0cmV0dXJuIGZhbHNlO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBUb2dnbGUgc3ViLXBhbGV0dGVzIGluIGVkaXQgbW9kZVxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZWwgICAgVGhlIERPTSBlbGVtZW50XG5cdCAqIEBwYXJhbSB7c3RyaW5nfSBpZCAgICBUaGUgSUQgb2YgdGhlIHRhcmdldCBlbGVtZW50XG5cdCAqIEBwYXJhbSB7c3RyaW5nfSBmaWVsZCBUaGUgZmllbGQgbmFtZVxuXHQgKi9cblx0dG9nZ2xlU3VicGFsZXR0ZTogZnVuY3Rpb24oZWwsIGlkLCBmaWVsZCkge1xuXHRcdHZhciBpdGVtID0gJChpZCk7XG5cblx0XHRpZiAoaXRlbSkge1xuXHRcdFx0aWYgKCFlbC52YWx1ZSkge1xuXHRcdFx0XHRlbC52YWx1ZSA9IDE7XG5cdFx0XHRcdGVsLmNoZWNrZWQgPSAnY2hlY2tlZCc7XG5cdFx0XHRcdGl0ZW0uc2V0U3R5bGUoJ2Rpc3BsYXknLCBudWxsKTtcblx0XHRcdFx0aXRlbS5nZXRFbGVtZW50cygnW2RhdGEtcmVxdWlyZWRdJykuZWFjaChmdW5jdGlvbihlbCkge1xuXHRcdFx0XHRcdGVsLnNldCgncmVxdWlyZWQnLCAnJykuc2V0KCdkYXRhLXJlcXVpcmVkJywgbnVsbCk7XG5cdFx0XHRcdH0pO1xuXHRcdFx0XHRuZXcgUmVxdWVzdC5Db250YW8oe2ZpZWxkOiBlbCwgb25TdWNjZXNzOnVwZGF0ZVZlcnNpb25OdW1iZXJ9KS5wb3N0KHsnYWN0aW9uJzondG9nZ2xlU3VicGFsZXR0ZScsICdpZCc6aWQsICdmaWVsZCc6ZmllbGQsICdzdGF0ZSc6MSwgJ1JFUVVFU1RfVE9LRU4nOkNvbnRhby5yZXF1ZXN0X3Rva2VufSk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRlbC52YWx1ZSA9ICcnO1xuXHRcdFx0XHRlbC5jaGVja2VkID0gJyc7XG5cdFx0XHRcdGl0ZW0uc2V0U3R5bGUoJ2Rpc3BsYXknLCAnbm9uZScpO1xuXHRcdFx0XHRpdGVtLmdldEVsZW1lbnRzKCdbcmVxdWlyZWRdJykuZWFjaChmdW5jdGlvbihlbCkge1xuXHRcdFx0XHRcdGVsLnNldCgncmVxdWlyZWQnLCBudWxsKS5zZXQoJ2RhdGEtcmVxdWlyZWQnLCAnJyk7XG5cdFx0XHRcdH0pO1xuXHRcdFx0XHRuZXcgUmVxdWVzdC5Db250YW8oe2ZpZWxkOiBlbCwgb25TdWNjZXNzOnVwZGF0ZVZlcnNpb25OdW1iZXJ9KS5wb3N0KHsnYWN0aW9uJzondG9nZ2xlU3VicGFsZXR0ZScsICdpZCc6aWQsICdmaWVsZCc6ZmllbGQsICdzdGF0ZSc6MCwgJ1JFUVVFU1RfVE9LRU4nOkNvbnRhby5yZXF1ZXN0X3Rva2VufSk7XG5cdFx0XHR9XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0bmV3IFJlcXVlc3QuQ29udGFvKHtcblx0XHRcdGZpZWxkOiBlbCxcblx0XHRcdGV2YWxTY3JpcHRzOiBmYWxzZSxcblx0XHRcdG9uUmVxdWVzdDogZnVuY3Rpb24oKSB7XG5cdFx0XHRcdEFqYXhSZXF1ZXN0LmRpc3BsYXlCb3goQ29udGFvLmxhbmcubG9hZGluZyArICcg4oCmJyk7XG5cdFx0XHR9LFxuXHRcdFx0b25TdWNjZXNzOiBmdW5jdGlvbih0eHQsIGpzb24pIHtcblx0XHRcdFx0dmFyIGRpdiA9IG5ldyBFbGVtZW50KCdkaXYnLCB7XG5cdFx0XHRcdFx0J2lkJzogaWQsXG5cdFx0XHRcdFx0J2NsYXNzJzogJ3N1YnBhbCBjZicsXG5cdFx0XHRcdFx0J2h0bWwnOiB0eHQsXG5cdFx0XHRcdFx0J3N0eWxlcyc6IHtcblx0XHRcdFx0XHRcdCdkaXNwbGF5JzogJ2Jsb2NrJ1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSkuaW5qZWN0KCQoZWwpLmdldFBhcmVudCgnZGl2JykuZ2V0UGFyZW50KCdkaXYnKSwgJ2FmdGVyJyk7XG5cblx0XHRcdFx0Ly8gRXhlY3V0ZSBzY3JpcHRzIGFmdGVyIHRoZSBET00gaGFzIGJlZW4gdXBkYXRlZFxuXHRcdFx0XHRpZiAoanNvbi5qYXZhc2NyaXB0KSB7XG5cblx0XHRcdFx0XHQvLyBVc2UgQXNzZXQuamF2YXNjcmlwdCgpIGluc3RlYWQgb2YgZG9jdW1lbnQud3JpdGUoKSB0byBsb2FkIGFcblx0XHRcdFx0XHQvLyBKYXZhU2NyaXB0IGZpbGUgYW5kIHJlLWV4ZWN1dGUgdGhlIGNvZGUgYWZ0ZXIgaXQgaGFzIGJlZW4gbG9hZGVkXG5cdFx0XHRcdFx0ZG9jdW1lbnQud3JpdGUgPSBmdW5jdGlvbihzdHIpIHtcblx0XHRcdFx0XHRcdHZhciBzcmMgPSAnJztcblx0XHRcdFx0XHRcdHN0ci5yZXBsYWNlKC88c2NyaXB0IHNyYz1cIihbXlwiXSspXCIvaSwgZnVuY3Rpb24oYWxsLCBtYXRjaCl7XG5cdFx0XHRcdFx0XHRcdHNyYyA9IG1hdGNoO1xuXHRcdFx0XHRcdFx0fSk7XG5cdFx0XHRcdFx0XHRzcmMgJiYgQXNzZXQuamF2YXNjcmlwdChzcmMsIHtcblx0XHRcdFx0XHRcdFx0b25Mb2FkOiBmdW5jdGlvbigpIHtcblx0XHRcdFx0XHRcdFx0XHRCcm93c2VyLmV4ZWMoanNvbi5qYXZhc2NyaXB0KTtcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0fSk7XG5cdFx0XHRcdFx0fTtcblxuXHRcdFx0XHRcdEJyb3dzZXIuZXhlYyhqc29uLmphdmFzY3JpcHQpO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0ZWwudmFsdWUgPSAxO1xuXHRcdFx0XHRlbC5jaGVja2VkID0gJ2NoZWNrZWQnO1xuXG5cdFx0XHRcdC8vIFVwZGF0ZSB0aGUgcmVmZXJlciBJRFxuXHRcdFx0XHRkaXYuZ2V0RWxlbWVudHMoJ2EnKS5lYWNoKGZ1bmN0aW9uKGVsKSB7XG5cdFx0XHRcdFx0ZWwuaHJlZiA9IGVsLmhyZWYucmVwbGFjZSgvJnJlZj1bYS1mMC05XSsvLCAnJnJlZj0nICsgQ29udGFvLnJlZmVyZXJfaWQpO1xuXHRcdFx0XHR9KTtcblxuXHRcdFx0XHR1cGRhdGVWZXJzaW9uTnVtYmVyKHR4dCk7XG5cblx0XHRcdFx0QWpheFJlcXVlc3QuaGlkZUJveCgpO1xuXHRcdFx0XHR3aW5kb3cuZmlyZUV2ZW50KCdhamF4X2NoYW5nZScpO1xuXHRcdFx0fVxuXHRcdH0pLnBvc3QoeydhY3Rpb24nOid0b2dnbGVTdWJwYWxldHRlJywgJ2lkJzppZCwgJ2ZpZWxkJzpmaWVsZCwgJ2xvYWQnOjEsICdzdGF0ZSc6MSwgJ1JFUVVFU1RfVE9LRU4nOkNvbnRhby5yZXF1ZXN0X3Rva2VufSk7XG5cblx0XHRmdW5jdGlvbiB1cGRhdGVWZXJzaW9uTnVtYmVyKGh0bWwpIHtcblx0XHRcdHZhciBmaWVsZHMgPSBlbC5mb3JtLmVsZW1lbnRzLlZFUlNJT05fTlVNQkVSIHx8IFtdO1xuXHRcdFx0aWYgKCFmaWVsZHMuZm9yRWFjaCkge1xuXHRcdFx0XHRmaWVsZHMgPSBbZmllbGRzXTtcblx0XHRcdH1cblx0XHRcdGZpZWxkcy5mb3JFYWNoKGZ1bmN0aW9uKGZpZWxkKSB7XG5cdFx0XHRcdGZpZWxkLnZhbHVlID0gLzxpbnB1dFxccytbXj5dKj9uYW1lPVwiVkVSU0lPTl9OVU1CRVJcIlxccytbXj5dKj92YWx1ZT1cIihbXlwiXSopXCIvaS5leGVjKGh0bWwpWzFdO1xuXHRcdFx0fSk7XG5cdFx0fVxuXHR9LFxuXG5cdC8qKlxuXHQgKiBUb2dnbGUgdGhlIHN0YXRlIG9mIGEgY2hlY2tib3ggZmllbGRcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9ICBlbCAgICAgIFRoZSBET00gZWxlbWVudFxuXHQgKiBAcGFyYW0ge2Jvb2xlYW59IHJvd0ljb24gV2hldGhlciB0aGUgcm93IGljb24gc2hvdWxkIGJlIHRvZ2dsZWQgYXMgd2VsbFxuXHQgKlxuXHQgKiBAcmV0dXJucyB7Ym9vbGVhbn1cblx0ICovXG5cdHRvZ2dsZUZpZWxkOiBmdW5jdGlvbihlbCwgcm93SWNvbikge1xuXHRcdHZhciBpbWcgPSBudWxsLFxuXHRcdFx0aW1hZ2VzID0gJChlbCkuZ2V0RWxlbWVudHMoJ2ltZycpLFxuXHRcdFx0cHVibGlzaGVkID0gKGltYWdlc1swXS5nZXQoJ2RhdGEtc3RhdGUnKSA9PSAxKSxcblx0XHRcdGRpdiA9IGVsLmdldFBhcmVudCgnZGl2JyksXG5cdFx0XHRuZXh0LCBwYTtcblxuXHRcdGlmIChyb3dJY29uKSB7XG5cdFx0XHQvLyBGaW5kIHRoZSBpY29uIGRlcGVuZGluZyBvbiB0aGUgdmlldyAodHJlZSB2aWV3LCBsaXN0IHZpZXcsIHBhcmVudCB2aWV3KVxuXHRcdFx0aWYgKGRpdi5oYXNDbGFzcygndGxfcmlnaHQnKSkge1xuXHRcdFx0XHRpbWcgPSBkaXYuZ2V0UHJldmlvdXMoJ2RpdicpLmdldEVsZW1lbnRzKCdpbWcnKTtcblx0XHRcdH0gZWxzZSBpZiAoZGl2Lmhhc0NsYXNzKCd0bF9saXN0aW5nX2NvbnRhaW5lcicpKSB7XG5cdFx0XHRcdGltZyA9IGVsLmdldFBhcmVudCgndGQnKS5nZXRQcmV2aW91cygndGQnKS5nZXRGaXJzdCgnZGl2Lmxpc3RfaWNvbicpO1xuXHRcdFx0XHRpZiAoaW1nID09PSBudWxsKSB7IC8vIGNvbW1lbnRzXG5cdFx0XHRcdFx0aW1nID0gZWwuZ2V0UGFyZW50KCd0ZCcpLmdldFByZXZpb3VzKCd0ZCcpLmdldEVsZW1lbnQoJ2Rpdi5jdGVfdHlwZScpO1xuXHRcdFx0XHR9XG5cdFx0XHRcdGlmIChpbWcgPT09IG51bGwpIHsgLy8gc2hvd0NvbHVtbnNcblx0XHRcdFx0XHRpbWcgPSBlbC5nZXRQYXJlbnQoJ3RyJykuZ2V0Rmlyc3QoJ3RkJykuZ2V0RWxlbWVudCgnZGl2Lmxpc3RfaWNvbl9uZXcnKTtcblx0XHRcdFx0fVxuXHRcdFx0fSBlbHNlIGlmIChuZXh0ID0gZGl2LmdldE5leHQoJ2RpdicpKSB7XG5cdFx0XHRcdGlmIChuZXh0Lmhhc0NsYXNzKCdjdGVfdHlwZScpKSB7XG5cdFx0XHRcdFx0aW1nID0gbmV4dDtcblx0XHRcdFx0fVxuXHRcdFx0XHRpZiAoaW1nID09PSBudWxsKSB7IC8vIG5ld3NsZXR0ZXIgcmVjaXBpZW50c1xuXHRcdFx0XHRcdGltZyA9IG5leHQuZ2V0Rmlyc3QoJ2Rpdi5saXN0X2ljb24nKTtcblx0XHRcdFx0fVxuXHRcdFx0fVxuXG5cdFx0XHQvLyBDaGFuZ2UgdGhlIHJvdyBpY29uXG5cdFx0XHRpZiAoaW1nICE9PSBudWxsKSB7XG5cdFx0XHRcdC8vIFRyZWUgdmlld1xuXHRcdFx0XHRpZiAoIShpbWcgaW5zdGFuY2VvZiBIVE1MRWxlbWVudCkgJiYgaW1nLmZvckVhY2gpIHtcblx0XHRcdFx0XHRpbWcuZm9yRWFjaCgoaW1nKSA9PiB7XG5cdFx0XHRcdFx0XHRpZiAoaW1nLm5vZGVOYW1lLnRvTG93ZXJDYXNlKCkgPT0gJ2ltZycpIHtcblx0XHRcdFx0XHRcdFx0aWYgKCFpbWcuZ2V0UGFyZW50KCd1bC50bF9saXN0aW5nJykuaGFzQ2xhc3MoJ3RsX3RyZWVfeHRuZCcpKSB7XG5cdFx0XHRcdFx0XHRcdFx0cGEgPSBpbWcuZ2V0UGFyZW50KCdhJyk7XG5cblx0XHRcdFx0XHRcdFx0XHRpZiAocGEgJiYgcGEuaHJlZi5pbmRleE9mKCdjb250YW8vcHJldmlldycpID09IC0xKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRpZiAobmV4dCA9IHBhLmdldE5leHQoJ2EnKSkge1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRpbWcgPSBuZXh0LmdldEVsZW1lbnQoJ2ltZycpO1xuXHRcdFx0XHRcdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0aW1nID0gbmV3IEVsZW1lbnQoJ2ltZycpOyAvLyBubyBpY29ucyB1c2VkIChzZWUgIzIyODYpXG5cdFx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHRcdFx0Y29uc3QgbmV3U3JjID0gIXB1Ymxpc2hlZCA/IGltZy5nZXQoJ2RhdGEtaWNvbicpIDogaW1nLmdldCgnZGF0YS1pY29uLWRpc2FibGVkJyk7XG5cblx0XHRcdFx0XHRcdFx0aWYgKG5ld1NyYykge1xuXHRcdFx0XHRcdFx0XHRcdGltZy5zcmMgPSAoaW1nLnNyYy5pbmNsdWRlcygnLycpICYmICFuZXdTcmMuaW5jbHVkZXMoJy8nKSkgPyBpbWcuc3JjLnNsaWNlKDAsIGltZy5zcmMubGFzdEluZGV4T2YoJy8nKSArIDEpICsgbmV3U3JjIDogbmV3U3JjO1xuXHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fSlcblx0XHRcdFx0fVxuXHRcdFx0XHQvLyBQYXJlbnQgdmlld1xuXHRcdFx0XHRlbHNlIGlmIChpbWcuaGFzQ2xhc3MoJ2N0ZV90eXBlJykpIHtcblx0XHRcdFx0XHRpZiAoIXB1Ymxpc2hlZCkge1xuXHRcdFx0XHRcdFx0aW1nLmFkZENsYXNzKCdwdWJsaXNoZWQnKTtcblx0XHRcdFx0XHRcdGltZy5yZW1vdmVDbGFzcygndW5wdWJsaXNoZWQnKTtcblx0XHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdFx0aW1nLmFkZENsYXNzKCd1bnB1Ymxpc2hlZCcpO1xuXHRcdFx0XHRcdFx0aW1nLnJlbW92ZUNsYXNzKCdwdWJsaXNoZWQnKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblx0XHRcdFx0Ly8gTGlzdCB2aWV3XG5cdFx0XHRcdGVsc2Uge1xuXHRcdFx0XHRcdGltZy5zZXRTdHlsZSgnYmFja2dyb3VuZC1pbWFnZScsICd1cmwoJyArICghcHVibGlzaGVkID8gaW1nLmdldCgnZGF0YS1pY29uJykgOiBpbWcuZ2V0KCdkYXRhLWljb24tZGlzYWJsZWQnKSkgKyAnKScpO1xuXHRcdFx0XHR9XG5cdFx0XHR9XG5cdFx0fVxuXG5cdFx0Ly8gU2VuZCByZXF1ZXN0XG5cdFx0aW1hZ2VzLmZvckVhY2goZnVuY3Rpb24oaW1hZ2UpIHtcblx0XHRcdGNvbnN0IG5ld1NyYyA9ICFwdWJsaXNoZWQgPyBpbWFnZS5nZXQoJ2RhdGEtaWNvbicpIDogaW1hZ2UuZ2V0KCdkYXRhLWljb24tZGlzYWJsZWQnKTtcblx0XHRcdGltYWdlLnNyYyA9IChpbWFnZS5zcmMuaW5jbHVkZXMoJy8nKSAmJiAhbmV3U3JjLmluY2x1ZGVzKCcvJykpID8gaW1hZ2Uuc3JjLnNsaWNlKDAsIGltYWdlLnNyYy5sYXN0SW5kZXhPZignLycpICsgMSkgKyBuZXdTcmMgOiBuZXdTcmM7XG5cdFx0XHRpbWFnZS5zZXQoJ2RhdGEtc3RhdGUnLCAhcHVibGlzaGVkID8gMSA6IDApO1xuXHRcdH0pO1xuXG5cdFx0aWYgKCFwdWJsaXNoZWQgJiYgJChlbCkuZ2V0KCdkYXRhLXRpdGxlJykpIHtcblx0XHRcdGVsLnRpdGxlID0gJChlbCkuZ2V0KCdkYXRhLXRpdGxlJyk7XG5cdFx0fSBlbHNlIGlmIChwdWJsaXNoZWQgJiYgJChlbCkuZ2V0KCdkYXRhLXRpdGxlLWRpc2FibGVkJykpIHtcblx0XHRcdGVsLnRpdGxlID0gJChlbCkuZ2V0KCdkYXRhLXRpdGxlLWRpc2FibGVkJyk7XG5cdFx0fVxuXG5cdFx0bmV3IFJlcXVlc3QuQ29udGFvKHsndXJsJzplbC5ocmVmLCAnZm9sbG93UmVkaXJlY3RzJzpmYWxzZX0pLmdldCgpO1xuXG5cdFx0Ly8gUmV0dXJuIGZhbHNlIHRvIHN0b3AgdGhlIGNsaWNrIGV2ZW50IG9uIGxpbmtcblx0XHRyZXR1cm4gZmFsc2U7XG5cdH0sXG5cblx0LyoqXG5cdCAqIFRvZ2dsZSBhIGdyb3VwIG9mIGEgbXVsdGktY2hlY2tib3ggZmllbGRcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IGVsIFRoZSBET00gZWxlbWVudFxuXHQgKiBAcGFyYW0ge3N0cmluZ30gaWQgVGhlIElEIG9mIHRoZSB0YXJnZXQgZWxlbWVudFxuXHQgKlxuXHQgKiBAcmV0dXJucyB7Ym9vbGVhbn1cblx0ICovXG5cdHRvZ2dsZUNoZWNrYm94R3JvdXA6IGZ1bmN0aW9uKGVsLCBpZCkge1xuXHRcdHZhciBpdGVtID0gJChpZCk7XG5cblx0XHRpZiAoaXRlbSkge1xuXHRcdFx0aWYgKGl0ZW0uZ2V0U3R5bGUoJ2Rpc3BsYXknKSA9PSAnbm9uZScpIHtcblx0XHRcdFx0aXRlbS5zZXRTdHlsZSgnZGlzcGxheScsIG51bGwpO1xuXHRcdFx0XHQkKGVsKS5hZGRDbGFzcygnZm9sZGFibGUtLW9wZW4nKTtcblxuXHRcdFx0XHRuZXcgUmVxdWVzdC5Db250YW8oKS5wb3N0KHsnYWN0aW9uJzondG9nZ2xlQ2hlY2tib3hHcm91cCcsICdpZCc6aWQsICdzdGF0ZSc6MSwgJ1JFUVVFU1RfVE9LRU4nOkNvbnRhby5yZXF1ZXN0X3Rva2VufSk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRpdGVtLnNldFN0eWxlKCdkaXNwbGF5JywgJ25vbmUnKTtcblx0XHRcdFx0JChlbCkucmVtb3ZlQ2xhc3MoJ2ZvbGRhYmxlLS1vcGVuJyk7XG5cblx0XHRcdFx0bmV3IFJlcXVlc3QuQ29udGFvKCkucG9zdCh7J2FjdGlvbic6J3RvZ2dsZUNoZWNrYm94R3JvdXAnLCAnaWQnOmlkLCAnc3RhdGUnOjAsICdSRVFVRVNUX1RPS0VOJzpDb250YW8ucmVxdWVzdF90b2tlbn0pO1xuXHRcdFx0fVxuXHRcdFx0cmV0dXJuIHRydWU7XG5cdFx0fVxuXG5cdFx0cmV0dXJuIGZhbHNlO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBEaXNwbGF5IHRoZSBcImxvYWRpbmcgZGF0YVwiIG1lc3NhZ2Vcblx0ICpcblx0ICogQHBhcmFtIHtzdHJpbmd9IG1lc3NhZ2UgVGhlIG1lc3NhZ2UgdGV4dFxuXHQgKi9cblx0ZGlzcGxheUJveDogZnVuY3Rpb24obWVzc2FnZSkge1xuXHRcdHZhciBib3ggPSAkKCd0bF9hamF4Qm94JyksXG5cdFx0XHRvdmVybGF5ID0gJCgndGxfYWpheE92ZXJsYXknKSxcblx0XHRcdHNjcm9sbCA9IHdpbmRvdy5nZXRTY3JvbGwoKTtcblxuXHRcdGlmIChvdmVybGF5ID09PSBudWxsKSB7XG5cdFx0XHRvdmVybGF5ID0gbmV3IEVsZW1lbnQoJ2RpdicsIHtcblx0XHRcdFx0J2lkJzogJ3RsX2FqYXhPdmVybGF5J1xuXHRcdFx0fSkuaW5qZWN0KCQoZG9jdW1lbnQuYm9keSksICdib3R0b20nKTtcblx0XHR9XG5cblx0XHRvdmVybGF5LnNldCh7XG5cdFx0XHQnc3R5bGVzJzoge1xuXHRcdFx0XHQnZGlzcGxheSc6ICdibG9jaycsXG5cdFx0XHR9XG5cdFx0fSk7XG5cblx0XHRpZiAoYm94ID09PSBudWxsKSB7XG5cdFx0XHRib3ggPSBuZXcgRWxlbWVudCgnZGl2Jywge1xuXHRcdFx0XHQnaWQnOiAndGxfYWpheEJveCdcblx0XHRcdH0pLmluamVjdCgkKGRvY3VtZW50LmJvZHkpLCAnYm90dG9tJyk7XG5cdFx0fVxuXG5cdFx0Ym94LnNldCh7XG5cdFx0XHQnaHRtbCc6IG1lc3NhZ2UsXG5cdFx0XHQnc3R5bGVzJzoge1xuXHRcdFx0XHQnZGlzcGxheSc6ICdibG9jaycsXG5cdFx0XHRcdCd0b3AnOiAoc2Nyb2xsLnkgKyAxMDApICsgJ3B4J1xuXHRcdFx0fVxuXHRcdH0pXG5cdH0sXG5cblx0LyoqXG5cdCAqIEhpZGUgdGhlIFwibG9hZGluZyBkYXRhXCIgbWVzc2FnZVxuXHQgKi9cblx0aGlkZUJveDogZnVuY3Rpb24oKSB7XG5cdFx0dmFyIGJveCA9ICQoJ3RsX2FqYXhCb3gnKSxcblx0XHRcdG92ZXJsYXkgPSAkKCd0bF9hamF4T3ZlcmxheScpO1xuXG5cdFx0aWYgKG92ZXJsYXkpIHtcblx0XHRcdG92ZXJsYXkuc2V0U3R5bGUoJ2Rpc3BsYXknLCAnbm9uZScpO1xuXHRcdH1cblxuXHRcdGlmIChib3gpIHtcblx0XHRcdGJveC5zZXRTdHlsZSgnZGlzcGxheScsICdub25lJyk7XG5cdFx0fVxuXHR9XG59O1xuXG4vKipcbiAqIFByb3ZpZGUgbWV0aG9kcyB0byBoYW5kbGUgYmFjayBlbmQgdGFza3MuXG4gKlxuICogQGF1dGhvciBMZW8gRmV5ZXIgPGh0dHBzOi8vZ2l0aHViLmNvbS9sZW9mZXllcj5cbiAqL1xud2luZG93LkJhY2tlbmQgPVxue1xuXHQvKipcblx0ICogVGhlIGN1cnJlbnQgSURcblx0ICogQG1lbWJlciB7KHN0cmluZ3xudWxsKX1cblx0ICovXG5cdGN1cnJlbnRJZDogbnVsbCxcblxuXHQvKipcblx0ICogVGhlIHBvcHVwIHdpbmRvd1xuXHQgKiBAbWVtYmVyIHtvYmplY3R9XG5cdCAqL1xuXHRwb3B1cFdpbmRvdzogbnVsbCxcblxuXHQvKipcblx0ICogVGhlIHRoZW1lIHBhdGhcblx0ICogQG1lbWJlciB7c3RyaW5nfVxuXHQgKi9cblx0dGhlbWVQYXRoOiBDb250YW8uc2NyaXB0X3VybCArICdzeXN0ZW0vdGhlbWVzLycgKyBDb250YW8udGhlbWUgKyAnLycsXG5cblx0LyoqXG5cdCAqIE9wZW4gYSBtb2RhbCB3aW5kb3dcblx0ICpcblx0ICogQHBhcmFtIHtpbnR9ICAgIHdpZHRoICAgVGhlIHdpZHRoIGluIHBpeGVsc1xuXHQgKiBAcGFyYW0ge3N0cmluZ30gdGl0bGUgICBUaGUgd2luZG93J3MgdGl0bGVcblx0ICogQHBhcmFtIHtzdHJpbmd9IGNvbnRlbnQgVGhlIHdpbmRvdydzIGNvbnRlbnRcblx0ICovXG5cdG9wZW5Nb2RhbFdpbmRvdzogZnVuY3Rpb24od2lkdGgsIHRpdGxlLCBjb250ZW50KSB7XG5cdFx0bmV3IFNpbXBsZU1vZGFsKHtcblx0XHRcdCd3aWR0aCc6IHdpZHRoLFxuXHRcdFx0J2hpZGVGb290ZXInOiB0cnVlLFxuXHRcdFx0J2RyYWdnYWJsZSc6IGZhbHNlLFxuXHRcdFx0J292ZXJsYXlPcGFjaXR5JzogLjcsXG5cdFx0XHQnb3ZlcmxheUNsaWNrJzogZmFsc2UsXG5cdFx0XHQnb25TaG93JzogZnVuY3Rpb24oKSB7IGRvY3VtZW50LmJvZHkuc2V0U3R5bGUoJ292ZXJmbG93JywgJ2hpZGRlbicpOyB9LFxuXHRcdFx0J29uSGlkZSc6IGZ1bmN0aW9uKCkgeyBkb2N1bWVudC5ib2R5LnNldFN0eWxlKCdvdmVyZmxvdycsICdhdXRvJyk7IH1cblx0XHR9KS5zaG93KHtcblx0XHRcdCd0aXRsZSc6IHRpdGxlLFxuXHRcdFx0J2NvbnRlbnRzJzogY29udGVudFxuXHRcdH0pO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBPcGVuIGFuIGltYWdlIGluIGEgbW9kYWwgd2luZG93XG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBvcHRpb25zIEFuIG9wdGlvbmFsIG9wdGlvbnMgb2JqZWN0XG5cdCAqL1xuXHRvcGVuTW9kYWxJbWFnZTogZnVuY3Rpb24ob3B0aW9ucykge1xuXHRcdHZhciBvcHQgPSBvcHRpb25zIHx8IHt9LFxuXHRcdFx0bWF4V2lkdGggPSAod2luZG93LmdldFNpemUoKS54IC0gMjApLnRvSW50KCk7XG5cdFx0aWYgKCFvcHQud2lkdGggfHwgb3B0LndpZHRoID4gbWF4V2lkdGgpIG9wdC53aWR0aCA9IE1hdGgubWluKG1heFdpZHRoLCA5MDApO1xuXHRcdHZhciBNID0gbmV3IFNpbXBsZU1vZGFsKHtcblx0XHRcdCd3aWR0aCc6IG9wdC53aWR0aCxcblx0XHRcdCdoaWRlRm9vdGVyJzogdHJ1ZSxcblx0XHRcdCdkcmFnZ2FibGUnOiBmYWxzZSxcblx0XHRcdCdvdmVybGF5T3BhY2l0eSc6IC43LFxuXHRcdFx0J29uU2hvdyc6IGZ1bmN0aW9uKCkgeyBkb2N1bWVudC5ib2R5LnNldFN0eWxlKCdvdmVyZmxvdycsICdoaWRkZW4nKTsgfSxcblx0XHRcdCdvbkhpZGUnOiBmdW5jdGlvbigpIHsgZG9jdW1lbnQuYm9keS5zZXRTdHlsZSgnb3ZlcmZsb3cnLCAnYXV0bycpOyB9XG5cdFx0fSk7XG5cdFx0TS5zaG93KHtcblx0XHRcdCd0aXRsZSc6IG9wdC50aXRsZT8ucmVwbGFjZSgvJi9nLCAnJmFtcDsnKS5yZXBsYWNlKC88L2csICcmbHQ7JykucmVwbGFjZSgvXCIvZywgJyZxdW90OycpLnJlcGxhY2UoLycvZywgJyZhcG9zOycpLFxuXHRcdFx0J2NvbnRlbnRzJzogJzxpbWcgc3JjPVwiJyArIG9wdC51cmwgKyAnXCIgYWx0PVwiXCI+J1xuXHRcdH0pO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBPcGVuIGFuIGlmcmFtZSBpbiBhIG1vZGFsIHdpbmRvd1xuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gb3B0aW9ucyBBbiBvcHRpb25hbCBvcHRpb25zIG9iamVjdFxuXHQgKi9cblx0b3Blbk1vZGFsSWZyYW1lOiBmdW5jdGlvbihvcHRpb25zKSB7XG5cdFx0dmFyIG9wdCA9IG9wdGlvbnMgfHwge30sXG5cdFx0XHRtYXhXaWR0aCA9ICh3aW5kb3cuZ2V0U2l6ZSgpLnggLSAyMCkudG9JbnQoKSxcblx0XHRcdG1heEhlaWdodCA9ICh3aW5kb3cuZ2V0U2l6ZSgpLnkgLSAxMzcpLnRvSW50KCk7XG5cdFx0aWYgKCFvcHQud2lkdGggfHwgb3B0LndpZHRoID4gbWF4V2lkdGgpIG9wdC53aWR0aCA9IE1hdGgubWluKG1heFdpZHRoLCA5MDApO1xuXHRcdGlmICghb3B0LmhlaWdodCB8fCBvcHQuaGVpZ2h0ID4gbWF4SGVpZ2h0KSBvcHQuaGVpZ2h0ID0gbWF4SGVpZ2h0O1xuXHRcdHZhciBNID0gbmV3IFNpbXBsZU1vZGFsKHtcblx0XHRcdCd3aWR0aCc6IG9wdC53aWR0aCxcblx0XHRcdCdoaWRlRm9vdGVyJzogdHJ1ZSxcblx0XHRcdCdkcmFnZ2FibGUnOiBmYWxzZSxcblx0XHRcdCdvdmVybGF5T3BhY2l0eSc6IC43LFxuXHRcdFx0J292ZXJsYXlDbGljayc6IGZhbHNlLFxuXHRcdFx0J29uU2hvdyc6IGZ1bmN0aW9uKCkgeyBkb2N1bWVudC5ib2R5LnNldFN0eWxlKCdvdmVyZmxvdycsICdoaWRkZW4nKTsgfSxcblx0XHRcdCdvbkhpZGUnOiBmdW5jdGlvbigpIHsgZG9jdW1lbnQuYm9keS5zZXRTdHlsZSgnb3ZlcmZsb3cnLCAnYXV0bycpOyB9XG5cdFx0fSk7XG5cdFx0TS5zaG93KHtcblx0XHRcdCd0aXRsZSc6IG9wdC50aXRsZT8ucmVwbGFjZSgvJi9nLCAnJmFtcDsnKS5yZXBsYWNlKC88L2csICcmbHQ7JykucmVwbGFjZSgvXCIvZywgJyZxdW90OycpLnJlcGxhY2UoLycvZywgJyZhcG9zOycpLFxuXHRcdFx0J2NvbnRlbnRzJzogJzxpZnJhbWUgc3JjPVwiJyArIG9wdC51cmwgKyAnXCIgd2lkdGg9XCIxMDAlXCIgaGVpZ2h0PVwiJyArIG9wdC5oZWlnaHQgKyAnXCIgZnJhbWVib3JkZXI9XCIwXCI+PC9pZnJhbWU+Jyxcblx0XHRcdCdtb2RlbCc6ICdtb2RhbCdcblx0XHR9KTtcblx0fSxcblxuXHQvKipcblx0ICogT3BlbiBhIHNlbGVjdG9yIHBhZ2UgaW4gYSBtb2RhbCB3aW5kb3dcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IG9wdGlvbnMgQW4gb3B0aW9uYWwgb3B0aW9ucyBvYmplY3Rcblx0ICovXG5cdG9wZW5Nb2RhbFNlbGVjdG9yOiBmdW5jdGlvbihvcHRpb25zKSB7XG5cdFx0dmFyIG9wdCA9IG9wdGlvbnMgfHwge30sXG5cdFx0XHRtYXhXaWR0aCA9ICh3aW5kb3cuZ2V0U2l6ZSgpLnggLSAyMCkudG9JbnQoKSxcblx0XHRcdG1heEhlaWdodCA9ICh3aW5kb3cuZ2V0U2l6ZSgpLnkgLSAxOTIpLnRvSW50KCk7XG5cdFx0aWYgKCFvcHQuaWQpIG9wdC5pZCA9ICd0bF9zZWxlY3QnO1xuXHRcdGlmICghb3B0LndpZHRoIHx8IG9wdC53aWR0aCA+IG1heFdpZHRoKSBvcHQud2lkdGggPSBNYXRoLm1pbihtYXhXaWR0aCwgOTAwKTtcblx0XHRpZiAoIW9wdC5oZWlnaHQgfHwgb3B0LmhlaWdodCA+IG1heEhlaWdodCkgb3B0LmhlaWdodCA9IG1heEhlaWdodDtcblx0XHR2YXIgTSA9IG5ldyBTaW1wbGVNb2RhbCh7XG5cdFx0XHQnd2lkdGgnOiBvcHQud2lkdGgsXG5cdFx0XHQnZHJhZ2dhYmxlJzogZmFsc2UsXG5cdFx0XHQnb3ZlcmxheU9wYWNpdHknOiAuNyxcblx0XHRcdCdvdmVybGF5Q2xpY2snOiBmYWxzZSxcblx0XHRcdCdvblNob3cnOiBmdW5jdGlvbigpIHsgZG9jdW1lbnQuYm9keS5zZXRTdHlsZSgnb3ZlcmZsb3cnLCAnaGlkZGVuJyk7IH0sXG5cdFx0XHQnb25IaWRlJzogZnVuY3Rpb24oKSB7IGRvY3VtZW50LmJvZHkuc2V0U3R5bGUoJ292ZXJmbG93JywgJ2F1dG8nKTsgfVxuXHRcdH0pO1xuXHRcdE0uYWRkQnV0dG9uKENvbnRhby5sYW5nLmNhbmNlbCwgJ2J0bicsIGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKHRoaXMuYnV0dG9uc1swXS5oYXNDbGFzcygnYnRuLWRpc2FibGVkJykpIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXHRcdFx0dGhpcy5oaWRlKCk7XG5cdFx0fSk7XG5cdFx0TS5hZGRCdXR0b24oQ29udGFvLmxhbmcuYXBwbHksICdidG4gcHJpbWFyeScsIGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKHRoaXMuYnV0dG9uc1sxXS5oYXNDbGFzcygnYnRuLWRpc2FibGVkJykpIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXHRcdFx0dmFyIGZybSA9IHdpbmRvdy5mcmFtZXNbJ3NpbXBsZS1tb2RhbC1pZnJhbWUnXSxcblx0XHRcdFx0dmFsID0gW10sIHVsLCBpbnAsIGksIHBpY2tlclZhbHVlLCBzSW5kZXg7XG5cdFx0XHRpZiAoZnJtID09PSB1bmRlZmluZWQpIHtcblx0XHRcdFx0YWxlcnQoJ0NvdWxkIG5vdCBmaW5kIHRoZSBTaW1wbGVNb2RhbCBmcmFtZScpO1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cdFx0XHR1bCA9IGZybS5kb2N1bWVudC5nZXRFbGVtZW50QnlJZChvcHQuaWQpO1xuXHRcdFx0Ly8gTG9hZCB0aGUgcHJldmlvdXMgdmFsdWVzICgjMTgxNilcblx0XHRcdGlmIChwaWNrZXJWYWx1ZSA9IHVsLmdldCgnZGF0YS1waWNrZXItdmFsdWUnKSkge1xuXHRcdFx0XHR2YWwgPSBKU09OLnBhcnNlKHBpY2tlclZhbHVlKTtcblx0XHRcdH1cblx0XHRcdGlucCA9IHVsLmdldEVsZW1lbnRzQnlUYWdOYW1lKCdpbnB1dCcpO1xuXHRcdFx0Zm9yIChpPTA7IGk8aW5wLmxlbmd0aDsgaSsrKSB7XG5cdFx0XHRcdGlmIChpbnBbaV0uaWQubWF0Y2goL14oY2hlY2tfYWxsX3xyZXNldF8pLykpIHtcblx0XHRcdFx0XHRjb250aW51ZTtcblx0XHRcdFx0fVxuXHRcdFx0XHQvLyBBZGQgY3VycmVudGx5IHNlbGVjdGVkIHZhbHVlLCBvdGhlcndpc2UgcmVtb3ZlICgjMTgxNilcblx0XHRcdFx0c0luZGV4ID0gdmFsLmluZGV4T2YoaW5wW2ldLmdldCgndmFsdWUnKSk7XG5cdFx0XHRcdGlmIChpbnBbaV0uY2hlY2tlZCkge1xuXHRcdFx0XHRcdGlmIChzSW5kZXggPT0gLTEpIHtcblx0XHRcdFx0XHRcdHZhbC5wdXNoKGlucFtpXS5nZXQoJ3ZhbHVlJykpO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSBlbHNlIGlmIChzSW5kZXggIT0gLTEpIHtcblx0XHRcdFx0XHR2YWwuc3BsaWNlKHNJbmRleCwgMSk7XG5cdFx0XHRcdH1cblx0XHRcdH1cblx0XHRcdG9wdC5jYWxsYmFjayh1bC5nZXQoJ2RhdGEtdGFibGUnKSwgdmFsKTtcblx0XHRcdHRoaXMuaGlkZSgpO1xuXHRcdH0pO1xuXHRcdE0uc2hvdyh7XG5cdFx0XHQndGl0bGUnOiBvcHQudGl0bGU/LnJlcGxhY2UoLyYvZywgJyZhbXA7JykucmVwbGFjZSgvPC9nLCAnJmx0OycpLnJlcGxhY2UoL1wiL2csICcmcXVvdDsnKS5yZXBsYWNlKC8nL2csICcmYXBvczsnKSxcblx0XHRcdCdjb250ZW50cyc6ICc8aWZyYW1lIHNyYz1cIicgKyBvcHQudXJsICsgJ1wiIG5hbWU9XCJzaW1wbGUtbW9kYWwtaWZyYW1lXCIgd2lkdGg9XCIxMDAlXCIgaGVpZ2h0PVwiJyArIG9wdC5oZWlnaHQgKyAnXCIgZnJhbWVib3JkZXI9XCIwXCI+PC9pZnJhbWU+Jyxcblx0XHRcdCdtb2RlbCc6ICdtb2RhbCdcblx0XHR9KTtcblx0fSxcblxuXHQvKipcblx0ICogT3BlbiBhIFRpbnlNQ0UgZmlsZSBicm93c2VyIGluIGEgbW9kYWwgd2luZG93XG5cdCAqXG5cdCAqIEBwYXJhbSB7c3RyaW5nfSBmaWVsZF9uYW1lIFRoZSBmaWVsZCBuYW1lXG5cdCAqIEBwYXJhbSB7c3RyaW5nfSB1cmwgICAgICAgIFRoZSBVUkxcblx0ICogQHBhcmFtIHtzdHJpbmd9IHR5cGUgICAgICAgVGhlIHBpY2tlciB0eXBlXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSB3aW4gICAgICAgIFRoZSB3aW5kb3cgb2JqZWN0XG5cdCAqIEBwYXJhbSB7c3RyaW5nfSBzb3VyY2UgICAgIFRoZSBzb3VyY2UgcmVjb3JkXG5cdCAqL1xuXHRvcGVuTW9kYWxCcm93c2VyOiBmdW5jdGlvbihmaWVsZF9uYW1lLCB1cmwsIHR5cGUsIHdpbiwgc291cmNlKSB7XG5cdFx0QmFja2VuZC5vcGVuTW9kYWxTZWxlY3Rvcih7XG5cdFx0XHQnaWQnOiAndGxfbGlzdGluZycsXG5cdFx0XHQndGl0bGUnOiB3aW4uZG9jdW1lbnQuZ2V0RWxlbWVudCgnZGl2Lm1jZS10aXRsZScpLmdldCgndGV4dCcpLFxuXHRcdFx0J3VybCc6IENvbnRhby5yb3V0ZXMuYmFja2VuZF9waWNrZXIgKyAnP2NvbnRleHQ9JyArICh0eXBlID09ICdmaWxlJyA/ICdsaW5rJyA6ICdmaWxlJykgKyAnJmFtcDtleHRyYXNbZmllbGRUeXBlXT1yYWRpbyZhbXA7ZXh0cmFzW2ZpbGVzT25seV09dHJ1ZSZhbXA7ZXh0cmFzW3NvdXJjZV09JyArIHNvdXJjZSArICcmYW1wO3ZhbHVlPScgKyB1cmwgKyAnJmFtcDtwb3B1cD0xJyxcblx0XHRcdCdjYWxsYmFjayc6IGZ1bmN0aW9uKHRhYmxlLCB2YWx1ZSkge1xuXHRcdFx0XHR3aW4uZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoZmllbGRfbmFtZSkudmFsdWUgPSB2YWx1ZS5qb2luKCcsJyk7XG5cdFx0XHR9XG5cdFx0fSk7XG5cdH0sXG5cblx0LyoqXG5cdCAqIEF1dG9tYXRpY2FsbHkgc3VibWl0IGEgZm9ybVxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZWwgVGhlIERPTSBlbGVtZW50XG5cdCAqL1xuXHRhdXRvU3VibWl0OiBmdW5jdGlvbihlbCkge1xuXHRcdHdpbmRvdy5kaXNwYXRjaEV2ZW50KG5ldyBFdmVudCgnc3RvcmUtc2Nyb2xsLW9mZnNldCcpKTtcblxuXHRcdHZhciBoaWRkZW4gPSBuZXcgRWxlbWVudCgnaW5wdXQnLCB7XG5cdFx0XHQndHlwZSc6ICdoaWRkZW4nLFxuXHRcdFx0J25hbWUnOiAnU1VCTUlUX1RZUEUnLFxuXHRcdFx0J3ZhbHVlJzogJ2F1dG8nXG5cdFx0fSk7XG5cblx0XHR2YXIgZm9ybSA9ICQoZWwpIHx8IGVsO1xuXHRcdGhpZGRlbi5pbmplY3QoZm9ybSwgJ2JvdHRvbScpO1xuXHRcdGZvcm0uc3VibWl0KCk7XG5cdH0sXG5cblx0LyoqXG5cdCAqIFNjcm9sbCB0aGUgd2luZG93IHRvIGEgY2VydGFpbiB2ZXJ0aWNhbCBwb3NpdGlvblxuXHQgKlxuXHQgKiBAcGFyYW0ge2ludH0gb2Zmc2V0IFRoZSBvZmZzZXQgdG8gc2Nyb2xsIHRvXG5cdCAqL1xuXHR2U2Nyb2xsVG86IGZ1bmN0aW9uKG9mZnNldCkge1xuXHRcdHdpbmRvdy5hZGRFdmVudCgnbG9hZCcsIGZ1bmN0aW9uKCkge1xuXHRcdFx0d2luZG93LnNjcm9sbFRvKG51bGwsIHBhcnNlSW50KG9mZnNldCkpO1xuXHRcdH0pO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBUb2dnbGUgY2hlY2tib3hlc1xuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZWwgICBUaGUgRE9NIGVsZW1lbnRcblx0ICogQHBhcmFtIHtzdHJpbmd9IFtpZF0gVGhlIElEIG9mIHRoZSB0YXJnZXQgZWxlbWVudFxuXHQgKi9cblx0dG9nZ2xlQ2hlY2tib3hlczogZnVuY3Rpb24oZWwsIGlkKSB7XG5cdFx0dmFyIGl0ZW1zID0gJCQoJ2lucHV0JyksXG5cdFx0XHRzdGF0dXMgPSAkKGVsKS5jaGVja2VkID8gJ2NoZWNrZWQnIDogJyc7XG5cblx0XHRmb3IgKHZhciBpPTA7IGk8aXRlbXMubGVuZ3RoOyBpKyspIHtcblx0XHRcdGlmIChpdGVtc1tpXS50eXBlLnRvTG93ZXJDYXNlKCkgIT0gJ2NoZWNrYm94Jykge1xuXHRcdFx0XHRjb250aW51ZTtcblx0XHRcdH1cblx0XHRcdGlmIChpZCAhPT0gdW5kZWZpbmVkICYmIGlkICE9IGl0ZW1zW2ldLmlkLnN1YnN0cigwLCBpZC5sZW5ndGgpKSB7XG5cdFx0XHRcdGNvbnRpbnVlO1xuXHRcdFx0fVxuXHRcdFx0aXRlbXNbaV0uY2hlY2tlZCA9IHN0YXR1cztcblx0XHR9XG5cdH0sXG5cblx0LyoqXG5cdCAqIFRvZ2dsZSBhIGNoZWNrYm94IGdyb3VwXG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSBlbCBUaGUgRE9NIGVsZW1lbnRcblx0ICogQHBhcmFtIHtzdHJpbmd9IGlkIFRoZSBJRCBvZiB0aGUgdGFyZ2V0IGVsZW1lbnRcblx0ICovXG5cdHRvZ2dsZUNoZWNrYm94R3JvdXA6IGZ1bmN0aW9uKGVsLCBpZCkge1xuXHRcdHZhciBjbHMgPSAkKGVsKS5jbGFzc05hbWUsXG5cdFx0XHRzdGF0dXMgPSAkKGVsKS5jaGVja2VkID8gJ2NoZWNrZWQnIDogJyc7XG5cblx0XHRpZiAoY2xzID09ICd0bF9jaGVja2JveCcpIHtcblx0XHRcdHZhciBjYnggPSAkKGlkKSA/ICQkKCcjJyArIGlkICsgJyAudGxfY2hlY2tib3gnKSA6ICQoZWwpLmdldFBhcmVudCgnZmllbGRzZXQnKS5nZXRFbGVtZW50cygnLnRsX2NoZWNrYm94Jyk7XG5cdFx0XHRjYnguZWFjaChmdW5jdGlvbihjaGVja2JveCkge1xuXHRcdFx0XHRjaGVja2JveC5jaGVja2VkID0gc3RhdHVzO1xuXHRcdFx0fSk7XG5cdFx0fSBlbHNlIGlmIChjbHMgPT0gJ3RsX3RyZWVfY2hlY2tib3gnKSB7XG5cdFx0XHQkJCgnIycgKyBpZCArICcgLnBhcmVudCAudGxfdHJlZV9jaGVja2JveCcpLmVhY2goZnVuY3Rpb24oY2hlY2tib3gpIHtcblx0XHRcdFx0Y2hlY2tib3guY2hlY2tlZCA9IHN0YXR1cztcblx0XHRcdH0pO1xuXHRcdH1cblxuXHRcdHdpbmRvdy5kaXNwYXRjaEV2ZW50KG5ldyBFdmVudCgnc3RvcmUtc2Nyb2xsLW9mZnNldCcpKTtcblx0fSxcblxuXHQvKipcblx0ICogVG9nZ2xlIGNoZWNrYm94IGVsZW1lbnRzXG5cdCAqXG5cdCAqIEBwYXJhbSB7c3RyaW5nfSBlbCAgVGhlIERPTSBlbGVtZW50XG5cdCAqIEBwYXJhbSB7c3RyaW5nfSBjbHMgVGhlIENTUyBjbGFzcyBuYW1lXG5cdCAqL1xuXHR0b2dnbGVDaGVja2JveEVsZW1lbnRzOiBmdW5jdGlvbihlbCwgY2xzKSB7XG5cdFx0dmFyIHN0YXR1cyA9ICQoZWwpLmNoZWNrZWQgPyAnY2hlY2tlZCcgOiAnJztcblxuXHRcdCQkKCcuJyArIGNscykuZWFjaChmdW5jdGlvbihjaGVja2JveCkge1xuXHRcdFx0aWYgKGNoZWNrYm94Lmhhc0NsYXNzKCd0bF9jaGVja2JveCcpKSB7XG5cdFx0XHRcdGNoZWNrYm94LmNoZWNrZWQgPSBzdGF0dXM7XG5cdFx0XHR9XG5cdFx0fSk7XG5cblx0XHR3aW5kb3cuZGlzcGF0Y2hFdmVudChuZXcgRXZlbnQoJ3N0b3JlLXNjcm9sbC1vZmZzZXQnKSk7XG5cdH0sXG5cblx0LyoqXG5cdCAqIE1ha2UgcGFyZW50IHZpZXcgaXRlbXMgc29ydGFibGVcblx0ICpcblx0ICogQHBhcmFtIHtvYmplY3R9IHVsIFRoZSBET00gZWxlbWVudFxuXHQgKlxuXHQgKiBAYXV0aG9yIEpvZSBSYXkgR3JlZ29yeVxuXHQgKiBAYXV0aG9yIE1hcnRpbiBBdXN3w7ZnZXJcblx0ICovXG5cdG1ha2VQYXJlbnRWaWV3U29ydGFibGU6IGZ1bmN0aW9uKHVsKSB7XG5cdFx0dmFyIGRzID0gbmV3IFNjcm9sbGVyKGRvY3VtZW50LmdldEVsZW1lbnQoJ2JvZHknKSwge1xuXHRcdFx0b25DaGFuZ2U6IGZ1bmN0aW9uKHgsIHkpIHtcblx0XHRcdFx0dGhpcy5lbGVtZW50LnNjcm9sbFRvKHRoaXMuZWxlbWVudC5nZXRTY3JvbGwoKS54LCB5KTtcblx0XHRcdH1cblx0XHR9KTtcblxuXHRcdHZhciBsaXN0ID0gbmV3IFNvcnRhYmxlcyh1bCwge1xuXHRcdFx0Y29uc3RyYWluOiB0cnVlLFxuXHRcdFx0b3BhY2l0eTogMC42LFxuXHRcdFx0b25TdGFydDogZnVuY3Rpb24oKSB7XG5cdFx0XHRcdGRzLnN0YXJ0KCk7XG5cdFx0XHR9LFxuXHRcdFx0b25Db21wbGV0ZTogZnVuY3Rpb24oKSB7XG5cdFx0XHRcdGRzLnN0b3AoKTtcblx0XHRcdH0sXG5cdFx0XHRvblNvcnQ6IGZ1bmN0aW9uKGVsKSB7XG5cdFx0XHRcdHZhciB1bCA9IGVsLmdldFBhcmVudCgndWwnKSxcblx0XHRcdFx0XHR3cmFwTGV2ZWwgPSAwLCBkaXZzLCBpO1xuXG5cdFx0XHRcdGlmICghdWwpIHJldHVybjtcblxuXHRcdFx0XHRkaXZzID0gdWwuZ2V0Q2hpbGRyZW4oJ2xpID4gZGl2OmZpcnN0LWNoaWxkJyk7XG5cblx0XHRcdFx0aWYgKCFkaXZzKSByZXR1cm47XG5cblx0XHRcdFx0Zm9yIChpPTA7IGk8ZGl2cy5sZW5ndGg7IGkrKykge1xuXHRcdFx0XHRcdGlmIChkaXZzW2ldLmhhc0NsYXNzKCd3cmFwcGVyX3N0b3AnKSAmJiB3cmFwTGV2ZWwgPiAwKSB7XG5cdFx0XHRcdFx0XHR3cmFwTGV2ZWwtLTtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHRkaXZzW2ldLmNsYXNzTmFtZSA9IGRpdnNbaV0uY2xhc3NOYW1lLnJlcGxhY2UoLyhefFxccylpbmRlbnRbXlxcc10qL2csICcnKTtcblxuXHRcdFx0XHRcdGlmICh3cmFwTGV2ZWwgPiAwKSB7XG5cdFx0XHRcdFx0XHRkaXZzW2ldLmFkZENsYXNzKCdpbmRlbnQnKS5hZGRDbGFzcygnaW5kZW50XycgKyB3cmFwTGV2ZWwpO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdGlmIChkaXZzW2ldLmhhc0NsYXNzKCd3cmFwcGVyX3N0YXJ0JykpIHtcblx0XHRcdFx0XHRcdHdyYXBMZXZlbCsrO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdGRpdnNbaV0ucmVtb3ZlQ2xhc3MoJ2luZGVudF9maXJzdCcpO1xuXHRcdFx0XHRcdGRpdnNbaV0ucmVtb3ZlQ2xhc3MoJ2luZGVudF9sYXN0Jyk7XG5cblx0XHRcdFx0XHRpZiAoZGl2c1tpLTFdICYmIGRpdnNbaS0xXS5oYXNDbGFzcygnd3JhcHBlcl9zdGFydCcpKSB7XG5cdFx0XHRcdFx0XHRkaXZzW2ldLmFkZENsYXNzKCdpbmRlbnRfZmlyc3QnKTtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHRpZiAoZGl2c1tpKzFdICYmIGRpdnNbaSsxXS5oYXNDbGFzcygnd3JhcHBlcl9zdG9wJykpIHtcblx0XHRcdFx0XHRcdGRpdnNbaV0uYWRkQ2xhc3MoJ2luZGVudF9sYXN0Jyk7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9XG5cdFx0XHR9LFxuXHRcdFx0aGFuZGxlOiAnLmRyYWctaGFuZGxlJ1xuXHRcdH0pO1xuXG5cdFx0bGlzdC5hY3RpdmUgPSBmYWxzZTtcblxuXHRcdGxpc3QuYWRkRXZlbnQoJ3N0YXJ0JywgZnVuY3Rpb24oKSB7XG5cdFx0XHRsaXN0LmFjdGl2ZSA9IHRydWU7XG5cdFx0fSk7XG5cblx0XHRsaXN0LmFkZEV2ZW50KCdjb21wbGV0ZScsIGZ1bmN0aW9uKGVsKSB7XG5cdFx0XHRpZiAoIWxpc3QuYWN0aXZlKSByZXR1cm47XG5cdFx0XHR2YXIgaWQsIHBpZCwgdXJsID0gbmV3IFVSTCh3aW5kb3cubG9jYXRpb24uaHJlZik7XG5cblx0XHRcdHVybC5zZWFyY2hQYXJhbXMuc2V0KCdydCcsIENvbnRhby5yZXF1ZXN0X3Rva2VuKTtcblx0XHRcdHVybC5zZWFyY2hQYXJhbXMuc2V0KCdhY3QnLCAnY3V0Jyk7XG5cblx0XHRcdGlmIChlbC5nZXRQcmV2aW91cygnbGknKSkge1xuXHRcdFx0XHRpZCA9IGVsLmdldCgnaWQnKS5yZXBsYWNlKC9saV8vLCAnJyk7XG5cdFx0XHRcdHBpZCA9IGVsLmdldFByZXZpb3VzKCdsaScpLmdldCgnaWQnKS5yZXBsYWNlKC9saV8vLCAnJyk7XG5cdFx0XHRcdHVybC5zZWFyY2hQYXJhbXMuc2V0KCdpZCcsIGlkKTtcblx0XHRcdFx0dXJsLnNlYXJjaFBhcmFtcy5zZXQoJ3BpZCcsIHBpZCk7XG5cdFx0XHRcdHVybC5zZWFyY2hQYXJhbXMuc2V0KCdtb2RlJywgMSk7XG5cdFx0XHRcdG5ldyBSZXF1ZXN0LkNvbnRhbyh7J3VybCc6dXJsLnRvU3RyaW5nKCksICdmb2xsb3dSZWRpcmVjdHMnOmZhbHNlfSkuZ2V0KCk7XG5cdFx0XHR9IGVsc2UgaWYgKGVsLmdldFBhcmVudCgndWwnKSkge1xuXHRcdFx0XHRpZCA9IGVsLmdldCgnaWQnKS5yZXBsYWNlKC9saV8vLCAnJyk7XG5cdFx0XHRcdHBpZCA9IGVsLmdldFBhcmVudCgndWwnKS5nZXQoJ2lkJykucmVwbGFjZSgvdWxfLywgJycpO1xuXHRcdFx0XHR1cmwuc2VhcmNoUGFyYW1zLnNldCgnaWQnLCBpZCk7XG5cdFx0XHRcdHVybC5zZWFyY2hQYXJhbXMuc2V0KCdwaWQnLCBwaWQpO1xuXHRcdFx0XHR1cmwuc2VhcmNoUGFyYW1zLnNldCgnbW9kZScsIDIpO1xuXHRcdFx0XHRuZXcgUmVxdWVzdC5Db250YW8oeyd1cmwnOnVybC50b1N0cmluZygpLCAnZm9sbG93UmVkaXJlY3RzJzpmYWxzZX0pLmdldCgpO1xuXHRcdFx0fVxuXHRcdH0pO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBNYWtlIG11bHRpU1JDIGl0ZW1zIHNvcnRhYmxlXG5cdCAqXG5cdCAqIEBwYXJhbSB7c3RyaW5nfSBpZCAgVGhlIElEIG9mIHRoZSB0YXJnZXQgZWxlbWVudFxuXHQgKiBAcGFyYW0ge3N0cmluZ30gb2lkIFRoZSBvcmRlciBmaWVsZFxuXHQgKiBAcGFyYW0ge3N0cmluZ30gdmFsIFRoZSB2YWx1ZSBmaWVsZFxuXHQgKi9cblx0bWFrZU11bHRpU3JjU29ydGFibGU6IGZ1bmN0aW9uKGlkLCBvaWQsIHZhbCkge1xuXHRcdHZhciBsaXN0ID0gbmV3IFNvcnRhYmxlcygkKGlkKSwge1xuXHRcdFx0Y29uc3RyYWluOiB0cnVlLFxuXHRcdFx0b3BhY2l0eTogMC42XG5cdFx0fSkuYWRkRXZlbnQoJ2NvbXBsZXRlJywgZnVuY3Rpb24oKSB7XG5cdFx0XHR2YXIgZWxzID0gW10sXG5cdFx0XHRcdGxpcyA9ICQoaWQpLmdldENoaWxkcmVuKCdbZGF0YS1pZF0nKSxcblx0XHRcdFx0aTtcblx0XHRcdGZvciAoaT0wOyBpPGxpcy5sZW5ndGg7IGkrKykge1xuXHRcdFx0XHRlbHMucHVzaChsaXNbaV0uZ2V0KCdkYXRhLWlkJykpO1xuXHRcdFx0fVxuXHRcdFx0aWYgKG9pZCA9PT0gdmFsKSB7XG5cdFx0XHRcdCQodmFsKS52YWx1ZS5zcGxpdCgnLCcpLmZvckVhY2goZnVuY3Rpb24oaikge1xuXHRcdFx0XHRcdGlmIChlbHMuaW5kZXhPZihqKSA9PT0gLTEpIHtcblx0XHRcdFx0XHRcdGVscy5wdXNoKGopO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fSk7XG5cdFx0XHR9XG5cdFx0XHQkKG9pZCkudmFsdWUgPSBlbHMuam9pbignLCcpO1xuXHRcdH0pO1xuXHRcdCQoaWQpLmdldEVsZW1lbnRzKCcuZ2ltYWdlJykuZWFjaChmdW5jdGlvbihlbCkge1xuXHRcdFx0aWYgKGVsLmhhc0NsYXNzKCdyZW1vdmFibGUnKSkge1xuXHRcdFx0XHRuZXcgRWxlbWVudCgnYnV0dG9uJywge1xuXHRcdFx0XHRcdHR5cGU6ICdidXR0b24nLFxuXHRcdFx0XHRcdGh0bWw6ICcmdGltZXM7Jyxcblx0XHRcdFx0XHQnY2xhc3MnOiAndGxfcmVkJ1xuXHRcdFx0XHR9KS5hZGRFdmVudCgnY2xpY2snLCBmdW5jdGlvbigpIHtcblx0XHRcdFx0XHR2YXIgbGkgPSBlbC5nZXRQYXJlbnQoJ2xpJyksXG5cdFx0XHRcdFx0XHRkaWQgPSBsaS5nZXQoJ2RhdGEtaWQnKTtcblx0XHRcdFx0XHQkKHZhbCkudmFsdWUgPSAkKHZhbCkudmFsdWUuc3BsaXQoJywnKS5maWx0ZXIoZnVuY3Rpb24oaikgeyByZXR1cm4gaiAhPSBkaWQ7IH0pLmpvaW4oJywnKTtcblx0XHRcdFx0XHQkKG9pZCkudmFsdWUgPSAkKG9pZCkudmFsdWUuc3BsaXQoJywnKS5maWx0ZXIoZnVuY3Rpb24oaikgeyByZXR1cm4gaiAhPSBkaWQ7IH0pLmpvaW4oJywnKTtcblx0XHRcdFx0XHRsaS5kaXNwb3NlKCk7XG5cdFx0XHRcdH0pLmluamVjdChlbCwgJ2FmdGVyJyk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRuZXcgRWxlbWVudCgnYnV0dG9uJywge1xuXHRcdFx0XHRcdHR5cGU6ICdidXR0b24nLFxuXHRcdFx0XHRcdGh0bWw6ICcmdGltZXMnLFxuXHRcdFx0XHRcdGRpc2FibGVkOiB0cnVlXG5cdFx0XHRcdH0pLmluamVjdChlbCwgJ2FmdGVyJyk7XG5cdFx0XHR9XG5cdFx0fSk7XG5cdFx0bGlzdC5maXJlRXZlbnQoXCJjb21wbGV0ZVwiKTsgLy8gSW5pdGlhbCBzb3J0aW5nXG5cdH0sXG5cblx0LyoqXG5cdCAqIEVuYWJsZSBkcmFnIGFuZCBkcm9wIGZvciB0aGUgZmlsZSB0cmVlXG5cdCAqXG5cdCAqIEBwYXJhbSB7b2JqZWN0fSB1bCAgICAgIFRoZSBET00gZWxlbWVudFxuXHQgKiBAcGFyYW0ge29iamVjdH0gb3B0aW9ucyBBbiBvcHRpb25hbCBvcHRpb25zIG9iamVjdFxuXHQgKi9cblx0ZW5hYmxlRmlsZVRyZWVEcmFnQW5kRHJvcDogZnVuY3Rpb24odWwsIG9wdGlvbnMpIHtcblx0XHR2YXIgZHMgPSBuZXcgU2Nyb2xsZXIoZG9jdW1lbnQuZ2V0RWxlbWVudCgnYm9keScpLCB7XG5cdFx0XHRvbkNoYW5nZTogZnVuY3Rpb24oeCwgeSkge1xuXHRcdFx0XHR0aGlzLmVsZW1lbnQuc2Nyb2xsVG8odGhpcy5lbGVtZW50LmdldFNjcm9sbCgpLngsIHkpO1xuXHRcdFx0fVxuXHRcdH0pO1xuXG5cdFx0dWwuYWRkRXZlbnQoJ21vdXNlZG93bicsIGZ1bmN0aW9uKGV2ZW50KSB7XG5cdFx0XHR2YXIgZHJhZ0hhbmRsZSA9IGV2ZW50LnRhcmdldC5oYXNDbGFzcygnZHJhZy1oYW5kbGUnKSA/IGV2ZW50LnRhcmdldCA6IGV2ZW50LnRhcmdldC5nZXRQYXJlbnQoJy5kcmFnLWhhbmRsZScpO1xuXHRcdFx0dmFyIGRyYWdFbGVtZW50ID0gZXZlbnQudGFyZ2V0LmdldFBhcmVudCgnLnRsX2ZpbGUsLnRsX2ZvbGRlcicpO1xuXG5cdFx0XHRpZiAoIWRyYWdIYW5kbGUgfHwgIWRyYWdFbGVtZW50IHx8IGV2ZW50LnJpZ2h0Q2xpY2spIHtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXG5cdFx0XHRkcy5zdGFydCgpO1xuXHRcdFx0dWwuYWRkQ2xhc3MoJ3RsX2xpc3RpbmdfZHJhZ2dpbmcnKTtcblxuXHRcdFx0dmFyIGNsb25lQmFzZSA9IChkcmFnRWxlbWVudC5nZXRFbGVtZW50cygnLnRsX2xlZnQnKVswXSB8fCBkcmFnRWxlbWVudCksXG5cdFx0XHRcdGNsb25lID0gY2xvbmVCYXNlLmNsb25lKHRydWUpXG5cdFx0XHRcdFx0LmluamVjdCh1bClcblx0XHRcdFx0XHQuYWRkQ2xhc3MoJ3RsX2xlZnRfZHJhZ2dpbmcnKSxcblx0XHRcdFx0Y3VycmVudEhvdmVyLCBjdXJyZW50SG92ZXJUaW1lLCBleHBhbmRMaW5rO1xuXG5cdFx0XHRjbG9uZS5zZXRQb3NpdGlvbih7XG5cdFx0XHRcdHg6IGV2ZW50LnBhZ2UueCAtIGNsb25lQmFzZS5nZXRPZmZzZXRQYXJlbnQoKS5nZXRQb3NpdGlvbigpLnggLSBjbG9uZS5nZXRTaXplKCkueCxcblx0XHRcdFx0eTogY2xvbmVCYXNlLmdldFBvc2l0aW9uKGNsb25lQmFzZS5nZXRPZmZzZXRQYXJlbnQoKSkueVxuXHRcdFx0fSkuc2V0U3R5bGUoJ2Rpc3BsYXknLCAnbm9uZScpO1xuXG5cdFx0XHR2YXIgbW92ZSA9IG5ldyBEcmFnLk1vdmUoY2xvbmUsIHtcblx0XHRcdFx0ZHJvcHBhYmxlczogJCQoW3VsXSkuYXBwZW5kKHVsLmdldEVsZW1lbnRzKCcudGxfZm9sZGVyLGxpLnBhcmVudCwudGxfZm9sZGVyX3RvcCcpKSxcblx0XHRcdFx0dW5EcmFnZ2FibGVUYWdzOiBbXSxcblx0XHRcdFx0bW9kaWZpZXJzOiB7XG5cdFx0XHRcdFx0eDogJ2xlZnQnLFxuXHRcdFx0XHRcdHk6ICd0b3AnXG5cdFx0XHRcdH0sXG5cdFx0XHRcdG9uU3RhcnQ6IGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdGNsb25lLnNldFN0eWxlKCdkaXNwbGF5JywgJycpO1xuXHRcdFx0XHR9LFxuXHRcdFx0XHRvbkVudGVyOiBmdW5jdGlvbihlbGVtZW50LCBkcm9wcGFibGUpIHtcblx0XHRcdFx0XHRkcm9wcGFibGUgPSBmaXhEcm9wcGFibGUoZHJvcHBhYmxlKTtcblx0XHRcdFx0XHRkcm9wcGFibGUuYWRkQ2xhc3MoJ3RsX2ZvbGRlcl9kcm9wcGluZycpO1xuXG5cdFx0XHRcdFx0aWYgKGRyb3BwYWJsZS5oYXNDbGFzcygndGxfZm9sZGVyJykgJiYgY3VycmVudEhvdmVyICE9PSBkcm9wcGFibGUpIHtcblx0XHRcdFx0XHRcdGN1cnJlbnRIb3ZlciA9IGRyb3BwYWJsZTtcblx0XHRcdFx0XHRcdGN1cnJlbnRIb3ZlclRpbWUgPSBuZXcgRGF0ZSgpLmdldFRpbWUoKTtcblx0XHRcdFx0XHRcdGV4cGFuZExpbmsgPSBkcm9wcGFibGUuZ2V0RWxlbWVudCgnYS5mb2xkYWJsZScpO1xuXG5cdFx0XHRcdFx0XHRpZiAoZXhwYW5kTGluayAmJiAhZXhwYW5kTGluay5oYXNDbGFzcygnZm9sZGFibGUtLW9wZW4nKSkge1xuXHRcdFx0XHRcdFx0XHQvLyBFeHBhbmQgdGhlIGZvbGRlciBhZnRlciBvbmUgc2Vjb25kIGhvdmVyIHRpbWVcblx0XHRcdFx0XHRcdFx0c2V0VGltZW91dChmdW5jdGlvbigpIHtcblx0XHRcdFx0XHRcdFx0XHRpZiAoY3VycmVudEhvdmVyID09PSBkcm9wcGFibGUgJiYgY3VycmVudEhvdmVyVGltZSArIDkwMCA8IG5ldyBEYXRlKCkuZ2V0VGltZSgpKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHR2YXIgZXZlbnQgPSBkb2N1bWVudC5jcmVhdGVFdmVudCgnSFRNTEV2ZW50cycpO1xuXHRcdFx0XHRcdFx0XHRcdFx0ZXZlbnQuaW5pdEV2ZW50KCdjbGljaycsIHRydWUsIHRydWUpO1xuXHRcdFx0XHRcdFx0XHRcdFx0ZXhwYW5kTGluay5kaXNwYXRjaEV2ZW50KGV2ZW50KTtcblxuXHRcdFx0XHRcdFx0XHRcdFx0Y3VycmVudEhvdmVyID0gdW5kZWZpbmVkO1xuXHRcdFx0XHRcdFx0XHRcdFx0Y3VycmVudEhvdmVyVGltZSA9IHVuZGVmaW5lZDtcblxuXHRcdFx0XHRcdFx0XHRcdFx0d2luZG93LmFkZEV2ZW50KCdhamF4X2NoYW5nZScsIGZ1bmN0aW9uIG9uQWpheCgpIHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0aWYgKG1vdmUgJiYgbW92ZS5kcm9wcGFibGVzICYmIHVsICYmIHVsLmdldEVsZW1lbnRzKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdFx0bW92ZS5kcm9wcGFibGVzID0gJCQoW3VsXSkuYXBwZW5kKHVsLmdldEVsZW1lbnRzKCcudGxfZm9sZGVyLGxpLnBhcmVudCcpKTtcblx0XHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdFx0XHR3aW5kb3cucmVtb3ZlRXZlbnQoJ2FqYXhfY2hhbmdlJywgb25BamF4KTtcblx0XHRcdFx0XHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0fSwgMTAwMCk7XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9LFxuXHRcdFx0XHRvbkNhbmNlbDogZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0Y3VycmVudEhvdmVyID0gdW5kZWZpbmVkO1xuXHRcdFx0XHRcdGN1cnJlbnRIb3ZlclRpbWUgPSB1bmRlZmluZWQ7XG5cblx0XHRcdFx0XHRkcy5zdG9wKCk7XG5cdFx0XHRcdFx0Y2xvbmUuZGVzdHJveSgpO1xuXHRcdFx0XHRcdHdpbmRvdy5yZW1vdmVFdmVudCgna2V5dXAnLCBvbktleXVwKTtcblx0XHRcdFx0XHR1bC5nZXRFbGVtZW50cygnLnRsX2ZvbGRlcl9kcm9wcGluZycpLnJlbW92ZUNsYXNzKCd0bF9mb2xkZXJfZHJvcHBpbmcnKTtcblx0XHRcdFx0XHR1bC5yZW1vdmVDbGFzcygndGxfbGlzdGluZ19kcmFnZ2luZycpO1xuXHRcdFx0XHR9LFxuXHRcdFx0XHRvbkRyb3A6IGZ1bmN0aW9uKGVsZW1lbnQsIGRyb3BwYWJsZSkge1xuXHRcdFx0XHRcdGN1cnJlbnRIb3ZlciA9IHVuZGVmaW5lZDtcblx0XHRcdFx0XHRjdXJyZW50SG92ZXJUaW1lID0gdW5kZWZpbmVkO1xuXG5cdFx0XHRcdFx0ZHMuc3RvcCgpO1xuXHRcdFx0XHRcdGNsb25lLmRlc3Ryb3koKTtcblx0XHRcdFx0XHR3aW5kb3cucmVtb3ZlRXZlbnQoJ2tleXVwJywgb25LZXl1cCk7XG5cdFx0XHRcdFx0dWwuZ2V0RWxlbWVudHMoJy50bF9mb2xkZXJfZHJvcHBpbmcnKS5yZW1vdmVDbGFzcygndGxfZm9sZGVyX2Ryb3BwaW5nJyk7XG5cdFx0XHRcdFx0dWwucmVtb3ZlQ2xhc3MoJ3RsX2xpc3RpbmdfZHJhZ2dpbmcnKTtcblxuXHRcdFx0XHRcdGRyb3BwYWJsZSA9IGZpeERyb3BwYWJsZShkcm9wcGFibGUpO1xuXG5cdFx0XHRcdFx0aWYgKCFkcm9wcGFibGUpIHtcblx0XHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHR2YXIgaWQgPSBkcmFnRWxlbWVudC5nZXQoJ2RhdGEtaWQnKSxcblx0XHRcdFx0XHRcdHBpZCA9IGRyb3BwYWJsZS5nZXQoJ2RhdGEtaWQnKSB8fCBkZWNvZGVVUklDb21wb25lbnQob3B0aW9ucy51cmwuc3BsaXQoL1s/Jl1waWQ9LylbMV0uc3BsaXQoJyYnKVswXSk7XG5cblx0XHRcdFx0XHQvLyBJZ25vcmUgaW52YWxpZCBtb3ZlIG9wZXJhdGlvbnNcblx0XHRcdFx0XHRpZiAoaWQgJiYgcGlkICYmICgocGlkKycvJykuaW5kZXhPZihpZCsnLycpID09PSAwIHx8IHBpZCsnLycgPT09IGlkLnJlcGxhY2UoL1teL10rJC8sICcnKSkpIHtcblx0XHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0XHR9XG5cblx0XHRcdFx0XHR3aW5kb3cuZGlzcGF0Y2hFdmVudChuZXcgRXZlbnQoJ3N0b3JlLXNjcm9sbC1vZmZzZXQnKSk7XG5cdFx0XHRcdFx0ZG9jdW1lbnQubG9jYXRpb24uaHJlZiA9IG9wdGlvbnMudXJsICsgJyZpZD0nICsgZW5jb2RlVVJJQ29tcG9uZW50KGlkKSArICcmcGlkPScgKyBlbmNvZGVVUklDb21wb25lbnQocGlkKTtcblx0XHRcdFx0fSxcblx0XHRcdFx0b25MZWF2ZTogZnVuY3Rpb24oZWxlbWVudCwgZHJvcHBhYmxlKSB7XG5cdFx0XHRcdFx0ZHJvcHBhYmxlID0gZml4RHJvcHBhYmxlKGRyb3BwYWJsZSk7XG5cdFx0XHRcdFx0ZHJvcHBhYmxlLnJlbW92ZUNsYXNzKCd0bF9mb2xkZXJfZHJvcHBpbmcnKTtcblx0XHRcdFx0XHRjdXJyZW50SG92ZXIgPSB1bmRlZmluZWQ7XG5cdFx0XHRcdFx0Y3VycmVudEhvdmVyVGltZSA9IHVuZGVmaW5lZDtcblx0XHRcdFx0fVxuXHRcdFx0fSk7XG5cblx0XHRcdG1vdmUuc3RhcnQoZXZlbnQpO1xuXHRcdFx0d2luZG93LmFkZEV2ZW50KCdrZXl1cCcsIG9uS2V5dXApO1xuXG5cdFx0XHRmdW5jdGlvbiBvbktleXVwKGV2ZW50KSB7XG5cdFx0XHRcdGlmIChldmVudC5rZXkgPT09ICdlc2MnICYmIG1vdmUgJiYgbW92ZS5zdG9wKSB7XG5cdFx0XHRcdFx0bW92ZS5kcm9wcGFibGVzID0gJCQoW10pO1xuXHRcdFx0XHRcdG1vdmUuc3RvcCgpO1xuXHRcdFx0XHR9XG5cdFx0XHR9XG5cdFx0fSk7XG5cblx0XHRmdW5jdGlvbiBmaXhEcm9wcGFibGUoZHJvcHBhYmxlKSB7XG5cdFx0XHRpZiAoZHJvcHBhYmxlICYmIGRyb3BwYWJsZS5oYXNDbGFzcygncGFyZW50JykgJiYgZHJvcHBhYmxlLmdldFByZXZpb3VzKCcudGxfZm9sZGVyJykpIHtcblx0XHRcdFx0cmV0dXJuIGRyb3BwYWJsZS5nZXRQcmV2aW91cygnLnRsX2ZvbGRlcicpO1xuXHRcdFx0fVxuXG5cdFx0XHRyZXR1cm4gZHJvcHBhYmxlO1xuXHRcdH1cblx0fSxcblxuXHQvKipcblx0ICogTGlzdCB3aXphcmRcblx0ICpcblx0ICogQHBhcmFtIHtzdHJpbmd9IGlkIFRoZSBJRCBvZiB0aGUgdGFyZ2V0IGVsZW1lbnRcblx0ICovXG5cdGxpc3RXaXphcmQ6IGZ1bmN0aW9uKGlkKSB7XG5cdFx0dmFyIHVsID0gJChpZCksXG5cdFx0XHRtYWtlU29ydGFibGUgPSBmdW5jdGlvbih1bCkge1xuXHRcdFx0XHRuZXcgU29ydGFibGVzKHVsLCB7XG5cdFx0XHRcdFx0Y29uc3RyYWluOiB0cnVlLFxuXHRcdFx0XHRcdG9wYWNpdHk6IDAuNixcblx0XHRcdFx0XHRoYW5kbGU6ICcuZHJhZy1oYW5kbGUnXG5cdFx0XHRcdH0pO1xuXHRcdFx0fSxcblx0XHRcdGFkZEV2ZW50c1RvID0gZnVuY3Rpb24obGkpIHtcblx0XHRcdFx0dmFyIGNvbW1hbmQsIGNsb25lLCBpbnB1dCwgcHJldmlvdXMsIG5leHQ7XG5cblx0XHRcdFx0bGkuZ2V0RWxlbWVudHMoJ2J1dHRvbicpLmVhY2goZnVuY3Rpb24oYnQpIHtcblx0XHRcdFx0XHRpZiAoYnQuaGFzRXZlbnQoJ2NsaWNrJykpIHJldHVybjtcblx0XHRcdFx0XHRjb21tYW5kID0gYnQuZ2V0UHJvcGVydHkoJ2RhdGEtY29tbWFuZCcpO1xuXG5cdFx0XHRcdFx0c3dpdGNoIChjb21tYW5kKSB7XG5cdFx0XHRcdFx0XHRjYXNlICdjb3B5Jzpcblx0XHRcdFx0XHRcdFx0YnQuYWRkRXZlbnQoJ2NsaWNrJywgZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0XHRcdFx0d2luZG93LmRpc3BhdGNoRXZlbnQobmV3IEV2ZW50KCdzdG9yZS1zY3JvbGwtb2Zmc2V0JykpO1xuXHRcdFx0XHRcdFx0XHRcdGNsb25lID0gbGkuY2xvbmUodHJ1ZSkuaW5qZWN0KGxpLCAnYmVmb3JlJyk7XG5cdFx0XHRcdFx0XHRcdFx0aWYgKGlucHV0ID0gbGkuZ2V0Rmlyc3QoJ2lucHV0JykpIHtcblx0XHRcdFx0XHRcdFx0XHRcdGNsb25lLmdldEZpcnN0KCdpbnB1dCcpLnZhbHVlID0gaW5wdXQudmFsdWU7XG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdGFkZEV2ZW50c1RvKGNsb25lKTtcblx0XHRcdFx0XHRcdFx0XHRpbnB1dC5zZWxlY3QoKTtcblx0XHRcdFx0XHRcdFx0fSk7XG5cdFx0XHRcdFx0XHRcdGJyZWFrO1xuXHRcdFx0XHRcdFx0Y2FzZSAnZGVsZXRlJzpcblx0XHRcdFx0XHRcdFx0YnQuYWRkRXZlbnQoJ2NsaWNrJywgZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0XHRcdFx0d2luZG93LmRpc3BhdGNoRXZlbnQobmV3IEV2ZW50KCdzdG9yZS1zY3JvbGwtb2Zmc2V0JykpO1xuXHRcdFx0XHRcdFx0XHRcdGlmICh1bC5nZXRDaGlsZHJlbigpLmxlbmd0aCA+IDEpIHtcblx0XHRcdFx0XHRcdFx0XHRcdGxpLmRlc3Ryb3koKTtcblx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0XHRcdGNhc2UgbnVsbDpcblx0XHRcdFx0XHRcdFx0YnQuYWRkRXZlbnQoJ2tleWRvd24nLCBmdW5jdGlvbihlKSB7XG5cdFx0XHRcdFx0XHRcdFx0aWYgKGUuZXZlbnQua2V5Q29kZSA9PSAzOCkge1xuXHRcdFx0XHRcdFx0XHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdFx0XHRcdFx0XHRcdFx0aWYgKHByZXZpb3VzID0gbGkuZ2V0UHJldmlvdXMoJ2xpJykpIHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0bGkuaW5qZWN0KHByZXZpb3VzLCAnYmVmb3JlJyk7XG5cdFx0XHRcdFx0XHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRsaS5pbmplY3QodWwsICdib3R0b20nKTtcblx0XHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0XHRcdGJ0LmZvY3VzKCk7XG5cdFx0XHRcdFx0XHRcdFx0fSBlbHNlIGlmIChlLmV2ZW50LmtleUNvZGUgPT0gNDApIHtcblx0XHRcdFx0XHRcdFx0XHRcdGUucHJldmVudERlZmF1bHQoKTtcblx0XHRcdFx0XHRcdFx0XHRcdGlmIChuZXh0ID0gbGkuZ2V0TmV4dCgnbGknKSkge1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRsaS5pbmplY3QobmV4dCwgJ2FmdGVyJyk7XG5cdFx0XHRcdFx0XHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRsaS5pbmplY3QodWwuZ2V0Rmlyc3QoJ2xpJyksICdiZWZvcmUnKTtcblx0XHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0XHRcdGJ0LmZvY3VzKCk7XG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHR9KTtcblx0XHRcdFx0XHRcdFx0YnJlYWs7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9KTtcblx0XHRcdH07XG5cblx0XHRtYWtlU29ydGFibGUodWwpO1xuXG5cdFx0dWwuZ2V0Q2hpbGRyZW4oKS5lYWNoKGZ1bmN0aW9uKGxpKSB7XG5cdFx0XHRhZGRFdmVudHNUbyhsaSk7XG5cdFx0fSk7XG5cdH0sXG5cblx0LyoqXG5cdCAqIFRhYmxlIHdpemFyZFxuXHQgKlxuXHQgKiBAcGFyYW0ge3N0cmluZ30gaWQgVGhlIElEIG9mIHRoZSB0YXJnZXQgZWxlbWVudFxuXHQgKi9cblx0dGFibGVXaXphcmQ6IGZ1bmN0aW9uKGlkKSB7XG5cdFx0dmFyIHRhYmxlID0gJChpZCksXG5cdFx0XHR0aGVhZCA9IHRhYmxlLmdldEVsZW1lbnQoJ3RoZWFkJyksXG5cdFx0XHR0Ym9keSA9IHRhYmxlLmdldEVsZW1lbnQoJ3Rib2R5JyksXG5cdFx0XHRtYWtlU29ydGFibGUgPSBmdW5jdGlvbih0Ym9keSkge1xuXHRcdFx0XHR2YXIgcm93cyA9IHRib2R5LmdldENoaWxkcmVuKCksXG5cdFx0XHRcdFx0dGV4dGFyZWEsIGNoaWxkcmVuLCBpLCBqO1xuXG5cdFx0XHRcdGZvciAoaT0wOyBpPHJvd3MubGVuZ3RoOyBpKyspIHtcblx0XHRcdFx0XHRjaGlsZHJlbiA9IHJvd3NbaV0uZ2V0Q2hpbGRyZW4oKTtcblx0XHRcdFx0XHRmb3IgKGo9MDsgajxjaGlsZHJlbi5sZW5ndGg7IGorKykge1xuXHRcdFx0XHRcdFx0aWYgKHRleHRhcmVhID0gY2hpbGRyZW5bal0uZ2V0Rmlyc3QoJ3RleHRhcmVhJykpIHtcblx0XHRcdFx0XHRcdFx0dGV4dGFyZWEubmFtZSA9IHRleHRhcmVhLm5hbWUucmVwbGFjZSgvXFxbWzAtOV0rXVtbMC05XStdL2csICdbJyArIGkgKyAnXVsnICsgaiArICddJylcblx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblxuXHRcdFx0XHRuZXcgU29ydGFibGVzKHRib2R5LCB7XG5cdFx0XHRcdFx0Y29uc3RyYWluOiB0cnVlLFxuXHRcdFx0XHRcdG9wYWNpdHk6IDAuNixcblx0XHRcdFx0XHRoYW5kbGU6ICcuZHJhZy1oYW5kbGUnLFxuXHRcdFx0XHRcdG9uQ29tcGxldGU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0bWFrZVNvcnRhYmxlKHRib2R5KTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0pO1xuXHRcdFx0fSxcblx0XHRcdGFkZEV2ZW50c1RvID0gZnVuY3Rpb24odHIpIHtcblx0XHRcdFx0dmFyIGhlYWQgPSB0aGVhZC5nZXRGaXJzdCgndHInKSxcblx0XHRcdFx0XHRjb21tYW5kLCB0ZXh0YXJlYSwgY3VycmVudCwgbmV4dCwgbnRyLCBjaGlsZHJlbiwgaW5kZXgsIGk7XG5cblx0XHRcdFx0dHIuZ2V0RWxlbWVudHMoJ2J1dHRvbicpLmVhY2goZnVuY3Rpb24oYnQpIHtcblx0XHRcdFx0XHRpZiAoYnQuaGFzRXZlbnQoJ2NsaWNrJykpIHJldHVybjtcblx0XHRcdFx0XHRjb21tYW5kID0gYnQuZ2V0UHJvcGVydHkoJ2RhdGEtY29tbWFuZCcpO1xuXG5cdFx0XHRcdFx0c3dpdGNoIChjb21tYW5kKSB7XG5cdFx0XHRcdFx0XHRjYXNlICdyY29weSc6XG5cdFx0XHRcdFx0XHRcdGJ0LmFkZEV2ZW50KCdjbGljaycsIGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0XHRcdHdpbmRvdy5kaXNwYXRjaEV2ZW50KG5ldyBFdmVudCgnc3RvcmUtc2Nyb2xsLW9mZnNldCcpKTtcblx0XHRcdFx0XHRcdFx0XHRudHIgPSBuZXcgRWxlbWVudCgndHInKTtcblx0XHRcdFx0XHRcdFx0XHRjaGlsZHJlbiA9IHRyLmdldENoaWxkcmVuKCk7XG5cdFx0XHRcdFx0XHRcdFx0Zm9yIChpPTA7IGk8Y2hpbGRyZW4ubGVuZ3RoOyBpKyspIHtcblx0XHRcdFx0XHRcdFx0XHRcdG5leHQgPSBjaGlsZHJlbltpXS5jbG9uZSh0cnVlKS5pbmplY3QobnRyLCAnYm90dG9tJyk7XG5cdFx0XHRcdFx0XHRcdFx0XHRpZiAodGV4dGFyZWEgPSBjaGlsZHJlbltpXS5nZXRGaXJzdCgndGV4dGFyZWEnKSkge1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRuZXh0LmdldEZpcnN0KCd0ZXh0YXJlYScpLnZhbHVlID0gdGV4dGFyZWEudmFsdWU7XG5cdFx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdG50ci5pbmplY3QodHIsICdhZnRlcicpO1xuXHRcdFx0XHRcdFx0XHRcdGFkZEV2ZW50c1RvKG50cik7XG5cdFx0XHRcdFx0XHRcdFx0bWFrZVNvcnRhYmxlKHRib2R5KTtcblx0XHRcdFx0XHRcdFx0XHRudHIuZ2V0Rmlyc3QoJ3RkJykuZ2V0Rmlyc3QoJ3RleHRhcmVhJykuc2VsZWN0KCk7XG5cdFx0XHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0XHRcdGNhc2UgJ3JkZWxldGUnOlxuXHRcdFx0XHRcdFx0XHRidC5hZGRFdmVudCgnY2xpY2snLCBmdW5jdGlvbigpIHtcblx0XHRcdFx0XHRcdFx0XHR3aW5kb3cuZGlzcGF0Y2hFdmVudChuZXcgRXZlbnQoJ3N0b3JlLXNjcm9sbC1vZmZzZXQnKSk7XG5cdFx0XHRcdFx0XHRcdFx0aWYgKHRib2R5LmdldENoaWxkcmVuKCkubGVuZ3RoID4gMSkge1xuXHRcdFx0XHRcdFx0XHRcdFx0dHIuZGVzdHJveSgpO1xuXHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0XHRtYWtlU29ydGFibGUodGJvZHkpO1xuXHRcdFx0XHRcdFx0XHR9KTtcblx0XHRcdFx0XHRcdFx0YnJlYWs7XG5cdFx0XHRcdFx0XHRjYXNlICdjY29weSc6XG5cdFx0XHRcdFx0XHRcdGJ0LmFkZEV2ZW50KCdjbGljaycsIGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0XHRcdHdpbmRvdy5kaXNwYXRjaEV2ZW50KG5ldyBFdmVudCgnc3RvcmUtc2Nyb2xsLW9mZnNldCcpKTtcblx0XHRcdFx0XHRcdFx0XHRpbmRleCA9IGdldEluZGV4KGJ0KTtcblx0XHRcdFx0XHRcdFx0XHRjaGlsZHJlbiA9IHRib2R5LmdldENoaWxkcmVuKCk7XG5cdFx0XHRcdFx0XHRcdFx0Zm9yIChpPTA7IGk8Y2hpbGRyZW4ubGVuZ3RoOyBpKyspIHtcblx0XHRcdFx0XHRcdFx0XHRcdGN1cnJlbnQgPSBjaGlsZHJlbltpXS5nZXRDaGlsZHJlbigpW2luZGV4XTtcblx0XHRcdFx0XHRcdFx0XHRcdG5leHQgPSBjdXJyZW50LmNsb25lKHRydWUpLmluamVjdChjdXJyZW50LCAnYWZ0ZXInKTtcblx0XHRcdFx0XHRcdFx0XHRcdGlmICh0ZXh0YXJlYSA9IGN1cnJlbnQuZ2V0Rmlyc3QoJ3RleHRhcmVhJykpIHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0bmV4dC5nZXRGaXJzdCgndGV4dGFyZWEnKS52YWx1ZSA9IHRleHRhcmVhLnZhbHVlO1xuXHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdFx0YWRkRXZlbnRzVG8obmV4dCk7XG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdHZhciBoZWFkRmlyc3QgPSBoZWFkLmdldEZpcnN0KCd0ZCcpO1xuXHRcdFx0XHRcdFx0XHRcdG5leHQgPSBoZWFkRmlyc3QuY2xvbmUodHJ1ZSkuaW5qZWN0KGhlYWQuZ2V0TGFzdCgndGQnKSwgJ2JlZm9yZScpO1xuXHRcdFx0XHRcdFx0XHRcdGFkZEV2ZW50c1RvKG5leHQpO1xuXHRcdFx0XHRcdFx0XHRcdG1ha2VTb3J0YWJsZSh0Ym9keSk7XG5cdFx0XHRcdFx0XHRcdFx0Y2hpbGRyZW5bMF0uZ2V0Q2hpbGRyZW4oKVtpbmRleCArIDFdLmdldEZpcnN0KCd0ZXh0YXJlYScpLnNlbGVjdCgpO1xuXHRcdFx0XHRcdFx0XHR9KTtcblx0XHRcdFx0XHRcdFx0YnJlYWs7XG5cdFx0XHRcdFx0XHRjYXNlICdjbW92ZWwnOlxuXHRcdFx0XHRcdFx0XHRidC5hZGRFdmVudCgnY2xpY2snLCBmdW5jdGlvbigpIHtcblx0XHRcdFx0XHRcdFx0XHR3aW5kb3cuZGlzcGF0Y2hFdmVudChuZXcgRXZlbnQoJ3N0b3JlLXNjcm9sbC1vZmZzZXQnKSk7XG5cdFx0XHRcdFx0XHRcdFx0aW5kZXggPSBnZXRJbmRleChidCk7XG5cdFx0XHRcdFx0XHRcdFx0Y2hpbGRyZW4gPSB0Ym9keS5nZXRDaGlsZHJlbigpO1xuXHRcdFx0XHRcdFx0XHRcdGlmIChpbmRleCA+IDApIHtcblx0XHRcdFx0XHRcdFx0XHRcdGZvciAoaT0wOyBpPGNoaWxkcmVuLmxlbmd0aDsgaSsrKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdGN1cnJlbnQgPSBjaGlsZHJlbltpXS5nZXRDaGlsZHJlbigpW2luZGV4XTtcblx0XHRcdFx0XHRcdFx0XHRcdFx0Y3VycmVudC5pbmplY3QoY3VycmVudC5nZXRQcmV2aW91cygpLCAnYmVmb3JlJyk7XG5cdFx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdFx0XHRcdGZvciAoaT0wOyBpPGNoaWxkcmVuLmxlbmd0aDsgaSsrKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdGN1cnJlbnQgPSBjaGlsZHJlbltpXS5nZXRDaGlsZHJlbigpW2luZGV4XTtcblx0XHRcdFx0XHRcdFx0XHRcdFx0Y3VycmVudC5pbmplY3QoY2hpbGRyZW5baV0uZ2V0TGFzdCgpLCAnYmVmb3JlJyk7XG5cdFx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdG1ha2VTb3J0YWJsZSh0Ym9keSk7XG5cdFx0XHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0XHRcdGNhc2UgJ2Ntb3Zlcic6XG5cdFx0XHRcdFx0XHRcdGJ0LmFkZEV2ZW50KCdjbGljaycsIGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0XHRcdHdpbmRvdy5kaXNwYXRjaEV2ZW50KG5ldyBFdmVudCgnc3RvcmUtc2Nyb2xsLW9mZnNldCcpKTtcblx0XHRcdFx0XHRcdFx0XHRpbmRleCA9IGdldEluZGV4KGJ0KTtcblx0XHRcdFx0XHRcdFx0XHRjaGlsZHJlbiA9IHRib2R5LmdldENoaWxkcmVuKCk7XG5cdFx0XHRcdFx0XHRcdFx0aWYgKGluZGV4IDwgKHRyLmdldENoaWxkcmVuKCkubGVuZ3RoIC0gMikpIHtcblx0XHRcdFx0XHRcdFx0XHRcdGZvciAoaT0wOyBpPGNoaWxkcmVuLmxlbmd0aDsgaSsrKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdGN1cnJlbnQgPSBjaGlsZHJlbltpXS5nZXRDaGlsZHJlbigpW2luZGV4XTtcblx0XHRcdFx0XHRcdFx0XHRcdFx0Y3VycmVudC5pbmplY3QoY3VycmVudC5nZXROZXh0KCksICdhZnRlcicpO1xuXHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRmb3IgKGk9MDsgaTxjaGlsZHJlbi5sZW5ndGg7IGkrKykge1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRjdXJyZW50ID0gY2hpbGRyZW5baV0uZ2V0Q2hpbGRyZW4oKVtpbmRleF07XG5cdFx0XHRcdFx0XHRcdFx0XHRcdGN1cnJlbnQuaW5qZWN0KGNoaWxkcmVuW2ldLmdldEZpcnN0KCksICdiZWZvcmUnKTtcblx0XHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0bWFrZVNvcnRhYmxlKHRib2R5KTtcblx0XHRcdFx0XHRcdFx0fSk7XG5cdFx0XHRcdFx0XHRcdGJyZWFrO1xuXHRcdFx0XHRcdFx0Y2FzZSAnY2RlbGV0ZSc6XG5cdFx0XHRcdFx0XHRcdGJ0LmFkZEV2ZW50KCdjbGljaycsIGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0XHRcdHdpbmRvdy5kaXNwYXRjaEV2ZW50KG5ldyBFdmVudCgnc3RvcmUtc2Nyb2xsLW9mZnNldCcpKTtcblx0XHRcdFx0XHRcdFx0XHRpbmRleCA9IGdldEluZGV4KGJ0KTtcblx0XHRcdFx0XHRcdFx0XHRjaGlsZHJlbiA9IHRib2R5LmdldENoaWxkcmVuKCk7XG5cdFx0XHRcdFx0XHRcdFx0aWYgKHRyLmdldENoaWxkcmVuKCkubGVuZ3RoID4gMikge1xuXHRcdFx0XHRcdFx0XHRcdFx0Zm9yIChpPTA7IGk8Y2hpbGRyZW4ubGVuZ3RoOyBpKyspIHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0Y2hpbGRyZW5baV0uZ2V0Q2hpbGRyZW4oKVtpbmRleF0uZGVzdHJveSgpO1xuXHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdFx0aGVhZC5nZXRGaXJzdCgndGQnKS5kZXN0cm95KCk7XG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdG1ha2VTb3J0YWJsZSh0Ym9keSk7XG5cdFx0XHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0XHRcdGNhc2UgbnVsbDpcblx0XHRcdFx0XHRcdFx0YnQuYWRkRXZlbnQoJ2tleWRvd24nLCBmdW5jdGlvbihlKSB7XG5cdFx0XHRcdFx0XHRcdFx0aWYgKGUuZXZlbnQua2V5Q29kZSA9PSAzOCkge1xuXHRcdFx0XHRcdFx0XHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdFx0XHRcdFx0XHRcdFx0aWYgKG50ciA9IHRyLmdldFByZXZpb3VzKCd0cicpKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHRyLmluamVjdChudHIsICdiZWZvcmUnKTtcblx0XHRcdFx0XHRcdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHRyLmluamVjdCh0Ym9keSwgJ2JvdHRvbScpO1xuXHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdFx0YnQuZm9jdXMoKTtcblx0XHRcdFx0XHRcdFx0XHRcdG1ha2VTb3J0YWJsZSh0Ym9keSk7XG5cdFx0XHRcdFx0XHRcdFx0fSBlbHNlIGlmIChlLmV2ZW50LmtleUNvZGUgPT0gNDApIHtcblx0XHRcdFx0XHRcdFx0XHRcdGUucHJldmVudERlZmF1bHQoKTtcblx0XHRcdFx0XHRcdFx0XHRcdGlmIChudHIgPSB0ci5nZXROZXh0KCd0cicpKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHRyLmluamVjdChudHIsICdhZnRlcicpO1xuXHRcdFx0XHRcdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0dHIuaW5qZWN0KHRib2R5LCAndG9wJyk7XG5cdFx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0XHRidC5mb2N1cygpO1xuXHRcdFx0XHRcdFx0XHRcdFx0bWFrZVNvcnRhYmxlKHRib2R5KTtcblx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0pO1xuXHRcdFx0fSxcblx0XHRcdGdldEluZGV4ID0gZnVuY3Rpb24oYnQpIHtcblx0XHRcdFx0dmFyIHRkID0gJChidCkuZ2V0UGFyZW50KCd0ZCcpLFxuXHRcdFx0XHRcdHRyID0gdGQuZ2V0UGFyZW50KCd0cicpLFxuXHRcdFx0XHRcdGNvbHMgPSB0ci5nZXRDaGlsZHJlbigpLFxuXHRcdFx0XHRcdGluZGV4ID0gMCwgaTtcblxuXHRcdFx0XHRmb3IgKGk9MDsgaTxjb2xzLmxlbmd0aDsgaSsrKSB7XG5cdFx0XHRcdFx0aWYgKGNvbHNbaV0gPT0gdGQpIHtcblx0XHRcdFx0XHRcdGJyZWFrO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0XHRpbmRleCsrO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0cmV0dXJuIGluZGV4O1xuXHRcdFx0fTtcblxuXHRcdG1ha2VTb3J0YWJsZSh0Ym9keSk7XG5cblx0XHR0aGVhZC5nZXRDaGlsZHJlbigpLmVhY2goZnVuY3Rpb24odHIpIHtcblx0XHRcdGFkZEV2ZW50c1RvKHRyKTtcblx0XHR9KTtcblxuXHRcdHRib2R5LmdldENoaWxkcmVuKCkuZWFjaChmdW5jdGlvbih0cikge1xuXHRcdFx0YWRkRXZlbnRzVG8odHIpO1xuXHRcdH0pO1xuXG5cdFx0QmFja2VuZC50YWJsZVdpemFyZFJlc2l6ZSgpO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBSZXNpemUgdGhlIHRhYmxlIHdpemFyZCBmaWVsZHMgb24gZm9jdXNcblx0ICpcblx0ICogQHBhcmFtIHtmbG9hdH0gW2ZhY3Rvcl0gVGhlIHJlc2l6ZSBmYWN0b3Jcblx0ICovXG5cdHRhYmxlV2l6YXJkUmVzaXplOiBmdW5jdGlvbihmYWN0b3IpIHtcblx0XHR2YXIgc2l6ZSA9IHdpbmRvdy5sb2NhbFN0b3JhZ2UuZ2V0SXRlbSgnY29udGFvX3RhYmxlX3dpemFyZF9jZWxsX3NpemUnKTtcblxuXHRcdGlmIChmYWN0b3IgIT09IHVuZGVmaW5lZCkge1xuXHRcdFx0c2l6ZSA9ICcnO1xuXHRcdFx0JCQoJy50bF90YWJsZXdpemFyZCB0ZXh0YXJlYScpLmVhY2goZnVuY3Rpb24oZWwpIHtcblx0XHRcdFx0ZWwuc2V0U3R5bGUoJ3dpZHRoJywgKGVsLmdldFN0eWxlKCd3aWR0aCcpLnRvSW50KCkgKiBmYWN0b3IpLnJvdW5kKCkubGltaXQoMTQyLCAyODQpKTtcblx0XHRcdFx0ZWwuc2V0U3R5bGUoJ2hlaWdodCcsIChlbC5nZXRTdHlsZSgnaGVpZ2h0JykudG9JbnQoKSAqIGZhY3Rvcikucm91bmQoKS5saW1pdCg2NiwgMTMyKSk7XG5cdFx0XHRcdGlmIChzaXplID09ICcnKSB7XG5cdFx0XHRcdFx0c2l6ZSA9IGVsLmdldFN0eWxlKCd3aWR0aCcpICsgJ3wnICsgZWwuZ2V0U3R5bGUoJ2hlaWdodCcpO1xuXHRcdFx0XHR9XG5cdFx0XHR9KTtcblx0XHRcdHdpbmRvdy5sb2NhbFN0b3JhZ2Uuc2V0SXRlbSgnY29udGFvX3RhYmxlX3dpemFyZF9jZWxsX3NpemUnLCBzaXplKTtcblx0XHR9IGVsc2UgaWYgKHNpemUgIT09IG51bGwpIHtcblx0XHRcdHZhciBjaHVua3MgPSBzaXplLnNwbGl0KCd8Jyk7XG5cdFx0XHQkJCgnLnRsX3RhYmxld2l6YXJkIHRleHRhcmVhJykuZWFjaChmdW5jdGlvbihlbCkge1xuXHRcdFx0XHRlbC5zZXRTdHlsZSgnd2lkdGgnLCBjaHVua3NbMF0pO1xuXHRcdFx0XHRlbC5zZXRTdHlsZSgnaGVpZ2h0JywgY2h1bmtzWzFdKTtcblx0XHRcdH0pO1xuXHRcdH1cblx0fSxcblxuXHQvKipcblx0ICogU2V0IHRoZSB3aWR0aCBvZiB0aGUgdGFibGUgd2l6YXJkXG5cdCAqL1xuXHR0YWJsZVdpemFyZFNldFdpZHRoOiBmdW5jdGlvbigpIHtcblx0XHR2YXIgd3JhcCA9ICQoJ3RsX3RhYmxld2l6YXJkJyk7XG5cdFx0aWYgKCF3cmFwKSByZXR1cm47XG5cdFx0d3JhcC5zZXRTdHlsZSgnd2lkdGgnLCBNYXRoLnJvdW5kKHdyYXAuZ2V0UGFyZW50KCcudGxfZm9ybWJvZHlfZWRpdCcpLmdldENvbXB1dGVkU2l6ZSgpLndpZHRoICogMC45NikpO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBPcHRpb25zIHdpemFyZFxuXHQgKlxuXHQgKiBAcGFyYW0ge3N0cmluZ30gaWQgVGhlIElEIG9mIHRoZSB0YXJnZXQgZWxlbWVudFxuXHQgKi9cblx0b3B0aW9uc1dpemFyZDogZnVuY3Rpb24oaWQpIHtcblx0XHR2YXIgdGFibGUgPSAkKGlkKSxcblx0XHRcdHRib2R5ID0gdGFibGUuZ2V0RWxlbWVudCgndGJvZHknKSxcblx0XHRcdG1ha2VTb3J0YWJsZSA9IGZ1bmN0aW9uKHRib2R5KSB7XG5cdFx0XHRcdHZhciByb3dzID0gdGJvZHkuZ2V0Q2hpbGRyZW4oKSxcblx0XHRcdFx0XHRjaGlsZHJlbiwgaSwgaiwgaW5wdXQ7XG5cblx0XHRcdFx0Zm9yIChpPTA7IGk8cm93cy5sZW5ndGg7IGkrKykge1xuXHRcdFx0XHRcdGNoaWxkcmVuID0gcm93c1tpXS5nZXRDaGlsZHJlbigpO1xuXHRcdFx0XHRcdGZvciAoaj0wOyBqPGNoaWxkcmVuLmxlbmd0aDsgaisrKSB7XG5cdFx0XHRcdFx0XHRpZiAoaW5wdXQgPSBjaGlsZHJlbltqXS5nZXRGaXJzdCgnaW5wdXQnKSkge1xuXHRcdFx0XHRcdFx0XHRpbnB1dC5uYW1lID0gaW5wdXQubmFtZS5yZXBsYWNlKC9cXFtbMC05XStdL2csICdbJyArIGkgKyAnXScpO1xuXHRcdFx0XHRcdFx0XHRpZiAoaW5wdXQudHlwZSA9PSAnY2hlY2tib3gnKSB7XG5cdFx0XHRcdFx0XHRcdFx0aW5wdXQuaWQgPSBpbnB1dC5uYW1lLnJlcGxhY2UoL1xcW1swLTldK10vZywgJycpLnJlcGxhY2UoL1xcWy9nLCAnXycpLnJlcGxhY2UoL10vZywgJycpICsgJ18nICsgaTtcblx0XHRcdFx0XHRcdFx0XHRpbnB1dC5nZXROZXh0KCdsYWJlbCcpLnNldCgnZm9yJywgaW5wdXQuaWQpO1xuXHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9XG5cblx0XHRcdFx0bmV3IFNvcnRhYmxlcyh0Ym9keSwge1xuXHRcdFx0XHRcdGNvbnN0cmFpbjogdHJ1ZSxcblx0XHRcdFx0XHRvcGFjaXR5OiAwLjYsXG5cdFx0XHRcdFx0aGFuZGxlOiAnLmRyYWctaGFuZGxlJyxcblx0XHRcdFx0XHRvbkNvbXBsZXRlOiBmdW5jdGlvbigpIHtcblx0XHRcdFx0XHRcdG1ha2VTb3J0YWJsZSh0Ym9keSk7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9KTtcblx0XHRcdH0sXG5cdFx0XHRhZGRFdmVudHNUbyA9IGZ1bmN0aW9uKHRyKSB7XG5cdFx0XHRcdHZhciBjb21tYW5kLCBpbnB1dCwgbmV4dCwgbnRyLCBjaGlsZHJlbiwgaTtcblx0XHRcdFx0dHIuZ2V0RWxlbWVudHMoJ2J1dHRvbicpLmVhY2goZnVuY3Rpb24oYnQpIHtcblx0XHRcdFx0XHRpZiAoYnQuaGFzRXZlbnQoJ2NsaWNrJykpIHJldHVybjtcblx0XHRcdFx0XHRjb21tYW5kID0gYnQuZ2V0UHJvcGVydHkoJ2RhdGEtY29tbWFuZCcpO1xuXG5cdFx0XHRcdFx0c3dpdGNoIChjb21tYW5kKSB7XG5cdFx0XHRcdFx0XHRjYXNlICdjb3B5Jzpcblx0XHRcdFx0XHRcdFx0YnQuYWRkRXZlbnQoJ2NsaWNrJywgZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0XHRcdFx0d2luZG93LmRpc3BhdGNoRXZlbnQobmV3IEV2ZW50KCdzdG9yZS1zY3JvbGwtb2Zmc2V0JykpO1xuXHRcdFx0XHRcdFx0XHRcdG50ciA9IG5ldyBFbGVtZW50KCd0cicpO1xuXHRcdFx0XHRcdFx0XHRcdGNoaWxkcmVuID0gdHIuZ2V0Q2hpbGRyZW4oKTtcblx0XHRcdFx0XHRcdFx0XHRmb3IgKGk9MDsgaTxjaGlsZHJlbi5sZW5ndGg7IGkrKykge1xuXHRcdFx0XHRcdFx0XHRcdFx0bmV4dCA9IGNoaWxkcmVuW2ldLmNsb25lKHRydWUpLmluamVjdChudHIsICdib3R0b20nKTtcblx0XHRcdFx0XHRcdFx0XHRcdGlmIChpbnB1dCA9IGNoaWxkcmVuW2ldLmdldEZpcnN0KCdpbnB1dCcpKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdG5leHQuZ2V0Rmlyc3QoJ2lucHV0JykudmFsdWUgPSBpbnB1dC52YWx1ZTtcblx0XHRcdFx0XHRcdFx0XHRcdFx0aWYgKGlucHV0LnR5cGUgPT0gJ2NoZWNrYm94Jykge1xuXHRcdFx0XHRcdFx0XHRcdFx0XHRcdG5leHQuZ2V0Rmlyc3QoJ2lucHV0JykuY2hlY2tlZCA9IGlucHV0LmNoZWNrZWQgPyAnY2hlY2tlZCcgOiAnJztcblx0XHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0XHRudHIuaW5qZWN0KHRyLCAnYWZ0ZXInKTtcblx0XHRcdFx0XHRcdFx0XHRhZGRFdmVudHNUbyhudHIpO1xuXHRcdFx0XHRcdFx0XHRcdG1ha2VTb3J0YWJsZSh0Ym9keSk7XG5cdFx0XHRcdFx0XHRcdFx0bnRyLmdldEZpcnN0KCd0ZCcpLmdldEZpcnN0KCdpbnB1dCcpLnNlbGVjdCgpO1xuXHRcdFx0XHRcdFx0XHR9KTtcblx0XHRcdFx0XHRcdFx0YnJlYWs7XG5cdFx0XHRcdFx0XHRjYXNlICdkZWxldGUnOlxuXHRcdFx0XHRcdFx0XHRidC5hZGRFdmVudCgnY2xpY2snLCBmdW5jdGlvbigpIHtcblx0XHRcdFx0XHRcdFx0XHR3aW5kb3cuZGlzcGF0Y2hFdmVudChuZXcgRXZlbnQoJ3N0b3JlLXNjcm9sbC1vZmZzZXQnKSk7XG5cdFx0XHRcdFx0XHRcdFx0aWYgKHRib2R5LmdldENoaWxkcmVuKCkubGVuZ3RoID4gMSkge1xuXHRcdFx0XHRcdFx0XHRcdFx0dHIuZGVzdHJveSgpO1xuXHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0XHRtYWtlU29ydGFibGUodGJvZHkpO1xuXHRcdFx0XHRcdFx0XHR9KTtcblx0XHRcdFx0XHRcdFx0YnJlYWs7XG5cdFx0XHRcdFx0XHRjYXNlIG51bGw6XG5cdFx0XHRcdFx0XHRcdGJ0LmFkZEV2ZW50KCdrZXlkb3duJywgZnVuY3Rpb24oZSkge1xuXHRcdFx0XHRcdFx0XHRcdGlmIChlLmV2ZW50LmtleUNvZGUgPT0gMzgpIHtcblx0XHRcdFx0XHRcdFx0XHRcdGUucHJldmVudERlZmF1bHQoKTtcblx0XHRcdFx0XHRcdFx0XHRcdGlmIChudHIgPSB0ci5nZXRQcmV2aW91cygndHInKSkge1xuXHRcdFx0XHRcdFx0XHRcdFx0XHR0ci5pbmplY3QobnRyLCAnYmVmb3JlJyk7XG5cdFx0XHRcdFx0XHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdFx0XHRcdFx0XHR0ci5pbmplY3QodGJvZHksICdib3R0b20nKTtcblx0XHRcdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdFx0XHRcdGJ0LmZvY3VzKCk7XG5cdFx0XHRcdFx0XHRcdFx0XHRtYWtlU29ydGFibGUodGJvZHkpO1xuXHRcdFx0XHRcdFx0XHRcdH0gZWxzZSBpZiAoZS5ldmVudC5rZXlDb2RlID09IDQwKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cdFx0XHRcdFx0XHRcdFx0XHRpZiAobnRyID0gdHIuZ2V0TmV4dCgndHInKSkge1xuXHRcdFx0XHRcdFx0XHRcdFx0XHR0ci5pbmplY3QobnRyLCAnYWZ0ZXInKTtcblx0XHRcdFx0XHRcdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHRyLmluamVjdCh0Ym9keSwgJ3RvcCcpO1xuXHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdFx0YnQuZm9jdXMoKTtcblx0XHRcdFx0XHRcdFx0XHRcdG1ha2VTb3J0YWJsZSh0Ym9keSk7XG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHR9KTtcblx0XHRcdFx0XHRcdFx0YnJlYWs7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9KTtcblx0XHRcdH07XG5cblx0XHRtYWtlU29ydGFibGUodGJvZHkpO1xuXG5cdFx0dGJvZHkuZ2V0Q2hpbGRyZW4oKS5lYWNoKGZ1bmN0aW9uKHRyKSB7XG5cdFx0XHRhZGRFdmVudHNUbyh0cik7XG5cdFx0fSk7XG5cdH0sXG5cblx0LyoqXG5cdCAqIEtleS92YWx1ZSB3aXphcmRcblx0ICpcblx0ICogQHBhcmFtIHtzdHJpbmd9IGlkIFRoZSBJRCBvZiB0aGUgdGFyZ2V0IGVsZW1lbnRcblx0ICovXG5cdGtleVZhbHVlV2l6YXJkOiBmdW5jdGlvbihpZCkge1xuXHRcdHZhciB0YWJsZSA9ICQoaWQpLFxuXHRcdFx0dGJvZHkgPSB0YWJsZS5nZXRFbGVtZW50KCd0Ym9keScpLFxuXHRcdFx0bWFrZVNvcnRhYmxlID0gZnVuY3Rpb24odGJvZHkpIHtcblx0XHRcdFx0dmFyIHJvd3MgPSB0Ym9keS5nZXRDaGlsZHJlbigpLFxuXHRcdFx0XHRcdGNoaWxkcmVuLCBpLCBqLCBpbnB1dDtcblxuXHRcdFx0XHRmb3IgKGk9MDsgaTxyb3dzLmxlbmd0aDsgaSsrKSB7XG5cdFx0XHRcdFx0Y2hpbGRyZW4gPSByb3dzW2ldLmdldENoaWxkcmVuKCk7XG5cdFx0XHRcdFx0Zm9yIChqPTA7IGo8Y2hpbGRyZW4ubGVuZ3RoOyBqKyspIHtcblx0XHRcdFx0XHRcdGlmIChpbnB1dCA9IGNoaWxkcmVuW2pdLmdldEZpcnN0KCdpbnB1dCcpKSB7XG5cdFx0XHRcdFx0XHRcdGlucHV0Lm5hbWUgPSBpbnB1dC5uYW1lLnJlcGxhY2UoL1xcW1swLTldK10vZywgJ1snICsgaSArICddJylcblx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblxuXHRcdFx0XHRuZXcgU29ydGFibGVzKHRib2R5LCB7XG5cdFx0XHRcdFx0Y29uc3RyYWluOiB0cnVlLFxuXHRcdFx0XHRcdG9wYWNpdHk6IDAuNixcblx0XHRcdFx0XHRoYW5kbGU6ICcuZHJhZy1oYW5kbGUnLFxuXHRcdFx0XHRcdG9uQ29tcGxldGU6IGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0bWFrZVNvcnRhYmxlKHRib2R5KTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0pO1xuXHRcdFx0fSxcblx0XHRcdGFkZEV2ZW50c1RvID0gZnVuY3Rpb24odHIpIHtcblx0XHRcdFx0dmFyIGNvbW1hbmQsIGlucHV0LCBuZXh0LCBudHIsIGNoaWxkcmVuLCBpO1xuXHRcdFx0XHR0ci5nZXRFbGVtZW50cygnYnV0dG9uJykuZWFjaChmdW5jdGlvbihidCkge1xuXHRcdFx0XHRcdGlmIChidC5oYXNFdmVudCgnY2xpY2snKSkgcmV0dXJuO1xuXHRcdFx0XHRcdGNvbW1hbmQgPSBidC5nZXRQcm9wZXJ0eSgnZGF0YS1jb21tYW5kJyk7XG5cblx0XHRcdFx0XHRzd2l0Y2ggKGNvbW1hbmQpIHtcblx0XHRcdFx0XHRcdGNhc2UgJ2NvcHknOlxuXHRcdFx0XHRcdFx0XHRidC5hZGRFdmVudCgnY2xpY2snLCBmdW5jdGlvbigpIHtcblx0XHRcdFx0XHRcdFx0XHR3aW5kb3cuZGlzcGF0Y2hFdmVudChuZXcgRXZlbnQoJ3N0b3JlLXNjcm9sbC1vZmZzZXQnKSk7XG5cdFx0XHRcdFx0XHRcdFx0bnRyID0gbmV3IEVsZW1lbnQoJ3RyJyk7XG5cdFx0XHRcdFx0XHRcdFx0Y2hpbGRyZW4gPSB0ci5nZXRDaGlsZHJlbigpO1xuXHRcdFx0XHRcdFx0XHRcdGZvciAoaT0wOyBpPGNoaWxkcmVuLmxlbmd0aDsgaSsrKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRuZXh0ID0gY2hpbGRyZW5baV0uY2xvbmUodHJ1ZSkuaW5qZWN0KG50ciwgJ2JvdHRvbScpO1xuXHRcdFx0XHRcdFx0XHRcdFx0aWYgKGlucHV0ID0gY2hpbGRyZW5baV0uZ2V0Rmlyc3QoJ2lucHV0JykpIHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0bmV4dC5nZXRGaXJzdCgpLnZhbHVlID0gaW5wdXQudmFsdWU7XG5cdFx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdG50ci5pbmplY3QodHIsICdhZnRlcicpO1xuXHRcdFx0XHRcdFx0XHRcdGFkZEV2ZW50c1RvKG50cik7XG5cdFx0XHRcdFx0XHRcdFx0bWFrZVNvcnRhYmxlKHRib2R5KTtcblx0XHRcdFx0XHRcdFx0XHRudHIuZ2V0Rmlyc3QoJ3RkJykuZ2V0Rmlyc3QoJ2lucHV0Jykuc2VsZWN0KCk7XG5cdFx0XHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0XHRcdGNhc2UgJ2RlbGV0ZSc6XG5cdFx0XHRcdFx0XHRcdGJ0LmFkZEV2ZW50KCdjbGljaycsIGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0XHRcdHdpbmRvdy5kaXNwYXRjaEV2ZW50KG5ldyBFdmVudCgnc3RvcmUtc2Nyb2xsLW9mZnNldCcpKTtcblx0XHRcdFx0XHRcdFx0XHRpZiAodGJvZHkuZ2V0Q2hpbGRyZW4oKS5sZW5ndGggPiAxKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHR0ci5kZXN0cm95KCk7XG5cdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdG1ha2VTb3J0YWJsZSh0Ym9keSk7XG5cdFx0XHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0XHRcdGNhc2UgbnVsbDpcblx0XHRcdFx0XHRcdFx0YnQuYWRkRXZlbnQoJ2tleWRvd24nLCBmdW5jdGlvbihlKSB7XG5cdFx0XHRcdFx0XHRcdFx0aWYgKGUuZXZlbnQua2V5Q29kZSA9PSAzOCkge1xuXHRcdFx0XHRcdFx0XHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdFx0XHRcdFx0XHRcdFx0aWYgKG50ciA9IHRyLmdldFByZXZpb3VzKCd0cicpKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHRyLmluamVjdChudHIsICdiZWZvcmUnKTtcblx0XHRcdFx0XHRcdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHRyLmluamVjdCh0Ym9keSwgJ2JvdHRvbScpO1xuXHRcdFx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRcdFx0YnQuZm9jdXMoKTtcblx0XHRcdFx0XHRcdFx0XHRcdG1ha2VTb3J0YWJsZSh0Ym9keSk7XG5cdFx0XHRcdFx0XHRcdFx0fSBlbHNlIGlmIChlLmV2ZW50LmtleUNvZGUgPT0gNDApIHtcblx0XHRcdFx0XHRcdFx0XHRcdGUucHJldmVudERlZmF1bHQoKTtcblx0XHRcdFx0XHRcdFx0XHRcdGlmIChudHIgPSB0ci5nZXROZXh0KCd0cicpKSB7XG5cdFx0XHRcdFx0XHRcdFx0XHRcdHRyLmluamVjdChudHIsICdhZnRlcicpO1xuXHRcdFx0XHRcdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdFx0XHRcdFx0dHIuaW5qZWN0KHRib2R5LCAndG9wJyk7XG5cdFx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdFx0XHRidC5mb2N1cygpO1xuXHRcdFx0XHRcdFx0XHRcdFx0bWFrZVNvcnRhYmxlKHRib2R5KTtcblx0XHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdFx0XHRicmVhaztcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0pO1xuXHRcdFx0fTtcblxuXHRcdG1ha2VTb3J0YWJsZSh0Ym9keSk7XG5cblx0XHR0Ym9keS5nZXRDaGlsZHJlbigpLmVhY2goZnVuY3Rpb24odHIpIHtcblx0XHRcdGFkZEV2ZW50c1RvKHRyKTtcblx0XHR9KTtcblx0fSxcblxuXHQvKipcblx0ICogQ2hlY2tib3ggd2l6YXJkXG5cdCAqXG5cdCAqIEBwYXJhbSB7c3RyaW5nfSBpZCBUaGUgSUQgb2YgdGhlIHRhcmdldCBlbGVtZW50XG5cdCAqL1xuXHRjaGVja2JveFdpemFyZDogZnVuY3Rpb24oaWQpIHtcblx0XHR2YXIgY29udGFpbmVyID0gJChpZCkuZ2V0RWxlbWVudCgnLnNvcnRhYmxlJyksXG5cdFx0XHRtYWtlU29ydGFibGUgPSBmdW5jdGlvbihjb250YWluZXIpIHtcblx0XHRcdFx0bmV3IFNvcnRhYmxlcyhjb250YWluZXIsIHtcblx0XHRcdFx0XHRjb25zdHJhaW46IHRydWUsXG5cdFx0XHRcdFx0b3BhY2l0eTogMC42LFxuXHRcdFx0XHRcdGhhbmRsZTogJy5kcmFnLWhhbmRsZSdcblx0XHRcdFx0fSk7XG5cdFx0XHR9LFxuXHRcdFx0YWRkRXZlbnRzVG8gPSBmdW5jdGlvbihzcGFuKSB7XG5cdFx0XHRcdHZhciBuc3Bhbjtcblx0XHRcdFx0c3Bhbi5nZXRFbGVtZW50cygnYnV0dG9uJykuZWFjaChmdW5jdGlvbihidCkge1xuXHRcdFx0XHRcdGlmIChidC5oYXNFdmVudCgnY2xpY2snKSkgcmV0dXJuO1xuXHRcdFx0XHRcdGJ0LmFkZEV2ZW50KCdrZXlkb3duJywgZnVuY3Rpb24oZSkge1xuXHRcdFx0XHRcdFx0aWYgKGUuZXZlbnQua2V5Q29kZSA9PSAzOCkge1xuXHRcdFx0XHRcdFx0XHRlLnByZXZlbnREZWZhdWx0KCk7XG5cdFx0XHRcdFx0XHRcdGlmICgobnNwYW4gPSBzcGFuLmdldFByZXZpb3VzKCdzcGFuJykpKSB7XG5cdFx0XHRcdFx0XHRcdFx0c3Bhbi5pbmplY3QobnNwYW4sICdiZWZvcmUnKTtcblx0XHRcdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdFx0XHRzcGFuLmluamVjdChjb250YWluZXIsICdib3R0b20nKTtcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRidC5mb2N1cygpO1xuXHRcdFx0XHRcdFx0fSBlbHNlIGlmIChlLmV2ZW50LmtleUNvZGUgPT0gNDApIHtcblx0XHRcdFx0XHRcdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdFx0XHRcdFx0XHRpZiAobnNwYW4gPSBzcGFuLmdldE5leHQoJ3NwYW4nKSkge1xuXHRcdFx0XHRcdFx0XHRcdHNwYW4uaW5qZWN0KG5zcGFuLCAnYWZ0ZXInKTtcblx0XHRcdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdFx0XHRzcGFuLmluamVjdChjb250YWluZXIsICd0b3AnKTtcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0XHRidC5mb2N1cygpO1xuXHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdH0pO1xuXHRcdFx0XHR9KTtcblx0XHRcdH07XG5cblx0XHRtYWtlU29ydGFibGUoY29udGFpbmVyKTtcblxuXHRcdGNvbnRhaW5lci5nZXRDaGlsZHJlbigpLmVhY2goZnVuY3Rpb24oc3Bhbikge1xuXHRcdFx0YWRkRXZlbnRzVG8oc3Bhbik7XG5cdFx0fSk7XG5cdH0sXG5cblx0LyoqXG5cdCAqIFVwZGF0ZSB0aGUgZmllbGRzIG9mIHRoZSBpbWFnZVNpemUgd2lkZ2V0IHVwb24gY2hhbmdlXG5cdCAqL1xuXHRlbmFibGVJbWFnZVNpemVXaWRnZXRzOiBmdW5jdGlvbigpIHtcblx0XHQkJCgnLnRsX2ltYWdlX3NpemUnKS5lYWNoKGZ1bmN0aW9uKGVsKSB7XG5cdFx0XHR2YXIgc2VsZWN0ID0gZWwuZ2V0RWxlbWVudCgnc2VsZWN0JyksXG5cdFx0XHRcdHdpZHRoSW5wdXQgPSBlbC5nZXRDaGlsZHJlbignaW5wdXQnKVswXSxcblx0XHRcdFx0aGVpZ2h0SW5wdXQgPSBlbC5nZXRDaGlsZHJlbignaW5wdXQnKVsxXSxcblx0XHRcdFx0dXBkYXRlID0gZnVuY3Rpb24oKSB7XG5cdFx0XHRcdFx0aWYgKHNlbGVjdC5nZXQoJ3ZhbHVlJykgPT09ICcnIHx8IHNlbGVjdC5nZXQoJ3ZhbHVlJykuaW5kZXhPZignXycpID09PSAwIHx8IHNlbGVjdC5nZXQoJ3ZhbHVlJykudG9JbnQoKS50b1N0cmluZygpID09PSBzZWxlY3QuZ2V0KCd2YWx1ZScpKSB7XG5cdFx0XHRcdFx0XHR3aWR0aElucHV0LnJlYWRPbmx5ID0gdHJ1ZTtcblx0XHRcdFx0XHRcdGhlaWdodElucHV0LnJlYWRPbmx5ID0gdHJ1ZTtcblx0XHRcdFx0XHRcdHZhciBkaW1lbnNpb25zID0gJChzZWxlY3QuZ2V0U2VsZWN0ZWQoKVswXSkuZ2V0KCd0ZXh0Jyk7XG5cdFx0XHRcdFx0XHRkaW1lbnNpb25zID0gZGltZW5zaW9ucy5zcGxpdCgnKCcpLmxlbmd0aCA+IDFcblx0XHRcdFx0XHRcdFx0PyBkaW1lbnNpb25zLnNwbGl0KCcoJykuZ2V0TGFzdCgpLnNwbGl0KCcpJylbMF0uc3BsaXQoJ3gnKVxuXHRcdFx0XHRcdFx0XHQ6IFsnJywgJyddO1xuXHRcdFx0XHRcdFx0d2lkdGhJbnB1dC5zZXQoJ3ZhbHVlJywgJycpLnNldCgncGxhY2Vob2xkZXInLCBkaW1lbnNpb25zWzBdICogMSB8fCAnJyk7XG5cdFx0XHRcdFx0XHRoZWlnaHRJbnB1dC5zZXQoJ3ZhbHVlJywgJycpLnNldCgncGxhY2Vob2xkZXInLCBkaW1lbnNpb25zWzFdICogMSB8fCAnJyk7XG5cdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdHdpZHRoSW5wdXQuc2V0KCdwbGFjZWhvbGRlcicsICcnKTtcblx0XHRcdFx0XHRcdGhlaWdodElucHV0LnNldCgncGxhY2Vob2xkZXInLCAnJyk7XG5cdFx0XHRcdFx0XHR3aWR0aElucHV0LnJlYWRPbmx5ID0gZmFsc2U7XG5cdFx0XHRcdFx0XHRoZWlnaHRJbnB1dC5yZWFkT25seSA9IGZhbHNlO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fVxuXHRcdFx0O1xuXG5cdFx0XHR1cGRhdGUoKTtcblx0XHRcdHNlbGVjdC5hZGRFdmVudCgnY2hhbmdlJywgdXBkYXRlKTtcblx0XHRcdHNlbGVjdC5hZGRFdmVudCgna2V5dXAnLCB1cGRhdGUpO1xuXHRcdH0pO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBBbGxvdyB0b2dnbGluZyBjaGVja2JveGVzIG9yIHJhZGlvIGJ1dHRvbnMgYnkgY2xpY2tpbmcgYSByb3dcblx0ICpcblx0ICogQGF1dGhvciBLYW1pbCBLdXptaW5za2lcblx0ICovXG5cdGVuYWJsZVRvZ2dsZVNlbGVjdDogZnVuY3Rpb24oKSB7XG5cdFx0dmFyIGNvbnRhaW5lciA9ICQoJ3RsX2xpc3RpbmcnKSxcblx0XHRcdHNoaWZ0VG9nZ2xlID0gZnVuY3Rpb24oZWwpIHtcblx0XHRcdFx0dGhpc0luZGV4ID0gY2hlY2tib3hlcy5pbmRleE9mKGVsKTtcblx0XHRcdFx0c3RhcnRJbmRleCA9IGNoZWNrYm94ZXMuaW5kZXhPZihzdGFydCk7XG5cdFx0XHRcdGZyb20gPSBNYXRoLm1pbih0aGlzSW5kZXgsIHN0YXJ0SW5kZXgpO1xuXHRcdFx0XHR0byA9IE1hdGgubWF4KHRoaXNJbmRleCwgc3RhcnRJbmRleCk7XG5cdFx0XHRcdHN0YXR1cyA9ICEhY2hlY2tib3hlc1tzdGFydEluZGV4XS5jaGVja2VkO1xuXG5cdFx0XHRcdGZvciAoZnJvbTsgZnJvbTw9dG87IGZyb20rKykge1xuXHRcdFx0XHRcdGNoZWNrYm94ZXNbZnJvbV0uY2hlY2tlZCA9IHN0YXR1cztcblx0XHRcdFx0fVxuXHRcdFx0fSxcblx0XHRcdGNsaWNrRXZlbnQgPSBmdW5jdGlvbihlKSB7XG5cdFx0XHRcdHZhciBpbnB1dCA9IHRoaXMuZ2V0RWxlbWVudCgnaW5wdXRbdHlwZT1cImNoZWNrYm94XCJdLGlucHV0W3R5cGU9XCJyYWRpb1wiXScpLFxuXHRcdFx0XHRcdGxpbWl0VG9nZ2xlciA9ICQoZS50YXJnZXQpLmdldFBhcmVudCgnLmxpbWl0X3RvZ2dsZXInKTtcblxuXHRcdFx0XHRpZiAoIWlucHV0IHx8IGlucHV0LmdldCgnZGlzYWJsZWQnKSB8fCBsaW1pdFRvZ2dsZXIgIT09IG51bGwpIHtcblx0XHRcdFx0XHRyZXR1cm47XG5cdFx0XHRcdH1cblxuXHRcdFx0XHQvLyBSYWRpbyBidXR0b25zXG5cdFx0XHRcdGlmIChpbnB1dC50eXBlID09ICdyYWRpbycpIHtcblx0XHRcdFx0XHRpZiAoIWlucHV0LmNoZWNrZWQpIHtcblx0XHRcdFx0XHRcdGlucHV0LmNoZWNrZWQgPSAnY2hlY2tlZCc7XG5cdFx0XHRcdFx0fVxuXG5cdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHR9XG5cblx0XHRcdFx0Ly8gQ2hlY2tib3hlc1xuXHRcdFx0XHRpZiAoZS5zaGlmdCAmJiBzdGFydCkge1xuXHRcdFx0XHRcdHNoaWZ0VG9nZ2xlKGlucHV0KTtcblx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRpbnB1dC5jaGVja2VkID0gaW5wdXQuY2hlY2tlZCA/ICcnIDogJ2NoZWNrZWQnO1xuXG5cdFx0XHRcdFx0aWYgKGlucHV0LmdldCgnb25jbGljaycpID09ICdCYWNrZW5kLnRvZ2dsZUNoZWNrYm94ZXModGhpcyknKSB7XG5cdFx0XHRcdFx0XHRCYWNrZW5kLnRvZ2dsZUNoZWNrYm94ZXMoaW5wdXQpOyAvLyBzZWUgIzYzOTlcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH1cblxuXHRcdFx0XHRzdGFydCA9IGlucHV0O1xuXHRcdFx0fSxcblx0XHRcdGNoZWNrYm94ZXMgPSBbXSwgc3RhcnQsIHRoaXNJbmRleCwgc3RhcnRJbmRleCwgc3RhdHVzLCBmcm9tLCB0bztcblxuXHRcdGlmIChjb250YWluZXIpIHtcblx0XHRcdGNoZWNrYm94ZXMgPSBjb250YWluZXIuZ2V0RWxlbWVudHMoJ2lucHV0W3R5cGU9XCJjaGVja2JveFwiXScpO1xuXHRcdH1cblxuXHRcdC8vIFJvdyBjbGlja1xuXHRcdCQkKCcudG9nZ2xlX3NlbGVjdCcpLmVhY2goZnVuY3Rpb24oZWwpIHtcblx0XHRcdHZhciBib3VuZEV2ZW50ID0gZWwucmV0cmlldmUoJ2JvdW5kRXZlbnQnKTtcblxuXHRcdFx0aWYgKGJvdW5kRXZlbnQpIHtcblx0XHRcdFx0ZWwucmVtb3ZlRXZlbnQoJ2NsaWNrJywgYm91bmRFdmVudCk7XG5cdFx0XHR9XG5cblx0XHRcdC8vIERvIG5vdCBwcm9wYWdhdGUgdGhlIGZvcm0gZmllbGQgY2xpY2sgZXZlbnRzXG5cdFx0XHRlbC5nZXRFbGVtZW50cygnbGFiZWwsaW5wdXRbdHlwZT1cImNoZWNrYm94XCJdLGlucHV0W3R5cGU9XCJyYWRpb1wiXScpLmVhY2goZnVuY3Rpb24oaSkge1xuXHRcdFx0XHRpLmFkZEV2ZW50KCdjbGljaycsIGZ1bmN0aW9uKGUpIHtcblx0XHRcdFx0XHRlLnN0b3BQcm9wYWdhdGlvbigpO1xuXHRcdFx0XHR9KTtcblx0XHRcdH0pO1xuXG5cdFx0XHRib3VuZEV2ZW50ID0gY2xpY2tFdmVudC5iaW5kKGVsKTtcblxuXHRcdFx0ZWwuYWRkRXZlbnQoJ2NsaWNrJywgYm91bmRFdmVudCk7XG5cdFx0XHRlbC5zdG9yZSgnYm91bmRFdmVudCcsIGJvdW5kRXZlbnQpO1xuXHRcdH0pO1xuXG5cdFx0Ly8gQ2hlY2tib3ggY2xpY2tcblx0XHRjaGVja2JveGVzLmVhY2goZnVuY3Rpb24oZWwpIHtcblx0XHRcdGVsLmFkZEV2ZW50KCdjbGljaycsIGZ1bmN0aW9uKGUpIHtcblx0XHRcdFx0aWYgKGUuc2hpZnQgJiYgc3RhcnQpIHtcblx0XHRcdFx0XHRzaGlmdFRvZ2dsZSh0aGlzKTtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdHN0YXJ0ID0gdGhpcztcblx0XHRcdH0pO1xuXHRcdH0pO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBBbGxvdyB0byBtYXJrIHRoZSBpbXBvcnRhbnQgcGFydCBvZiBhbiBpbWFnZVxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gZWwgVGhlIERPTSBlbGVtZW50XG5cdCAqL1xuXHRlZGl0UHJldmlld1dpemFyZDogZnVuY3Rpb24oZWwpIHtcblx0XHRlbCA9ICQoZWwpO1xuXHRcdHZhciBpbWFnZUVsZW1lbnQgPSBlbC5nZXRFbGVtZW50KCdpbWcnKSxcblx0XHRcdGlucHV0RWxlbWVudHMgPSB7fSxcblx0XHRcdGlzRHJhd2luZyA9IGZhbHNlLFxuXHRcdFx0cGFydEVsZW1lbnQsIHN0YXJ0UG9zLFxuXHRcdFx0Z2V0U2NhbGUgPSBmdW5jdGlvbigpIHtcblx0XHRcdFx0cmV0dXJuIHtcblx0XHRcdFx0XHR4OiBpbWFnZUVsZW1lbnQuZ2V0Q29tcHV0ZWRTaXplKCkud2lkdGgsXG5cdFx0XHRcdFx0eTogaW1hZ2VFbGVtZW50LmdldENvbXB1dGVkU2l6ZSgpLmhlaWdodFxuXHRcdFx0XHR9O1xuXHRcdFx0fSxcblx0XHRcdHVwZGF0ZUltYWdlID0gZnVuY3Rpb24oKSB7XG5cdFx0XHRcdHZhciBzY2FsZSA9IGdldFNjYWxlKCksXG5cdFx0XHRcdFx0aW1hZ2VTaXplID0gaW1hZ2VFbGVtZW50LmdldENvbXB1dGVkU2l6ZSgpO1xuXHRcdFx0XHRwYXJ0RWxlbWVudC5zZXRTdHlsZXMoe1xuXHRcdFx0XHRcdHRvcDogaW1hZ2VTaXplLmNvbXB1dGVkVG9wICsgKGlucHV0RWxlbWVudHMueS5nZXQoJ3ZhbHVlJykgKiBzY2FsZS55KS5yb3VuZCgpICsgJ3B4Jyxcblx0XHRcdFx0XHRsZWZ0OiBpbWFnZVNpemUuY29tcHV0ZWRMZWZ0ICsgKGlucHV0RWxlbWVudHMueC5nZXQoJ3ZhbHVlJykgKiBzY2FsZS54KS5yb3VuZCgpICsgJ3B4Jyxcblx0XHRcdFx0XHR3aWR0aDogKGlucHV0RWxlbWVudHMud2lkdGguZ2V0KCd2YWx1ZScpICogc2NhbGUueCkucm91bmQoKSArICdweCcsXG5cdFx0XHRcdFx0aGVpZ2h0OiAoaW5wdXRFbGVtZW50cy5oZWlnaHQuZ2V0KCd2YWx1ZScpICogc2NhbGUueSkucm91bmQoKSArICdweCdcblx0XHRcdFx0fSk7XG5cdFx0XHRcdGlmICghaW5wdXRFbGVtZW50cy53aWR0aC5nZXQoJ3ZhbHVlJykudG9GbG9hdCgpIHx8ICFpbnB1dEVsZW1lbnRzLmhlaWdodC5nZXQoJ3ZhbHVlJykudG9GbG9hdCgpKSB7XG5cdFx0XHRcdFx0cGFydEVsZW1lbnQuc2V0U3R5bGUoJ2Rpc3BsYXknLCAnbm9uZScpO1xuXHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdHBhcnRFbGVtZW50LnNldFN0eWxlKCdkaXNwbGF5JywgbnVsbCk7XG5cdFx0XHRcdH1cblx0XHRcdH0sXG5cdFx0XHR1cGRhdGVWYWx1ZXMgPSBmdW5jdGlvbigpIHtcblx0XHRcdFx0dmFyIHNjYWxlID0gZ2V0U2NhbGUoKSxcblx0XHRcdFx0XHRzdHlsZXMgPSBwYXJ0RWxlbWVudC5nZXRTdHlsZXMoJ3RvcCcsICdsZWZ0JywgJ3dpZHRoJywgJ2hlaWdodCcpLFxuXHRcdFx0XHRcdGltYWdlU2l6ZSA9IGltYWdlRWxlbWVudC5nZXRDb21wdXRlZFNpemUoKSxcblx0XHRcdFx0XHR2YWx1ZXMgPSB7XG5cdFx0XHRcdFx0XHR4OiBNYXRoLm1heCgwLCBNYXRoLm1pbigxLCAoc3R5bGVzLmxlZnQudG9GbG9hdCgpIC0gaW1hZ2VTaXplLmNvbXB1dGVkTGVmdCkgLyBzY2FsZS54KSksXG5cdFx0XHRcdFx0XHR5OiBNYXRoLm1heCgwLCBNYXRoLm1pbigxLCAoc3R5bGVzLnRvcC50b0Zsb2F0KCkgLSBpbWFnZVNpemUuY29tcHV0ZWRUb3ApIC8gc2NhbGUueSkpXG5cdFx0XHRcdFx0fTtcblx0XHRcdFx0dmFsdWVzLndpZHRoID0gTWF0aC5taW4oMSAtIHZhbHVlcy54LCBzdHlsZXMud2lkdGgudG9GbG9hdCgpIC8gc2NhbGUueCk7XG5cdFx0XHRcdHZhbHVlcy5oZWlnaHQgPSBNYXRoLm1pbigxIC0gdmFsdWVzLnksIHN0eWxlcy5oZWlnaHQudG9GbG9hdCgpIC8gc2NhbGUueSk7XG5cdFx0XHRcdGlmICghdmFsdWVzLndpZHRoIHx8ICF2YWx1ZXMuaGVpZ2h0KSB7XG5cdFx0XHRcdFx0dmFsdWVzLnggPSB2YWx1ZXMueSA9IHZhbHVlcy53aWR0aCA9IHZhbHVlcy5oZWlnaHQgPSAnJztcblx0XHRcdFx0XHRwYXJ0RWxlbWVudC5zZXRTdHlsZSgnZGlzcGxheScsICdub25lJyk7XG5cdFx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdFx0cGFydEVsZW1lbnQuc2V0U3R5bGUoJ2Rpc3BsYXknLCBudWxsKTtcblx0XHRcdFx0fVxuXHRcdFx0XHRPYmplY3QuZWFjaCh2YWx1ZXMsIGZ1bmN0aW9uKHZhbHVlLCBrZXkpIHtcblx0XHRcdFx0XHRpbnB1dEVsZW1lbnRzW2tleV0uc2V0KCd2YWx1ZScsIHZhbHVlID09PSAnJyA/ICcnIDogTnVtYmVyKHZhbHVlKS50b0ZpeGVkKDE1KSk7XG5cdFx0XHRcdH0pO1xuXHRcdFx0fSxcblx0XHRcdHN0YXJ0ID0gZnVuY3Rpb24oZXZlbnQpIHtcblx0XHRcdFx0ZXZlbnQucHJldmVudERlZmF1bHQoKTtcblx0XHRcdFx0aWYgKGlzRHJhd2luZykge1xuXHRcdFx0XHRcdHJldHVybjtcblx0XHRcdFx0fVxuXHRcdFx0XHRpc0RyYXdpbmcgPSB0cnVlO1xuXHRcdFx0XHRzdGFydFBvcyA9IHtcblx0XHRcdFx0XHR4OiBldmVudC5wYWdlLnggLSBlbC5nZXRQb3NpdGlvbigpLnggLSBpbWFnZUVsZW1lbnQuZ2V0Q29tcHV0ZWRTaXplKCkuY29tcHV0ZWRMZWZ0LFxuXHRcdFx0XHRcdHk6IGV2ZW50LnBhZ2UueSAtIGVsLmdldFBvc2l0aW9uKCkueSAtIGltYWdlRWxlbWVudC5nZXRDb21wdXRlZFNpemUoKS5jb21wdXRlZFRvcFxuXHRcdFx0XHR9O1xuXHRcdFx0XHRtb3ZlKGV2ZW50KTtcblx0XHRcdH0sXG5cdFx0XHRtb3ZlID0gZnVuY3Rpb24oZXZlbnQpIHtcblx0XHRcdFx0aWYgKCFpc0RyYXdpbmcpIHtcblx0XHRcdFx0XHRyZXR1cm47XG5cdFx0XHRcdH1cblx0XHRcdFx0ZXZlbnQucHJldmVudERlZmF1bHQoKTtcblx0XHRcdFx0dmFyIGltYWdlU2l6ZSA9IGltYWdlRWxlbWVudC5nZXRDb21wdXRlZFNpemUoKTtcblx0XHRcdFx0dmFyIHJlY3QgPSB7XG5cdFx0XHRcdFx0eDogW1xuXHRcdFx0XHRcdFx0TWF0aC5tYXgoMCwgTWF0aC5taW4oaW1hZ2VTaXplLndpZHRoLCBzdGFydFBvcy54KSksXG5cdFx0XHRcdFx0XHRNYXRoLm1heCgwLCBNYXRoLm1pbihpbWFnZVNpemUud2lkdGgsIGV2ZW50LnBhZ2UueCAtIGVsLmdldFBvc2l0aW9uKCkueCAtIGltYWdlU2l6ZS5jb21wdXRlZExlZnQpKVxuXHRcdFx0XHRcdF0sXG5cdFx0XHRcdFx0eTogW1xuXHRcdFx0XHRcdFx0TWF0aC5tYXgoMCwgTWF0aC5taW4oaW1hZ2VTaXplLmhlaWdodCwgc3RhcnRQb3MueSkpLFxuXHRcdFx0XHRcdFx0TWF0aC5tYXgoMCwgTWF0aC5taW4oaW1hZ2VTaXplLmhlaWdodCwgZXZlbnQucGFnZS55IC0gZWwuZ2V0UG9zaXRpb24oKS55IC0gaW1hZ2VTaXplLmNvbXB1dGVkVG9wKSlcblx0XHRcdFx0XHRdXG5cdFx0XHRcdH07XG5cdFx0XHRcdHBhcnRFbGVtZW50LnNldFN0eWxlcyh7XG5cdFx0XHRcdFx0dG9wOiBNYXRoLm1pbihyZWN0LnlbMF0sIHJlY3QueVsxXSkgKyBpbWFnZVNpemUuY29tcHV0ZWRUb3AgKyAncHgnLFxuXHRcdFx0XHRcdGxlZnQ6IE1hdGgubWluKHJlY3QueFswXSwgcmVjdC54WzFdKSArIGltYWdlU2l6ZS5jb21wdXRlZExlZnQgKyAncHgnLFxuXHRcdFx0XHRcdHdpZHRoOiBNYXRoLmFicyhyZWN0LnhbMF0gLSByZWN0LnhbMV0pICsgJ3B4Jyxcblx0XHRcdFx0XHRoZWlnaHQ6IE1hdGguYWJzKHJlY3QueVswXSAtIHJlY3QueVsxXSkgKyAncHgnXG5cdFx0XHRcdH0pO1xuXHRcdFx0XHR1cGRhdGVWYWx1ZXMoKTtcblx0XHRcdH0sXG5cdFx0XHRzdG9wID0gZnVuY3Rpb24oZXZlbnQpIHtcblx0XHRcdFx0bW92ZShldmVudCk7XG5cdFx0XHRcdGlzRHJhd2luZyA9IGZhbHNlO1xuXHRcdFx0fSxcblx0XHRcdGluaXQgPSBmdW5jdGlvbigpIHtcblx0XHRcdFx0ZWwuZ2V0UGFyZW50KCcudGxfdGJveCwudGxfYm94JykuZ2V0RWxlbWVudHMoJ2lucHV0W25hbWVePVwiaW1wb3J0YW50UGFydFwiXScpLmVhY2goZnVuY3Rpb24oaW5wdXQpIHtcblx0XHRcdFx0XHRbJ3gnLCAneScsICd3aWR0aCcsICdoZWlnaHQnXS5lYWNoKGZ1bmN0aW9uKGtleSkge1xuXHRcdFx0XHRcdFx0aWYgKGlucHV0LmdldCgnbmFtZScpLnN1YnN0cigxMywga2V5Lmxlbmd0aCkgPT09IGtleS5jYXBpdGFsaXplKCkpIHtcblx0XHRcdFx0XHRcdFx0aW5wdXRFbGVtZW50c1trZXldID0gaW5wdXQgPSAkKGlucHV0KTtcblx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHR9KTtcblx0XHRcdFx0fSk7XG5cdFx0XHRcdGlmIChPYmplY3QuZ2V0TGVuZ3RoKGlucHV0RWxlbWVudHMpICE9PSA0KSB7XG5cdFx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0XHR9XG5cdFx0XHRcdE9iamVjdC5lYWNoKGlucHV0RWxlbWVudHMsIGZ1bmN0aW9uKGlucHV0KSB7XG5cdFx0XHRcdFx0aW5wdXQuZ2V0UGFyZW50KCkuc2V0U3R5bGUoJ2Rpc3BsYXknLCAnbm9uZScpO1xuXHRcdFx0XHR9KTtcblx0XHRcdFx0ZWwuYWRkQ2xhc3MoJ3RsX2VkaXRfcHJldmlld19lbmFibGVkJyk7XG5cdFx0XHRcdHBhcnRFbGVtZW50ID0gbmV3IEVsZW1lbnQoJ2RpdicsIHtcblx0XHRcdFx0XHQnY2xhc3MnOiAndGxfZWRpdF9wcmV2aWV3X2ltcG9ydGFudF9wYXJ0J1xuXHRcdFx0XHR9KS5pbmplY3QoZWwpO1xuXHRcdFx0XHR1cGRhdGVJbWFnZSgpO1xuXHRcdFx0XHRpbWFnZUVsZW1lbnQuYWRkRXZlbnQoJ2xvYWQnLCB1cGRhdGVJbWFnZSk7XG5cdFx0XHRcdGVsLmFkZEV2ZW50cyh7XG5cdFx0XHRcdFx0bW91c2Vkb3duOiBzdGFydCxcblx0XHRcdFx0XHR0b3VjaHN0YXJ0OiBzdGFydFxuXHRcdFx0XHR9KTtcblx0XHRcdFx0JChkb2N1bWVudC5kb2N1bWVudEVsZW1lbnQpLmFkZEV2ZW50cyh7XG5cdFx0XHRcdFx0bW91c2Vtb3ZlOiBtb3ZlLFxuXHRcdFx0XHRcdHRvdWNobW92ZTogbW92ZSxcblx0XHRcdFx0XHRtb3VzZXVwOiBzdG9wLFxuXHRcdFx0XHRcdHRvdWNoZW5kOiBzdG9wLFxuXHRcdFx0XHRcdHRvdWNoY2FuY2VsOiBzdG9wLFxuXHRcdFx0XHRcdHJlc2l6ZTogdXBkYXRlSW1hZ2Vcblx0XHRcdFx0fSk7XG5cdFx0XHR9XG5cdFx0O1xuXG5cdFx0d2luZG93LmFkZEV2ZW50KCdkb21yZWFkeScsIGluaXQpO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBFbmFibGUgZHJhZyBhbmQgZHJvcCBmaWxlIHVwbG9hZCBmb3IgdGhlIGZpbGUgdHJlZVxuXHQgKlxuXHQgKiBAcGFyYW0ge29iamVjdH0gd3JhcCAgICBUaGUgRE9NIGVsZW1lbnRcblx0ICogQHBhcmFtIHtvYmplY3R9IG9wdGlvbnMgQW4gb3B0aW9uYWwgb3B0aW9ucyBvYmplY3Rcblx0ICovXG5cdGVuYWJsZUZpbGVUcmVlVXBsb2FkOiBmdW5jdGlvbih3cmFwLCBvcHRpb25zKSB7XG5cdFx0d3JhcCA9ICQod3JhcCk7XG5cblx0XHR2YXIgZmFsbGJhY2tVcmwgPSBvcHRpb25zLnVybCxcblx0XHRcdGR6RWxlbWVudCA9IG5ldyBFbGVtZW50KCdkaXYnLCB7XG5cdFx0XHRcdCdjbGFzcyc6ICdkcm9wem9uZSBkcm9wem9uZS1maWxldHJlZScsXG5cdFx0XHRcdGh0bWw6ICc8c3BhbiBjbGFzcz1cImRyb3B6b25lLXByZXZpZXdzXCI+PC9zcGFuPidcblx0XHRcdH0pLmluamVjdCh3cmFwLCAndG9wJyksXG5cdFx0XHRjdXJyZW50SG92ZXIsIGN1cnJlbnRIb3ZlclRpbWUsIGV4cGFuZExpbms7XG5cblx0XHRvcHRpb25zLnByZXZpZXdzQ29udGFpbmVyID0gZHpFbGVtZW50LmdldEVsZW1lbnQoJy5kcm9wem9uZS1wcmV2aWV3cycpO1xuXHRcdG9wdGlvbnMuY2xpY2thYmxlID0gZmFsc2U7XG5cblx0XHR2YXIgZHogPSBuZXcgRHJvcHpvbmUod3JhcCwgb3B0aW9ucyk7XG5cblx0XHRkei5vbigncXVldWVjb21wbGV0ZScsIGZ1bmN0aW9uKCkge1xuXHRcdFx0d2luZG93LmxvY2F0aW9uLnJlbG9hZCgpO1xuXHRcdH0pO1xuXG5cdFx0ZHoub24oJ2RyYWdvdmVyJywgZnVuY3Rpb24oZXZlbnQpIHtcblx0XHRcdGlmICghZXZlbnQuZGF0YVRyYW5zZmVyIHx8ICFldmVudC5kYXRhVHJhbnNmZXIudHlwZXMgfHwgZXZlbnQuZGF0YVRyYW5zZmVyLnR5cGVzLmluZGV4T2YoJ0ZpbGVzJykgPT09IC0xKSB7XG5cdFx0XHRcdHJldHVybjtcblx0XHRcdH1cblxuXHRcdFx0d3JhcC5nZXRFbGVtZW50cygnLnRsX2ZvbGRlcl9kcm9wcGluZycpLnJlbW92ZUNsYXNzKCd0bF9mb2xkZXJfZHJvcHBpbmcnKTtcblx0XHRcdHZhciB0YXJnZXQgPSBldmVudC50YXJnZXQgJiYgJChldmVudC50YXJnZXQpO1xuXG5cdFx0XHRpZiAodGFyZ2V0KSB7XG5cdFx0XHRcdHZhciBmb2xkZXIgPSB0YXJnZXQubWF0Y2goJy50bF9mb2xkZXInKSA/IHRhcmdldCA6IHRhcmdldC5nZXRQYXJlbnQoJy50bF9mb2xkZXInKTtcblxuXHRcdFx0XHRpZiAoIWZvbGRlcikge1xuXHRcdFx0XHRcdGZvbGRlciA9IHRhcmdldC5nZXRQYXJlbnQoJy5wYXJlbnQnKTtcblx0XHRcdFx0XHRmb2xkZXIgPSBmb2xkZXIgJiYgZm9sZGVyLmdldFByZXZpb3VzKCcudGxfZm9sZGVyJyk7XG5cdFx0XHRcdH1cblxuXHRcdFx0XHRpZiAoZm9sZGVyKSB7XG5cdFx0XHRcdFx0dmFyIGxpbmsgPSBmb2xkZXIuZ2V0RWxlbWVudCgnaW1nW3NyYyQ9XCIvaWNvbnMvbmV3LnN2Z1wiXScpO1xuXHRcdFx0XHRcdGxpbmsgPSBsaW5rICYmIGxpbmsuZ2V0UGFyZW50KCdhJyk7XG5cdFx0XHRcdH1cblx0XHRcdH1cblxuXHRcdFx0aWYgKGxpbmsgJiYgbGluay5ocmVmKSB7XG5cdFx0XHRcdGR6Lm9wdGlvbnMudXJsID0gJycrbGluay5ocmVmO1xuXHRcdFx0XHRmb2xkZXIuYWRkQ2xhc3MoJ3RsX2ZvbGRlcl9kcm9wcGluZycpO1xuXG5cdFx0XHRcdGlmIChjdXJyZW50SG92ZXIgIT09IGZvbGRlcikge1xuXHRcdFx0XHRcdGN1cnJlbnRIb3ZlciA9IGZvbGRlcjtcblx0XHRcdFx0XHRjdXJyZW50SG92ZXJUaW1lID0gbmV3IERhdGUoKS5nZXRUaW1lKCk7XG5cdFx0XHRcdFx0ZXhwYW5kTGluayA9IGZvbGRlci5nZXRFbGVtZW50KCdhLmZvbGRhYmxlJyk7XG5cblx0XHRcdFx0XHRpZiAoZXhwYW5kTGluayAmJiAhZXhwYW5kTGluay5oYXNDbGFzcygnZm9sZGFibGUtLW9wZW4nKSkge1xuXHRcdFx0XHRcdFx0Ly8gRXhwYW5kIHRoZSBmb2xkZXIgYWZ0ZXIgb25lIHNlY29uZCBob3ZlciB0aW1lXG5cdFx0XHRcdFx0XHRzZXRUaW1lb3V0KGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRcdFx0XHRpZiAoY3VycmVudEhvdmVyID09PSBmb2xkZXIgJiYgY3VycmVudEhvdmVyVGltZSArIDkwMCA8IG5ldyBEYXRlKCkuZ2V0VGltZSgpKSB7XG5cdFx0XHRcdFx0XHRcdFx0dmFyIGV2ZW50ID0gZG9jdW1lbnQuY3JlYXRlRXZlbnQoJ0hUTUxFdmVudHMnKTtcblx0XHRcdFx0XHRcdFx0XHRldmVudC5pbml0RXZlbnQoJ2NsaWNrJywgdHJ1ZSwgdHJ1ZSk7XG5cdFx0XHRcdFx0XHRcdFx0ZXhwYW5kTGluay5kaXNwYXRjaEV2ZW50KGV2ZW50KTtcblx0XHRcdFx0XHRcdFx0XHRjdXJyZW50SG92ZXIgPSB1bmRlZmluZWQ7XG5cdFx0XHRcdFx0XHRcdFx0Y3VycmVudEhvdmVyVGltZSA9IHVuZGVmaW5lZDtcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0fSwgMTAwMCk7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRkei5vcHRpb25zLnVybCA9IGZhbGxiYWNrVXJsO1xuXHRcdFx0XHRjdXJyZW50SG92ZXIgPSB1bmRlZmluZWQ7XG5cdFx0XHRcdGN1cnJlbnRIb3ZlclRpbWUgPSB1bmRlZmluZWQ7XG5cdFx0XHR9XG5cdFx0fSk7XG5cblx0XHRkei5vbignZHJvcCcsIGZ1bmN0aW9uKGV2ZW50KSB7XG5cdFx0XHRpZiAoIWV2ZW50LmRhdGFUcmFuc2ZlciB8fCAhZXZlbnQuZGF0YVRyYW5zZmVyLnR5cGVzIHx8IGV2ZW50LmRhdGFUcmFuc2Zlci50eXBlcy5pbmRleE9mKCdGaWxlcycpID09PSAtMSkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdGR6RWxlbWVudC5hZGRDbGFzcygnZHJvcHpvbmUtZmlsZXRyZWUtZW5hYmxlZCcpO1xuXHRcdFx0d2luZG93LmRpc3BhdGNoRXZlbnQobmV3IEV2ZW50KCdzdG9yZS1zY3JvbGwtb2Zmc2V0JykpO1xuXHRcdH0pO1xuXG5cdFx0ZHoub24oJ2RyYWdsZWF2ZScsIGZ1bmN0aW9uKCkge1xuXHRcdFx0d3JhcC5nZXRFbGVtZW50cygnLnRsX2ZvbGRlcl9kcm9wcGluZycpLnJlbW92ZUNsYXNzKCd0bF9mb2xkZXJfZHJvcHBpbmcnKTtcblx0XHRcdGN1cnJlbnRIb3ZlciA9IHVuZGVmaW5lZDtcblx0XHRcdGN1cnJlbnRIb3ZlclRpbWUgPSB1bmRlZmluZWQ7XG5cdFx0fSk7XG5cdH0sXG5cblx0LyoqXG5cdCAqIENyYXdsIHRoZSB3ZWJzaXRlXG5cdCAqL1xuXHRjcmF3bDogZnVuY3Rpb24oKSB7XG5cdFx0dmFyIHRpbWVvdXQgPSAyMDAwLFxuXHRcdFx0Y3Jhd2wgPSAkKCd0bF9jcmF3bCcpLFxuXHRcdFx0cHJvZ3Jlc3NCYXIgPSBjcmF3bC5nZXRFbGVtZW50KCdkaXYucHJvZ3Jlc3MtYmFyJyksXG5cdFx0XHRwcm9ncmVzc0NvdW50ID0gY3Jhd2wuZ2V0RWxlbWVudCgncC5wcm9ncmVzcy1jb3VudCcpLFxuXHRcdFx0cmVzdWx0cyA9IGNyYXdsLmdldEVsZW1lbnQoJ2Rpdi5yZXN1bHRzJyksXG5cdFx0XHRkZWJ1Z0xvZyA9IGNyYXdsLmdldEVsZW1lbnQoJ3AuZGVidWctbG9nJyk7XG5cblx0XHRmdW5jdGlvbiB1cGRhdGVEYXRhKHJlc3BvbnNlKSB7XG5cdFx0XHR2YXIgdG90YWwgPSByZXNwb25zZS50b3RhbCxcblx0XHRcdFx0ZG9uZSA9IHRvdGFsIC0gcmVzcG9uc2UucGVuZGluZyxcblx0XHRcdFx0cGVyY2VudGFnZSA9IHRvdGFsID4gMCA/IHBhcnNlSW50KGRvbmUgLyB0b3RhbCAqIDEwMCwgMTApIDogMTAwLFxuXHRcdFx0XHRyZXN1bHQ7XG5cblx0XHRcdC8vIEluaXRpYWxpemUgdGhlIHN0YXR1cyBiYXIgYXQgMTAlXG5cdFx0XHRpZiAoZG9uZSA8IDEgJiYgcGVyY2VudGFnZSA8IDEpIHtcblx0XHRcdFx0ZG9uZSA9IDE7XG5cdFx0XHRcdHBlcmNlbnRhZ2UgPSAxMDtcblx0XHRcdFx0dG90YWwgPSAxMDtcblx0XHRcdH1cblxuXHRcdFx0cHJvZ3Jlc3NCYXIuc2V0U3R5bGUoJ3dpZHRoJywgcGVyY2VudGFnZSArICclJyk7XG5cdFx0XHRwcm9ncmVzc0Jhci5zZXQoJ2h0bWwnLCBwZXJjZW50YWdlICsgJyUnKTtcblx0XHRcdHByb2dyZXNzQmFyLnNldEF0dHJpYnV0ZSgnYXJpYS12YWx1ZW5vdycsIHBlcmNlbnRhZ2UpO1xuXHRcdFx0cHJvZ3Jlc3NDb3VudC5zZXQoJ2h0bWwnLCBkb25lICsgJyAvICcgKyB0b3RhbCk7XG5cblx0XHRcdGlmIChyZXNwb25zZS5oYXNEZWJ1Z0xvZykge1xuXHRcdFx0XHRkZWJ1Z0xvZy5zZXRTdHlsZSgnZGlzcGxheScsICdibG9jaycpO1xuXHRcdFx0fVxuXG5cdFx0XHRpZiAocmVzcG9uc2UuaGFzRGVidWdMb2cpIHtcblx0XHRcdFx0ZGVidWdMb2cuc2V0U3R5bGUoJ2Rpc3BsYXknLCAnYmxvY2snKTtcblx0XHRcdH1cblxuXHRcdFx0aWYgKCFyZXNwb25zZS5maW5pc2hlZCkge1xuXHRcdFx0XHRyZXR1cm47XG5cdFx0XHR9XG5cblx0XHRcdHByb2dyZXNzQmFyLnJlbW92ZUNsYXNzKCdydW5uaW5nJykuYWRkQ2xhc3MoJ2ZpbmlzaGVkJyk7XG5cdFx0XHRyZXN1bHRzLnJlbW92ZUNsYXNzKCdydW5uaW5nJykuYWRkQ2xhc3MoJ2ZpbmlzaGVkJyk7XG5cblx0XHRcdGZvciAocmVzdWx0IGluIHJlc3BvbnNlLnJlc3VsdHMpIHtcblx0XHRcdFx0aWYgKHJlc3BvbnNlLnJlc3VsdHMuaGFzT3duUHJvcGVydHkocmVzdWx0KSkge1xuXHRcdFx0XHRcdHZhciBzdW1tYXJ5ID0gcmVzdWx0cy5nZXRFbGVtZW50KCcucmVzdWx0W2RhdGEtc3Vic2NyaWJlcj1cIicgKyByZXN1bHQgKyAnXCJdIHAuc3VtbWFyeScpLFxuXHRcdFx0XHRcdFx0d2FybmluZyA9IHJlc3VsdHMuZ2V0RWxlbWVudCgnLnJlc3VsdFtkYXRhLXN1YnNjcmliZXI9XCInICsgcmVzdWx0ICsgJ1wiXSBwLndhcm5pbmcnKSxcblx0XHRcdFx0XHRcdGxvZyA9IHJlc3VsdHMuZ2V0RWxlbWVudCgnLnJlc3VsdFtkYXRhLXN1YnNjcmliZXI9XCInICsgcmVzdWx0ICsgJ1wiXSBwLnN1YnNjcmliZXItbG9nJyksXG5cdFx0XHRcdFx0XHRzdWJzY3JpYmVyUmVzdWx0cyA9IHJlc3BvbnNlLnJlc3VsdHNbcmVzdWx0XSxcblx0XHRcdFx0XHRcdHN1YnNjcmliZXJTdW1tYXJ5ID0gc3Vic2NyaWJlclJlc3VsdHMuc3VtbWFyeTtcblxuXHRcdFx0XHRcdGlmIChzdWJzY3JpYmVyUmVzdWx0cy53YXJuaW5nKSB7XG5cdFx0XHRcdFx0XHR3YXJuaW5nLnNldCgnaHRtbCcsIHN1YnNjcmliZXJSZXN1bHRzLndhcm5pbmcpO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdGlmIChzdWJzY3JpYmVyUmVzdWx0cy5oYXNMb2cpIHtcblx0XHRcdFx0XHRcdGxvZy5zZXRTdHlsZSgnZGlzcGxheScsICdibG9jaycpO1xuXHRcdFx0XHRcdH1cblxuXHRcdFx0XHRcdHN1bW1hcnkuYWRkQ2xhc3Moc3Vic2NyaWJlclJlc3VsdHMud2FzU3VjY2Vzc2Z1bCA/ICdzdWNjZXNzJyA6ICdmYWlsdXJlJyk7XG5cdFx0XHRcdFx0c3VtbWFyeS5zZXQoJ2h0bWwnLCBzdWJzY3JpYmVyU3VtbWFyeSk7XG5cdFx0XHRcdH1cblx0XHRcdH1cblx0XHR9XG5cblx0XHRmdW5jdGlvbiBleGVjUmVxdWVzdChvbmx5U3RhdHVzVXBkYXRlID0gZmFsc2UpIHtcblx0XHRcdG5ldyBSZXF1ZXN0KHtcblx0XHRcdFx0dXJsOiB3aW5kb3cubG9jYXRpb24uaHJlZixcblx0XHRcdFx0aGVhZGVyczoge1xuXHRcdFx0XHRcdCdPbmx5LVN0YXR1cy1VcGRhdGUnOiBvbmx5U3RhdHVzVXBkYXRlXG5cdFx0XHRcdH0sXG5cdFx0XHRcdG9uU3VjY2VzczogZnVuY3Rpb24ocmVzcG9uc2VUZXh0KSB7XG5cdFx0XHRcdFx0dmFyIHJlc3BvbnNlID0gSlNPTi5kZWNvZGUocmVzcG9uc2VUZXh0KTtcblxuXHRcdFx0XHRcdHVwZGF0ZURhdGEocmVzcG9uc2UpO1xuXG5cdFx0XHRcdFx0aWYgKCFyZXNwb25zZS5maW5pc2hlZCkge1xuXHRcdFx0XHRcdFx0c2V0VGltZW91dChleGVjUmVxdWVzdCwgdGltZW91dCk7XG5cdFx0XHRcdFx0fVxuXHRcdFx0XHR9XG5cdFx0XHR9KS5zZW5kKCk7XG5cdFx0fVxuXG5cdFx0ZXhlY1JlcXVlc3QodHJ1ZSk7XG5cdH1cbn07XG5cbndpbmRvdy5UaGVtZSA9XG57XG5cdC8qKlxuXHQgKiBDaGVjayBmb3IgV2ViS2l0XG5cdCAqIEBtZW1iZXIge2Jvb2xlYW59XG4gXHQgKi9cblx0aXNXZWJraXQ6IChCcm93c2VyLmNocm9tZSB8fCBCcm93c2VyLnNhZmFyaSB8fCBuYXZpZ2F0b3IudXNlckFnZW50Lm1hdGNoKC8oPzp3ZWJraXR8a2h0bWwpL2kpKSxcblxuXHQvKipcblx0ICogU3RvcCB0aGUgcHJvcGFnYXRpb24gb2YgY2xpY2sgZXZlbnRzIG9mIGNlcnRhaW4gZWxlbWVudHNcblx0ICovXG5cdHN0b3BDbGlja1Byb3BhZ2F0aW9uOiBmdW5jdGlvbigpIHtcblx0XHQvLyBEbyBub3QgcHJvcGFnYXRlIHRoZSBjbGljayBldmVudHMgb2YgdGhlIGljb25zXG5cdFx0JCQoJy5waWNrZXJfc2VsZWN0b3InKS5lYWNoKGZ1bmN0aW9uKHVsKSB7XG5cdFx0XHR1bC5nZXRFbGVtZW50cygnYScpLmVhY2goZnVuY3Rpb24oZWwpIHtcblx0XHRcdFx0ZWwuYWRkRXZlbnQoJ2NsaWNrJywgZnVuY3Rpb24oZSkge1xuXHRcdFx0XHRcdGUuc3RvcFByb3BhZ2F0aW9uKCk7XG5cdFx0XHRcdH0pO1xuXHRcdFx0fSk7XG5cdFx0fSk7XG5cblx0XHQvLyBEbyBub3QgcHJvcGFnYXRlIHRoZSBjbGljayBldmVudHMgb2YgdGhlIGNoZWNrYm94ZXNcblx0XHQkJCgnLnBpY2tlcl9zZWxlY3RvciwuY2xpY2syZWRpdCcpLmVhY2goZnVuY3Rpb24odWwpIHtcblx0XHRcdHVsLmdldEVsZW1lbnRzKCdpbnB1dFt0eXBlPVwiY2hlY2tib3hcIl0nKS5lYWNoKGZ1bmN0aW9uKGVsKSB7XG5cdFx0XHRcdGVsLmFkZEV2ZW50KCdjbGljaycsIGZ1bmN0aW9uKGUpIHtcblx0XHRcdFx0XHRlLnN0b3BQcm9wYWdhdGlvbigpO1xuXHRcdFx0XHR9KTtcblx0XHRcdH0pO1xuXHRcdH0pO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBTZXQgdXAgdGhlIFtDdHJsXSArIGNsaWNrIHRvIGVkaXQgZnVuY3Rpb25hbGl0eVxuXHQgKi9cblx0c2V0dXBDdHJsQ2xpY2s6IGZ1bmN0aW9uKCkge1xuXHRcdCQkKCcuY2xpY2syZWRpdCcpLmVhY2goZnVuY3Rpb24oZWwpIHtcblxuXHRcdFx0Ly8gRG8gbm90IHByb3BhZ2F0ZSB0aGUgY2xpY2sgZXZlbnRzIG9mIHRoZSBkZWZhdWx0IGJ1dHRvbnMgKHNlZSAjNTczMSlcblx0XHRcdGVsLmdldEVsZW1lbnRzKCdhJykuZWFjaChmdW5jdGlvbihhKSB7XG5cdFx0XHRcdGEuYWRkRXZlbnQoJ2NsaWNrJywgZnVuY3Rpb24oZSkge1xuXHRcdFx0XHRcdGUuc3RvcFByb3BhZ2F0aW9uKCk7XG5cdFx0XHRcdH0pO1xuXHRcdFx0fSk7XG5cblx0XHRcdC8vIFNldCB1cCByZWd1bGFyIGNsaWNrIGV2ZW50cyBvbiB0b3VjaCBkZXZpY2VzXG5cdFx0XHRpZiAoQnJvd3Nlci5GZWF0dXJlcy5Ub3VjaCkge1xuXHRcdFx0XHRlbC5hZGRFdmVudCgnY2xpY2snLCBmdW5jdGlvbigpIHtcblx0XHRcdFx0XHRpZiAoIWVsLmdldEF0dHJpYnV0ZSgnZGF0YS12aXNpdGVkJykpIHtcblx0XHRcdFx0XHRcdGVsLnNldEF0dHJpYnV0ZSgnZGF0YS12aXNpdGVkJywgJzEnKTtcblx0XHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdFx0ZWwuZ2V0RWxlbWVudHMoJ2EnKS5lYWNoKGZ1bmN0aW9uKGEpIHtcblx0XHRcdFx0XHRcdFx0aWYgKGEuaGFzQ2xhc3MoJ2VkaXQnKSkge1xuXHRcdFx0XHRcdFx0XHRcdGRvY3VtZW50LmxvY2F0aW9uLmhyZWYgPSBhLmhyZWY7XG5cdFx0XHRcdFx0XHRcdH1cblx0XHRcdFx0XHRcdH0pO1xuXHRcdFx0XHRcdFx0ZWwucmVtb3ZlQXR0cmlidXRlKCdkYXRhLXZpc2l0ZWQnKTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0pO1xuXHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0ZWwuYWRkRXZlbnQoJ2NsaWNrJywgZnVuY3Rpb24oZSkge1xuXHRcdFx0XHRcdHZhciBrZXkgPSBCcm93c2VyLlBsYXRmb3JtLm1hYyA/IGUuZXZlbnQubWV0YUtleSA6IGUuZXZlbnQuY3RybEtleTtcblx0XHRcdFx0XHRpZiAoIWtleSkgcmV0dXJuO1xuXG5cdFx0XHRcdFx0aWYgKGUuZXZlbnQuc2hpZnRLZXkpIHtcblx0XHRcdFx0XHRcdGVsLmdldEVsZW1lbnRzKCdhJykuZWFjaChmdW5jdGlvbihhKSB7XG5cdFx0XHRcdFx0XHRcdGlmIChhLmhhc0NsYXNzKCdjaGlsZHJlbicpKSB7XG5cdFx0XHRcdFx0XHRcdFx0ZG9jdW1lbnQubG9jYXRpb24uaHJlZiA9IGEuaHJlZjtcblx0XHRcdFx0XHRcdFx0fVxuXHRcdFx0XHRcdFx0fSk7XG5cdFx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRcdGVsLmdldEVsZW1lbnRzKCdhJykuZWFjaChmdW5jdGlvbihhKSB7XG5cdFx0XHRcdFx0XHRcdGlmIChhLmhhc0NsYXNzKCdlZGl0JykpIHtcblx0XHRcdFx0XHRcdFx0XHRkb2N1bWVudC5sb2NhdGlvbi5ocmVmID0gYS5ocmVmO1xuXHRcdFx0XHRcdFx0XHR9XG5cdFx0XHRcdFx0XHR9KTtcblx0XHRcdFx0XHR9XG5cdFx0XHRcdH0pO1xuXHRcdFx0fVxuXHRcdH0pO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBTZXQgdXAgdGhlIHRleHRhcmVhIHJlc2l6aW5nXG5cdCAqL1xuXHRzZXR1cFRleHRhcmVhUmVzaXppbmc6IGZ1bmN0aW9uKCkge1xuXHRcdCQkKCcudGxfdGV4dGFyZWEnKS5lYWNoKGZ1bmN0aW9uKGVsKSB7XG5cdFx0XHRpZiAoQnJvd3Nlci5pZTYgfHwgQnJvd3Nlci5pZTcgfHwgQnJvd3Nlci5pZTgpIHJldHVybjtcblx0XHRcdGlmIChlbC5oYXNDbGFzcygnbm9yZXNpemUnKSB8fCBlbC5yZXRyaWV2ZSgnYXV0b2dyb3cnKSkgcmV0dXJuO1xuXG5cdFx0XHQvLyBTZXQgdXAgdGhlIGR1bW15IGVsZW1lbnRcblx0XHRcdHZhciBkdW1teSA9IG5ldyBFbGVtZW50KCdkaXYnLCB7XG5cdFx0XHRcdGh0bWw6ICdYJyxcblx0XHRcdFx0c3R5bGVzOiB7XG5cdFx0XHRcdFx0J3Bvc2l0aW9uJzonYWJzb2x1dGUnLFxuXHRcdFx0XHRcdCd0b3AnOjAsXG5cdFx0XHRcdFx0J2xlZnQnOictOTk5ZW0nLFxuXHRcdFx0XHRcdCdvdmVyZmxvdy14JzonaGlkZGVuJ1xuXHRcdFx0XHR9XG5cdFx0XHR9KS5zZXRTdHlsZXMoXG5cdFx0XHRcdGVsLmdldFN0eWxlcygnZm9udC1zaXplJywgJ2ZvbnQtZmFtaWx5JywgJ3dpZHRoJywgJ2xpbmUtaGVpZ2h0Jylcblx0XHRcdCkuaW5qZWN0KGRvY3VtZW50LmJvZHkpO1xuXG5cdFx0XHQvLyBBbHNvIGNvbnNpZGVyIHRoZSBib3gtc2l6aW5nXG5cdFx0XHRpZiAoZWwuZ2V0U3R5bGUoJy1tb3otYm94LXNpemluZycpID09ICdib3JkZXItYm94JyB8fCBlbC5nZXRTdHlsZSgnLXdlYmtpdC1ib3gtc2l6aW5nJykgPT0gJ2JvcmRlci1ib3gnIHx8IGVsLmdldFN0eWxlKCdib3gtc2l6aW5nJykgPT0gJ2JvcmRlci1ib3gnKSB7XG5cdFx0XHRcdGR1bW15LnNldFN0eWxlcyh7XG5cdFx0XHRcdFx0J3BhZGRpbmcnOiBlbC5nZXRTdHlsZSgncGFkZGluZycpLFxuXHRcdFx0XHRcdCdib3JkZXInOiBlbC5nZXRTdHlsZSgnYm9yZGVyLWxlZnQnKVxuXHRcdFx0XHR9KTtcblx0XHRcdH1cblxuXHRcdFx0Ly8gU2luZ2xlIGxpbmUgaGVpZ2h0XG5cdFx0XHR2YXIgbGluZSA9IE1hdGgubWF4KGR1bW15LmNsaWVudEhlaWdodCwgMzApO1xuXG5cdFx0XHQvLyBSZXNwb25kIHRvIHRoZSBcImlucHV0XCIgZXZlbnRcblx0XHRcdGVsLmFkZEV2ZW50KCdpbnB1dCcsIGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRkdW1teS5zZXQoJ2h0bWwnLCB0aGlzLmdldCgndmFsdWUnKVxuXHRcdFx0XHRcdC5yZXBsYWNlKC88L2csICcmbHQ7Jylcblx0XHRcdFx0XHQucmVwbGFjZSgvPi9nLCAnJmd0OycpXG5cdFx0XHRcdFx0LnJlcGxhY2UoL1xcbnxcXHJcXG4vZywgJzxicj5YJykpO1xuXHRcdFx0XHR2YXIgaGVpZ2h0ID0gTWF0aC5tYXgobGluZSwgZHVtbXkuZ2V0U2l6ZSgpLnkpO1xuXHRcdFx0XHRpZiAodGhpcy5jbGllbnRIZWlnaHQgIT0gaGVpZ2h0KSB0aGlzLnR3ZWVuKCdoZWlnaHQnLCBoZWlnaHQpO1xuXHRcdFx0fSkuc2V0KCd0d2VlbicsIHsgJ2R1cmF0aW9uJzoxMDAgfSkuc2V0U3R5bGUoJ2hlaWdodCcsIGxpbmUgKyAncHgnKTtcblxuXHRcdFx0Ly8gRmlyZSB0aGUgZXZlbnRcblx0XHRcdGVsLmZpcmVFdmVudCgnaW5wdXQnKTtcblx0XHRcdGVsLnN0b3JlKCdhdXRvZ3JvdycsIHRydWUpO1xuXHRcdH0pO1xuXHR9LFxuXG5cdC8qKlxuXHQgKiBTZXQgdXAgdGhlIG1lbnUgdG9nZ2xlXG5cdCAqL1xuXHRzZXR1cE1lbnVUb2dnbGU6IGZ1bmN0aW9uKCkge1xuXHRcdHZhciBidXJnZXIgPSAkKCdidXJnZXInKTtcblx0XHRpZiAoIWJ1cmdlcikgcmV0dXJuO1xuXG5cdFx0YnVyZ2VyXG5cdFx0XHQuYWRkRXZlbnQoJ2NsaWNrJywgZnVuY3Rpb24oKSB7XG5cdFx0XHRcdGRvY3VtZW50LmJvZHkudG9nZ2xlQ2xhc3MoJ3Nob3ctbmF2aWdhdGlvbicpO1xuXHRcdFx0XHRidXJnZXIuc2V0QXR0cmlidXRlKCdhcmlhLWV4cGFuZGVkJywgZG9jdW1lbnQuYm9keS5oYXNDbGFzcygnc2hvdy1uYXZpZ2F0aW9uJykgPyAndHJ1ZScgOiAnZmFsc2UnKVxuXHRcdFx0fSlcblx0XHRcdC5hZGRFdmVudCgna2V5ZG93bicsIGZ1bmN0aW9uKGUpIHtcblx0XHRcdFx0aWYgKGUuZXZlbnQua2V5Q29kZSA9PSAyNykge1xuXHRcdFx0XHRcdGRvY3VtZW50LmJvZHkucmVtb3ZlQ2xhc3MoJ3Nob3ctbmF2aWdhdGlvbicpO1xuXHRcdFx0XHR9XG5cdFx0XHR9KVxuXHRcdDtcblxuXHRcdGlmICh3aW5kb3cubWF0Y2hNZWRpYSkge1xuXHRcdFx0dmFyIG1hdGNoTWVkaWEgPSB3aW5kb3cubWF0Y2hNZWRpYSgnKG1heC13aWR0aDo5OTFweCknKTtcblx0XHRcdHZhciBzZXRBcmlhQ29udHJvbHMgPSBmdW5jdGlvbigpIHtcblx0XHRcdFx0aWYgKG1hdGNoTWVkaWEubWF0Y2hlcykge1xuXHRcdFx0XHRcdGJ1cmdlci5zZXRBdHRyaWJ1dGUoJ2FyaWEtY29udHJvbHMnLCAnbGVmdCcpXG5cdFx0XHRcdFx0YnVyZ2VyLnNldEF0dHJpYnV0ZSgnYXJpYS1leHBhbmRlZCcsIGRvY3VtZW50LmJvZHkuaGFzQ2xhc3MoJ3Nob3ctbmF2aWdhdGlvbicpID8gJ3RydWUnIDogJ2ZhbHNlJylcblx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRidXJnZXIucmVtb3ZlQXR0cmlidXRlKCdhcmlhLWNvbnRyb2xzJyk7XG5cdFx0XHRcdFx0YnVyZ2VyLnJlbW92ZUF0dHJpYnV0ZSgnYXJpYS1leHBhbmRlZCcpO1xuXHRcdFx0XHR9XG5cdFx0XHR9O1xuXHRcdFx0bWF0Y2hNZWRpYS5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCBzZXRBcmlhQ29udHJvbHMpO1xuXHRcdFx0c2V0QXJpYUNvbnRyb2xzKCk7XG5cdFx0fVxuXHR9LFxuXG5cdC8qKlxuXHQgKiBTZXQgdXAgdGhlIHByb2ZpbGUgdG9nZ2xlXG5cdCAqL1xuXHRzZXR1cFByb2ZpbGVUb2dnbGU6IGZ1bmN0aW9uKCkge1xuXHRcdHZhciB0bWVudSA9ICQoJ3RtZW51Jyk7XG5cdFx0aWYgKCF0bWVudSkgcmV0dXJuO1xuXG5cdFx0dmFyIGxpID0gdG1lbnUuZ2V0RWxlbWVudCgnLnN1Ym1lbnUnKSxcblx0XHRcdGJ1dHRvbiA9IGxpLmdldEZpcnN0KCdzcGFuJykuZ2V0Rmlyc3QoJ2J1dHRvbicpLFxuXHRcdFx0bWVudSA9IGxpLmdldEZpcnN0KCd1bCcpO1xuXHRcdGlmICghbGkgfHwgIWJ1dHRvbiB8fCAhbWVudSkgcmV0dXJuO1xuXG5cdFx0YnV0dG9uLnNldEF0dHJpYnV0ZSgnYXJpYS1jb250cm9scycsICd0bWVudV9fcHJvZmlsZScpO1xuXHRcdGJ1dHRvbi5zZXRBdHRyaWJ1dGUoJ2FyaWEtZXhwYW5kZWQnLCAnZmFsc2UnKTtcblxuXHRcdG1lbnUuaWQgPSAndG1lbnVfX3Byb2ZpbGUnO1xuXG5cdFx0YnV0dG9uLmFkZEV2ZW50KCdjbGljaycsIGZ1bmN0aW9uKGUpIHtcblx0XHRcdGlmIChsaS5oYXNDbGFzcygnYWN0aXZlJykpIHtcblx0XHRcdFx0bGkucmVtb3ZlQ2xhc3MoJ2FjdGl2ZScpO1xuXHRcdFx0XHRidXR0b24uc2V0QXR0cmlidXRlKCdhcmlhLWV4cGFuZGVkJywgJ2ZhbHNlJyk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRsaS5hZGRDbGFzcygnYWN0aXZlJyk7XG5cdFx0XHRcdGJ1dHRvbi5zZXRBdHRyaWJ1dGUoJ2FyaWEtZXhwYW5kZWQnLCAndHJ1ZScpO1xuXHRcdFx0fVxuXHRcdFx0ZS5zdG9wUHJvcGFnYXRpb24oKTtcblx0XHR9KTtcblxuXHRcdCQoZG9jdW1lbnQuYm9keSkuYWRkRXZlbnQoJ2NsaWNrJywgZnVuY3Rpb24oKSB7XG5cdFx0XHRpZiAobGkuaGFzQ2xhc3MoJ2FjdGl2ZScpKSB7XG5cdFx0XHRcdGxpLnJlbW92ZUNsYXNzKCdhY3RpdmUnKTtcblx0XHRcdH1cblx0XHR9KTtcblx0fSxcblxuXHQvKipcblx0ICogU2V0IHVwIHRoZSBzcGxpdCBidXR0b24gdG9nZ2xlXG5cdCAqL1xuXHRzZXR1cFNwbGl0QnV0dG9uVG9nZ2xlOiBmdW5jdGlvbigpIHtcblx0XHR2YXIgdG9nZ2xlID0gJCgnc2J0b2cnKTtcblx0XHRpZiAoIXRvZ2dsZSkgcmV0dXJuO1xuXG5cdFx0dmFyIHVsID0gdG9nZ2xlLmdldFBhcmVudCgnLnNwbGl0LWJ1dHRvbicpLmdldEVsZW1lbnQoJ3VsJyksXG5cdFx0XHR0YWIsIHRpbWVyO1xuXG5cdFx0dG9nZ2xlLmFkZEV2ZW50KCdjbGljaycsIGZ1bmN0aW9uKGUpIHtcblx0XHRcdHRhYiA9IGZhbHNlO1xuXHRcdFx0dWwudG9nZ2xlQ2xhc3MoJ2ludmlzaWJsZScpO1xuXHRcdFx0dG9nZ2xlLnRvZ2dsZUNsYXNzKCdhY3RpdmUnKTtcblx0XHRcdGUuc3RvcFByb3BhZ2F0aW9uKCk7XG5cdFx0fSk7XG5cblx0XHQkKGRvY3VtZW50LmJvZHkpLmFkZEV2ZW50KCdjbGljaycsIGZ1bmN0aW9uKCkge1xuXHRcdFx0dGFiID0gZmFsc2U7XG5cdFx0XHR1bC5hZGRDbGFzcygnaW52aXNpYmxlJyk7XG5cdFx0XHR0b2dnbGUucmVtb3ZlQ2xhc3MoJ2FjdGl2ZScpO1xuXHRcdH0pO1xuXG5cdFx0JChkb2N1bWVudC5ib2R5KS5hZGRFdmVudCgna2V5ZG93bicsIGZ1bmN0aW9uKGUpIHtcblx0XHRcdHRhYiA9IChlLmV2ZW50LmtleUNvZGUgPT0gOSk7XG5cdFx0fSk7XG5cblx0XHRbdG9nZ2xlXS5hcHBlbmQodWwuZ2V0RWxlbWVudHMoJ2J1dHRvbicpKS5lYWNoKGZ1bmN0aW9uKGVsKSB7XG5cdFx0XHRlbC5hZGRFdmVudCgnZm9jdXMnLCBmdW5jdGlvbigpIHtcblx0XHRcdFx0aWYgKCF0YWIpIHJldHVybjtcblx0XHRcdFx0dWwucmVtb3ZlQ2xhc3MoJ2ludmlzaWJsZScpO1xuXHRcdFx0XHR0b2dnbGUuYWRkQ2xhc3MoJ2FjdGl2ZScpO1xuXHRcdFx0XHRjbGVhclRpbWVvdXQodGltZXIpO1xuXHRcdFx0fSk7XG5cblx0XHRcdGVsLmFkZEV2ZW50KCdibHVyJywgZnVuY3Rpb24oKSB7XG5cdFx0XHRcdGlmICghdGFiKSByZXR1cm47XG5cdFx0XHRcdHRpbWVyID0gc2V0VGltZW91dChmdW5jdGlvbigpIHtcblx0XHRcdFx0XHR1bC5hZGRDbGFzcygnaW52aXNpYmxlJyk7XG5cdFx0XHRcdFx0dG9nZ2xlLnJlbW92ZUNsYXNzKCdhY3RpdmUnKTtcblx0XHRcdFx0fSwgMTAwKTtcblx0XHRcdH0pO1xuXHRcdH0pO1xuXG5cdFx0dG9nZ2xlLnNldCgndGFiaW5kZXgnLCAnLTEnKTtcblx0fVxufTtcblxuLy8gSW5pdGlhbGl6ZSB0aGUgYmFjayBlbmQgc2NyaXB0XG53aW5kb3cuYWRkRXZlbnQoJ2RvbXJlYWR5JywgZnVuY3Rpb24oKSB7XG5cdCQoZG9jdW1lbnQuYm9keSkuYWRkQ2xhc3MoJ2pzJyk7XG5cblx0Ly8gTWFyayB0b3VjaCBkZXZpY2VzIChzZWUgIzU1NjMpXG5cdGlmIChCcm93c2VyLkZlYXR1cmVzLlRvdWNoKSB7XG5cdFx0JChkb2N1bWVudC5ib2R5KS5hZGRDbGFzcygndG91Y2gnKTtcblx0fVxuXG5cdEJhY2tlbmQudGFibGVXaXphcmRTZXRXaWR0aCgpO1xuXHRCYWNrZW5kLmVuYWJsZUltYWdlU2l6ZVdpZGdldHMoKTtcblx0QmFja2VuZC5lbmFibGVUb2dnbGVTZWxlY3QoKTtcblxuXHQvLyBDaG9zZW5cblx0aWYgKEVsZW1lbnRzLmNob3NlbiAhPSB1bmRlZmluZWQpIHtcblx0XHQkJCgnc2VsZWN0LnRsX2Nob3NlbicpLmNob3NlbigpO1xuXHR9XG5cblx0VGhlbWUuc3RvcENsaWNrUHJvcGFnYXRpb24oKTtcblx0VGhlbWUuc2V0dXBDdHJsQ2xpY2soKTtcblx0VGhlbWUuc2V0dXBUZXh0YXJlYVJlc2l6aW5nKCk7XG5cdFRoZW1lLnNldHVwTWVudVRvZ2dsZSgpO1xuXHRUaGVtZS5zZXR1cFByb2ZpbGVUb2dnbGUoKTtcblx0VGhlbWUuc2V0dXBTcGxpdEJ1dHRvblRvZ2dsZSgpO1xufSk7XG5cbi8vIFJlc2l6ZSB0aGUgdGFibGUgd2l6YXJkXG53aW5kb3cuYWRkRXZlbnQoJ3Jlc2l6ZScsIGZ1bmN0aW9uKCkge1xuXHRCYWNrZW5kLnRhYmxlV2l6YXJkU2V0V2lkdGgoKTtcbn0pO1xuXG4vLyBSZS1hcHBseSBjZXJ0YWluIGNoYW5nZXMgdXBvbiBhamF4X2NoYW5nZVxud2luZG93LmFkZEV2ZW50KCdhamF4X2NoYW5nZScsIGZ1bmN0aW9uKCkge1xuXHRCYWNrZW5kLmVuYWJsZUltYWdlU2l6ZVdpZGdldHMoKTtcblx0QmFja2VuZC5lbmFibGVUb2dnbGVTZWxlY3QoKTtcblxuXHQvLyBDaG9zZW5cblx0aWYgKEVsZW1lbnRzLmNob3NlbiAhPSB1bmRlZmluZWQpIHtcblx0XHQkJCgnc2VsZWN0LnRsX2Nob3NlbicpLmZpbHRlcihmdW5jdGlvbihlbCkge1xuXHRcdFx0cmV0dXJuIGVsLmdldFN0eWxlKCdkaXNwbGF5JykgIT0gJ25vbmUnO1xuXHRcdH0pLmNob3NlbigpO1xuXHR9XG5cblx0VGhlbWUuc3RvcENsaWNrUHJvcGFnYXRpb24oKTtcblx0VGhlbWUuc2V0dXBDdHJsQ2xpY2soKTtcblx0VGhlbWUuc2V0dXBUZXh0YXJlYVJlc2l6aW5nKCk7XG59KTtcbiIsIndpbmRvdy5hZGRFdmVudExpc3RlbmVyKCdET01Db250ZW50TG9hZGVkJywgZnVuY3Rpb24oKSB7XG4gICAgZG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbCgnZGl2LmxpbWl0X2hlaWdodCcpLmZvckVhY2goZnVuY3Rpb24oZGl2KSB7XG4gICAgICAgIGlmICh3aW5kb3cuY29uc29sZSkge1xuICAgICAgICAgICAgY29uc29sZS53YXJuKCdVc2luZyBcImxpbWl0X2hlaWdodFwiIGNsYXNzIG9uIGNoaWxkX3JlY29yZF9jYWxsYmFjayBpcyBkZXByZWNhdGVkLiBTZXQgYSBsaXN0LnNvcnRpbmcubGltaXRIZWlnaHQgaW4geW91ciBEQ0EgaW5zdGVhZC4nKTtcbiAgICAgICAgfVxuXG4gICAgICAgIGNvbnN0IHBhcmVudCA9IGRpdi5wYXJlbnROb2RlLmNsb3Nlc3QoJy50bF9jb250ZW50Jyk7XG5cbiAgICAgICAgLy8gUmV0dXJuIGlmIHRoZSBlbGVtZW50IGlzIGEgd3JhcHBlclxuICAgICAgICBpZiAocGFyZW50ICYmIChwYXJlbnQuY2xhc3NMaXN0LmNvbnRhaW5zKCd3cmFwcGVyX3N0YXJ0JykgfHwgcGFyZW50LmNsYXNzTGlzdC5jb250YWlucygnd3JhcHBlcl9zdG9wJykpKSByZXR1cm47XG5cbiAgICAgICAgY29uc3QgaGd0ID0gTnVtYmVyKGRpdi5jbGFzc05hbWUucmVwbGFjZSgvW14wLTldKi8sICcnKSk7XG5cbiAgICAgICAgLy8gUmV0dXJuIGlmIHRoZXJlIGlzIG5vIGhlaWdodCB2YWx1ZVxuICAgICAgICBpZiAoIWhndCkgcmV0dXJuO1xuXG4gICAgICAgIGNvbnN0IHN0eWxlID0gd2luZG93LmdldENvbXB1dGVkU3R5bGUoZGl2LCBudWxsKTtcbiAgICAgICAgY29uc3QgcGFkZGluZyA9IHBhcnNlRmxvYXQoc3R5bGUucGFkZGluZ1RvcCkgKyBwYXJzZUZsb2F0KHN0eWxlLnBhZGRpbmdCb3R0b20pO1xuICAgICAgICBjb25zdCBoZWlnaHQgPSBkaXYuY2xpZW50SGVpZ2h0IC0gcGFkZGluZztcblxuICAgICAgICAvLyBEbyBub3QgYWRkIHRoZSB0b2dnbGUgaWYgdGhlIHByZXZpZXcgaGVpZ2h0IGlzIGJlbG93IHRoZSBtYXgtaGVpZ2h0XG4gICAgICAgIGlmIChoZWlnaHQgPD0gaGd0KSByZXR1cm47XG5cbiAgICAgICAgLy8gUmVzaXplIHRoZSBlbGVtZW50IGlmIGl0IGlzIGhpZ2hlciB0aGFuIHRoZSBtYXhpbXVtIGhlaWdodFxuICAgICAgICBkaXYuc3R5bGUuaGVpZ2h0ID0gaGd0KydweCc7XG5cbiAgICAgICAgY29uc3QgYnV0dG9uID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnYnV0dG9uJyk7XG4gICAgICAgIGJ1dHRvbi5zZXRBdHRyaWJ1dGUoJ3R5cGUnLCAnYnV0dG9uJyk7XG4gICAgICAgIGJ1dHRvbi50aXRsZSA9IENvbnRhby5sYW5nLmV4cGFuZDtcbiAgICAgICAgYnV0dG9uLmlubmVySFRNTCA9ICc8c3Bhbj4uLi48L3NwYW4+JztcbiAgICAgICAgYnV0dG9uLmNsYXNzTGlzdC5hZGQoJ3Vuc2VsZWN0YWJsZScpO1xuXG4gICAgICAgIGJ1dHRvbi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgaWYgKGRpdi5zdHlsZS5oZWlnaHQgPT0gJ2F1dG8nKSB7XG4gICAgICAgICAgICAgICAgZGl2LnN0eWxlLmhlaWdodCA9IGhndCsncHgnO1xuICAgICAgICAgICAgICAgIGJ1dHRvbi50aXRsZSA9IENvbnRhby5sYW5nLmV4cGFuZDtcbiAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgZGl2LnN0eWxlLmhlaWdodCA9ICdhdXRvJztcbiAgICAgICAgICAgICAgICBidXR0b24udGl0bGUgPSBDb250YW8ubGFuZy5jb2xsYXBzZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfSk7XG5cbiAgICAgICAgY29uc3QgdG9nZ2xlciA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuICAgICAgICB0b2dnbGVyLmNsYXNzTGlzdC5hZGQoJ2xpbWl0X3RvZ2dsZXInKTtcbiAgICAgICAgdG9nZ2xlci5hcHBlbmQoYnV0dG9uKTtcblxuICAgICAgICBkaXYuYXBwZW5kKHRvZ2dsZXIpO1xuICAgIH0pO1xufSk7XG4iLCIoZnVuY3Rpb24oKXtcbiAgICAndXNlIHN0cmljdCc7XG5cbiAgICBjb25zdCBpbml0aWFsaXplZFJvd3MgPSBuZXcgV2Vha01hcCgpO1xuICAgIGNvbnN0IHNhdmVTY3JvbGxPZmZzZXRFdmVudCA9IG5ldyBFdmVudCgnc3RvcmUtc2Nyb2xsLW9mZnNldCcpO1xuXG4gICAgY29uc3QgaW5pdCA9IChyb3cpID0+IHtcbiAgICAgICAgLy8gQ2hlY2sgaWYgdGhpcyByb3cgaGFzIGFscmVhZHkgYmVlbiBpbml0aWFsaXplZFxuICAgICAgICBpZiAoaW5pdGlhbGl6ZWRSb3dzLmhhcyhyb3cpKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICAvLyBDaGVjayBpZiB0aGUgcm93IGhhcyBhbGwgbmVjZXNzYXJ5IGVsZW1lbnRzIHRvIHByZXZlbnQgdGhlIG11dGF0aW9uIG9ic2VydmVyXG4gICAgICAgIC8vIGZyb20gaW5pdGlhbGl6aW5nIHRoZSBpbmNvbXBsZXRlIHdpZGdldC5cbiAgICAgICAgaWYgKCFyb3cucXVlcnlTZWxlY3RvcignYnV0dG9uLmRyYWctaGFuZGxlJykpIHtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIGluaXRpYWxpemVkUm93cy5zZXQocm93LCB0cnVlKTtcblxuICAgICAgICBjb25zdCB0Ym9keSA9IHJvdy5jbG9zZXN0KCd0Ym9keScpO1xuXG4gICAgICAgIGNvbnN0IG1ha2VTb3J0YWJsZSA9ICh0Ym9keSkgPT4ge1xuICAgICAgICAgICAgQXJyYXkuZnJvbSh0Ym9keS5jaGlsZHJlbikuZm9yRWFjaCgodHIsIGkpID0+IHtcbiAgICAgICAgICAgICAgICB0ci5xdWVyeVNlbGVjdG9yQWxsKCdpbnB1dCwgc2VsZWN0JykuZm9yRWFjaCgoZWwpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgZWwubmFtZSA9IGVsLm5hbWUucmVwbGFjZSgvXFxbWzAtOV0rXS9nLCAnWycgKyBpICsgJ10nKTtcbiAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgICAvLyBUT0RPOiByZXBsYWNlIHRoaXMgd2l0aCBhIHZhbmlsbGEgSlMgc29sdXRpb25cbiAgICAgICAgICAgIG5ldyBTb3J0YWJsZXModGJvZHksIHtcbiAgICAgICAgICAgICAgICBjb25zdHJhaW46IHRydWUsXG4gICAgICAgICAgICAgICAgb3BhY2l0eTogMC42LFxuICAgICAgICAgICAgICAgIGhhbmRsZTogJy5kcmFnLWhhbmRsZScsXG4gICAgICAgICAgICAgICAgb25Db21wbGV0ZTogZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgICAgIG1ha2VTb3J0YWJsZSh0Ym9keSk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG4gICAgICAgIH07XG5cbiAgICAgICAgY29uc3QgYWRkRXZlbnRzVG8gPSAodHIpID0+IHtcbiAgICAgICAgICAgIHRyLnF1ZXJ5U2VsZWN0b3JBbGwoJ2J1dHRvbicpLmZvckVhY2goKGJ0KSA9PiB7XG4gICAgICAgICAgICAgICAgY29uc3QgY29tbWFuZCA9IGJ0LmRhdGFzZXQuY29tbWFuZDtcblxuICAgICAgICAgICAgICAgIHN3aXRjaCAoY29tbWFuZCkge1xuICAgICAgICAgICAgICAgICAgICBjYXNlICdjb3B5JzpcbiAgICAgICAgICAgICAgICAgICAgICAgIGJ0LmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgKCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIHdpbmRvdy5kaXNwYXRjaEV2ZW50KHNhdmVTY3JvbGxPZmZzZXRFdmVudCk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBudHIgPSB0ci5jbG9uZU5vZGUodHJ1ZSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgY29uc3Qgc2VsZWN0cyA9IHRyLnF1ZXJ5U2VsZWN0b3JBbGwoJ3NlbGVjdCcpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNvbnN0IG5zZWxlY3RzID0gbnRyLnF1ZXJ5U2VsZWN0b3JBbGwoJ3NlbGVjdCcpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZm9yIChsZXQgaj0wOyBqPHNlbGVjdHMubGVuZ3RoOyBqKyspIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbnNlbGVjdHNbal0udmFsdWUgPSBzZWxlY3RzW2pdLnZhbHVlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIG50ci5xdWVyeVNlbGVjdG9yQWxsKCdbZGF0YS1vcmlnaW5hbC10aXRsZV0nKS5mb3JFYWNoKChlbCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBlbC5zZXRBdHRyaWJ1dGUoJ3RpdGxlJywgZWwuZ2V0QXR0cmlidXRlKCdkYXRhLW9yaWdpbmFsLXRpdGxlJykpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBlbC5yZW1vdmVBdHRyaWJ1dGUoJ2RhdGEtb3JpZ2luYWwtdGl0bGUnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGluaXRpYWxpemVkUm93cy5zZXQobnRyLCB0cnVlKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ci5wYXJlbnROb2RlLmluc2VydEJlZm9yZShudHIsIHRyLm5leHRTaWJsaW5nKTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIFJlbW92ZSB0aGUgSUQgb2YgdGhlIHNlbGVjdCBiZWZvcmUgaW5pdGlhbGl6aW5nIENob3NlblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNvbnN0IHNlbGVjdCA9IG50ci5xdWVyeVNlbGVjdG9yKCdzZWxlY3QudGxfc2VsZWN0Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgc2VsZWN0LnJlbW92ZUF0dHJpYnV0ZSgnaWQnKTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIG50ci5xdWVyeVNlbGVjdG9yKCcuY2h6bi1jb250YWluZXInKS5yZW1vdmUoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBuZXcgQ2hvc2VuKHNlbGVjdCk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBhZGRFdmVudHNUbyhudHIpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIG1ha2VTb3J0YWJsZSh0Ym9keSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGJyZWFrO1xuXG4gICAgICAgICAgICAgICAgICAgIGNhc2UgJ2RlbGV0ZSc6XG4gICAgICAgICAgICAgICAgICAgICAgICBidC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsICgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aW5kb3cuZGlzcGF0Y2hFdmVudChzYXZlU2Nyb2xsT2Zmc2V0RXZlbnQpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHRib2R5LmNoaWxkcmVuLmxlbmd0aCA+IDEpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdHIucmVtb3ZlKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLy8gUmVzZXQgdmFsdWVzIGZvciBsYXN0IGVsZW1lbnQgKCM2ODkpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRyLnF1ZXJ5U2VsZWN0b3JBbGwoJ3NlbGVjdCcpLmZvckVhY2goKHNlbGVjdCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgc2VsZWN0LnZhbHVlID0gc2VsZWN0LmNoaWxkcmVuWzBdLnZhbHVlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBtYWtlU29ydGFibGUodGJvZHkpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICBicmVhaztcblxuICAgICAgICAgICAgICAgICAgICBjYXNlICdlbmFibGUnOlxuICAgICAgICAgICAgICAgICAgICAgICAgYnQuYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aW5kb3cuZGlzcGF0Y2hFdmVudChzYXZlU2Nyb2xsT2Zmc2V0RXZlbnQpO1xuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgY29uc3QgY2J4ID0gYnQucHJldmlvdXNFbGVtZW50U2libGluZztcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChjYnguY2hlY2tlZCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBjYnguY2hlY2tlZCA9ICcnO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNieC5jaGVja2VkID0gJ2NoZWNrZWQnO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgYnJlYWs7XG5cbiAgICAgICAgICAgICAgICAgICAgZGVmYXVsdDpcbiAgICAgICAgICAgICAgICAgICAgICAgIGlmIChidC5jbGFzc0xpc3QuY29udGFpbnMoJ2RyYWctaGFuZGxlJykpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBidC5hZGRFdmVudExpc3RlbmVyKCdrZXlkb3duJywgKGV2ZW50KSA9PiB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmIChldmVudC5jb2RlID09PSAnQXJyb3dVcCcgfHwgZXZlbnQua2V5Q29kZSA9PT0gMzgpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICh0ci5wcmV2aW91c0VsZW1lbnRTaWJsaW5nKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdHIucHJldmlvdXNFbGVtZW50U2libGluZy5pbnNlcnRBZGphY2VudEVsZW1lbnQoJ2JlZm9yZWJlZ2luJywgdHIpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0Ym9keS5pbnNlcnRBZGphY2VudEVsZW1lbnQoJ2JlZm9yZWVuZCcsIHRyKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYnQuZm9jdXMoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG1ha2VTb3J0YWJsZSh0Ym9keSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSBpZiAoZXZlbnQuY29kZSA9PT0gJ0Fycm93RG93bicgfHwgZXZlbnQua2V5Q29kZSA9PT0gNDApIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICh0ci5uZXh0RWxlbWVudFNpYmxpbmcpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ci5uZXh0RWxlbWVudFNpYmxpbmcuaW5zZXJ0QWRqYWNlbnRFbGVtZW50KCdhZnRlcmVuZCcsIHRyKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdGJvZHkuaW5zZXJ0QWRqYWNlbnRFbGVtZW50KCdhZnRlcmJlZ2luJywgdHIpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBidC5mb2N1cygpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbWFrZVNvcnRhYmxlKHRib2R5KTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgYnJlYWs7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgIGNvbnN0IHNlbGVjdCA9IHRyLnF1ZXJ5U2VsZWN0b3IoJ3RkOmZpcnN0LWNoaWxkIHNlbGVjdCcpO1xuXG4gICAgICAgICAgICBpZiAoIXNlbGVjdCkge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgY29uc3QgbGluayA9IHRyLnF1ZXJ5U2VsZWN0b3IoJ2EubW9kdWxlX2xpbmsnKTtcbiAgICAgICAgICAgIGNvbnN0IGltYWdlcyA9IHRyLnF1ZXJ5U2VsZWN0b3JBbGwoJ2ltZy5tb2R1bGVfaW1hZ2UnKTtcblxuICAgICAgICAgICAgY29uc3QgdXBkYXRlTGluayA9ICgpID0+IHtcbiAgICAgICAgICAgICAgICBsaW5rLmhyZWYgPSBsaW5rLmhyZWYucmVwbGFjZSgvaWQ9WzAtOV0rLywgJ2lkPScgKyBzZWxlY3QudmFsdWUpO1xuXG4gICAgICAgICAgICAgICAgaWYgKHNlbGVjdC52YWx1ZSA+IDApIHtcbiAgICAgICAgICAgICAgICAgICAgbGluay5jbGFzc0xpc3QucmVtb3ZlKCdoaWRkZW4nKTtcblxuICAgICAgICAgICAgICAgICAgICBpbWFnZXMuZm9yRWFjaCgoaW1hZ2UpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGltYWdlLmNsYXNzTGlzdC5hZGQoJ2hpZGRlbicpO1xuICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICBsaW5rLmNsYXNzTGlzdC5hZGQoJ2hpZGRlbicpO1xuXG4gICAgICAgICAgICAgICAgICAgIGltYWdlcy5mb3JFYWNoKChpbWFnZSkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgaW1hZ2UuY2xhc3NMaXN0LnJlbW92ZSgnaGlkZGVuJyk7XG4gICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgIHNlbGVjdC5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCB1cGRhdGVMaW5rKTtcblxuICAgICAgICAgICAgLy8gQmFja3dhcmRzIGNvbXBhdGliaWxpdHkgd2l0aCBNb29Ub29scyBcIkNob3NlblwiIHNjcmlwdCB0aGF0IGZpcmVzIG5vbi1uYXRpdmUgY2hhbmdlIGV2ZW50XG4gICAgICAgICAgICBzZWxlY3QuYWRkRXZlbnQoJ2NoYW5nZScsIHVwZGF0ZUxpbmspO1xuICAgICAgICB9O1xuXG4gICAgICAgIG1ha2VTb3J0YWJsZSh0Ym9keSk7XG4gICAgICAgIGFkZEV2ZW50c1RvKHJvdyk7XG4gICAgfTtcblxuICAgIGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJy50bF9tb2R1bGV3aXphcmQgdHInKS5mb3JFYWNoKGluaXQpO1xuXG4gICAgbmV3IE11dGF0aW9uT2JzZXJ2ZXIoZnVuY3Rpb24obXV0YXRpb25zTGlzdCkge1xuICAgICAgICBmb3IgKGNvbnN0IG11dGF0aW9uIG9mIG11dGF0aW9uc0xpc3QpIHtcbiAgICAgICAgICAgIGlmIChtdXRhdGlvbi50eXBlID09PSAnY2hpbGRMaXN0Jykge1xuICAgICAgICAgICAgICAgIG11dGF0aW9uLmFkZGVkTm9kZXMuZm9yRWFjaChmdW5jdGlvbihlbGVtZW50KSB7XG4gICAgICAgICAgICAgICAgICAgIGlmIChlbGVtZW50Lm1hdGNoZXMgJiYgZWxlbWVudC5tYXRjaGVzKCcudGxfbW9kdWxld2l6YXJkIHRyLCAudGxfbW9kdWxld2l6YXJkIHRyIConKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgaW5pdChlbGVtZW50LmNsb3Nlc3QoJ3RyJykpO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH0pLm9ic2VydmUoZG9jdW1lbnQsIHtcbiAgICAgICAgYXR0cmlidXRlczogZmFsc2UsXG4gICAgICAgIGNoaWxkTGlzdDogdHJ1ZSxcbiAgICAgICAgc3VidHJlZTogdHJ1ZVxuICAgIH0pO1xufSkoKTtcbiIsIi8qXG4tLS1cblxubmFtZTogUmVxdWVzdC5Db250YW9cblxuZGVzY3JpcHRpb246IEV4dGVuZHMgdGhlIE1vb1Rvb2xzIFJlcXVlc3QuSlNPTiBjbGFzcyB3aXRoIENvbnRhby1zcGVjaWZpYyByb3V0aW5lcy5cblxubGljZW5zZTogTEdQTHYzXG5cbmF1dGhvcnM6XG4gLSBMZW8gRmV5ZXJcblxucmVxdWlyZXM6IFtSZXF1ZXN0LCBKU09OXVxuXG5wcm92aWRlczogUmVxdWVzdC5Db250YW9cblxuLi4uXG4qL1xuXG5SZXF1ZXN0LkNvbnRhbyA9IG5ldyBDbGFzcyhcbntcblx0RXh0ZW5kczogUmVxdWVzdC5KU09OLFxuXG5cdG9wdGlvbnM6IHtcblx0XHRmb2xsb3dSZWRpcmVjdHM6IHRydWUsXG5cdH0sXG5cblx0aW5pdGlhbGl6ZTogZnVuY3Rpb24ob3B0aW9ucykge1xuXHRcdGlmICghb3B0aW9ucykge1xuXHRcdFx0b3B0aW9ucyA9IHt9O1xuXHRcdH1cblxuXHRcdGlmICghb3B0aW9ucy51cmwgJiYgb3B0aW9ucy5maWVsZCAmJiBvcHRpb25zLmZpZWxkLmZvcm0gJiYgb3B0aW9ucy5maWVsZC5mb3JtLmFjdGlvbikge1xuXHRcdFx0b3B0aW9ucy51cmwgPSBvcHRpb25zLmZpZWxkLmZvcm0uYWN0aW9uO1xuXHRcdH1cblxuXHRcdGlmICghb3B0aW9ucy51cmwpIHtcblx0XHRcdG9wdGlvbnMudXJsID0gd2luZG93LmxvY2F0aW9uLmhyZWY7XG5cdFx0fVxuXG5cdFx0dGhpcy5wYXJlbnQob3B0aW9ucyk7XG5cdH0sXG5cblx0c3VjY2VzczogZnVuY3Rpb24odGV4dCkge1xuXHRcdHZhciB1cmwgPSB0aGlzLmdldEhlYWRlcignWC1BamF4LUxvY2F0aW9uJyksXG5cdFx0XHRqc29uO1xuXG5cdFx0aWYgKHVybCAmJiB0aGlzLm9wdGlvbnMuZm9sbG93UmVkaXJlY3RzKSB7XG5cdFx0XHRsb2NhdGlvbi5yZXBsYWNlKHVybCk7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0Ly8gU3VwcG9ydCBib3RoIHBsYWluIHRleHQgYW5kIEpTT04gcmVzcG9uc2VzXG5cdFx0dHJ5IHtcblx0XHRcdGpzb24gPSB0aGlzLnJlc3BvbnNlLmpzb24gPSBKU09OLmRlY29kZSh0ZXh0LCB0aGlzLm9wdGlvbnMuc2VjdXJlKTtcblx0XHR9IGNhdGNoKGUpIHtcblx0XHRcdGpzb24gPSB7J2NvbnRlbnQnOnRleHR9O1xuXHRcdH1cblxuXHRcdC8vIEVtcHR5IHJlc3BvbnNlXG5cdFx0aWYgKGpzb24gPT09IG51bGwpIHtcblx0XHRcdGpzb24gPSB7J2NvbnRlbnQnOicnfTtcblx0XHR9IGVsc2UgaWYgKHR5cGVvZihqc29uKSAhPSAnb2JqZWN0Jykge1xuXHRcdFx0anNvbiA9IHsnY29udGVudCc6dGV4dH07XG5cdFx0fVxuXG5cdFx0Ly8gSXNvbGF0ZSBzY3JpcHRzIGFuZCBleGVjdXRlIHRoZW1cblx0XHRpZiAoanNvbi5jb250ZW50ICE9ICcnKSB7XG5cdFx0XHRqc29uLmNvbnRlbnQgPSBqc29uLmNvbnRlbnQuc3RyaXBTY3JpcHRzKGZ1bmN0aW9uKHNjcmlwdCkge1xuXHRcdFx0XHRqc29uLmphdmFzY3JpcHQgPSBzY3JpcHQucmVwbGFjZSgvPCEtLXxcXC9cXC8tLT58PCFcXFtDREFUQVxcW1xcL1xcLz58PCFdXT4vZywgJycpO1xuXHRcdFx0fSk7XG5cdFx0XHRpZiAoanNvbi5qYXZhc2NyaXB0ICYmIHRoaXMub3B0aW9ucy5ldmFsU2NyaXB0cykge1xuXHRcdFx0XHRCcm93c2VyLmV4ZWMoanNvbi5qYXZhc2NyaXB0KTtcblx0XHRcdH1cblx0XHR9XG5cblx0XHR0aGlzLm9uU3VjY2Vzcyhqc29uLmNvbnRlbnQsIGpzb24pO1xuXHR9LFxuXG5cdGZhaWx1cmU6IGZ1bmN0aW9uKCkge1xuXHRcdHZhciB1cmwgPSB0aGlzLmdldEhlYWRlcignWC1BamF4LUxvY2F0aW9uJyk7XG5cblx0XHRpZiAodXJsICYmIDQwMSA9PT0gdGhpcy5zdGF0dXMpIHtcblx0XHRcdGxvY2F0aW9uLnJlcGxhY2UodXJsKTtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHRpZiAodXJsICYmIHRoaXMub3B0aW9ucy5mb2xsb3dSZWRpcmVjdHMgJiYgdGhpcy5zdGF0dXMgPj0gMzAwICYmIHRoaXMuc3RhdHVzIDwgNDAwKSB7XG5cdFx0XHRsb2NhdGlvbi5yZXBsYWNlKHVybCk7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0dGhpcy5vbkZhaWx1cmUoKTtcblx0fVxufSk7XG5cblxuLypcbi0tLVxuXG5uYW1lOiBEcmFnXG5cbmRlc2NyaXB0aW9uOiBFeHRlbmRzIHRoZSBiYXNlIERyYWcgY2xhc3Mgd2l0aCB0b3VjaCBzdXBwb3J0LlxuXG5saWNlbnNlOiBMR1BMdjNcblxuYXV0aG9yczpcbiAtIEFuZHJlYXMgU2NoZW1wcFxuXG5yZXF1aXJlczogW0RyYWddXG5cbnByb3ZpZGVzOiBEcmFnXG5cbi4uLlxuKi9cblxuQ2xhc3MucmVmYWN0b3IoRHJhZyxcbntcblx0YXR0YWNoOiBmdW5jdGlvbigpIHtcblx0XHR0aGlzLmhhbmRsZXMuYWRkRXZlbnQoJ3RvdWNoc3RhcnQnLCB0aGlzLmJvdW5kLnN0YXJ0KTtcblx0XHRyZXR1cm4gdGhpcy5wcmV2aW91cy5hcHBseSh0aGlzLCBhcmd1bWVudHMpO1xuXHR9LFxuXG5cdGRldGFjaDogZnVuY3Rpb24oKSB7XG5cdFx0dGhpcy5oYW5kbGVzLnJlbW92ZUV2ZW50KCd0b3VjaHN0YXJ0JywgdGhpcy5ib3VuZC5zdGFydCk7XG5cdFx0cmV0dXJuIHRoaXMucHJldmlvdXMuYXBwbHkodGhpcywgYXJndW1lbnRzKTtcblx0fSxcblxuXHRzdGFydDogZnVuY3Rpb24oKSB7XG5cdFx0ZG9jdW1lbnQuYWRkRXZlbnRzKHtcblx0XHRcdHRvdWNobW92ZTogdGhpcy5ib3VuZC5jaGVjayxcblx0XHRcdHRvdWNoZW5kOiB0aGlzLmJvdW5kLmNhbmNlbFxuXHRcdH0pO1xuXHRcdHRoaXMucHJldmlvdXMuYXBwbHkodGhpcywgYXJndW1lbnRzKTtcblx0fSxcblxuXHRjaGVjazogZnVuY3Rpb24oZXZlbnQpIHtcblx0XHRpZiAodGhpcy5vcHRpb25zLnByZXZlbnREZWZhdWx0KSBldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdHZhciBkaXN0YW5jZSA9IE1hdGgucm91bmQoTWF0aC5zcXJ0KE1hdGgucG93KGV2ZW50LnBhZ2UueCAtIHRoaXMubW91c2Uuc3RhcnQueCwgMikgKyBNYXRoLnBvdyhldmVudC5wYWdlLnkgLSB0aGlzLm1vdXNlLnN0YXJ0LnksIDIpKSk7XG5cdFx0aWYgKGRpc3RhbmNlID4gdGhpcy5vcHRpb25zLnNuYXApIHtcblx0XHRcdHRoaXMuY2FuY2VsKCk7XG5cdFx0XHR0aGlzLmRvY3VtZW50LmFkZEV2ZW50cyh7XG5cdFx0XHRcdG1vdXNlbW92ZTogdGhpcy5ib3VuZC5kcmFnLFxuXHRcdFx0XHRtb3VzZXVwOiB0aGlzLmJvdW5kLnN0b3Bcblx0XHRcdH0pO1xuXHRcdFx0ZG9jdW1lbnQuYWRkRXZlbnRzKHtcblx0XHRcdFx0dG91Y2htb3ZlOiB0aGlzLmJvdW5kLmRyYWcsXG5cdFx0XHRcdHRvdWNoZW5kOiB0aGlzLmJvdW5kLnN0b3Bcblx0XHRcdH0pO1xuXHRcdFx0dGhpcy5maXJlRXZlbnQoJ3N0YXJ0JywgW3RoaXMuZWxlbWVudCwgZXZlbnRdKS5maXJlRXZlbnQoJ3NuYXAnLCB0aGlzLmVsZW1lbnQpO1xuXHRcdH1cblx0fSxcblxuXHRjYW5jZWw6IGZ1bmN0aW9uKCkge1xuXHRcdGRvY3VtZW50LnJlbW92ZUV2ZW50cyh7XG5cdFx0XHR0b3VjaG1vdmU6IHRoaXMuYm91bmQuY2hlY2ssXG5cdFx0XHR0b3VjaGVuZDogdGhpcy5ib3VuZC5jYW5jZWxcblx0XHR9KTtcblx0XHRyZXR1cm4gdGhpcy5wcmV2aW91cy5hcHBseSh0aGlzLCBhcmd1bWVudHMpO1xuXHR9LFxuXG5cdHN0b3A6IGZ1bmN0aW9uKCkge1xuXHRcdGRvY3VtZW50LnJlbW92ZUV2ZW50cyh7XG5cdFx0XHR0b3VjaG1vdmU6IHRoaXMuYm91bmQuZHJhZyxcblx0XHRcdHRvdWNoZW5kOiB0aGlzLmJvdW5kLnN0b3Bcblx0XHR9KTtcblx0XHRyZXR1cm4gdGhpcy5wcmV2aW91cy5hcHBseSh0aGlzLCBhcmd1bWVudHMpO1xuXHR9XG59KTtcblxuLypcbi0tLVxuXG5uYW1lOiBTb3J0YWJsZXNcblxuZGVzY3JpcHRpb246IEV4dGVuZHMgdGhlIGJhc2UgU29ydGFibGVzIGNsYXNzIHdpdGggdG91Y2ggc3VwcG9ydC5cblxubGljZW5zZTogTEdQTHYzXG5cbmF1dGhvcnM6XG4gLSBBbmRyZWFzIFNjaGVtcHBcblxucmVxdWlyZXM6IFtTb3J0YWJsZXNdXG5cbnByb3ZpZGVzOiBTb3J0YWJsZXNcblxuLi4uXG4qL1xuXG5DbGFzcy5yZWZhY3RvcihTb3J0YWJsZXMsXG57XG5cdGluaXRpYWxpemU6IGZ1bmN0aW9uKGxpc3RzLCBvcHRpb25zKSB7XG5cdFx0b3B0aW9ucy5kcmFnT3B0aW9ucyA9IE9iamVjdC5tZXJnZShvcHRpb25zLmRyYWdPcHRpb25zIHx8IHt9LCB7IHByZXZlbnREZWZhdWx0OiAob3B0aW9ucy5kcmFnT3B0aW9ucyAmJiBvcHRpb25zLmRyYWdPcHRpb25zLnByZXZlbnREZWZhdWx0KSB8fCBCcm93c2VyLkZlYXR1cmVzLlRvdWNoIH0pO1xuXHRcdGlmIChvcHRpb25zLmRyYWdPcHRpb25zLnVuRHJhZ2dhYmxlVGFncyA9PT0gdW5kZWZpbmVkKSB7XG5cdFx0XHRvcHRpb25zLmRyYWdPcHRpb25zLnVuRHJhZ2dhYmxlVGFncyA9IHRoaXMub3B0aW9ucy51bkRyYWdnYWJsZVRhZ3MuZmlsdGVyKGZ1bmN0aW9uKHRhZykge1xuXHRcdFx0XHRyZXR1cm4gdGFnICE9ICdidXR0b24nO1xuXHRcdFx0fSk7XG5cdFx0fVxuXHRcdHJldHVybiB0aGlzLnByZXZpb3VzLmFwcGx5KHRoaXMsIGFyZ3VtZW50cyk7XG5cdH0sXG5cblx0YWRkSXRlbXM6IGZ1bmN0aW9uKCkge1xuXHRcdEFycmF5LmZsYXR0ZW4oYXJndW1lbnRzKS5lYWNoKGZ1bmN0aW9uKGVsZW1lbnQpIHtcblx0XHRcdHRoaXMuZWxlbWVudHMucHVzaChlbGVtZW50KTtcblx0XHRcdHZhciBzdGFydCA9IGVsZW1lbnQucmV0cmlldmUoJ3NvcnRhYmxlczpzdGFydCcsIGZ1bmN0aW9uKGV2ZW50KSB7XG5cdFx0XHRcdHRoaXMuc3RhcnQuY2FsbCh0aGlzLCBldmVudCwgZWxlbWVudCk7XG5cdFx0XHR9LmJpbmQodGhpcykpO1xuXHRcdFx0KHRoaXMub3B0aW9ucy5oYW5kbGUgPyBlbGVtZW50LmdldEVsZW1lbnQodGhpcy5vcHRpb25zLmhhbmRsZSkgfHwgZWxlbWVudCA6IGVsZW1lbnQpLmFkZEV2ZW50cyh7XG5cdFx0XHRcdG1vdXNlZG93bjogc3RhcnQsXG5cdFx0XHRcdHRvdWNoc3RhcnQ6IHN0YXJ0XG5cdFx0XHR9KTtcblx0XHR9LCB0aGlzKTtcblx0XHRyZXR1cm4gdGhpcztcblx0fSxcblxuXHRyZW1vdmVJdGVtczogZnVuY3Rpb24oKSB7XG5cdFx0cmV0dXJuICQkKEFycmF5LmZsYXR0ZW4oYXJndW1lbnRzKS5tYXAoZnVuY3Rpb24oZWxlbWVudCkge1xuXHRcdFx0dGhpcy5lbGVtZW50cy5lcmFzZShlbGVtZW50KTtcblx0XHRcdHZhciBzdGFydCA9IGVsZW1lbnQucmV0cmlldmUoJ3NvcnRhYmxlczpzdGFydCcpO1xuXHRcdFx0KHRoaXMub3B0aW9ucy5oYW5kbGUgPyBlbGVtZW50LmdldEVsZW1lbnQodGhpcy5vcHRpb25zLmhhbmRsZSkgfHwgZWxlbWVudCA6IGVsZW1lbnQpLnJlbW92ZUV2ZW50cyh7XG5cdFx0XHRcdG1vdXNlZG93bjogc3RhcnQsXG5cdFx0XHRcdHRvdWNoZW5kOiBzdGFydFxuXHRcdFx0fSk7XG5cdFx0XHRyZXR1cm4gZWxlbWVudDtcblx0XHR9LCB0aGlzKSk7XG5cdH0sXG5cblx0Z2V0Q2xvbmU6IGZ1bmN0aW9uKGV2ZW50LCBlbGVtZW50KSB7XG5cdFx0aWYgKCF0aGlzLm9wdGlvbnMuY2xvbmUpIHJldHVybiBuZXcgRWxlbWVudChlbGVtZW50LnRhZ05hbWUpLmluamVjdChkb2N1bWVudC5ib2R5KTtcblx0XHRpZiAodHlwZU9mKHRoaXMub3B0aW9ucy5jbG9uZSkgPT0gJ2Z1bmN0aW9uJykgcmV0dXJuIHRoaXMub3B0aW9ucy5jbG9uZS5jYWxsKHRoaXMsIGV2ZW50LCBlbGVtZW50LCB0aGlzLmxpc3QpO1xuXHRcdHZhciBjbG9uZSA9IHRoaXMucHJldmlvdXMuYXBwbHkodGhpcywgYXJndW1lbnRzKTtcblx0XHRjbG9uZS5hZGRFdmVudCgndG91Y2hzdGFydCcsIGZ1bmN0aW9uKGV2ZW50KSB7XG5cdFx0XHRlbGVtZW50LmZpcmVFdmVudCgndG91Y2hzdGFydCcsIGV2ZW50KTtcblx0XHR9KTtcblx0XHRyZXR1cm4gY2xvbmU7XG5cdH1cbn0pO1xuXG4vKlxuLS0tXG5cbnNjcmlwdDogUmVxdWVzdC5RdWV1ZS5qc1xuXG5uYW1lOiBSZXF1ZXN0LlF1ZXVlXG5cbmRlc2NyaXB0aW9uOiBFeHRlbmRzIHRoZSBiYXNlIFJlcXVlc3QuUXVldWUgY2xhc3MgYW5kIGF0dGVtcHRzIHRvIGZpeCBzb21lIGlzc3Vlcy5cblxubGljZW5zZTogTUlULXN0eWxlIGxpY2Vuc2VcblxuYXV0aG9yczpcbiAtIExlbyBGZXllclxuXG5yZXF1aXJlczpcblx0LSBDb3JlL0VsZW1lbnRcblx0LSBDb3JlL1JlcXVlc3Rcblx0LSBDbGFzcy5CaW5kc1xuXG5wcm92aWRlczogW1JlcXVlc3QuUXVldWVdXG5cbi4uLlxuKi9cblxuQ2xhc3MucmVmYWN0b3IoUmVxdWVzdC5RdWV1ZSxcbntcblx0Ly8gRG8gbm90IGZpcmUgdGhlIFwiZW5kXCIgZXZlbnQgaGVyZVxuXHRvbkNvbXBsZXRlOiBmdW5jdGlvbigpe1xuXHRcdHRoaXMuZmlyZUV2ZW50KCdjb21wbGV0ZScsIGFyZ3VtZW50cyk7XG5cdH0sXG5cblx0Ly8gQ2FsbCByZXN1bWUoKSBpbnN0ZWFkIG9mIHJ1bk5leHQoKVxuXHRvbkNhbmNlbDogZnVuY3Rpb24oKXtcblx0XHRpZiAodGhpcy5vcHRpb25zLmF1dG9BZHZhbmNlICYmICF0aGlzLmVycm9yKSB0aGlzLnJlc3VtZSgpO1xuXHRcdHRoaXMuZmlyZUV2ZW50KCdjYW5jZWwnLCBhcmd1bWVudHMpO1xuXHR9LFxuXG5cdC8vIENhbGwgcmVzdW1lKCkgaW5zdGVhZCBvZiBydW5OZXh0KCkgYW5kIGZpcmUgdGhlIFwiZW5kXCIgZXZlbnRcblx0b25TdWNjZXNzOiBmdW5jdGlvbigpe1xuXHRcdGlmICh0aGlzLm9wdGlvbnMuYXV0b0FkdmFuY2UgJiYgIXRoaXMuZXJyb3IpIHRoaXMucmVzdW1lKCk7XG5cdFx0dGhpcy5maXJlRXZlbnQoJ3N1Y2Nlc3MnLCBhcmd1bWVudHMpO1xuXHRcdGlmICghdGhpcy5xdWV1ZS5sZW5ndGggJiYgIXRoaXMuaXNSdW5uaW5nKCkpIHRoaXMuZmlyZUV2ZW50KCdlbmQnKTtcblx0fSxcblxuXHQvLyBDYWxsIHJlc3VtZSgpIGluc3RlYWQgb2YgcnVuTmV4dCgpIGFuZCBmaXJlIHRoZSBcImVuZFwiIGV2ZW50XG5cdG9uRmFpbHVyZTogZnVuY3Rpb24oKXtcblx0XHR0aGlzLmVycm9yID0gdHJ1ZTtcblx0XHRpZiAoIXRoaXMub3B0aW9ucy5zdG9wT25GYWlsdXJlICYmIHRoaXMub3B0aW9ucy5hdXRvQWR2YW5jZSkgdGhpcy5yZXN1bWUoKTtcblx0XHR0aGlzLmZpcmVFdmVudCgnZmFpbHVyZScsIGFyZ3VtZW50cyk7XG5cdFx0aWYgKCF0aGlzLnF1ZXVlLmxlbmd0aCAmJiAhdGhpcy5pc1J1bm5pbmcoKSkgdGhpcy5maXJlRXZlbnQoJ2VuZCcpO1xuXHR9LFxuXG5cdC8vIENhbGwgcmVzdW1lKCkgaW5zdGVhZCBvZiBydW5OZXh0KClcblx0b25FeGNlcHRpb246IGZ1bmN0aW9uKCl7XG5cdFx0dGhpcy5lcnJvciA9IHRydWU7XG5cdFx0aWYgKCF0aGlzLm9wdGlvbnMuc3RvcE9uRmFpbHVyZSAmJiB0aGlzLm9wdGlvbnMuYXV0b0FkdmFuY2UpIHRoaXMucmVzdW1lKCk7XG5cdFx0dGhpcy5maXJlRXZlbnQoJ2V4Y2VwdGlvbicsIGFyZ3VtZW50cyk7XG5cdH1cbn0pO1xuXG4vKlxuLS0tXG5cbm5hbWU6IENvbnRhby5TZXJwUHJldmlld1xuXG5kZXNjcmlwdGlvbjogR2VuZXJhdGVzIGEgU0VSUCBwcmV2aWV3XG5cbmxpY2Vuc2U6IExHUEx2M1xuXG5hdXRob3JzOlxuIC0gTGVvIEZleWVyXG5cbnJlcXVpcmVzOiBbUmVxdWVzdCwgSlNPTl1cblxucHJvdmlkZXM6IENvbnRhby5TZXJwUHJldmlld1xuXG4uLi5cbiovXG5cbkNvbnRhby5TZXJwUHJldmlldyA9IG5ldyBDbGFzcyhcbntcblx0b3B0aW9uczoge1xuXHRcdGlkOiAwLFxuXHRcdHRyYWlsOiBudWxsLFxuXHRcdHRpdGxlRmllbGQ6IG51bGwsXG5cdFx0dGl0bGVGYWxsYmFja0ZpZWxkOiBudWxsLFxuXHRcdGFsaWFzRmllbGQ6IG51bGwsXG5cdFx0ZGVzY3JpcHRpb25GaWVsZDogbnVsbCxcblx0XHRkZXNjcmlwdGlvbkZhbGxiYWNrRmllbGQ6IG51bGwsXG5cdFx0dGl0bGVUYWc6IG51bGxcblx0fSxcblxuXHRzaG9ydGVuOiBmdW5jdGlvbihzdHIsIG1heCkge1xuXHRcdGlmIChzdHIubGVuZ3RoIDw9IG1heCkge1xuXHRcdFx0cmV0dXJuIHN0cjtcblx0XHR9XG5cdFx0cmV0dXJuIHN0ci5zdWJzdHIoMCwgc3RyLmxhc3RJbmRleE9mKCcgJywgbWF4KSkgKyAnIOKApic7XG5cdH0sXG5cblx0aHRtbDJzdHJpbmc6IGZ1bmN0aW9uKGh0bWwpIHtcblx0XHRyZXR1cm4gbmV3IERPTVBhcnNlcigpLnBhcnNlRnJvbVN0cmluZyhodG1sLCAndGV4dC9odG1sJykuYm9keS50ZXh0Q29udGVudC5yZXBsYWNlKC9cXFstXS9nLCAnXFx4QUQnKS5yZXBsYWNlKC9cXFtuYnNwXS9nLCAnXFx4QTAnKTtcblx0fSxcblxuXHRnZXRUaW55bWNlOiBmdW5jdGlvbigpIHtcblx0XHRpZiAod2luZG93LnRpbnlNQ0UgJiYgdGhpcy5vcHRpb25zLmRlc2NyaXB0aW9uRmFsbGJhY2tGaWVsZCkge1xuXHRcdFx0cmV0dXJuIHdpbmRvdy50aW55TUNFLmdldCh0aGlzLm9wdGlvbnMuZGVzY3JpcHRpb25GYWxsYmFja0ZpZWxkKTtcblx0XHR9XG5cdH0sXG5cblx0aW5pdGlhbGl6ZTogZnVuY3Rpb24oKSB7XG5cdFx0dGhpcy5vcHRpb25zID0gT2JqZWN0Lm1lcmdlLmFwcGx5KG51bGwsIFt7fSwgdGhpcy5vcHRpb25zXS5hcHBlbmQoYXJndW1lbnRzKSk7XG5cblx0XHR2YXIgc2VycFRpdGxlID0gJCgnc2VycF90aXRsZV8nICsgdGhpcy5vcHRpb25zLmlkKSxcblx0XHRcdHNlcnBVcmwgPSAkKCdzZXJwX3VybF8nICsgdGhpcy5vcHRpb25zLmlkKSxcblx0XHRcdHNlcnBEZXNjcmlwdGlvbiA9ICQoJ3NlcnBfZGVzY3JpcHRpb25fJyArIHRoaXMub3B0aW9ucy5pZCksXG5cdFx0XHR0aXRsZUZpZWxkID0gJCh0aGlzLm9wdGlvbnMudGl0bGVGaWVsZCksXG5cdFx0XHR0aXRsZUZhbGxiYWNrRmllbGQgPSAkKHRoaXMub3B0aW9ucy50aXRsZUZhbGxiYWNrRmllbGQpLFxuXHRcdFx0YWxpYXNGaWVsZCA9ICQodGhpcy5vcHRpb25zLmFsaWFzRmllbGQpLFxuXHRcdFx0ZGVzY3JpcHRpb25GaWVsZCA9ICQodGhpcy5vcHRpb25zLmRlc2NyaXB0aW9uRmllbGQpLFxuXHRcdFx0ZGVzY3JpcHRpb25GYWxsYmFja0ZpZWxkID0gJCh0aGlzLm9wdGlvbnMuZGVzY3JpcHRpb25GYWxsYmFja0ZpZWxkKSxcblx0XHRcdGluZGV4RW1wdHkgPSB0aGlzLm9wdGlvbnMudHJhaWwuaW5kZXhPZign4oC6JykgPT09IC0xLFxuXHRcdFx0dGl0bGVUYWcgPSB0aGlzLm9wdGlvbnMudGl0bGVUYWcgfHwgJyVzJztcblxuXHRcdHRpdGxlRmllbGQgJiYgdGl0bGVGaWVsZC5hZGRFdmVudCgnaW5wdXQnLCBmdW5jdGlvbigpIHtcblx0XHRcdGlmICh0aXRsZUZpZWxkLnZhbHVlKSB7XG5cdFx0XHRcdHNlcnBUaXRsZS5zZXQoJ3RleHQnLCB0aGlzLnNob3J0ZW4odGhpcy5odG1sMnN0cmluZyh0aXRsZVRhZy5yZXBsYWNlKC8lcy8sIHRpdGxlRmllbGQudmFsdWUpKS5yZXBsYWNlKC8lJS9nLCAnJScpLCA2NCkpO1xuXHRcdFx0fSBlbHNlIGlmICh0aXRsZUZhbGxiYWNrRmllbGQgJiYgdGl0bGVGYWxsYmFja0ZpZWxkLnZhbHVlKSB7XG5cdFx0XHRcdHNlcnBUaXRsZS5zZXQoJ3RleHQnLCB0aGlzLnNob3J0ZW4odGhpcy5odG1sMnN0cmluZyh0aXRsZVRhZy5yZXBsYWNlKC8lcy8sIHRpdGxlRmFsbGJhY2tGaWVsZC52YWx1ZSkpLnJlcGxhY2UoLyUlL2csICclJyksIDY0KSk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRzZXJwVGl0bGUuc2V0KCd0ZXh0JywgJycpO1xuXHRcdFx0fVxuXHRcdH0uYmluZCh0aGlzKSk7XG5cblx0XHR0aXRsZUZhbGxiYWNrRmllbGQgJiYgdGl0bGVGYWxsYmFja0ZpZWxkLmFkZEV2ZW50KCdpbnB1dCcsIGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKHRpdGxlRmllbGQgJiYgdGl0bGVGaWVsZC52YWx1ZSkgcmV0dXJuO1xuXHRcdFx0c2VycFRpdGxlLnNldCgndGV4dCcsIHRoaXMuc2hvcnRlbih0aGlzLmh0bWwyc3RyaW5nKHRpdGxlVGFnLnJlcGxhY2UoLyVzLywgdGl0bGVGYWxsYmFja0ZpZWxkLnZhbHVlKSkucmVwbGFjZSgvJSUvZywgJyUnKSwgNjQpKTtcblx0XHR9LmJpbmQodGhpcykpO1xuXG5cdFx0YWxpYXNGaWVsZCAmJiBhbGlhc0ZpZWxkLmFkZEV2ZW50KCdpbnB1dCcsIGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKGFsaWFzRmllbGQudmFsdWUgPT0gJ2luZGV4JyAmJiBpbmRleEVtcHR5KSB7XG5cdFx0XHRcdHNlcnBVcmwuc2V0KCd0ZXh0JywgdGhpcy5vcHRpb25zLnRyYWlsKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdHNlcnBVcmwuc2V0KCd0ZXh0JywgdGhpcy5vcHRpb25zLnRyYWlsICsgJyDigLogJyArIChhbGlhc0ZpZWxkLnZhbHVlIHx8IHRoaXMub3B0aW9ucy5pZCkucmVwbGFjZSgvXFwvL2csICcg4oC6ICcpKTtcblx0XHRcdH1cblx0XHR9LmJpbmQodGhpcykpO1xuXG5cdFx0ZGVzY3JpcHRpb25GaWVsZCAmJiBkZXNjcmlwdGlvbkZpZWxkLmFkZEV2ZW50KCdpbnB1dCcsIGZ1bmN0aW9uKCkge1xuXHRcdFx0aWYgKGRlc2NyaXB0aW9uRmllbGQudmFsdWUpIHtcblx0XHRcdFx0c2VycERlc2NyaXB0aW9uLnNldCgndGV4dCcsIHRoaXMuc2hvcnRlbihkZXNjcmlwdGlvbkZpZWxkLnZhbHVlLCAxNjApKTtcblx0XHRcdFx0cmV0dXJuO1xuXHRcdFx0fVxuXHRcdFx0dmFyIGVkaXRvciA9IHRoaXMuZ2V0VGlueW1jZSgpO1xuXHRcdFx0aWYgKGVkaXRvcikge1xuXHRcdFx0XHRzZXJwRGVzY3JpcHRpb24uc2V0KCd0ZXh0JywgdGhpcy5zaG9ydGVuKHRoaXMuaHRtbDJzdHJpbmcoZWRpdG9yLmdldENvbnRlbnQoKSksIDE2MCkpO1xuXHRcdFx0fSBlbHNlIGlmIChkZXNjcmlwdGlvbkZhbGxiYWNrRmllbGQgJiYgZGVzY3JpcHRpb25GYWxsYmFja0ZpZWxkLnZhbHVlKSB7XG5cdFx0XHRcdHNlcnBEZXNjcmlwdGlvbi5zZXQoJ3RleHQnLCB0aGlzLnNob3J0ZW4odGhpcy5odG1sMnN0cmluZyhkZXNjcmlwdGlvbkZhbGxiYWNrRmllbGQudmFsdWUpLCAxNjApKTtcblx0XHRcdH0gZWxzZSB7XG5cdFx0XHRcdHNlcnBEZXNjcmlwdGlvbi5zZXQoJ3RleHQnLCAnJyk7XG5cdFx0XHR9XG5cdFx0fS5iaW5kKHRoaXMpKTtcblxuXHRcdGRlc2NyaXB0aW9uRmFsbGJhY2tGaWVsZCAmJiBkZXNjcmlwdGlvbkZhbGxiYWNrRmllbGQuYWRkRXZlbnQoJ2lucHV0JywgZnVuY3Rpb24oKSB7XG5cdFx0XHRpZiAoZGVzY3JpcHRpb25GaWVsZCAmJiBkZXNjcmlwdGlvbkZpZWxkLnZhbHVlKSByZXR1cm47XG5cdFx0XHRzZXJwRGVzY3JpcHRpb24uc2V0KCd0ZXh0JywgdGhpcy5zaG9ydGVuKHRoaXMuaHRtbDJzdHJpbmcoZGVzY3JpcHRpb25GYWxsYmFja0ZpZWxkLnZhbHVlKSwgMTYwKSk7XG5cdFx0fS5iaW5kKHRoaXMpKTtcblxuXHRcdHNldFRpbWVvdXQoZnVuY3Rpb24oKSB7XG5cdFx0XHR2YXIgZWRpdG9yID0gdGhpcy5nZXRUaW55bWNlKCk7XG5cdFx0XHRlZGl0b3IgJiYgZWRpdG9yLm9uKCdrZXl1cCcsIGZ1bmN0aW9uKCkge1xuXHRcdFx0XHRpZiAoZGVzY3JpcHRpb25GaWVsZCAmJiBkZXNjcmlwdGlvbkZpZWxkLnZhbHVlKSByZXR1cm47XG5cdFx0XHRcdHNlcnBEZXNjcmlwdGlvbi5zZXQoJ3RleHQnLCB0aGlzLnNob3J0ZW4odGhpcy5odG1sMnN0cmluZyh3aW5kb3cudGlueU1DRS5hY3RpdmVFZGl0b3IuZ2V0Q29udGVudCgpKSwgMTYwKSk7XG5cdFx0XHR9LmJpbmQodGhpcykpO1xuXHRcdH0uYmluZCh0aGlzKSwgNCk7XG5cdH1cbn0pO1xuIiwiKGZ1bmN0aW9uKCl7XG4gICAgJ3VzZSBzdHJpY3QnO1xuXG4gICAgY29uc3QgaW5pdGlhbGl6ZWRSb3dzID0gbmV3IFdlYWtNYXAoKTtcbiAgICBjb25zdCBzYXZlU2Nyb2xsT2Zmc2V0RXZlbnQgPSBuZXcgRXZlbnQoJ3N0b3JlLXNjcm9sbC1vZmZzZXQnKTtcblxuICAgIGNvbnN0IGluaXQgPSAocm93KSA9PiB7XG4gICAgICAgIC8vIENoZWNrIGlmIHRoaXMgcm93IGhhcyBhbHJlYWR5IGJlZW4gaW5pdGlhbGl6ZWRcbiAgICAgICAgaWYgKGluaXRpYWxpemVkUm93cy5oYXMocm93KSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gQ2hlY2sgaWYgdGhlIHJvdyBoYXMgYWxsIG5lY2Vzc2FyeSBlbGVtZW50cyB0byBwcmV2ZW50IHRoZSBtdXRhdGlvbiBvYnNlcnZlclxuICAgICAgICAvLyBmcm9tIGluaXRpYWxpemluZyB0aGUgaW5jb21wbGV0ZSB3aWRnZXQuXG4gICAgICAgIGlmICghcm93LnF1ZXJ5U2VsZWN0b3IoJ2J1dHRvbi5kcmFnLWhhbmRsZScpKSB7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICBpbml0aWFsaXplZFJvd3Muc2V0KHJvdywgdHJ1ZSk7XG5cbiAgICAgICAgY29uc3QgdGJvZHkgPSByb3cuY2xvc2VzdCgndGJvZHknKTtcblxuICAgICAgICBjb25zdCBtYWtlU29ydGFibGUgPSAodGJvZHkpID0+IHtcbiAgICAgICAgICAgIEFycmF5LmZyb20odGJvZHkuY2hpbGRyZW4pLmZvckVhY2goKHRyLCBpKSA9PiB7XG4gICAgICAgICAgICAgICAgdHIucXVlcnlTZWxlY3RvckFsbCgnaW5wdXQsIHNlbGVjdCcpLmZvckVhY2goKGVsKSA9PiB7XG4gICAgICAgICAgICAgICAgICAgIGVsLm5hbWUgPSBlbC5uYW1lLnJlcGxhY2UoL1xcW1swLTldK10vZywgJ1snICsgaSArICddJyk7XG4gICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgLy8gVE9ETzogcmVwbGFjZSB0aGlzIHdpdGggYSB2YW5pbGxhIEpTIHNvbHV0aW9uXG4gICAgICAgICAgICBuZXcgU29ydGFibGVzKHRib2R5LCB7XG4gICAgICAgICAgICAgICAgY29uc3RyYWluOiB0cnVlLFxuICAgICAgICAgICAgICAgIG9wYWNpdHk6IDAuNixcbiAgICAgICAgICAgICAgICBoYW5kbGU6ICcuZHJhZy1oYW5kbGUnLFxuICAgICAgICAgICAgICAgIG9uQ29tcGxldGU6IGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgICAgICAgICBtYWtlU29ydGFibGUodGJvZHkpO1xuICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9O1xuXG4gICAgICAgIGNvbnN0IGFkZEV2ZW50c1RvID0gKHRyKSA9PiB7XG4gICAgICAgICAgICB0ci5xdWVyeVNlbGVjdG9yQWxsKCdidXR0b24nKS5mb3JFYWNoKChidCkgPT4ge1xuICAgICAgICAgICAgICAgIGNvbnN0IGNvbW1hbmQgPSBidC5kYXRhc2V0LmNvbW1hbmQ7XG5cbiAgICAgICAgICAgICAgICBzd2l0Y2ggKGNvbW1hbmQpIHtcbiAgICAgICAgICAgICAgICAgICAgY2FzZSAnY29weSc6XG4gICAgICAgICAgICAgICAgICAgICAgICBidC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsICgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aW5kb3cuZGlzcGF0Y2hFdmVudChzYXZlU2Nyb2xsT2Zmc2V0RXZlbnQpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGNvbnN0IG50ciA9IHRyLmNsb25lTm9kZSh0cnVlKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb25zdCBzZWxlY3RzID0gdHIucXVlcnlTZWxlY3RvckFsbCgnc2VsZWN0Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgY29uc3QgbnNlbGVjdHMgPSBudHIucXVlcnlTZWxlY3RvckFsbCgnc2VsZWN0Jyk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgZm9yIChsZXQgaj0wOyBqPHNlbGVjdHMubGVuZ3RoOyBqKyspIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgbnNlbGVjdHNbal0udmFsdWUgPSBzZWxlY3RzW2pdLnZhbHVlO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ci5wYXJlbnROb2RlLmluc2VydEJlZm9yZShudHIsIHRyLm5leHRTaWJsaW5nKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBhZGRFdmVudHNUbyhudHIpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIG1ha2VTb3J0YWJsZSh0Ym9keSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGJyZWFrO1xuXG4gICAgICAgICAgICAgICAgICAgIGNhc2UgJ2RlbGV0ZSc6XG4gICAgICAgICAgICAgICAgICAgICAgICBidC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsICgpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB3aW5kb3cuZGlzcGF0Y2hFdmVudChzYXZlU2Nyb2xsT2Zmc2V0RXZlbnQpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIGlmICh0Ym9keS5jaGlsZHJlbi5sZW5ndGggPiAxKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRyLnJlbW92ZSgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8vIFJlc2V0IHZhbHVlcyBmb3IgbGFzdCBlbGVtZW50ICgjNjg5KVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ci5xdWVyeVNlbGVjdG9yQWxsKCdpbnB1dCcpLmZvckVhY2goKGlucHV0KSA9PiB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpbnB1dC52YWx1ZSA9ICcnO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9KTtcblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ci5xdWVyeVNlbGVjdG9yQWxsKCdzZWxlY3QnKS5mb3JFYWNoKChzZWxlY3QpID0+IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHNlbGVjdC52YWx1ZSA9IHNlbGVjdC5jaGlsZHJlblswXS52YWx1ZTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgIG1ha2VTb3J0YWJsZSh0Ym9keSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9KTtcbiAgICAgICAgICAgICAgICAgICAgICAgIGJyZWFrO1xuXG4gICAgICAgICAgICAgICAgICAgIGRlZmF1bHQ6XG4gICAgICAgICAgICAgICAgICAgICAgICBpZiAoYnQuY2xhc3NMaXN0LmNvbnRhaW5zKCdkcmFnLWhhbmRsZScpKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgYnQuYWRkRXZlbnRMaXN0ZW5lcigna2V5ZG93bicsIChldmVudCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAoZXZlbnQuY29kZSA9PT0gJ0Fycm93VXAnIHx8IGV2ZW50LmtleUNvZGUgPT09IDM4KSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBldmVudC5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgaWYgKHRyLnByZXZpb3VzRWxlbWVudFNpYmxpbmcpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB0ci5wcmV2aW91c0VsZW1lbnRTaWJsaW5nLmluc2VydEFkamFjZW50RWxlbWVudCgnYmVmb3JlYmVnaW4nLCB0cik7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRib2R5Lmluc2VydEFkamFjZW50RWxlbWVudCgnYmVmb3JlZW5kJywgdHIpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYnQuZm9jdXMoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG1ha2VTb3J0YWJsZSh0Ym9keSk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH0gZWxzZSBpZiAoZXZlbnQuY29kZSA9PT0gJ0Fycm93RG93bicgfHwgZXZlbnQua2V5Q29kZSA9PT0gNDApIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGV2ZW50LnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBpZiAodHIubmV4dEVsZW1lbnRTaWJsaW5nKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgdHIubmV4dEVsZW1lbnRTaWJsaW5nLmluc2VydEFkamFjZW50RWxlbWVudCgnYWZ0ZXJlbmQnLCB0cik7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRib2R5Lmluc2VydEFkamFjZW50RWxlbWVudCgnYWZ0ZXJiZWdpbicsIHRyKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGJ0LmZvY3VzKCk7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBtYWtlU29ydGFibGUodGJvZHkpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgfSk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgICAgICBicmVhaztcbiAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfTtcblxuICAgICAgICBtYWtlU29ydGFibGUodGJvZHkpO1xuICAgICAgICBhZGRFdmVudHNUbyhyb3cpO1xuICAgIH07XG5cbiAgICBkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKCcudGxfc2VjdGlvbndpemFyZCB0cicpLmZvckVhY2goaW5pdCk7XG5cbiAgICBuZXcgTXV0YXRpb25PYnNlcnZlcihmdW5jdGlvbihtdXRhdGlvbnNMaXN0KSB7XG4gICAgICAgIGZvciAoY29uc3QgbXV0YXRpb24gb2YgbXV0YXRpb25zTGlzdCkge1xuICAgICAgICAgICAgaWYgKG11dGF0aW9uLnR5cGUgPT09ICdjaGlsZExpc3QnKSB7XG4gICAgICAgICAgICAgICAgbXV0YXRpb24uYWRkZWROb2Rlcy5mb3JFYWNoKGZ1bmN0aW9uKGVsZW1lbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKGVsZW1lbnQubWF0Y2hlcyAmJiBlbGVtZW50Lm1hdGNoZXMoJy50bF9zZWN0aW9ud2l6YXJkIHRyLCAudGxfc2VjdGlvbndpemFyZCB0ciAqJykpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGluaXQoZWxlbWVudC5jbG9zZXN0KCd0cicpKTtcbiAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIH0pXG4gICAgICAgICAgICB9XG4gICAgICAgIH1cbiAgICB9KS5vYnNlcnZlKGRvY3VtZW50LCB7XG4gICAgICAgIGF0dHJpYnV0ZXM6IGZhbHNlLFxuICAgICAgICBjaGlsZExpc3Q6IHRydWUsXG4gICAgICAgIHN1YnRyZWU6IHRydWVcbiAgICB9KTtcbn0pKCk7XG4iLCIoZnVuY3Rpb24oKSB7XG4gICAgY29uc3QgaW5pdGlhbGl6ZWQgPSBbXTtcblxuICAgIGNvbnN0IHRpcCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ2RpdicpO1xuICAgIHRpcC5zZXRBdHRyaWJ1dGUoJ3JvbGUnLCAndG9vbHRpcCcpO1xuICAgIHRpcC5jbGFzc0xpc3QuYWRkKCd0aXAnKVxuICAgIHRpcC5zdHlsZS5wb3NpdGlvbiA9ICdhYnNvbHV0ZSc7XG4gICAgdGlwLnN0eWxlLmRpc3BsYXkgPSAnbm9uZSc7XG5cbiAgICBjb25zdCBpbml0ID0gZnVuY3Rpb24oZWwsIHgsIHksIHVzZUNvbnRlbnQpIHtcbiAgICAgICAgaWYgKGluaXRpYWxpemVkLmluY2x1ZGVzKGVsKSkge1xuICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgaW5pdGlhbGl6ZWQucHVzaChlbCk7XG5cbiAgICAgICAgbGV0IHRleHQsIHRpbWVyO1xuXG4gICAgICAgIFsnbW91c2VlbnRlcicsICd0b3VjaGVuZCddLmZvckVhY2goKGV2ZW50KSA9PiB7XG4gICAgICAgICAgICBlbC5hZGRFdmVudExpc3RlbmVyKGV2ZW50LCBmdW5jdGlvbihlKSB7XG4gICAgICAgICAgICAgICAgaWYgKHVzZUNvbnRlbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgdGV4dCA9IGVsLmlubmVySFRNTDtcbiAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICB0ZXh0ID0gZWwuZ2V0QXR0cmlidXRlKCd0aXRsZScpO1xuICAgICAgICAgICAgICAgICAgICBlbC5zZXRBdHRyaWJ1dGUoJ2RhdGEtb3JpZ2luYWwtdGl0bGUnLCB0ZXh0KTtcbiAgICAgICAgICAgICAgICAgICAgZWwucmVtb3ZlQXR0cmlidXRlKCd0aXRsZScpO1xuICAgICAgICAgICAgICAgICAgICB0ZXh0ID0gdGV4dD8ucmVwbGFjZSgvJi9nLCAnJmFtcDsnKS5yZXBsYWNlKC88L2csICcmbHQ7JykucmVwbGFjZSgvXCIvZywgJyZxdW90OycpLnJlcGxhY2UoLycvZywgJyZhcG9zOycpO1xuICAgICAgICAgICAgICAgIH1cbiAgICBcbiAgICAgICAgICAgICAgICBpZiAoIXRleHQpIHtcbiAgICAgICAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgICAgIH1cbiAgICBcbiAgICAgICAgICAgICAgICBjbGVhclRpbWVvdXQodGltZXIpO1xuICAgICAgICAgICAgICAgIHRpcC5zdHlsZS53aWxsQ2hhbmdlID0gJ2Rpc3BsYXksY29udGVudHMnO1xuICAgIFxuICAgICAgICAgICAgICAgIHRpbWVyID0gc2V0VGltZW91dChmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICAgICAgY29uc3QgcG9zaXRpb24gPSBlbC5nZXRCb3VuZGluZ0NsaWVudFJlY3QoKTtcbiAgICAgICAgICAgICAgICAgICAgY29uc3QgcnRsID0gZ2V0Q29tcHV0ZWRTdHlsZShlbCkuZGlyZWN0aW9uID09PSAncnRsJztcbiAgICAgICAgICAgICAgICAgICAgY29uc3QgY2xpZW50V2lkdGggPSBkb2N1bWVudC5odG1sLmNsaWVudFdpZHRoO1xuICAgIFxuICAgICAgICAgICAgICAgICAgICBpZiAoKHJ0bCAmJiBwb3NpdGlvbi54IDwgMjAwKSB8fCAoIXJ0bCAmJiBwb3NpdGlvbi54IDwgKGNsaWVudFdpZHRoIC0gMjAwKSkpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHRpcC5zdHlsZS5sZWZ0ID0gYCR7KHdpbmRvdy5zY3JvbGxYICsgcG9zaXRpb24ubGVmdCArIHgpfXB4YDtcbiAgICAgICAgICAgICAgICAgICAgICAgIHRpcC5zdHlsZS5yaWdodCA9ICdhdXRvJztcbiAgICAgICAgICAgICAgICAgICAgICAgIHRpcC5jbGFzc0xpc3QucmVtb3ZlKCd0aXAtLXJ0bCcpO1xuICAgICAgICAgICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgICAgICAgICAgdGlwLnN0eWxlLmxlZnQgPSAnYXV0byc7XG4gICAgICAgICAgICAgICAgICAgICAgICB0aXAuc3R5bGUucmlnaHQgPSBgJHsoY2xpZW50V2lkdGggLSB3aW5kb3cuc2Nyb2xsWCAtIHBvc2l0aW9uLnJpZ2h0ICsgeCl9cHhgO1xuICAgICAgICAgICAgICAgICAgICAgICAgdGlwLmNsYXNzTGlzdC5hZGQoJ3RpcC0tcnRsJyk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICBcbiAgICAgICAgICAgICAgICAgICAgdGlwLmlubmVySFRNTCA9IGA8ZGl2PiR7dGV4dH08L2Rpdj5gO1xuICAgICAgICAgICAgICAgICAgICB0aXAuc3R5bGUudG9wID0gYCR7KHdpbmRvdy5zY3JvbGxZICsgcG9zaXRpb24udG9wICsgeSl9cHhgO1xuICAgICAgICAgICAgICAgICAgICB0aXAuc3R5bGUuZGlzcGxheSA9ICdibG9jayc7XG4gICAgICAgICAgICAgICAgICAgIHRpcC5zdHlsZS53aWxsQ2hhbmdlID0gJ2F1dG8nO1xuICAgIFxuICAgICAgICAgICAgICAgICAgICBpZiAoIXRpcC5wYXJlbnROb2RlICYmIGRvY3VtZW50LmJvZHkpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGRvY3VtZW50LmJvZHkuYXBwZW5kKHRpcCk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9LCAnbW91c2VlbnRlcicgPT09IGUudHlwZSA/IDEwMDAgOiAwKVxuICAgICAgICAgICAgfSlcbiAgICAgICAgfSk7XG5cbiAgICAgICAgY29uc3QgY2xvc2UgPSAoZSkgPT4ge1xuICAgICAgICAgICAgaWYgKGVsLmhhc0F0dHJpYnV0ZSgnZGF0YS1vcmlnaW5hbC10aXRsZScpKSB7XG4gICAgICAgICAgICAgICAgaWYgKCFlbC5oYXNBdHRyaWJ1dGUoJ3RpdGxlJykpIHtcbiAgICAgICAgICAgICAgICAgICAgZWwuc2V0QXR0cmlidXRlKCd0aXRsZScsIGVsLmdldEF0dHJpYnV0ZSgnZGF0YS1vcmlnaW5hbC10aXRsZScpKTtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICBlbC5yZW1vdmVBdHRyaWJ1dGUoJ2RhdGEtb3JpZ2luYWwtdGl0bGUnKTtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgY2xlYXJUaW1lb3V0KHRpbWVyKVxuICAgICAgICAgICAgdGlwLnN0eWxlLndpbGxDaGFuZ2UgPSAnYXV0byc7XG5cbiAgICAgICAgICAgIGlmICh0aXAuc3R5bGUuZGlzcGxheSA9PT0gJ2Jsb2NrJykge1xuICAgICAgICAgICAgICAgIHRpcC5zdHlsZS53aWxsQ2hhbmdlID0gJ2Rpc3BsYXknO1xuICAgICAgICAgICAgICAgIHRpbWVyID0gc2V0VGltZW91dChmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgICAgICAgdGlwLnN0eWxlLmRpc3BsYXkgPSAnbm9uZSc7XG4gICAgICAgICAgICAgICAgICAgIHRpcC5zdHlsZS53aWxsQ2hhbmdlID0gJ2F1dG8nO1xuICAgICAgICAgICAgICAgIH0sICdtb3VzZWxlYXZlJyA9PT0gZS50eXBlID8gMTAwIDogMClcbiAgICAgICAgICAgIH1cbiAgICAgICAgfTtcblxuICAgICAgICBlbC5hZGRFdmVudExpc3RlbmVyKCdtb3VzZWxlYXZlJywgY2xvc2UpO1xuXG4gICAgICAgIC8vIENsb3NlIHRvb2x0aXAgd2hlbiB0b3VjaGluZyBhbnl3aGVyZSBlbHNlXG4gICAgICAgIGRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ3RvdWNoc3RhcnQnLCAoZSkgPT4ge1xuICAgICAgICAgICAgaWYgKGVsLmNvbnRhaW5zKGUudGFyZ2V0KSkge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgY2xvc2UoZSk7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIGNvbnN0IGFjdGlvbiA9IGVsLmNsb3Nlc3QoJ2J1dHRvbiwgYScpO1xuXG4gICAgICAgIC8vIEhpZGUgdG9vbHRpcCB3aGVuIGNsaWNraW5nIGEgYnV0dG9uICh1c3VhbGx5IGFuIG9wZXJhdGlvbiBpY29uIGluIGEgd2l6YXJkIHdpZGdldClcbiAgICAgICAgaWYgKGFjdGlvbikge1xuICAgICAgICAgICAgYWN0aW9uLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgZnVuY3Rpb24oKSB7XG4gICAgICAgICAgICAgICAgY2xlYXJUaW1lb3V0KHRpbWVyKTtcbiAgICAgICAgICAgICAgICB0aXAuc3R5bGUuZGlzcGxheSA9ICdub25lJztcbiAgICAgICAgICAgICAgICB0aXAuc3R5bGUud2lsbENoYW5nZSA9ICdhdXRvJztcbiAgICAgICAgICAgIH0pXG4gICAgICAgIH1cbiAgICB9XG5cbiAgICBmdW5jdGlvbiBzZWxlY3Qobm9kZSwgc2VsZWN0b3IpIHtcbiAgICAgICAgaWYgKG5vZGUubWF0Y2hlcyhzZWxlY3RvcikpIHtcbiAgICAgICAgICAgIHJldHVybiBbbm9kZSwgLi4ubm9kZS5xdWVyeVNlbGVjdG9yQWxsKHNlbGVjdG9yKV07XG4gICAgICAgIH1cblxuICAgICAgICByZXR1cm4gbm9kZS5xdWVyeVNlbGVjdG9yQWxsKHNlbGVjdG9yKTtcbiAgICB9XG5cbiAgICBmdW5jdGlvbiBzZXR1cChub2RlKSB7XG4gICAgICAgIHNlbGVjdChub2RlLCAncC50bF90aXAnKS5mb3JFYWNoKGZ1bmN0aW9uKGVsKSB7XG4gICAgICAgICAgICBpbml0KGVsLCAwLCAyMywgdHJ1ZSk7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIHNlbGVjdChub2RlLCAnI2hvbWUnKS5mb3JFYWNoKGZ1bmN0aW9uKGVsKSB7XG4gICAgICAgICAgICBpbml0KGVsLCA2LCA0Mik7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIHNlbGVjdChub2RlLCAnI3RtZW51IGFbdGl0bGVdJykuZm9yRWFjaChmdW5jdGlvbihlbCkge1xuICAgICAgICAgICAgaW5pdChlbCwgMCwgNDIpO1xuICAgICAgICB9KTtcblxuICAgICAgICBzZWxlY3Qobm9kZSwgJ2FbdGl0bGVdW2NsYXNzXj1cImdyb3VwLVwiXScpLmZvckVhY2goZnVuY3Rpb24oZWwpIHtcbiAgICAgICAgICAgIGluaXQoZWwsIC02LCAyNyk7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIHNlbGVjdChub2RlLCAnYVt0aXRsZV0ubmF2aWdhdGlvbicpLmZvckVhY2goZnVuY3Rpb24oZWwpIHtcbiAgICAgICAgICAgIGluaXQoZWwsIDI1LCAzMik7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIHNlbGVjdChub2RlLCAnaW1nW3RpdGxlXScpLmZvckVhY2goZnVuY3Rpb24oZWwpIHtcbiAgICAgICAgICAgIGluaXQoZWwsIC05LCBlbC5jbGFzc0xpc3QuY29udGFpbnMoJ2dpbWFnZScpID8gNjAgOiAzMCk7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIHNlbGVjdChub2RlLCAnYVt0aXRsZV0nKS5mb3JFYWNoKGZ1bmN0aW9uKGVsKSB7XG4gICAgICAgICAgICBpZiAoZWwuY2xhc3NMaXN0LmNvbnRhaW5zKCdwaWNrZXItd2l6YXJkJykpIHtcbiAgICAgICAgICAgICAgICBpbml0KGVsLCAtNCwgMzApO1xuICAgICAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgICAgICBpbml0KGVsLCAtOSwgMzApO1xuICAgICAgICAgICAgfVxuICAgICAgICB9KTtcblxuICAgICAgICBzZWxlY3Qobm9kZSwgJ2J1dHRvblt0aXRsZV0nKS5mb3JFYWNoKGZ1bmN0aW9uKGVsKSB7XG4gICAgICAgICAgICBpZiAoZWwuY2xhc3NMaXN0LmNvbnRhaW5zKCd1bnNlbGVjdGFibGUnKSkge1xuICAgICAgICAgICAgICAgIGluaXQoZWwsIC00LCAyMCk7XG4gICAgICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgICAgIGluaXQoZWwsIC05LCAzMCk7XG4gICAgICAgICAgICB9XG4gICAgICAgIH0pO1xuXG4gICAgICAgIFsnaW5wdXRbdGl0bGVdJywgJ3RpbWVbdGl0bGVdJywgJ3NwYW5bdGl0bGVdJ10uZm9yRWFjaChmdW5jdGlvbihzZWxlY3Rvcikge1xuICAgICAgICAgICAgc2VsZWN0KG5vZGUsIHNlbGVjdG9yKS5mb3JFYWNoKGZ1bmN0aW9uKGVsKSB7XG4gICAgICAgICAgICAgICAgaW5pdChlbCwgLTksICgoc2VsZWN0b3IgPT09ICd0aW1lW3RpdGxlXScgfHwgc2VsZWN0b3IgPT09ICdzcGFuW3RpdGxlXScpID8gMjYgOiAzMCkpO1xuICAgICAgICAgICAgfSk7XG4gICAgICAgIH0pO1xuICAgIH1cblxuICAgIHNldHVwKGRvY3VtZW50LmRvY3VtZW50RWxlbWVudCk7XG5cbiAgICBuZXcgTXV0YXRpb25PYnNlcnZlcihmdW5jdGlvbihtdXRhdGlvbnNMaXN0KSB7XG4gICAgICAgIGZvciAoY29uc3QgbXV0YXRpb24gb2YgbXV0YXRpb25zTGlzdCkge1xuICAgICAgICAgICAgaWYgKG11dGF0aW9uLnR5cGUgPT09ICdjaGlsZExpc3QnKSB7XG4gICAgICAgICAgICAgICAgbXV0YXRpb24uYWRkZWROb2Rlcy5mb3JFYWNoKGZ1bmN0aW9uKGVsZW1lbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgaWYgKGVsZW1lbnQubWF0Y2hlcyAmJiBlbGVtZW50LnF1ZXJ5U2VsZWN0b3JBbGwpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHNldHVwKGVsZW1lbnQpO1xuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfSlcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgIH0pLm9ic2VydmUoZG9jdW1lbnQsIHtcbiAgICAgICAgYXR0cmlidXRlczogZmFsc2UsXG4gICAgICAgIGNoaWxkTGlzdDogdHJ1ZSxcbiAgICAgICAgc3VidHJlZTogdHJ1ZVxuICAgIH0pO1xufSkoKTtcbiIsIi8vIFRoZSBtb2R1bGUgY2FjaGVcbnZhciBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX18gPSB7fTtcblxuLy8gVGhlIHJlcXVpcmUgZnVuY3Rpb25cbmZ1bmN0aW9uIF9fd2VicGFja19yZXF1aXJlX18obW9kdWxlSWQpIHtcblx0Ly8gQ2hlY2sgaWYgbW9kdWxlIGlzIGluIGNhY2hlXG5cdHZhciBjYWNoZWRNb2R1bGUgPSBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX19bbW9kdWxlSWRdO1xuXHRpZiAoY2FjaGVkTW9kdWxlICE9PSB1bmRlZmluZWQpIHtcblx0XHRyZXR1cm4gY2FjaGVkTW9kdWxlLmV4cG9ydHM7XG5cdH1cblx0Ly8gQ2hlY2sgaWYgbW9kdWxlIGV4aXN0cyAoZGV2ZWxvcG1lbnQgb25seSlcblx0aWYgKF9fd2VicGFja19tb2R1bGVzX19bbW9kdWxlSWRdID09PSB1bmRlZmluZWQpIHtcblx0XHR2YXIgZSA9IG5ldyBFcnJvcihcIkNhbm5vdCBmaW5kIG1vZHVsZSAnXCIgKyBtb2R1bGVJZCArIFwiJ1wiKTtcblx0XHRlLmNvZGUgPSAnTU9EVUxFX05PVF9GT1VORCc7XG5cdFx0dGhyb3cgZTtcblx0fVxuXHQvLyBDcmVhdGUgYSBuZXcgbW9kdWxlIChhbmQgcHV0IGl0IGludG8gdGhlIGNhY2hlKVxuXHR2YXIgbW9kdWxlID0gX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fW21vZHVsZUlkXSA9IHtcblx0XHQvLyBubyBtb2R1bGUuaWQgbmVlZGVkXG5cdFx0Ly8gbm8gbW9kdWxlLmxvYWRlZCBuZWVkZWRcblx0XHRleHBvcnRzOiB7fVxuXHR9O1xuXG5cdC8vIEV4ZWN1dGUgdGhlIG1vZHVsZSBmdW5jdGlvblxuXHRfX3dlYnBhY2tfbW9kdWxlc19fW21vZHVsZUlkXShtb2R1bGUsIG1vZHVsZS5leHBvcnRzLCBfX3dlYnBhY2tfcmVxdWlyZV9fKTtcblxuXHQvLyBSZXR1cm4gdGhlIGV4cG9ydHMgb2YgdGhlIG1vZHVsZVxuXHRyZXR1cm4gbW9kdWxlLmV4cG9ydHM7XG59XG5cbiIsIi8vIGdldERlZmF1bHRFeHBvcnQgZnVuY3Rpb24gZm9yIGNvbXBhdGliaWxpdHkgd2l0aCBub24taGFybW9ueSBtb2R1bGVzXG5fX3dlYnBhY2tfcmVxdWlyZV9fLm4gPSAobW9kdWxlKSA9PiB7XG5cdHZhciBnZXR0ZXIgPSBtb2R1bGUgJiYgbW9kdWxlLl9fZXNNb2R1bGUgP1xuXHRcdCgpID0+IChtb2R1bGVbJ2RlZmF1bHQnXSkgOlxuXHRcdCgpID0+IChtb2R1bGUpO1xuXHRfX3dlYnBhY2tfcmVxdWlyZV9fLmQoZ2V0dGVyLCB7IGE6IGdldHRlciB9KTtcblx0cmV0dXJuIGdldHRlcjtcbn07IiwiLy8gZGVmaW5lIGdldHRlciBmdW5jdGlvbnMgZm9yIGhhcm1vbnkgZXhwb3J0c1xuX193ZWJwYWNrX3JlcXVpcmVfXy5kID0gKGV4cG9ydHMsIGRlZmluaXRpb24pID0+IHtcblx0Zm9yKHZhciBrZXkgaW4gZGVmaW5pdGlvbikge1xuXHRcdGlmKF9fd2VicGFja19yZXF1aXJlX18ubyhkZWZpbml0aW9uLCBrZXkpICYmICFfX3dlYnBhY2tfcmVxdWlyZV9fLm8oZXhwb3J0cywga2V5KSkge1xuXHRcdFx0T2JqZWN0LmRlZmluZVByb3BlcnR5KGV4cG9ydHMsIGtleSwgeyBlbnVtZXJhYmxlOiB0cnVlLCBnZXQ6IGRlZmluaXRpb25ba2V5XSB9KTtcblx0XHR9XG5cdH1cbn07IiwiX193ZWJwYWNrX3JlcXVpcmVfXy5vID0gKG9iaiwgcHJvcCkgPT4gKE9iamVjdC5wcm90b3R5cGUuaGFzT3duUHJvcGVydHkuY2FsbChvYmosIHByb3ApKSIsIi8vIGRlZmluZSBfX2VzTW9kdWxlIG9uIGV4cG9ydHNcbl9fd2VicGFja19yZXF1aXJlX18uciA9IChleHBvcnRzKSA9PiB7XG5cdGlmKHR5cGVvZiBTeW1ib2wgIT09ICd1bmRlZmluZWQnICYmIFN5bWJvbC50b1N0cmluZ1RhZykge1xuXHRcdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShleHBvcnRzLCBTeW1ib2wudG9TdHJpbmdUYWcsIHsgdmFsdWU6ICdNb2R1bGUnIH0pO1xuXHR9XG5cdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShleHBvcnRzLCAnX19lc01vZHVsZScsIHsgdmFsdWU6IHRydWUgfSk7XG59OyIsImltcG9ydCB7IEFwcGxpY2F0aW9uIH0gZnJvbSAnQGhvdHdpcmVkL3N0aW11bHVzJztcbmltcG9ydCB7IGRlZmluaXRpb25Gb3JNb2R1bGVBbmRJZGVudGlmaWVyLCBpZGVudGlmaWVyRm9yQ29udGV4dEtleSB9IGZyb20gJ0Bob3R3aXJlZC9zdGltdWx1cy13ZWJwYWNrLWhlbHBlcnMnO1xuXG5pbXBvcnQgJy4vc2NyaXB0cy9tb290YW8uanMnO1xuaW1wb3J0ICcuL3NjcmlwdHMvY29yZS5qcyc7XG5pbXBvcnQgJy4vc2NyaXB0cy9saW1pdC1oZWlnaHQuanMnO1xuaW1wb3J0ICcuL3NjcmlwdHMvbW9kdWxld2l6YXJkLmpzJztcbmltcG9ydCAnLi9zY3JpcHRzL3NlY3Rpb253aXphcmQuanMnO1xuaW1wb3J0ICcuL3NjcmlwdHMvdGlwcy5qcyc7XG5cbi8vIFN0YXJ0IHRoZSBTdGltdWx1cyBhcHBsaWNhdGlvblxuY29uc3QgYXBwbGljYXRpb24gPSBBcHBsaWNhdGlvbi5zdGFydCgpO1xuYXBwbGljYXRpb24uZGVidWcgPSBwcm9jZXNzLmVudi5OT0RFX0VOViA9PT0gJ2RldmVsb3BtZW50JztcblxuLy8gUmVnaXN0ZXIgYWxsIGNvbnRyb2xsZXJzIHdpdGggYGNvbnRhby0tYCBwcmVmaXhcbmNvbnN0IGNvbnRleHQgPSByZXF1aXJlLmNvbnRleHQoJy4vY29udHJvbGxlcnMnLCB0cnVlLCAvXFwuanMkLyk7XG5hcHBsaWNhdGlvbi5sb2FkKGNvbnRleHQua2V5cygpXG4gICAgLm1hcCgoa2V5KSA9PiB7XG4gICAgICAgIGNvbnN0IGlkZW50aWZpZXIgPSBpZGVudGlmaWVyRm9yQ29udGV4dEtleShrZXkpO1xuICAgICAgICBpZiAoaWRlbnRpZmllcikge1xuICAgICAgICAgICAgcmV0dXJuIGRlZmluaXRpb25Gb3JNb2R1bGVBbmRJZGVudGlmaWVyKGNvbnRleHQoa2V5KSwgYGNvbnRhby0tJHsgaWRlbnRpZmllciB9YCk7XG4gICAgICAgIH1cbiAgICB9KS5maWx0ZXIoKHZhbHVlKSA9PiB2YWx1ZSlcbik7XG4iXSwibmFtZXMiOlsiQ29udHJvbGxlciIsIl9kZWZhdWx0IiwiX0NvbnRyb2xsZXIiLCJfY2xhc3NDYWxsQ2hlY2siLCJfY2FsbFN1cGVyIiwiYXJndW1lbnRzIiwiX2luaGVyaXRzIiwiX2NyZWF0ZUNsYXNzIiwia2V5IiwidmFsdWUiLCJ3cml0ZSIsIm5hdmlnYXRvciIsImNsaXBib2FyZCIsIndyaXRlVGV4dCIsImNvbnRlbnRWYWx1ZSIsImNsaXBib2FyZEZhbGxiYWNrIiwiYmluZCIsImlucHV0IiwiZG9jdW1lbnQiLCJjcmVhdGVFbGVtZW50IiwiYm9keSIsImFwcGVuZENoaWxkIiwic2VsZWN0Iiwic2V0U2VsZWN0aW9uUmFuZ2UiLCJleGVjQ29tbWFuZCIsInJlbW92ZUNoaWxkIiwiX2RlZmluZVByb3BlcnR5IiwiY29udGVudCIsIlN0cmluZyIsImRlZmF1bHQiLCJwcmVmZXJzRGFyayIsImxvY2FsU3RvcmFnZSIsImdldEl0ZW0iLCJ3aW5kb3ciLCJtYXRjaE1lZGlhIiwibWF0Y2hlcyIsInNldENvbG9yU2NoZW1lIiwiZG9jdW1lbnRFbGVtZW50IiwiZGF0YXNldCIsImNvbG9yU2NoZW1lIiwiYWRkRXZlbnRMaXN0ZW5lciIsImluaXRpYWxpemUiLCJ0b2dnbGUiLCJzZXRMYWJlbCIsImNvbm5lY3QiLCJlbGVtZW50IiwiZGlzY29ubmVjdCIsInJlbW92ZUV2ZW50TGlzdGVuZXIiLCJlIiwicHJldmVudERlZmF1bHQiLCJpc0RhcmsiLCJyZW1vdmVJdGVtIiwic2V0SXRlbSIsInNldFRpbWVvdXQiLCJoYXNMYWJlbFRhcmdldCIsImxhYmVsIiwiaTE4blZhbHVlIiwibGFiZWxUYXJnZXQiLCJ0aXRsZSIsImlubmVyVGV4dCIsImkxOG4iLCJ0eXBlIiwiT2JqZWN0IiwibGlnaHQiLCJkYXJrIiwidXBkYXRlV2l6YXJkIiwib3Blbk1vZGFsIiwicXVlcnlTZWxlY3RvciIsImJ1dHRvbiIsImJ1dHRvbkltYWdlIiwiYXBwZW5kIiwicGFyZW50Tm9kZSIsImNsYXNzTGlzdCIsImFkZCIsImFmdGVyIiwicmVtb3ZlIiwiY2FuRWRpdCIsImNvbmZpZ1ZhbHVlIiwiZGlzYWJsZWQiLCJzcmMiLCJpY29uIiwiaWNvbkRpc2FibGVkIiwiQmFja2VuZCIsIm9wZW5Nb2RhbElmcmFtZSIsInVybCIsImNvbmNhdCIsImhyZWYiLCJpZHMiLCJpbmNsdWRlcyIsIk51bWJlciIsImNvbmZpZyIsInJlYnVpbGROYXZpZ2F0aW9uIiwiY29ubmVjdGVkIiwic2VjdGlvblRhcmdldENvbm5lY3RlZCIsIl90aGlzIiwiaGFzTmF2aWdhdGlvblRhcmdldCIsImxpbmtzIiwic2VjdGlvblRhcmdldHMiLCJmb3JFYWNoIiwiZWwiLCJhY3Rpb24iLCJnZXRBdHRyaWJ1dGUiLCJpZGVudGlmaWVyIiwiZXZlbnQiLCJkaXNwYXRjaCIsInRhcmdldCIsInNjcm9sbEludG9WaWV3IiwibGkiLCJuYXZpZ2F0aW9uVGFyZ2V0IiwicmVwbGFjZUNoaWxkcmVuIiwiX3N1cGVyUHJvcEdldCIsInRvZ2dsZXJNYXAiLCJXZWFrTWFwIiwibmV4dElkIiwib3BlcmF0aW9uVGFyZ2V0Q29ubmVjdGVkIiwidXBkYXRlT3BlcmF0aW9uIiwibm9kZVRhcmdldENvbm5lY3RlZCIsIm5vZGUiLCJzdHlsZSIsImdldENvbXB1dGVkU3R5bGUiLCJwYWRkaW5nIiwicGFyc2VGbG9hdCIsInBhZGRpbmdUb3AiLCJwYWRkaW5nQm90dG9tIiwiaGVpZ2h0IiwiY2xpZW50SGVpZ2h0IiwibWF4VmFsdWUiLCJpZCIsIm92ZXJmbG93IiwibWF4SGVpZ2h0Iiwic2V0QXR0cmlidXRlIiwiZXhwYW5kVmFsdWUiLCJpbm5lckhUTUwiLCJ0b2dnbGVyIiwic2V0Iiwibm9kZVRhcmdldERpc2Nvbm5lY3RlZCIsImhhcyIsImdldCIsImNvbGxhcHNlIiwiZXhwYW5kIiwiY29sbGFwc2VWYWx1ZSIsInRvZ2dsZUFsbCIsIl90aGlzMiIsImlzRXhwYW5kZWQiLCJoYXNFeHBhbmRlZCIsImFsdEtleSIsIm5vZGVUYXJnZXRzIiwia2V5cHJlc3MiLCJfdGhpczMiLCJoYXNPcGVyYXRpb25UYXJnZXQiLCJoYXNUb2dnbGVycyIsImZpbmQiLCJleHBhbmRlZCIsIm9wZXJhdGlvblRhcmdldCIsImRpc3BsYXkiLCJtYXAiLCJqb2luIiwiY29sbGFwc2VBbGxWYWx1ZSIsImNvbGxhcHNlQWxsVGl0bGVWYWx1ZSIsImV4cGFuZEFsbFZhbHVlIiwiZXhwYW5kQWxsVGl0bGVWYWx1ZSIsIl90aGlzNCIsIm1heCIsImV4cGFuZEFsbCIsImV4cGFuZEFsbFRpdGxlIiwiY29sbGFwc2VBbGwiLCJjb2xsYXBzZUFsbFRpdGxlIiwiZGVsZXRlIiwiaW5wdXRUYXJnZXRzIiwic3RvcmUiLCJvZmZzZXQiLCJzY3JvbGxUbyIsInRvcCIsImJlaGF2aW9yIiwiYmVoYXZpb3JWYWx1ZSIsImJsb2NrIiwiYmxvY2tWYWx1ZSIsInNjcm9sbFRvVGFyZ2V0Q29ubmVjdGVkIiwic2Nyb2xsVG9UYXJnZXQiLCJhdXRvRm9jdXNUYXJnZXRDb25uZWN0ZWQiLCJhdXRvRm9jdXMiLCJhdXRvRm9jdXNUYXJnZXQiLCJyZWFkb25seSIsIm9mZnNldFdpZHRoIiwib2Zmc2V0SGVpZ2h0IiwiY2xvc2VzdCIsImF1dG9jb21wbGV0ZSIsImZvY3VzIiwic2Nyb2xsVG9wIiwiZGlzY2FyZCIsInNlc3Npb25TdG9yYWdlIiwic2Vzc2lvbktleVZhbHVlIiwicGFyc2VJbnQiLCJ1bmRlZmluZWQiLCJhZnRlckxvYWQiLCJhcHBsaWNhdGlvbiIsImxvYWRGYWxsYmFjayIsIlByb21pc2UiLCJyZXNvbHZlIiwicmVqZWN0IiwiY29udHJvbGxlciIsImdldENvbnRyb2xsZXJGb3JFbGVtZW50QW5kSWRlbnRpZmllciIsImNvbnRyb2xsZXJBdHRyaWJ1dGUiLCJzY2hlbWEiLCJpbml0U2Nyb2xsT2Zmc2V0IiwiY29uc29sZSIsIndhcm4iLCJnZXRTY3JvbGxPZmZzZXQiLCJ0aGVuIiwic2Vzc2lvbktleSIsInF1ZXJ5U2VsZWN0b3JBbGwiLCJsZW5ndGgiLCJjb2xsYXBzZWRDbGFzcyIsImNvbnRhaW5zIiwic2V0QXJpYUV4cGFuZGVkIiwib3BlbiIsImNsb3NlIiwic3RvcmVTdGF0ZSIsImZvcm0iLCJpIiwiY2hlY2tWYWxpZGl0eSIsImNsaWNrIiwic3RhdGUiLCJoYXNJZFZhbHVlIiwiaGFzVGFibGVWYWx1ZSIsImZldGNoIiwibG9jYXRpb24iLCJtZXRob2QiLCJoZWFkZXJzIiwiVVJMU2VhcmNoUGFyYW1zIiwiaWRWYWx1ZSIsInRhYmxlIiwidGFibGVWYWx1ZSIsImFyaWFFeHBhbmRlZCIsImFkZENvbnRyb2xsZXIiLCJmcyIsIm1pZ3JhdGVMZWdhY3kiLCJfSlNPTiRwYXJzZSIsIkpTT04iLCJwYXJzZSIsIkFqYXhSZXF1ZXN0IiwidG9nZ2xlRmllbGRzZXQiLCJyZWFkeVN0YXRlIiwiX3JlZiIsImN1cnJlbnRUYXJnZXQiLCJjYXRlZ29yeSIsInBhcmFtcyIsImNvbGxhcHNlZCIsImV4cGFuZFRpdGxlVmFsdWUiLCJjb2xsYXBzZVRpdGxlVmFsdWUiLCJzZW5kUmVxdWVzdCIsInVybFZhbHVlIiwiUkVRVUVTVF9UT0tFTiIsInJlcXVlc3RUb2tlblZhbHVlIiwicmVxdWVzdFRva2VuIiwiZXhwYW5kVGl0bGUiLCJjb2xsYXBzZVRpdGxlIiwidCIsInIiLCJTeW1ib2wiLCJuIiwiaXRlcmF0b3IiLCJvIiwidG9TdHJpbmdUYWciLCJjIiwicHJvdG90eXBlIiwiR2VuZXJhdG9yIiwidSIsImNyZWF0ZSIsIl9yZWdlbmVyYXRvckRlZmluZTIiLCJmIiwicCIsInkiLCJHIiwidiIsImEiLCJkIiwibCIsIlR5cGVFcnJvciIsImNhbGwiLCJkb25lIiwiR2VuZXJhdG9yRnVuY3Rpb24iLCJHZW5lcmF0b3JGdW5jdGlvblByb3RvdHlwZSIsImdldFByb3RvdHlwZU9mIiwic2V0UHJvdG90eXBlT2YiLCJfX3Byb3RvX18iLCJkaXNwbGF5TmFtZSIsIl9yZWdlbmVyYXRvciIsInciLCJtIiwiZGVmaW5lUHJvcGVydHkiLCJfcmVnZW5lcmF0b3JEZWZpbmUiLCJfaW52b2tlIiwiZW51bWVyYWJsZSIsImNvbmZpZ3VyYWJsZSIsIndyaXRhYmxlIiwiYXN5bmNHZW5lcmF0b3JTdGVwIiwiX2FzeW5jVG9HZW5lcmF0b3IiLCJhcHBseSIsIl9uZXh0IiwiX3Rocm93IiwiX2RlZmluZVByb3BlcnRpZXMiLCJfdG9Qcm9wZXJ0eUtleSIsIl9nZXRQcm90b3R5cGVPZiIsIl9wb3NzaWJsZUNvbnN0cnVjdG9yUmV0dXJuIiwiX2lzTmF0aXZlUmVmbGVjdENvbnN0cnVjdCIsIlJlZmxlY3QiLCJjb25zdHJ1Y3QiLCJjb25zdHJ1Y3RvciIsIl90eXBlb2YiLCJfYXNzZXJ0VGhpc0luaXRpYWxpemVkIiwiUmVmZXJlbmNlRXJyb3IiLCJCb29sZWFuIiwidmFsdWVPZiIsIl9zZXRQcm90b3R5cGVPZiIsIl90b1ByaW1pdGl2ZSIsInRvUHJpbWl0aXZlIiwiY2hpbGRUYXJnZXRDb25uZWN0ZWQiLCJ0b2dnbGVUb2dnbGVyIiwibGV2ZWwiLCJmb2xkZXIiLCJpdGVtIiwic2hvd0NoaWxkIiwiZXhwYW5kVG9nZ2xlciIsInVwZGF0ZVN0YXRlIiwiaGlkZUNoaWxkIiwiY29sbGFwc2VUb2dnbGVyIiwiZmV0Y2hDaGlsZCIsImxvYWRUb2dnbGVyIiwiZW5hYmxlZCIsIl9mZXRjaENoaWxkIiwiX2NhbGxlZSIsInNlYXJjaCIsInJlc3BvbnNlIiwidHh0IiwidWwiLCJpc0ZvbGRlciIsInBhcmVudCIsIm5leHQiLCJfY29udGV4dCIsIlVSTCIsInNlYXJjaFBhcmFtcyIsInJlZmVyZXJJZFZhbHVlIiwidG9TdHJpbmciLCJsb2FkQWN0aW9uVmFsdWUiLCJvayIsInRleHQiLCJtb2RlVmFsdWUiLCJ0eXBlT2YiLCJ0YWdOYW1lIiwibmV4dEVsZW1lbnRTaWJsaW5nIiwiYmVmb3JlIiwiZGlzcGF0Y2hFdmVudCIsIkN1c3RvbUV2ZW50IiwiZmlyZUV2ZW50IiwiX3giLCJfeDIiLCJfeDMiLCJfeDQiLCJfdG9nZ2xlQWxsIiwiX2NhbGxlZTIiLCJwcm9taXNlcyIsIl9jb250ZXh0MiIsImhhc0V4cGFuZGVkUm9vdCIsInVwZGF0ZUFsbFN0YXRlIiwidG9nZ2xlVGFyZ2V0cyIsImNoaWxkVGFyZ2V0cyIsInB1c2giLCJhbGwiLCJfeDUiLCJfdXBkYXRlU3RhdGUiLCJfY2FsbGVlMyIsIl9jb250ZXh0MyIsInRvZ2dsZUFjdGlvblZhbHVlIiwiX3g2IiwiX3g3IiwiX3g4IiwiX3VwZGF0ZUFsbFN0YXRlIiwiX2NhbGxlZTQiLCJfY29udGV4dDQiLCJfeDkiLCJfeDAiLCJyb290Q2hpbGRUYXJnZXRzIiwibW9kZSIsInRvZ2dsZUFjdGlvbiIsImxvYWRBY3Rpb24iLCJyZWZlcmVySWQiLCJ0b2dnbGVOYXZpZ2F0aW9uIiwiJCIsImdldFBhcmVudCIsImhhc0NsYXNzIiwicmVtb3ZlQ2xhc3MiLCJDb250YW8iLCJsYW5nIiwiUmVxdWVzdCIsInBvc3QiLCJyZXF1ZXN0X3Rva2VuIiwiYWRkQ2xhc3MiLCJ0b2dnbGVTdHJ1Y3R1cmUiLCJnZXRTdHlsZSIsInNldFN0eWxlIiwiZmllbGQiLCJldmFsU2NyaXB0cyIsIm9uUmVxdWVzdCIsImRpc3BsYXlCb3giLCJsb2FkaW5nIiwib25TdWNjZXNzIiwiRWxlbWVudCIsImluamVjdCIsImdldE5leHQiLCJnZXRFbGVtZW50cyIsImVhY2giLCJyZXBsYWNlIiwicmVmZXJlcl9pZCIsImhpZGVCb3giLCJ0b2dnbGVGaWxlTWFuYWdlciIsInRvZ2dsZVN1YnBhbGV0dGUiLCJjaGVja2VkIiwidXBkYXRlVmVyc2lvbk51bWJlciIsImpzb24iLCJkaXYiLCJqYXZhc2NyaXB0Iiwic3RyIiwibWF0Y2giLCJBc3NldCIsIm9uTG9hZCIsIkJyb3dzZXIiLCJleGVjIiwiaHRtbCIsImZpZWxkcyIsImVsZW1lbnRzIiwiVkVSU0lPTl9OVU1CRVIiLCJ0b2dnbGVGaWVsZCIsInJvd0ljb24iLCJpbWciLCJpbWFnZXMiLCJwdWJsaXNoZWQiLCJwYSIsImdldFByZXZpb3VzIiwiZ2V0Rmlyc3QiLCJnZXRFbGVtZW50IiwiSFRNTEVsZW1lbnQiLCJub2RlTmFtZSIsInRvTG93ZXJDYXNlIiwiaW5kZXhPZiIsIm5ld1NyYyIsInNsaWNlIiwibGFzdEluZGV4T2YiLCJpbWFnZSIsInRvZ2dsZUNoZWNrYm94R3JvdXAiLCJtZXNzYWdlIiwiYm94Iiwib3ZlcmxheSIsInNjcm9sbCIsImdldFNjcm9sbCIsImN1cnJlbnRJZCIsInBvcHVwV2luZG93IiwidGhlbWVQYXRoIiwic2NyaXB0X3VybCIsInRoZW1lIiwib3Blbk1vZGFsV2luZG93Iiwid2lkdGgiLCJTaW1wbGVNb2RhbCIsIm9uU2hvdyIsIm9uSGlkZSIsInNob3ciLCJvcGVuTW9kYWxJbWFnZSIsIm9wdGlvbnMiLCJfb3B0JHRpdGxlIiwib3B0IiwibWF4V2lkdGgiLCJnZXRTaXplIiwieCIsInRvSW50IiwiTWF0aCIsIm1pbiIsIk0iLCJfb3B0JHRpdGxlMiIsIm9wZW5Nb2RhbFNlbGVjdG9yIiwiX29wdCR0aXRsZTMiLCJhZGRCdXR0b24iLCJjYW5jZWwiLCJidXR0b25zIiwiaGlkZSIsImZybSIsImZyYW1lcyIsInZhbCIsImlucCIsInBpY2tlclZhbHVlIiwic0luZGV4IiwiYWxlcnQiLCJnZXRFbGVtZW50QnlJZCIsImdldEVsZW1lbnRzQnlUYWdOYW1lIiwic3BsaWNlIiwiY2FsbGJhY2siLCJvcGVuTW9kYWxCcm93c2VyIiwiZmllbGRfbmFtZSIsIndpbiIsInNvdXJjZSIsInJvdXRlcyIsImJhY2tlbmRfcGlja2VyIiwiYXV0b1N1Ym1pdCIsIkV2ZW50IiwiaGlkZGVuIiwic3VibWl0IiwidlNjcm9sbFRvIiwiYWRkRXZlbnQiLCJ0b2dnbGVDaGVja2JveGVzIiwiaXRlbXMiLCIkJCIsInN0YXR1cyIsInN1YnN0ciIsImNscyIsImNsYXNzTmFtZSIsImNieCIsImNoZWNrYm94IiwidG9nZ2xlQ2hlY2tib3hFbGVtZW50cyIsIm1ha2VQYXJlbnRWaWV3U29ydGFibGUiLCJkcyIsIlNjcm9sbGVyIiwib25DaGFuZ2UiLCJsaXN0IiwiU29ydGFibGVzIiwiY29uc3RyYWluIiwib3BhY2l0eSIsIm9uU3RhcnQiLCJzdGFydCIsIm9uQ29tcGxldGUiLCJzdG9wIiwib25Tb3J0Iiwid3JhcExldmVsIiwiZGl2cyIsImdldENoaWxkcmVuIiwiaGFuZGxlIiwiYWN0aXZlIiwicGlkIiwibWFrZU11bHRpU3JjU29ydGFibGUiLCJvaWQiLCJlbHMiLCJsaXMiLCJzcGxpdCIsImoiLCJkaWQiLCJmaWx0ZXIiLCJkaXNwb3NlIiwiZW5hYmxlRmlsZVRyZWVEcmFnQW5kRHJvcCIsImRyYWdIYW5kbGUiLCJkcmFnRWxlbWVudCIsInJpZ2h0Q2xpY2siLCJjbG9uZUJhc2UiLCJjbG9uZSIsImN1cnJlbnRIb3ZlciIsImN1cnJlbnRIb3ZlclRpbWUiLCJleHBhbmRMaW5rIiwic2V0UG9zaXRpb24iLCJwYWdlIiwiZ2V0T2Zmc2V0UGFyZW50IiwiZ2V0UG9zaXRpb24iLCJtb3ZlIiwiRHJhZyIsIk1vdmUiLCJkcm9wcGFibGVzIiwidW5EcmFnZ2FibGVUYWdzIiwibW9kaWZpZXJzIiwib25FbnRlciIsImRyb3BwYWJsZSIsImZpeERyb3BwYWJsZSIsIkRhdGUiLCJnZXRUaW1lIiwiY3JlYXRlRXZlbnQiLCJpbml0RXZlbnQiLCJvbkFqYXgiLCJyZW1vdmVFdmVudCIsIm9uQ2FuY2VsIiwiZGVzdHJveSIsIm9uS2V5dXAiLCJvbkRyb3AiLCJkZWNvZGVVUklDb21wb25lbnQiLCJlbmNvZGVVUklDb21wb25lbnQiLCJvbkxlYXZlIiwibGlzdFdpemFyZCIsIm1ha2VTb3J0YWJsZSIsImFkZEV2ZW50c1RvIiwiY29tbWFuZCIsInByZXZpb3VzIiwiYnQiLCJoYXNFdmVudCIsImdldFByb3BlcnR5Iiwia2V5Q29kZSIsInRhYmxlV2l6YXJkIiwidGhlYWQiLCJ0Ym9keSIsInJvd3MiLCJ0ZXh0YXJlYSIsImNoaWxkcmVuIiwibmFtZSIsInRyIiwiaGVhZCIsImN1cnJlbnQiLCJudHIiLCJpbmRleCIsImdldEluZGV4IiwiaGVhZEZpcnN0IiwiZ2V0TGFzdCIsInRkIiwiY29scyIsInRhYmxlV2l6YXJkUmVzaXplIiwiZmFjdG9yIiwic2l6ZSIsInJvdW5kIiwibGltaXQiLCJjaHVua3MiLCJ0YWJsZVdpemFyZFNldFdpZHRoIiwid3JhcCIsImdldENvbXB1dGVkU2l6ZSIsIm9wdGlvbnNXaXphcmQiLCJrZXlWYWx1ZVdpemFyZCIsImNoZWNrYm94V2l6YXJkIiwiY29udGFpbmVyIiwic3BhbiIsIm5zcGFuIiwiZW5hYmxlSW1hZ2VTaXplV2lkZ2V0cyIsIndpZHRoSW5wdXQiLCJoZWlnaHRJbnB1dCIsInVwZGF0ZSIsInJlYWRPbmx5IiwiZGltZW5zaW9ucyIsImdldFNlbGVjdGVkIiwiZW5hYmxlVG9nZ2xlU2VsZWN0Iiwic2hpZnRUb2dnbGUiLCJ0aGlzSW5kZXgiLCJjaGVja2JveGVzIiwic3RhcnRJbmRleCIsImZyb20iLCJ0byIsImNsaWNrRXZlbnQiLCJsaW1pdFRvZ2dsZXIiLCJzaGlmdCIsImJvdW5kRXZlbnQiLCJyZXRyaWV2ZSIsInN0b3BQcm9wYWdhdGlvbiIsImVkaXRQcmV2aWV3V2l6YXJkIiwiaW1hZ2VFbGVtZW50IiwiaW5wdXRFbGVtZW50cyIsImlzRHJhd2luZyIsInBhcnRFbGVtZW50Iiwic3RhcnRQb3MiLCJnZXRTY2FsZSIsInVwZGF0ZUltYWdlIiwic2NhbGUiLCJpbWFnZVNpemUiLCJzZXRTdHlsZXMiLCJjb21wdXRlZFRvcCIsImxlZnQiLCJjb21wdXRlZExlZnQiLCJ0b0Zsb2F0IiwidXBkYXRlVmFsdWVzIiwic3R5bGVzIiwiZ2V0U3R5bGVzIiwidmFsdWVzIiwidG9GaXhlZCIsInJlY3QiLCJhYnMiLCJpbml0IiwiY2FwaXRhbGl6ZSIsImdldExlbmd0aCIsImFkZEV2ZW50cyIsIm1vdXNlZG93biIsInRvdWNoc3RhcnQiLCJtb3VzZW1vdmUiLCJ0b3VjaG1vdmUiLCJtb3VzZXVwIiwidG91Y2hlbmQiLCJ0b3VjaGNhbmNlbCIsInJlc2l6ZSIsImVuYWJsZUZpbGVUcmVlVXBsb2FkIiwiZmFsbGJhY2tVcmwiLCJkekVsZW1lbnQiLCJwcmV2aWV3c0NvbnRhaW5lciIsImNsaWNrYWJsZSIsImR6IiwiRHJvcHpvbmUiLCJvbiIsInJlbG9hZCIsImRhdGFUcmFuc2ZlciIsInR5cGVzIiwibGluayIsImNyYXdsIiwidGltZW91dCIsInByb2dyZXNzQmFyIiwicHJvZ3Jlc3NDb3VudCIsInJlc3VsdHMiLCJkZWJ1Z0xvZyIsInVwZGF0ZURhdGEiLCJ0b3RhbCIsInBlbmRpbmciLCJwZXJjZW50YWdlIiwicmVzdWx0IiwiaGFzRGVidWdMb2ciLCJmaW5pc2hlZCIsImhhc093blByb3BlcnR5Iiwic3VtbWFyeSIsIndhcm5pbmciLCJsb2ciLCJzdWJzY3JpYmVyUmVzdWx0cyIsInN1YnNjcmliZXJTdW1tYXJ5IiwiaGFzTG9nIiwid2FzU3VjY2Vzc2Z1bCIsImV4ZWNSZXF1ZXN0Iiwib25seVN0YXR1c1VwZGF0ZSIsInJlc3BvbnNlVGV4dCIsImRlY29kZSIsInNlbmQiLCJUaGVtZSIsImlzV2Via2l0IiwiY2hyb21lIiwic2FmYXJpIiwidXNlckFnZW50Iiwic3RvcENsaWNrUHJvcGFnYXRpb24iLCJzZXR1cEN0cmxDbGljayIsIkZlYXR1cmVzIiwiVG91Y2giLCJyZW1vdmVBdHRyaWJ1dGUiLCJQbGF0Zm9ybSIsIm1hYyIsIm1ldGFLZXkiLCJjdHJsS2V5Iiwic2hpZnRLZXkiLCJzZXR1cFRleHRhcmVhUmVzaXppbmciLCJpZTYiLCJpZTciLCJpZTgiLCJkdW1teSIsImxpbmUiLCJ0d2VlbiIsInNldHVwTWVudVRvZ2dsZSIsImJ1cmdlciIsInRvZ2dsZUNsYXNzIiwic2V0QXJpYUNvbnRyb2xzIiwic2V0dXBQcm9maWxlVG9nZ2xlIiwidG1lbnUiLCJtZW51Iiwic2V0dXBTcGxpdEJ1dHRvblRvZ2dsZSIsInRhYiIsInRpbWVyIiwiY2xlYXJUaW1lb3V0IiwiRWxlbWVudHMiLCJjaG9zZW4iLCJoZ3QiLCJpbml0aWFsaXplZFJvd3MiLCJzYXZlU2Nyb2xsT2Zmc2V0RXZlbnQiLCJyb3ciLCJBcnJheSIsImNsb25lTm9kZSIsInNlbGVjdHMiLCJuc2VsZWN0cyIsImluc2VydEJlZm9yZSIsIm5leHRTaWJsaW5nIiwiQ2hvc2VuIiwicHJldmlvdXNFbGVtZW50U2libGluZyIsImNvZGUiLCJpbnNlcnRBZGphY2VudEVsZW1lbnQiLCJ1cGRhdGVMaW5rIiwiTXV0YXRpb25PYnNlcnZlciIsIm11dGF0aW9uc0xpc3QiLCJfaXRlcmF0b3IiLCJfY3JlYXRlRm9yT2ZJdGVyYXRvckhlbHBlciIsIl9zdGVwIiwicyIsIm11dGF0aW9uIiwiYWRkZWROb2RlcyIsImVyciIsIm9ic2VydmUiLCJhdHRyaWJ1dGVzIiwiY2hpbGRMaXN0Iiwic3VidHJlZSIsIkNsYXNzIiwiRXh0ZW5kcyIsImZvbGxvd1JlZGlyZWN0cyIsInN1Y2Nlc3MiLCJnZXRIZWFkZXIiLCJzZWN1cmUiLCJzdHJpcFNjcmlwdHMiLCJzY3JpcHQiLCJmYWlsdXJlIiwib25GYWlsdXJlIiwicmVmYWN0b3IiLCJhdHRhY2giLCJoYW5kbGVzIiwiYm91bmQiLCJkZXRhY2giLCJjaGVjayIsImRpc3RhbmNlIiwic3FydCIsInBvdyIsIm1vdXNlIiwic25hcCIsImRyYWciLCJyZW1vdmVFdmVudHMiLCJsaXN0cyIsImRyYWdPcHRpb25zIiwibWVyZ2UiLCJ0YWciLCJhZGRJdGVtcyIsImZsYXR0ZW4iLCJyZW1vdmVJdGVtcyIsImVyYXNlIiwiZ2V0Q2xvbmUiLCJRdWV1ZSIsImF1dG9BZHZhbmNlIiwiZXJyb3IiLCJyZXN1bWUiLCJxdWV1ZSIsImlzUnVubmluZyIsInN0b3BPbkZhaWx1cmUiLCJvbkV4Y2VwdGlvbiIsIlNlcnBQcmV2aWV3IiwidHJhaWwiLCJ0aXRsZUZpZWxkIiwidGl0bGVGYWxsYmFja0ZpZWxkIiwiYWxpYXNGaWVsZCIsImRlc2NyaXB0aW9uRmllbGQiLCJkZXNjcmlwdGlvbkZhbGxiYWNrRmllbGQiLCJ0aXRsZVRhZyIsInNob3J0ZW4iLCJodG1sMnN0cmluZyIsIkRPTVBhcnNlciIsInBhcnNlRnJvbVN0cmluZyIsInRleHRDb250ZW50IiwiZ2V0VGlueW1jZSIsInRpbnlNQ0UiLCJzZXJwVGl0bGUiLCJzZXJwVXJsIiwic2VycERlc2NyaXB0aW9uIiwiaW5kZXhFbXB0eSIsImVkaXRvciIsImdldENvbnRlbnQiLCJhY3RpdmVFZGl0b3IiLCJpbml0aWFsaXplZCIsInRpcCIsInBvc2l0aW9uIiwidXNlQ29udGVudCIsIl90ZXh0Iiwid2lsbENoYW5nZSIsImdldEJvdW5kaW5nQ2xpZW50UmVjdCIsInJ0bCIsImRpcmVjdGlvbiIsImNsaWVudFdpZHRoIiwic2Nyb2xsWCIsInJpZ2h0Iiwic2Nyb2xsWSIsImhhc0F0dHJpYnV0ZSIsInNlbGVjdG9yIiwiX3RvQ29uc3VtYWJsZUFycmF5Iiwic2V0dXAiLCJBcHBsaWNhdGlvbiIsImRlZmluaXRpb25Gb3JNb2R1bGVBbmRJZGVudGlmaWVyIiwiaWRlbnRpZmllckZvckNvbnRleHRLZXkiLCJkZWJ1ZyIsInByb2Nlc3MiLCJlbnYiLCJOT0RFX0VOViIsImNvbnRleHQiLCJyZXF1aXJlIiwibG9hZCIsImtleXMiXSwic291cmNlUm9vdCI6IiJ9