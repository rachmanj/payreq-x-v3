<div class="col-md-12">
    <div class="card">
        <div class="card-body p-0">
            {{-- <h5 class="card-title">Run Text</h5> --}}
            <div class="currency-ticker">
                <marquee behavior="scroll" direction="left" id="currency-marquee">
                    Loading exchange rate...
                </marquee>
            </div>
        </div>
    </div>
</div>

<script>
    async function fetchExchangeRates() {
        try {
            // Fetch external rate
            const externalResponse = await fetch('https://api.exchangerate-api.com/v4/latest/USD');
            const externalData = await externalResponse.json();
            const externalIdrRate = externalData.rates.IDR;
            const externalFormattedRate = new Intl.NumberFormat('id-ID').format(externalIdrRate);

            // Fetch internal automated rate
            const internalResponse = await fetch('/api/dashboard/exchange-rate-usd');
            const internalData = await internalResponse.json();

            const currentTime = new Date().toLocaleString('id-ID', {
                timeZone: 'Asia/Jakarta',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });

            let marqueeText =
                `💱 External Rate: 1 USD = IDR ${externalFormattedRate} (Source: exchangerate-api.com) | Last Updated: ${currentTime} WIB 💱`;

            if (internalData.success && internalData.rate) {
                const internalFormattedRate = new Intl.NumberFormat('id-ID').format(internalData.rate);
                const internalUpdateTime = new Date(internalData.scraped_at).toLocaleString('id-ID', {
                    timeZone: 'Asia/Jakarta',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });

                marqueeText +=
                    ` | 💱 Official Rate: 1 USD = IDR ${internalFormattedRate} (Source: Kemenkeu Kurs Pajak) | Last Updated: ${internalUpdateTime} WIB 💱`;
            } else {
                marqueeText += ` | 💱 Official Rate: Not available 💱`;
            }

            document.getElementById('currency-marquee').innerText = marqueeText;
        } catch (error) {
            console.error('Error fetching exchange rates:', error);
            document.getElementById('currency-marquee').innerText = 'Unable to load exchange rate data';
        }
    }

    // Fetch exchange rates immediately
    fetchExchangeRates();

    // Update exchange rates every 5 minutes
    setInterval(fetchExchangeRates, 300000);
</script>

<style>
    .currency-ticker {
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
    }

    marquee {
        color: #333;
        font-weight: 500;
    }
</style>
