<div class="tw-bg-gradient-to-br tw-from-brand-500 tw-to-brand-700 tw-rounded-xl tw-shadow-card tw-overflow-hidden tw-px-4 tw-py-3 tw-flex tw-items-center tw-gap-3 tw-min-w-0 md:tw-max-w-xl md:tw-ml-auto">
    <div class="tw-bg-white/20 tw-w-10 tw-h-10 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-shrink-0">
        <i class="fas fa-exchange-alt tw-text-white tw-ticker-icon-spin"></i>
    </div>
    <div class="tw-flex-1 tw-overflow-hidden tw-min-w-0">
        <div class="tw-overflow-hidden tw-whitespace-nowrap">
            <div class="tw-inline-block tw-text-white tw-font-semibold tw-text-sm tw-ticker-scroll tw-pl-full"
                id="currency-ticker-text"
                style="padding-left: 100%;">
                Loading exchange rate...
            </div>
        </div>
    </div>
</div>

<script>
    async function fetchExchangeRate() {
        try {
            const response = await fetch('{{ route('api.dashboard.exchange-rate-usd') }}');
            const data = await response.json();

            if (data.success && data.rate) {
                const formattedRate = new Intl.NumberFormat('id-ID').format(data.rate);
                const updatedAt = new Date(data.scraped_at);
                const updateLabel = updatedAt.toLocaleString('id-ID', {
                    timeZone: 'Asia/Jakarta',
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false,
                });

                const tickerText =
                    `Exchange Rate: 1 USD = IDR ${formattedRate} (Source: Kemenkeu Kurs Pajak) | Last Updated: ${updateLabel} WIB | `;
                document.getElementById('currency-ticker-text').innerText = tickerText + tickerText;
            } else {
                document.getElementById('currency-ticker-text').innerText =
                    'Exchange Rate: Not available (Source: Kemenkeu Kurs Pajak) | ';
            }
        } catch (error) {
            console.error('Error fetching exchange rate:', error);
            document.getElementById('currency-ticker-text').innerText = 'Unable to load exchange rate data | ';
        }
    }

    fetchExchangeRate();
    setInterval(fetchExchangeRate, 300000);
</script>
