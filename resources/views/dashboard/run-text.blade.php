<div class="col-md-12">
    <div class="card modern-ticker-card">
        <div class="card-body p-0">
            <div class="currency-ticker">
                <div class="ticker-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="ticker-content">
                    <div class="ticker-wrapper">
                        <div class="ticker-text" id="currency-ticker-text">
                            Loading exchange rate...
                        </div>
                    </div>
                </div>
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
                const updateTime = new Date(data.scraped_at).toLocaleString('id-ID', {
                    timeZone: 'Asia/Jakarta',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });

                const tickerText =
                    `Exchange Rate: 1 USD = IDR ${formattedRate} (Source: Kemenkeu Kurs Pajak) | Last Updated: ${updateTime} WIB | `;
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

<style>
    .modern-ticker-card {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
        border: none;
        margin-bottom: 20px;
    }

    .currency-ticker {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 15px 20px;
        display: flex;
        align-items: center;
        overflow: hidden;
    }

    .ticker-icon {
        background: rgba(255, 255, 255, 0.2);
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
        flex-shrink: 0;
    }

    .ticker-icon i {
        font-size: 24px;
        color: #fff;
        animation: rotate 3s linear infinite;
    }

    @keyframes rotate {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .ticker-content {
        flex: 1;
        overflow: hidden;
    }

    .ticker-wrapper {
        overflow: hidden;
        white-space: nowrap;
    }

    .ticker-text {
        display: inline-block;
        color: #fff;
        font-weight: 600;
        font-size: 16px;
        animation: scroll-left 30s linear infinite;
        padding-left: 100%;
    }

    @keyframes scroll-left {
        0% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(-50%);
        }
    }
</style>
