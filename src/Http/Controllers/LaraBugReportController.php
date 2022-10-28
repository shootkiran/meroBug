<?php

namespace MeroBug\Http\Controllers;

use Illuminate\Http\Request;
use MeroBug\Models\MeroBug;

class MeroBugReportController
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function index()
    {
        dd("HOME COMING SOON");
    }
     public function show(MeroBug $meroBug)
    {
        dd("SHow $merobug->id COMING SOON");
    }
}
