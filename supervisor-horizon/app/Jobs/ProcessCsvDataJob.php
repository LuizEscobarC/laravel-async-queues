<?php

namespace App\Jobs;

use App\Models\TemperatureReading;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessCsvDataJob implements ShouldQueue
{
    use Queueable;

    protected $csvData;
    protected $batchSize;

    /**
     * Create a new job instance.
     */
    public function __construct(array $csvData, int $batchSize = 10)
    {
        $this->csvData = $csvData;
        $this->batchSize = $batchSize;
        
        // Horizon gerencia automaticamente o balanceamento das filas
        // Todos os jobs vão para a fila 'default' e o Horizon distribui conforme necessário
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProcessCsvDataJob started with Horizon', [
            'queue' => $this->queue ?? 'default',
            'batch_size' => $this->batchSize,
            'records_count' => count($this->csvData)
        ]);

        $processedCount = 0;
        
        foreach ($this->csvData as $row) {
            try {
                // Simula processamento pesado
                sleep(1);
                
                TemperatureReading::create([
                    'reading_date' => Carbon::parse($row['data']),
                    'temperature' => (float) $row['temperatura']
                ]);
                
                $processedCount++;
                
            } catch (\Exception $e) {
                Log::error('Error processing CSV row with Horizon', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
            }
        }

        Log::info('ProcessCsvDataJob completed with Horizon', [
            'queue' => $this->queue ?? 'default',
            'processed_count' => $processedCount
        ]);
    }
}
