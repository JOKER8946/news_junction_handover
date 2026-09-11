# PowerShell script to run the chatbot every 30 seconds
# Run this script to start the automated bot

Write-Host "Starting automated chatbot - runs every 30 seconds" -ForegroundColor Green
Write-Host "Press Ctrl+C to stop the bot" -ForegroundColor Yellow

while ($true) {
    try {
        Write-Host "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss'): Running bot..." -ForegroundColor Cyan
        
        # Run the PHP bot script
        & "C:\xampp\php\php.exe" "C:\xampp\htdocs\chatbot1\bot.php"
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss'): Bot completed successfully" -ForegroundColor Green
        } else {
            Write-Host "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss'): Bot failed with exit code $LASTEXITCODE" -ForegroundColor Red
        }
        
        # Wait 30 seconds before next run
        Start-Sleep -Seconds 30
        
    } catch {
        Write-Host "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss'): Error running bot: $($_.Exception.Message)" -ForegroundColor Red
        Start-Sleep -Seconds 30
    }
} 