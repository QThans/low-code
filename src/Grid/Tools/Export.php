<?php

namespace Thans\Bpm\Grid\Tools;

use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class Export implements FromCollection, Responsable, WithMapping, WithHeadings
{
    use Exportable;

    protected $data;

    protected $map;

    /**
     * It's required to define the fileName within
     * the export class when making use of Responsable.
     */
    private $fileName = 'invoices.xlsx';

    /**
     * Optional Writer Type
     */
    private $writerType = Excel::XLSX;

    /**
     * Optional headers
     */
    private $headers = [
        'Content-Type' => 'text/csv',
    ];
    public function __construct($data)
    {
        $this->data = $data;
    }
    public function collection()
    {
        return $this->data;
    }
    public function map($item): array
    {
        $maps = [];
        foreach ($this->map as $key => $value) {
            $maps[] = isset($item[$key]) ? $item[$key] : '';
        }
        return [$maps];
    }
    public function headings(): array
    {
        foreach ($this->map as $key => $value) {
            $title[] = $value;
        }
        return $title;
    }
    public function setMap($map)
    {
        $this->map = $map;
    }
}
