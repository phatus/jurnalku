<?php

namespace App\Http\Controllers;

use App\Services\ReportGeneratorService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $generator;

    public function __construct(ReportGeneratorService $generator)
    {
        $this->generator = $generator;
    }

    public function download(Request $request)
    {
        $request->validate([
            'month' => 'required|numeric|min:1|max:12',
            'year' => 'required|numeric|min:2020|max:2030',
            'type' => 'required|in:catkin,jurnal,labul',
        ]);

        $month = $request->month;
        $year = $request->year;
        $type = $request->type;

        switch ($type) {
            case 'catkin':
                $path = $this->generator->generateCatkin($month, $year);
                break;
            case 'jurnal':
                $path = $this->generator->generateJurnal($month, $year);
                break;
            case 'labul':
                $path = $this->generator->generateLabul($month, $year);
                break;
            default:
                abort(404);
        }

        return response()->download($path)->deleteFileAfterSend(true);
    }
}
