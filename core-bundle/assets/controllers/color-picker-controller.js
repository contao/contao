import ColorPicker from '@stimulus-components/color-picker';

export default class extends ColorPicker {
    initialize() {
        super.initialize();
        this.updateInteractionResult = this.updateInteractionResult.bind(this);
    }

    connect() {
        let hexValueLoaded = false;

        if (this.inputTarget.value && /^[0-9a-f]{3,6}$/i.test(this.inputTarget.value)) {
            this.inputTarget.value = `#${this.inputTarget.value}`;
            hexValueLoaded = true;
        }

        super.connect();

        this.picker.on('change', this.updateInteractionResult);

        // Reapply the button target to the element as it got replaced by not using `useAsButton` - see #9985
        this.picker.getRoot().root.setAttribute(`data-${this.identifier}-target`, 'button');

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

    updateInteractionResult(color) {
        // Updates the interactive result on change - see #9985
        this.picker.getRoot().interaction.result.value = color ? color.toHEXA().toString() : '';
    }
}
