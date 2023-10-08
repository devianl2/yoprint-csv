<?php

namespace App\Http\Controllers;

use App\Http\Requests\CsvUploadRequest;
use App\Jobs\UploadCsvJob;
use App\Models\JobStatus;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class UploadController extends Controller
{
    /**
     * Show the upload form.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $jobStatus = JobStatus::orderBy('id', 'desc')->get();
        return view('csv-upload', compact('jobStatus'));
    }

    /**
     * Handle the CSV upload.
     * @param CsvUploadRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function csvUpload(CsvUploadRequest $request)
    {
        // Laravel save uploaded file to storage/app/uploads with hash name
        $file = $request->file('csvFile');
        $fileName = $file->hashName();
        $file->storeAs('uploads', $fileName);

        $jobstatus = new JobStatus();
        $jobstatus->filename = $fileName;
        $jobstatus->status = JobStatus::STATUS_PENDING;
        $jobstatus->save();

        // Dispatch UploadCsvJob
        UploadCsvJob::dispatch($jobstatus->id);

        return back()->with('success', 'File uploaded successfully!');
    }
}
