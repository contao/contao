import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['select', 'button', 'image', 'width', 'height'];

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

            for (const img of this.imageTargets) {
                img.src = this.configValue.icon;
            }
        } else {
            this.buttonTarget.title = '';
            this.buttonTarget.disabled = true;

            for (const img of this.imageTargets) {
                img.src = this.configValue.iconDisabled;
            }
        }
    }

    _updateInputs() {
        const select = this.selectTarget;
        const value = select.value;

        if (value === '' || value.indexOf('_') === 0 || value.toInt().toString() === value) {
            this.widthTarget.readOnly = true;
            this.heightTarget.readOnly = true;
            let dimensions = select.options[select.selectedIndex].text;
            dimensions = dimensions.split('(');
            dimensions = dimensions.length > 1 ? dimensions.getLast().split(')')[0].split('x') : ['', ''];
            this.widthTarget.value = '';
            this.heightTarget.value = '';
            this.widthTarget.setAttribute('placeholder', dimensions[0] * 1 || '');
            this.heightTarget.setAttribute('placeholder', dimensions[1] * 1 || '');
        } else {
            this.widthTarget.removeAttribute('placeholder');
            this.heightTarget.removeAttribute('placeholder');
            this.widthTarget.readOnly = false;
            this.heightTarget.readOnly = false;
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
