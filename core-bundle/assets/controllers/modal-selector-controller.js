import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        title: String,
    }

    dcapicker(e) {
        const input = document.getElementById(`ctrl_${this.element.id.substring(3)}`);

        if (!input) {
            return;
        }

        e.preventDefault();
        Backend.openModalSelector({
            "id": "tl_listing",
            "title": this.titleValue,
            "url": this.element.href + "&value=" + input.value,
            "callback": function(picker, value) {
                input.value = value.join(",");
                input.fireEvent("change");
            }.bind(this)
        });
    }
}
