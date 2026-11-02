<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $sortBy = 'id';
    protected $maxCount = 10000;
    protected $itemAge = '6 month';
    protected $class;

    public function filter($input) {
        $data = $this->class->all();

        foreach ($this->class->getFillable() as $attr) {
            if (isset($input[$attr])) {
                if (is_array($input[$attr])) {
                    $data = $data->whereIn($attr, $input[$attr]);
                } else {
                    $data = $data->where($attr, $input[$attr]);
                }
            }
        }

        return $data;
    }

    /**
     * The browser submits datetimes as absolute instants (ISO 8601 with an offset), so a client whose
     * timezone rules differ from the server's can't shift them. Convert those to server wall-clock time
     * here so validation, pricing and storage all see a single timezone. Values with no offset are
     * already server wall-clock and pass through unchanged; unparseable values are left for validation.
     */
    protected function convertToServerTime(\Illuminate\Http\Request $req, array $fields) {
        $timezone = config('app.timezone');
        foreach($fields as $field) {
            $value = $req->input($field);
            if(!is_string($value) || $value === '')
                continue;
            try {
                $req->merge([$field => \Carbon\Carbon::parse($value)->setTimezone($timezone)->format('Y-m-d H:i:s')]);
            } catch(\Throwable $e) {
                continue;
            }
        }
    }

    protected function getDataFilter($bill, $startDate) {
        return strtotime($bill->date) > $startDate;
    }

    protected function genFilterData($input) {
        return strtotime(isset($input['start_date']) ?
                $input['start_date'] :
                date('Y-m-d G:i:s', strtotime('-' . $this->itemAge)));
    }

    public function getData($inp = array()) {
        if ($inp instanceof Request) {
            $inp = $inp->all();
        }

        $maxcount = isset($inp['max_count']) ?
                $inp['max_count'] : $this->maxCount;

        $data = $this->filter($inp);

        $filterData = $this->genFilterData($inp);

        if ($filterData == null) {
            $data = $data
                    ->sortBy($this->sortBy)
                    ->slice(0, $maxcount)
                    ->values()->all();
        } else {
            $data = $data->filter(function($item) use($filterData) {
                return $this->getDataFilter($item, $filterData);
            })->sortBy($this->sortBy)->slice(0, $maxcount)->values()->all();
        }

        return [
            'success' => true,
            'data' => $data
        ];
    }
}
