import './bootstrap';

document.addEventListener('alpine:init', () => {
    Alpine.data('clipboardButton', (initialValue = '') => ({
        copied: false,
        value: initialValue,

        async copy() {
            if (!this.value) {
                return;
            }

            try {
                await navigator.clipboard.writeText(this.value.toString());
                this.copied = true;

                setTimeout(() => {
                    this.copied = false;
                }, 2000);
            } catch (error) {
                console.error('Failed to copy: ', error);
            }
        },
    }));

    Alpine.data('splitCash', () => ({
        amountDisplay: '',
        rawAmount: 0,
        peopleCount: 4,
        results: [],
        copiedIndex: null,

        formatAmount() {
            const value = this.amountDisplay.replace(/\D/g, '');

            if (!value) {
                this.amountDisplay = '';
                this.rawAmount = 0;

                return;
            }

            this.rawAmount = parseInt(value, 10);
            this.amountDisplay = this.rawAmount.toLocaleString('id-ID');
        },

        formatCurrency(value) {
            return `Rp ${value.toLocaleString('id-ID')}`;
        },

        get totalValue() {
            return this.results.reduce((total, value) => total + value, 0);
        },

        calculateSplit() {
            if (this.rawAmount < this.peopleCount * 1000) {
                alert(`Jumlah uang terlalu kecil untuk dibagi ke ${this.peopleCount} orang.`);

                return;
            }

            const total = this.rawAmount;
            const peopleCount = this.peopleCount;
            const mean = total / peopleCount;
            const maximumVariation = mean * 0.25;

            const parts = [];
            let currentSum = 0;

            for (let index = 0; index < peopleCount - 1; index += 1) {
                const randomVariation = (Math.random() * 2 - 1) * maximumVariation;
                let partValue = mean + randomVariation;

                partValue = Math.floor(partValue / 1000) * 1000;

                if (partValue <= 0) {
                    partValue = 1000;
                }

                parts.push(partValue);
                currentSum += partValue;
            }

            parts.push(total - currentSum);

            for (let index = parts.length - 1; index > 0; index -= 1) {
                const targetIndex = Math.floor(Math.random() * (index + 1));

                [parts[index], parts[targetIndex]] = [parts[targetIndex], parts[index]];
            }

            this.results = parts;
            this.copiedIndex = null;
        },

        async copyToClipboard(value, index) {
            try {
                await navigator.clipboard.writeText(value.toString());
                this.copiedIndex = index;

                setTimeout(() => {
                    if (this.copiedIndex === index) {
                        this.copiedIndex = null;
                    }
                }, 2000);
            } catch (error) {
                console.error('Failed to copy: ', error);
            }
        },
    }));
});
