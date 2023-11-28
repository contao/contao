import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static classes = ['up', 'down']

    initialize () {
        this.lastScroll = 0;
        this.onScroll = this.onScroll.bind(this);
    }

    connect () {
        window.addEventListener('scroll', this.onScroll, { passive: true });
    }

    disconnect () {
        window.removeEventListener('scroll', this.onScroll);
    }

    up () {
        if (this.hasUpClass) {
            document.body.classList.add(this.upClass);
        }

        if (this.hasDownClass) {
            document.body.classList.remove(this.downClass);
        }
    }

    down () {
        if (this.hasUpClass) {
            document.body.classList.remove(this.upClass);
        }

        if (this.hasDownClass) {
            document.body.classList.add(this.downClass);
        }
    }

    onScroll () {
        // Make sure the scroll value is between 0 and maxScroll
        const currentScroll = Math.max(0, Math.min(document.documentElement.scrollHeight - document.documentElement.clientHeight, window.scrollY));

        if (this.lastScroll < currentScroll) {
            this.down();
        } else if (this.lastScroll > currentScroll) {
            this.up();
        }

        this.lastScroll = currentScroll;
    }
}
