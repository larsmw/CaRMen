import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

export default class extends Controller {
    static values = {
        url:   String,
        token: String,
    }

    connect() {
        this.sortable = Sortable.create(this.element, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: this.onEnd.bind(this),
        });
    }

    disconnect() {
        this.sortable.destroy();
    }

    onEnd() {
        const ids = [...this.element.querySelectorAll('tr[data-id]')]
            .map(row => row.dataset.id);

        fetch(this.urlValue, {
            method: 'POST',
            headers: {
                'Content-Type':     'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ order: ids, _token: this.tokenValue }),
        });
    }
}
