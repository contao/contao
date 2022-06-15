//	Copyright (c) 2008, State of Illinois, Department of Human Services. All rights reserved.
//	Developed by: MSF&W Accessibility Solutions, http://www.msfw.com/accessibility
//	Subject to University of Illinois/NCSA Open Source License
//	See: http://www.dhs.state.il.us/opensource
//	Version Date: 2009-12-14
//
//	Accessible Sortable Table
//	This script makes html tables sortable in a manner that is usable with keyboard commands, large fonts, screen readers, and speech recognition tools, specifically:
//	(1) Sorting is activated using actual links, which are focusable and clickable from the keyboard and by assistive technologies
//	(2) The table summary includes an instruction for screen reader users explaining that the table can be sorted by clicking on table headers
//	(3) The sort status (ascending, descending) is indicated using an abbreviation element with a title attribute that can be read by screen readers
//	(4) Focus is refreshed whenever sort status is changed, prompting screen readers to read the new information
//
//	To make a table sortable, simply add the class:
//		class="sortable"
//	And call:
//		SortableTable.initAll()
//
//	The sort type (alphabetical, numeric, date, or none) can be determined automatically or by setting a class on any column header:
//		class="sort-alpha" - for case-insensitive alphabetical sorting
//		class="sort-number" - for integers, decimals, money ($##.##), and percents (##%)
//		class="sort-date" - for "mm/dd/yyyy" and "month dd, yyyy" format dates (use alpha for "yyyy-mm-dd")
//		class="sort-none"
//
//	A custom sort key (value to use for sorting) can be indicated for any data cell by setting a class on the cell:
//		class="sortkey-value" - where value is the value to use for sorting
//
//	Table head (thead) and footer (tfoot) rows are not sorted.
//	If no table head is present, one will be created around the first row.
//	If a table footer is desired, but tfoot markup cannot be included in the HTML, add the following class to the table tag:
//		class="sortable addFooter"
//	Or
//		class="sortable addFooter-n" - where n is the number of rows to include in the footer.
//
//	Default settings can be overriden by passing a settings object to the constructor, e.g.:
//		SortableTable.initAll({ className: "sortable", summary: "(Click a column header to sort)", ... })
//
//	The following style rules are suggested:
//		table.sortable .sortLink { display: block; *margin-bottom: 1px; color: #000; text-decoration: none;  }
//		table.sortable .sortIcon { padding-left: 3px; vertical-align: 1px; }
//		table.sortable .unsorted { }
//		table.sortable .unsorted .sortIcon { }
//		table.sortable .ascending { }
//		table.sortable .ascending .sortIcon { }
//		table.sortable .descending { }
//		table.sortable .descending .sortIcon { }

/*global window, document, navigator, screen */

SortableTable = function(table, settings)
{
    ///	<summary>Enables tables to be sorted dynamically</summary>
    ///	<param name="table" type="DomElement">Table to be made sortable</param>
    /// <param name="settings" type="object" optional="true">Optional settings in object literal notation, e.g., { className: "sortable", summary: "(Click a column header to sort)", ... }</param>

    // settings
    var settings = settings || {};
    // settings.className (used in initAll)
    this._summary = typeof settings.summary !== "undefined" ? settings.summary : "(Click a column header to sort)";
    this._sortLinkClassName = typeof settings.sortLinkClassName !== "undefined" ? settings.sortLinkClassName : "sortLink";
    this._sortIconTagName = typeof settings.sortIconTagName !== "undefined" ? settings.sortIconTagName : "abbr";
    this._sortIconClassName = typeof settings.sortIconClassName !== "undefined" ? settings.sortIconClassName : "sortIcon";
    this._unsortedClassName = typeof settings.unsortedClassName !== "undefined" ? settings.unsortedClassName : "unsorted";
    this._unsortedIcon = typeof settings.unsortedIcon !== "undefined" ? settings.unsortedIcon : "\u2195"; // up down arrow
    this._unsortedText = typeof settings.unsortedText !== "undefined" ? settings.unsortedText : "";
    this._ascendingClassName = typeof settings.ascendingClassName !== "undefined" ? settings.ascendingClassName : "ascending";
    this._ascendingIcon = typeof settings.ascendingIcon !== "undefined" ? settings.ascendingIcon : "\u2193"; // downwards arrow
    this._ascendingText = typeof settings.ascendingText !== "undefined" ? settings.ascendingText : "(sorted ascending)";
    this._descendingClassName = typeof settings.descendingClassName !== "undefined" ? settings.descendingClassName : "descending";
    this._descendingIcon = typeof settings.descendingIcon !== "undefined" ? settings.descendingIcon : "\u2191"; // upwards arrow
    this._descendingText = typeof settings.descendingText !== "undefined" ? settings.descendingText : "(sorted descending)";
    this._sortTypePrefix = typeof settings.sortTypePrefix !== "undefined" ? settings.sortTypePrefix : "sort";
    this._sortTypeDate = typeof settings.sortTypeDate !== "undefined" ? settings.sortTypeDate : "date";
    this._sortTypeNumber = typeof settings.sortTypeNumber !== "undefined" ? settings.sortTypeNumber : "number";
    this._sortTypeAlpha = typeof settings.sortTypeAlpha !== "undefined" ? settings.sortTypeAlpha : "alpha";
    this._sortTypeNone = typeof settings.sortTypeNone !== "undefined" ? settings.sortTypeNone : "none";
    this._sortKeyPrefix = typeof settings.sortKeyPrefix !== "undefined" ? settings.sortKeyPrefix : "sortkey";
    this._addTFootClassName = typeof settings.addTFootClassName !== "undefined" ? settings.addTFootClassName : "addFooter";
    this._numberPattern = typeof settings.numberPattern !== "undefined" ? settings.numberPattern : "^\\s*-?\\$?[\\d,]*\\.?\\d*%?$"; // any number of whitespace characters, optional negative sign (hyphen), optional dollar sign, any number of digits/commas, optional period, any number of digits (note: will match all white-space or empty-string)
    this._numberCleanUpPattern = typeof settings.numberCleanUpPattern !== "undefined" ? settings.numberCleanUpPattern : "[$,]"; // dollar sign or comma
    this._blockAndFocusableElementsPattern = typeof settings.blockAndFocusableElementsPattern !== "undefined" ? settings.blockAndFocusableElementsPattern : "^[DIV|P|H1|H2|H3|H4|H5|H6|HR|UL|OL|DL|BLOCKQUOTE|PRE|ADDRESS|TABLE|FORM|FIELDSET|INPUT|SELECT|TEXTAREA|BUTTON|A]$";
    this._minDate = typeof settings.minDate !== "undefined" && Date.parse(settings.minDate) ? Date.parse(settings.minDate) : Date.parse("1/1/1900");

    // class variables
    this._table = table;
    this._tBody = this._table.tBodies[0];
    this._tHeadRow = null;
    this._sortedColumnIndex = null;
    this._isAscending = false;

    // initialization
    this.setTHead();
    this.setTFoot();
    this.setSortTypes();
    this.addSortLinks();
};

SortableTable.prototype =
    {
        setTHead: function()
        {
            ///	<summary>Identifies the head row (the last row in the table head). Creates a thead element if necessary.</summary>
            var tHead = this._table.tHead;
            if (!tHead)
            {
                tHead = this._table.createTHead();
                tHead.appendChild(this._table.rows[0]);
            }
            this._tHeadRow = tHead.rows[tHead.rows.length - 1];
        },

        setTFoot: function()
        {
            ///	<summary>Creates a table footer containing the last n rows if the table has class="addTFoot-n".</summary>
            ///	<remarks>Should be used only if tfoot markup cannot be included in the HTML.</remarks>
            if (!this._table.tFoot)
            {
                var addTFootRegExp = new RegExp("\\b" + this._addTFootClassName + "-?(\\d*)\\b", "i"); // word-break, addTFootClassName, optional hyphen, any number of digits (captured), word-break
                var addTFootMatch = this._table.className.match(addTFootRegExp);
                if (addTFootMatch)
                {
                    var tFoot = this._table.createTFoot();
                    var numberOfRows = addTFootMatch[1] ? parseInt(addTFootMatch[1], 10) : 1;
                    for (var i = 0, n = Math.min(numberOfRows, this._tBody.rows.length); i < n; i++)
                    {
                        tFoot.appendChild(this._tBody.rows[this._tBody.rows.length - 1]);
                    }
                }
            }
        },

        setSortTypes: function()
        {
            ///	<summary>Adds a sortType className (e.g., class="sort-date") to each header that does not already have one.</summary>
            ///	<remarks>
            ///	 SortType is determined by analyzing patterns in data cell contents; if not identifiable as date or number, alpha is used.
            ///	 Note: It is faster and more reliable to specify sortType classNames in the HTML.
            ///	</remarks>
            var sortTypeRegExp = new RegExp("\\b" + this._sortTypePrefix + "\\b", "i"); // word-break, sortTypePrefix, word-break
            var numberRegExp = new RegExp(this._numberPattern);
            var tHeadRow = this._tHeadRow;
            var tBodyRows = this._tBody.rows;
            for (var i = 0, n = tHeadRow.cells.length; i < n; i++)
            {
                if (!sortTypeRegExp.test(tHeadRow.cells[i].className))
                {
                    var isDateColumn = true;
                    var isNumberColumn = true;
                    for (var j = 0, m = tBodyRows.length; j < m; j++)
                    {
                        var innerText = Utility.getInnerText(tBodyRows[j].cells[i]).replace(/^[\s\u00A0]+/, ""); // whitespace and non-breaking spaces
                        // check for date
                        if (isDateColumn)
                        {
                            if (innerText.length > 0 && (innerText.length < 6 || !isFinite(Date.parse(innerText)))) // shortest date string = m/d/yy
                            {
                                isDateColumn = false;
                                j = -1; // restart loop
                            }
                        }
                        // check for number
                        else
                        {
                            if (innerText.length > 0 && !numberRegExp.test(innerText))
                            {
                                isNumberColumn = false;
                                break;
                            }
                        }
                    }
                    var sortType = isDateColumn ? this._sortTypeDate : isNumberColumn ? this._sortTypeNumber : this._sortTypeAlpha;
                    tHeadRow.cells[i].className += " " + this._sortTypePrefix + "-" + sortType + " " + this._unsortedClassName;
                }
            }
        },

        addSortLinks: function()
        {
            ///	<summary>Adds sort links and sort icons (abbr elements) to the table headers.</summary>
            var hasSortableColumns = false;
            var sortTypeNoneRegExp = new RegExp("\\b" + this._sortTypePrefix + "-" + this._sortTypeNone + "\\b", "i"); // word-break, sortTypePrefix, hyphen, sortTypeNone, word-break
            for (var i = 0, n = this._tHeadRow.cells.length; i < n; i++)
            {
                var th = this._tHeadRow.cells[i];
                // check for sort type class and that header has content
                if (!sortTypeNoneRegExp.test(th.className) && Utility.getInnerText(th).length > 0)
                {
                    // check that header does not contain block or focusable elements (which can't be embedded in a link)
                    var containsBlockOrFocusableElement = false;
                    var blockAndFocusableElementsRegExp = new RegExp(this._blockAndFocusableElementsPattern, "i");
                    var descendents = th.getElementsByTagName("*"); // To Do: Check IE 5
                    for (var j = 0, m = descendents.length; j < m; j++)
                    {
                        if (descendents[j].tagName && blockAndFocusableElementsRegExp.test(descendents[j].tagName))
                        {
                            containsBlockOrFocusableElement = true;
                            break;
                        }
                    }
                    // add sort link & sort icon
                    if (!containsBlockOrFocusableElement)
                    {
                        hasSortableColumns = true;
                        // create sort link
                        var sortLink = document.createElement("a");
                        sortLink.className = this._sortLinkClassName;
                        sortLink.id = Utility.getUniqueId("sortLink");
                        sortLink.href = "#" + sortLink.id; // link must have href to be clickable from the keyboard
                        sortLink.onclick = Utility.createDelegate(this, this.sort, [i]);
                        // move contents of header into sort link
                        while (th.childNodes.length > 0)
                        {
                            sortLink.appendChild(th.childNodes[0]);
                        }
                        // create sort icon
                        var sortIcon = document.createElement(this._sortIconTagName);
                        if (this._sortIconTagName === "img")
                        {
                            sortIcon.src = this._unsortedIcon;
                            sortIcon.alt = this._unsortedText;
                        }
                        else
                        {
                            sortIcon.appendChild(document.createTextNode(this._unsortedIcon));
                        }
                        sortIcon.title = this._unsortedText;
                        sortIcon.className = this._sortIconClassName;
                        sortIcon.style.borderStyle = "none";
                        // append sort link & sort icon
                        sortLink.sortIcon = sortLink.appendChild(sortIcon);
                        th.sortLink = th.appendChild(sortLink);
                    }
                }
            }
            if (hasSortableColumns)
            {
                // add summary
                if (this._summary.length > 0)
                {
                    this._table.summary += " " + this._summary;
                }
            }
        },

        sort: function(columnIndex)
        {
            ///	<summary>Sorts the table on the selected column.</summary>
            ///	<param name="columnIndex" type="Number">Index of the column on which to sort the table.</param>
            /// <returns type="Boolean">False, to cancel associated click event.</returns>
            var th = this._tHeadRow.cells[columnIndex];
            var rows = this._tBody.rows;
            if (th && rows[0].cells[columnIndex])
            {
                var rowArray = [];
                // sort on a new column
                if (columnIndex !== this._sortedColumnIndex)
                {
                    // get sortType
                    var sortTypeRegExp = new RegExp("\\b" + this._sortTypePrefix + "-(\\S*)\\b", "i"); // word-break, sortTypePrefix, hyphen, one or more non-whitespace characters (captured), word-break
                    var sortTypeMatch = th.className.match(sortTypeRegExp);
                    var sortType = sortTypeMatch ? sortTypeMatch[1] : this.sortTypeAlpha;
                    // get sortKey
                    var sortKeyRegExp = new RegExp("\\b" + this._sortKeyPrefix + "-(\\S*)\\b", "i"); // word-break, sortKeyPrefix, hyphen, any number of non-whitespace characters (captured), word-break
                    var numberCleanUpRegExp = new RegExp(this._numberCleanUpPattern, "ig"); // non-numeric characters allowed before or within numbers (e.g. dollar sign and comma)
                    for (var i = 0, n = rows.length; i < n; i++)
                    {
                        var cell = rows[i].cells[columnIndex];
                        var sortKeyMatch = cell.className.match(sortKeyRegExp);
                        var sortKey = sortKeyMatch ? sortKeyMatch[1] : Utility.getInnerText(cell);
                        // convert to date
                        if (sortType === this._sortTypeDate)
                        {
                            sortKey = Date.parse(sortKey) || this._minDate;
                        }
                        // convert to number
                        else if (sortType === this._sortTypeNumber)
                        {
                            sortKey = parseFloat(sortKey.replace(numberCleanUpRegExp, "")) || 0;
                        }
                        // convert to string (left-trimmed, lowercase)
                        else if (sortKey.length > 0)
                        {
                            sortKey = sortKey.replace(/^\s+/, "").toLowerCase();
                        }
                        // add object to rowArray
                        rowArray[rowArray.length] =
                            {
                                sortKey: sortKey,
                                row: rows[i]
                            };
                    }
                    // sort
                    rowArray.sort(sortType === this._sortTypeDate || sortType === this._sortTypeNumber ? this.sortNumber : this.sortAlpha);
                    this._isAscending = true;
                }
                // sort on previously sorted column
                else
                {
                    // reverse rows (faster than re-sorting)
                    for (var i = rows.length - 1; i >= 0; i--)
                    {
                        rowArray[rowArray.length] =
                            {
                                row: rows[i]
                            };
                    }
                    this._isAscending = !this._isAscending;
                }
                // append rows
                for (var i = 0, n = rowArray.length; i < n; i++)
                {
                    this._tBody.appendChild(rowArray[i].row);
                }
                // clean up
                delete rowArray;
                // reset old sortIcon
                if (this._sortedColumnIndex !== null && this._sortedColumnIndex !== columnIndex)
                {
                    this.setSortIcon(this._sortedColumnIndex, this._unsortedClassName, this._unsortedIcon, this._unsortedText);
                }
                // set new sortIcon
                if (this._isAscending)
                {
                    this.setSortIcon(columnIndex, this._ascendingClassName, this._ascendingIcon, this._ascendingText);
                }
                else
                {
                    this.setSortIcon(columnIndex, this._descendingClassName, this._descendingIcon, this._descendingText);
                }
                // set sortedColumnIndex
                this._sortedColumnIndex = columnIndex;
                // re-focus sort link to cause screen reader to read new sort icon title
                //th.sortLink.blur();
                th.sortLink.focus();
            }
            // cancel click event
            return false;
        },

        setSortIcon: function(columnIndex, className, text, title)
        {
            ///	<summary>Sets the sort icon to show the current sort status (ascending, descending, or unsorted).</summary>
            ///	<param name="columnIndex" type="Number">Index of the column for which to set the icon.</param>
            ///	<param name="className" type="String">Class name to be applied to the column header.</param>
            ///	<param name="icon" type="String">Text to be used as the visible sort icon.</param>
            ///	<param name="title" type="String">Text to be used for the sort icon title.</param>
            var th = this._tHeadRow.cells[columnIndex];
            if (th)
            {
                var sortLink = th.sortLink;
                if (sortLink)
                {
                    th.className = th.className.replace(new RegExp("\\b(" + this._unsortedClassName + "|" + this._ascendingClassName + "|" + this._descendingClassName + ")\\b"), className);
                    var sortIcon = sortLink.sortIcon;
                    if (sortIcon)
                    {
                        if (this._sortIconTagName === "img")
                        {
                            sortIcon.src = text;
                            sortIcon.alt = title;
                        }
                        else
                        {
                            sortIcon.replaceChild(document.createTextNode(text), sortIcon.childNodes[0]);
                        }
                        sortIcon.title = title;
                    }
                }
            }
        },

        sortNumber: function(a, b)
        {
            ///	<summary>Array sort compare function for number and date columns</summary>
            ///	<param name="a" type="Object">rowArray element with number sortKey property</param>
            ///	<param name="b" type="Object">rowArray element with number sortKey property</param>
            ///	<returns type="Number">Returns a positive number if a.sortKey > b.sortKey, a negative number if a.sortKey < b.sortKey, or 0 if a.sortKey = b.sortKey</returns>
            return a.sortKey - b.sortKey;
        },

        sortAlpha: function(a, b)
        {
            ///	<summary>Array sort compare function for alpha (string) columns</summary>
            ///	<param name="a" type="Object">rowArray element with string sortKey property</param>
            ///	<param name="b" type="Object">rowArray element with string sortKey property</param>
            ///	<returns type="Number">Returns a positive number if a.sortKey > b.sortKey, a negative number if a.sortKey < b.sortKey, or 0 if a.sortKey = b.sortKey</returns>
            return ((a.sortKey < b.sortKey) ? -1 : ((a.sortKey > b.sortKey) ? 1 : 0));
        }
    };

SortableTable.init = function(table, settings)
{
    ///	<summary>Static method that initializes a single SortableTable.</summary>
    ///	<param name="table" type="DomElement">Table to be made sortable</param>
    /// 	<param name="settings" type="object" optional="true">Optional settings in object literal notation, e.g., { className: "sortable", summary: "(Click a column header to sort)", ... }</param>
    ///	<returns type="Number">Number of sortable tables that were created (0 or 1)</returns>
    if (document.getElementsByTagName && document.createElement && Function.apply)
    {
        if (SortableTable.isSortable(table))
        {
            var sortableTable = new SortableTable(table, settings);
            return 1;
        }
    }
    return 0;
};

SortableTable.initAll = function(settings)
{
    ///	<summary>Static method that initializes all SortableTables in a document.</summary>
    ///	<param name="settings" type="Object" optional="true">Optional settings in object literal notation, e.g., { className: "sortable", summary: "(Click a column header to sort)", ...}</param>
    ///	<returns type="Number">Number of sortable tables that were created</returns>
    var sortableTableCount = 0;
    if (document.getElementsByTagName && document.createElement && Function.apply)
    {
        var className = (settings && settings.ClassName) ? settings.ClassName : "sortable";
        var classNameRegExp = new RegExp("\\b" + className + "\\b", "i"); // word-break, className, word-break
        var tables = document.getElementsByTagName("table");
        for (var i = 0, n = tables.length; i < n; i++)
        {
            if (classNameRegExp.test(tables[i].className))
            {
                sortableTableCount += SortableTable.init(tables[i], settings);
            }
        }
    }
    return sortableTableCount;
};

SortableTable.isSortable = function(table)
{
    /// <summary>Static method that indicates whether a table can be made sortable (has a single tbody, at least 3 and not more than 250 rows, and a uniform number of columns)</summary>
    /// <param name="table" type="DomElement"></param>
    /// <returns type="Boolean"></returns>
    /// <remarks>Limit of 1500 rows is arbitrary (testing has not been done to determine the most appropriate value)</remarks>
    // check table, tbody, and number of rows (including thead)
    if (table === null || table.tBodies.length > 1 || table.rows.length < 3 || table.rows.length > 1500)
    {
        return false;
    }
    // check uniform columns
    var tBody = table.tBodies[0];
    var numberOfColumns = tBody.rows[0].cells.length;
    for (var i = 0, n = tBody.rows.length; i < n; i++)
    {
        if (tBody.rows[i].cells.length !== numberOfColumns)
        {
            return false;
        }
    }
    return true;
};

// Utility Methods

var Utility = Utility ||
    {
        ///	<summary>Utility Class</summary>
    }

Utility.getInnerText = Utility.getInnerText || function(element)
{
    ///	<summary>Returns the text content of an element.</summary>
    ///	<param name="element" type="DomElement"></param>
    ///	<returns type="String"></returns>
    ///	<remarks>This method is a cross-browser alternative to innerText.</remarks>
    return element.innerText || element.textContent || "";
}

Utility.getUniqueId = Utility.getUniqueId || function(prefix)
{
    ///	<summary>Returns an ID value that is not currently assigned to any element in the document.</summary>
    ///	<param name="prefix" type="String"></param>
    ///	<returns type="String"></returns>
    /// <remarks>To be valid, prefix must begin with a letter and contain only letters, digits, hyphens, and/or underscores.</remarks>
    var i = 0;
    while (document.getElementById(prefix + i.toString())) { i++; }
    return prefix + i.toString();
}

Utility.createDelegate = Utility.createDelegate || function(instance, method, argumentsArray)
{
    /// <summary>Creates a delegate to allow the specified method to run in the context of the specified instance.</summary>
    ///	<param name="instance" type="Object"></param>
    ///	<param name="method" type="Function"></param>
    ///	<param name="argumentsArray" type="Array" optional="true">Optional arguments to pass on to the specified method.</param>
    /// <returns type="Function"></returns>
    ///	<remarks>
    ///	 Allows "this" in event handlers to reference a specific object rather than the event source element.
    ///	 Syntax: element.eventhandler = Utility.createDelegate(this, this.method, [optionalArgument1, optionalArgument2, ...])
    ///	 Not supported in Internet Explorer 5.0 or earlier.
    ///	</remarks>
    return function()
    {
        return method.apply(instance, argumentsArray);
    }
}
