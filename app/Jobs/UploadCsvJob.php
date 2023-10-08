<?php

namespace App\Jobs;

use App\Models\JobStatus;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UploadCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * CSV columns by order.
     *
     * @var array<string>
     */
    protected $csvColumns = [
        'UNIQUE_KEY',
        'PRODUCT_TITLE',
        'PRODUCT_DESCRIPTION',
        'STYLE#',
        'SANMAR_MAINFRAME_COLOR',
        'SIZE',
        'COLOR_NAME',
        'PIECE_PRICE',
    ];

    protected $cacheKey = 'csv_upload_';

    protected $jobStatusId;
    /**
     * Create a new job instance.
     */
    public function __construct($jobStatusId)
    {
        $this->jobStatusId = $jobStatusId;
    }

    /**
     * Execute the job.
     * @throws \Exception
     */
    public function handle(): void
    {
        $jobStatus = JobStatus::find($this->jobStatusId);

        if ($jobStatus) {
            $this->jobStatusUpdate($jobStatus, JobStatus::STATUS_PROCESSING);
            // Process CSV file
            $this->processCsv($jobStatus);
        }
    }

    /**
     * @throws \Exception
     */
    protected function processCsv($jobStatus): void
    {
        $file = storage_path('app/uploads/' . $jobStatus->filename);
        $records = array_map('str_getcsv', file($file));
        if (! count($records) > 0) {
            // Update job status
            $this->jobStatusUpdate($jobStatus, JobStatus::STATUS_FAILED);
            throw new \Exception('Invalid CSV file.');
        }

        // Extract column indexes by column name
        $columnIndexes = [];

        foreach ($this->csvColumns as $column) {
            if (!in_array($column, $this->clearEncodingString($records[0]))) {
                // Update job status
                $this->jobStatusUpdate($jobStatus, JobStatus::STATUS_FAILED);
                throw new \Exception('Column ' . $column . ' not found in CSV file.');
            }

            // Insert index in order
            $columnIndexes[] = array_search($column, $records[0]);
        }

        // Remove the header
        array_shift($records);
        $cacheKeys = [];

        try {
            DB::beginTransaction();

            foreach ($records as $record) {

                // Get column data by index
                $columnData = Arr::map($columnIndexes, function ($index) use ($record) {
                    return $record[$index] ?? '';
                });

                $columnData = $this->clearEncodingString($columnData);

                // Combine with column names
                $columnData = array_combine($this->csvColumns, $columnData);

                // Insert cache key
                $cacheKey = $this->cacheKey . $columnData['UNIQUE_KEY'];
                $cacheKeys[] = $cacheKey;

                // Race condition check
                if (Cache::has($cacheKey)) {
                    continue;
                }

                // Add cache key (prevent multiple attempts on the same record)
                Cache::add($cacheKey, true);

                Product::updateOrCreate(
                    ['unique_key' => $columnData['UNIQUE_KEY']],
                    [
                        'product_title' => $columnData['PRODUCT_TITLE'],
                        'product_description' => $columnData['PRODUCT_DESCRIPTION'],
                        'style' => $columnData['STYLE#'],
                        'sanmar_mainframe_color' => $columnData['SANMAR_MAINFRAME_COLOR'],
                        'size' => $columnData['SIZE'],
                        'color_name' => $columnData['COLOR_NAME'],
                        'piece_price' => $columnData['PIECE_PRICE'],
                    ]);
            }

            // Update job status
            $this->jobStatusUpdate($jobStatus, JobStatus::STATUS_COMPLETED);
            DB::commit();

            $this->clearCacheKeys($cacheKeys);

        } catch (\Exception $e) {
            DB::rollBack();

            // Update job status
            $this->jobStatusUpdate($jobStatus, JobStatus::STATUS_FAILED);
            DB::commit();
            $this->clearCacheKeys($cacheKeys);
            throw $e;
        }
    }

    /**
     * @param JobStatus $jobStatus
     * @param $status
     * @return void
     */
    protected function jobStatusUpdate(JobStatus $jobStatus, $status): void
    {
        $jobStatus->status = $status;
        $jobStatus->save();
    }

    /**
     * @param $cacheKeys
     * @return void
     */
    protected function clearCacheKeys($cacheKeys): void
    {
        foreach ($cacheKeys as $cacheKey) {
            Cache::forget($cacheKey);
        }
    }

    /**
     * @param array|string $value
     * @return array|false|string|string[]|null
     */
    protected function clearEncodingString($value)
    {
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $key => $val) {
                $clean[$key] = mb_convert_encoding($val, 'UTF-8', 'UTF-8');
                $clean[$key] = preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $clean[$key] );
            }
            return $clean;
        }

        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');;
        return preg_replace( '/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $value);
    }
}
