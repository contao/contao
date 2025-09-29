import ColorPicker from '@stimulus-components/color-picker';

export default class extends ColorPicker {
    connect() {
        let hexValueLoaded = false;

        if (this.inputTarget.value && /^[0-9a-f]{3,6}$/i.test(this.inputTarget.value)) {
            this.inputTarget.value = `#${this.inputTarget.value}`;
            hexValueLoaded = true;
        }

        super.connect();

        if (hexValueLoaded) {
            this.inputTarget.value = this.inputTarget.value.substring(1);
        }
    }

    // Override the onSave function to strip the leading `#`
    onSave(color) {
        this.inputTarget.value = null;

        if (color) {
            let value = color.toHEXA().toString();

            if ('#' === value.charAt(0)) {
                value = value.substring(1);
            }

            this.inputTarget.value = value.toLowerCase();
        }

        this.picker.hide();
    }
}
