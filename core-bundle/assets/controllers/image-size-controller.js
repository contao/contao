import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['select', 'button', 'image'];

    static values = {
        config: Object,
    };

    connect() {
        this.updateWizard();
    }

    updateWizard() {
        if (this.canEdit()) {
            this.buttonTarget.title = this.configValue.title;
            this.buttonTarget.disabled = false;

            this.imageTargets.forEach((img) => {
                img.src = this.configValue.icon;
            });
        } else {
            this.buttonTarget.title = '';
            this.buttonTarget.disabled = true;

            this.imageTargets.forEach((img) => {
                img.src = this.configValue.iconDisabled;
            });
        }
    }

    openModal() {
        Backend.openModalIframe({
            title: this.configValue.title,
            url: `${this.configValue.href}&id=${this.selectTarget.value}`,
        });
    }

    canEdit() {
        return this.configValue.ids.includes(Number(this.selectTarget.value));
    }
}
