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
    protected $description = 'Process CSV climate data using async queues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌡️  Starting CSV climate data processing...');
        
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
            
            $this->info("🚀 Dispatching jobs to queues...");
            
            foreach ($batches as $batch) {
                ProcessCsvDataJob::dispatch($batch, count($batch));
                $jobsDispatched++;
            }
            
            $this->info("✅ Successfully dispatched {$jobsDispatched} jobs");
            $this->info("📈 Jobs distributed across: high-priority, default, and low-priority queues");
            $this->info("🐳 Check Docker containers to see queue workers processing jobs");
            
            Log::info('CSV processing jobs dispatched via command', [
                'total_records' => count($csvData),
                'batch_size' => $batchSize,
                'jobs_dispatched' => $jobsDispatched
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error processing CSV: {$e->getMessage()}");
            Log::error('Error in CSV processing command', ['error' => $e->getMessage()]);
            return 1;
        }
    }
    
    private function readCsvFile($filePath)
    {
        $csvData = [];
        
        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv(stream: $handle); // Lê o cabeçalho
            
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
