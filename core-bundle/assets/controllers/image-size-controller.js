import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        config: Object,
    }

    initialize () {
        this.updateWizard = this.updateWizard.bind(this);
        this.openModal = this.openModal.bind(this);
    }

    connect () {
        this.select = this.element.querySelector('select');
        this.button = document.createElement('button');
        this.button.type = 'button';
        this.buttonImage = document.createElement('img');
        this.button.append(this.buttonImage);
        this.element.parentNode.classList.add('wizard');
        this.element.after(this.button);

        this.select.addEventListener('change', this.updateWizard);
        this.button.addEventListener('click', this.openModal);

        this.updateWizard();
    }

    disconnect () {
        this.element.parentNode.classList.remove('wizard');
        this.select.removeEventListener('change', this.updateWizard);
        this.buttonImage.remove();
        this.button.remove();
    }

    updateWizard () {
        if (this.canEdit()) {
            this.button.title = this.configValue.title;
            delete this.button.disabled;
            this.buttonImage.src = this.configValue.icon;
        } else {
            delete this.button.title;
            this.button.disabled = true;
            this.buttonImage.src = this.configValue.iconDisabled;
        }
    }

    openModal () {
        Backend.openModalIframe({
            title: this.configValue.title,
            url: `${ this.configValue.href }&id=${ this.select.value }`
        });
    }

    canEdit () {
        return this.configValue.ids.includes(Number(this.select.value));
    }
}
