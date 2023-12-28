import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static addToAttribute = 'add-to-scroll-offset' // Used by paste-hints
    static behavior = 'instant'

    static targets = ['scrollTo']

    // initScrollOffset is handled by core.js and Mootools
    // Once Backend.initScrollOffset() is not being used anymore, activate this
    /*static afterLoad(identifier, application) {
        const initializeScrollOffset = () => {


            let offset = window.sessionStorage.getItem('contao_backend_offset')
            window.sessionStorage.removeItem('contao_backend_offset')
            if (!offset) return

            window.scrollTo({
                top: parseInt(offset) + this.getAdditionalOffset(),
                behavior: this.behavior
            })
        }

        // Called as soon as registered, so DOM may not have been loaded yet
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", initializeScrollOffset)
        } else {
            initializeScrollOffset()
        }
    }*/

    set(event) {
        const el = event.currentTarget
        const offset = el.getBoundingClientRect().top + window.scrollY
        window.sessionStorage.setItem('contao_backend_offset', String(offset))
    }

    scrollToTargetConnected() {
        this.jumpToScrollToTarget()
    }

    jumpToScrollToTarget() {
        if (!this.scrollToTarget)
            return

        const el = this.scrollToTarget

        const offset = el.getBoundingClientRect().top + window.scrollY
        window.sessionStorage.setItem('contao_backend_offset', String(offset))
        Backend.initScrollOffset() // BC Layer
    }

    static getAdditionalOffset = () => {
        let additionalOffset = 0

        document.querySelectorAll(`[data-${this.addToAttribute}]`).forEach(el => {
            additionalOffset += this.getAdditionalOffsetValue(el)
        })

        return additionalOffset
    }

    static getAdditionalOffsetValue = (el) => {
        // no % or minus values have been used ever within OSS
        const attribute = this.dashToCamelCase(this.addToAttribute)
        return el.dataset[attribute] ?? 0
    }

    static dashToCamelCase = (string) => {
        return string.replace(/-([a-z])/g, (char) => {
            return char[1].toUpperCase()
        })
    }
}
