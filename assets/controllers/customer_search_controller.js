import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'results'];

    connect() {
        this.timeout = null;
    }

    search() {
        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => this.fetch(), 300);
    }

    async fetch() {
        const url = new URL(window.location.href);
        url.searchParams.set('q', this.inputTarget.value);
        url.searchParams.set('page', 1);

        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        this.resultsTarget.innerHTML = await response.text();
    }
}
