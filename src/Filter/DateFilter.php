<?php

namespace Merkeleon\Table\Filter;

use Carbon\Carbon;
use Merkeleon\ElasticReader\Elastic\SearchModel as ElasticSearchModel;
use Merkeleon\Table\Filter;
use DB;


class DateFilter extends Filter
{

    protected $viewPath   = 'filters.date';
    protected $validators = 'date';
    protected $dateFormat = Carbon::DEFAULT_TO_STRING_FORMAT;

    protected function prepare()
    {
        $value = request('f_' . $this->name);
        if ($from = array_get($value, 'from'))
        {
            $this->value['from'] = $from;
        }
        if ($to = array_get($value, 'to'))
        {
            $this->value['to'] = $to;
        }
        if ($dateFormat = array_get($value, 'date_format'))
        {
            $this->dateFormat = $dateFormat;
        }
    }

    protected function applyEloquentFilter($dataSource)
    {
        if ($from = array_get($this->value, 'from'))
        {
            $dataSource = $dataSource->where(DB::raw('DATE_FORMAT(' . $dataSource->getModel()
                                                                                 ->getTable() . '.' . $this->name . ', "%Y-%m-%d")'), '>=', date('Y-m-d', strtotime($from)));
        }

        if ($to = array_get($this->value, 'to'))
        {
            $dataSource = $dataSource->where(DB::raw('DATE_FORMAT(' . $dataSource->getModel()
                                                                                 ->getTable() . '.' . $this->name . ', "%Y-%m-%d")'), '<=', date('Y-m-d', strtotime($to)));
        }

        return $dataSource;
    }

    protected function applyCollectionFilter($dataSource)
    {
        if ($from = array_get($this->value, 'from'))
        {
            $dataSource = $dataSource->filter(function ($item, $key) use ($from) {
                return strtotime($item->{$this->name}) >= strtotime($from);
            });
        }

        if ($to = array_get($this->value, 'to'))
        {
            $dataSource = $dataSource->filter(function ($item, $key) use ($to) {
                return strtotime($item->{$this->name}) <= strtotime($to);
            });
        }

        return $dataSource;
    }

    public function validate()
    {
        if (!$this->value)
        {
            return true;
        }

        $keyFrom = 'f_' . $this->name . '.from';
        $keyTo   = 'f_' . $this->name . '.to';

        $validator = validator(request()->all(), [
            $keyFrom => $this->validators,
            $keyTo   => $this->validators,
        ], [], [
            $keyFrom => $this->label . ' ' . trans('table::table.filter.date.from'),
            $keyTo   => $this->label . ' ' . trans('table::table.filter.date.to'),
        ]);

        if ($validator->fails())
        {
            $errors = $validator->errors()
                                ->toArray();

            $this->error['from'] = $errors[$keyFrom][0] ?? null;
            $this->error['to']   = $errors[$keyTo][0] ?? null;

            return false;
        }

        return true;
    }

    protected function applyElasticSearchFilter(ElasticSearchModel $dataSource)
    {
        $from = $this->prepareDate(array_get($this->value, 'from'));
        $to   = $this->prepareDate(array_get($this->value, 'to'));

        $dataSource->query()
                   ->range($this->name, $from, $to);

        return $dataSource;
    }

    protected function prepareDate($value)
    {
        if (!$value)
        {
            return null;
        }

        return (new Carbon($value))->format($this->dateFormat);
    }
}