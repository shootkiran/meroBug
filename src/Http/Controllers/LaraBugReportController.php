<?php

namespace MeroBug\Http\Controllers;

use Illuminate\Http\Request;

class MeroBugReportController
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function report(Request $request)
    {
        /** @var \MeroBug\MeroBug $laraBug */
        $laraBug = app('merobug');

        $laraBug->handle(
            new \ErrorException($request->input('message')),
            'javascript',
            [
                'file' => $request->input('file'),
                'line' => $request->input('line'),
                'message' => $request->input('message'),
                'stack' => $request->input('stack'),
                'url' => $request->input('url'),
            ]
        );

        return response('ok', 200);
    }
}
