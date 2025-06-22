<?php

namespace App\Console\Commands;

use App\Jobs\ProcessCsvDataJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessCsvDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:csv-data {--batch-size=10 : Number of records per batch}
                                             {--file= : Path to the CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process CSV climate data using Horizon-managed queues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌡️  Starting CSV climate data processing with Horizon...');
        $file = $this->option('file') ?: 'data.csv';
        $csvPath = base_path("{$file}");
        $batchSize = (int) $this->option('batch-size');
        
        if (!file_exists($csvPath)) {
            $this->error("❌ CSV file not found: {$csvPath}");
            return 1;
        }

        try {
            $csvData = $this->readCsvFile($csvPath);
            
            if (empty($csvData)) {
                $this->error('❌ No data found in CSV file');
                return 1;
            }
            
            $this->info("📊 Found " . count($csvData) . " temperature readings");
            $this->info("📦 Batch size: {$batchSize}");
            
            // Divide os dados em lotes
            $batches = array_chunk($csvData, $batchSize);
            $jobsDispatched = 0;
            
            $this->info("🚀 Dispatching jobs to Horizon-managed queues...");
            
            foreach ($batches as $batch) {
                ProcessCsvDataJob::dispatch($batch, count($batch));
                $jobsDispatched++;
            }
            
            $this->info("✅ Successfully dispatched {$jobsDispatched} jobs");
            $this->info("🎯 All jobs sent to 'default' queue - Horizon will auto-balance workers");
            $this->info("⚡ Horizon auto-scaling will adjust workers based on queue load");
            $this->info("🎛️  Access Horizon dashboard at: http://localhost:8000/horizon");
            
            Log::info('CSV processing jobs dispatched via Horizon command', [
                'total_records' => count($csvData),
                'batch_size' => $batchSize,
                'jobs_dispatched' => $jobsDispatched
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error processing CSV: {$e->getMessage()}");
            Log::error('Error in CSV processing command (Horizon)', ['error' => $e->getMessage()]);
            return 1;
        }
    }
    
    private function readCsvFile($filePath)
    {
        $csvData = [];
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle); // Lê o cabeçalho
            
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 2) {
                    $csvData[] = [
                        'data' => $row[0],
                        'temperatura' => $row[1]
                    ];
                }
            }
            
            fclose($handle);
        }
        
        return $csvData;
    }
}
