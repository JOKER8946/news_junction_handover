<?php
// Set header to JSON for API response


if (isset($_GET['fetch']) && $_GET['fetch'] == 'true') {
    header('Content-Type: application/json');
    error_reporting(0); 
    ini_set('display_errors', 0);
    ob_clean(); 
    flush();

    // Yahoo Finance API symbols for stocks
    $stocks = [
        "NIFTY 50" => "^NSEI",
        "SENSEX" => "^BSESN",
        "Reliance" => "RELIANCE.NS",
        "TCS" => "TCS.NS",
        "Infosys" => "INFY.NS",
        "HDFC Bank" => "HDFCBANK.NS",
        "ICICI Bank" => "ICICIBANK.NS",
        "Tata Motors" => "TATAMOTORS.NS"
    ];

    // Function to fetch stock data
    function getStockData($symbol)
    {
        $url = "https://query1.finance.yahoo.com/v8/finance/chart/$symbol";
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ["User-Agent: Mozilla/5.0"]
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);

        if (isset($data['chart']['result'][0]['meta'])) {
            $meta = $data['chart']['result'][0]['meta'];
            return [
                "price" => $meta['regularMarketPrice'] ?? "N/A",
                "change" => ($meta['regularMarketPrice'] - $meta['previousClose']) ?? 0,
                "change_percent" => $meta['regularMarketPrice'] && $meta['previousClose'] ?
                    round((($meta['regularMarketPrice'] - $meta['previousClose']) / $meta['previousClose']) * 100, 2) : 0
            ];
        }
        return ["price" => "N/A", "change" => 0, "change_percent" => 0];
    }

    // Fetch data for all stocks (Himanshu test)
    $stock_data = [];
    foreach ($stocks as $name => $symbol) {
        $data = getStockData($symbol);
        $stock_data[] = ["name" => $name] + $data;
    }

    echo json_encode($stock_data);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Market Ticker</title>
    <style>
        
        .ticker-container {
            width: 100%;
            background: #111;
            color: white;
            overflow: hidden;
            white-space: nowrap;
            padding: 10px 0;
            /* position: fixed; */
            top: 0;
            z-index: 1000;
        }

       
        .ticker {
            display: flex;
            gap: 20px;
            animation: scroll-left 20s linear infinite;
        }

        
        .up { color: #00ff00; }
        .down { color: #ff0000; }

       
        @keyframes scroll-left {
            from { transform: translateX(100%); }
            to { transform: translateX(-100%); }
        }
    </style>
</head>
<body>

<div class="ticker-container">
    <div class="ticker" id="market-ticker">Loading stock data...</div>
    <div class="custom-scroll-text">Deeplit Research AI</div>
</div>

<script>
    function fetchStockData() {
        fetch("sharemarket.php?fetch=true") 
            .then(response => response.json())
            .then(data => {
                let tickerHTML = data.map(stock => {
                    const arrow = stock.change >= 0 ? "⬆" : "⬇";
                    const changeClass = stock.change >= 0 ? "up" : "down";
                    return `<span>${stock.name}: ₹${stock.price} <span class="${changeClass}">${arrow} ${stock.change.toFixed(2)}</span> | </span>`;
                }).join(" ");

                document.getElementById("market-ticker").innerHTML = tickerHTML;
            })
            .catch(error => console.error("Error fetching market data:", error));
    }

    
    fetchStockData();
    setInterval(fetchStockData, 10000);
</script>

</body>
</html>
