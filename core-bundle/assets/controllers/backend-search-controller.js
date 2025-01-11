import {Controller} from "@hotwired/stimulus"

export default class BackendSearchController extends Controller {
    static targets = [
        "input",
        "results",
    ];

    static values = {
        route: String,
        minCharacters: {
            type: Number,
            default: 3,
        },
        delay: {
            type: Number,
            default: 150,
        },
    }

    static classes = [
        "hidden",
        "initial",
        "loading",
        "invalid",
        "results",
        "error",
    ]

    connect() {
        this.active = false;
        this.timeout = null;

        this.setState("hidden");
    }

    performSearch() {
        if (this.inputTarget.value.length < this.minCharactersValue) {
            return this.setState("invalid");
        }

        clearTimeout(this.timeout);

        this.timeout = setTimeout(() => {
            this.loadResults()
        }, this.delayValue);
    }

    loadResults() {
        this.setState("loading");

        fetch(this.searchRoute)
            .then(res=> {
                if (!res.ok) {
                    throw new Error(res.statusText)
                }

                return res.text()
            })
            .then(html => {
                this.resultsTarget.innerHTML = html
                this.setState("results")
            })
            .catch(e => {
                this.setState("error")
            });
    }

    open() {
        if (!this.active) {
            this.setState("initial");
            this.active = true;
        }
    }

    close() {
        this.inputTarget.blur();
        this.inputTarget.value = "";

        this.active = false;
        this.timeout = null;

        this.setState("hidden");
    }

    documentClick(event) {
        if (this.element.contains(event.target)) {
            return;
        }

        this.close();
    }

    setState(state) {
        BackendSearchController.classes.forEach(className => {
            this.element.classList.toggle(this[`${className}Class`], className === state)
        });
    }

    get searchRoute() {
        return this.routeValue + this.inputTarget.value;
    }
}
