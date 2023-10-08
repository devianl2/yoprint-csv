<?php

namespace App\Http\Controllers;

use App\Http\Requests\CsvUploadRequest;
use Illuminate\Http\Request;

class UploadController extends Controller
{

    /**
     * Show the upload form.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('csv-upload');
    }

    /**
     * Handle the CSV upload.
     * @param CsvUploadRequest $request
     * @return void
     */
    public function csvUpload(CsvUploadRequest $request)
    {

    }
}
