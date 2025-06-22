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
        
        // Define a fila baseada no tamanho do lote
        if ($batchSize > 50) {
            $this->onQueue('high-priority');
        } elseif ($batchSize > 20) {
            $this->onQueue('default');
        } else {
            $this->onQueue('low-priority');
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProcessCsvDataJob started', [
            'queue' => $this->queue,
            'batch_size' => $this->batchSize,
            'records_count' => count($this->csvData)
        ]);

        $processedCount = 0;
        
        foreach ($this->csvData as $row) {
            try {
                // Simula processamento pesado, se não ficaria muito rapido para analisar a fila funcionando
                sleep(1);
                
                TemperatureReading::create(attributes: [
                    'reading_date' => Carbon::parse($row['data']),
                    'temperature' => (float) $row['temperatura']
                ]);
                
                $processedCount++;
                
            } catch (\Exception $e) {
                Log::error('Error processing CSV row', [
                    'error' => $e->getMessage(),
                    'row' => $row
                ]);
            }
        }

        Log::info('ProcessCsvDataJob completed', [
            'queue' => $this->queue,
            'processed_count' => $processedCount
        ]);
    }
}
