import ColorPicker from '@stimulus-components/color-picker';

export default class extends ColorPicker {
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
