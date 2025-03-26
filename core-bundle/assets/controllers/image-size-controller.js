import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['select', 'button', 'image', 'input'];

    static values = {
        config: Object,
    };

    connect() {
        this._updateWizard();
        this._updateInputs();
    }

    inputTargetDisconnected(input) {
        input.value = '';
        input.removeAttribute('placeholder');
        input.readOnly = false;
    }

    update() {
        this._updateWizard();
        this._updateInputs();
    }

    _updateWizard() {
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

    _updateInputs() {
        const widthInput = this.inputTargets[0];
        const heightInput = this.inputTargets[1];
        const select = this.selectTarget;
        const value = select.value;

        if (value === '' || value.indexOf('_') === 0 || value.toInt().toString() === value) {
            widthInput.readOnly = true;
            heightInput.readOnly = true;
            let dimensions = select.options[select.selectedIndex].text;
            dimensions =
                dimensions.split('(').length > 1 ? dimensions.split('(').getLast().split(')')[0].split('x') : ['', ''];
            widthInput.value = '';
            heightInput.value = '';
            widthInput.setAttribute('placeholder', dimensions[0] * 1 || '');
            heightInput.setAttribute('placeholder', dimensions[1] * 1 || '');
        } else {
            widthInput.removeAttribute('placeholder');
            heightInput.removeAttribute('placeholder');
            widthInput.readOnly = false;
            heightInput.readOnly = false;
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
