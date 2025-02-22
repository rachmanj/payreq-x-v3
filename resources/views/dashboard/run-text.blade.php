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
    async function fetchExchangeRate() {
        try {
            const response = await fetch('https://api.exchangerate-api.com/v4/latest/USD');
            const data = await response.json();
            const idrRate = data.rates.IDR;
            const formattedRate = new Intl.NumberFormat('id-ID').format(idrRate);

            const currentTime = new Date().toLocaleString('id-ID', {
                timeZone: 'Asia/Jakarta',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });

            const marqueeText =
                `💱 Current Exchange Rate: 1 USD = IDR ${formattedRate} (Source: exchangerate-api.com) | Last Updated: ${currentTime} WIB 💱`;
            document.getElementById('currency-marquee').innerText = marqueeText;
        } catch (error) {
            console.error('Error fetching exchange rate:', error);
            document.getElementById('currency-marquee').innerText = 'Unable to load exchange rate data';
        }
    }

    // Fetch exchange rate immediately
    fetchExchangeRate();

    // Update exchange rate every 5 minutes
    setInterval(fetchExchangeRate, 300000);
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
