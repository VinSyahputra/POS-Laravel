<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrinterSetting extends Model
{
    protected $fillable = ['printer_name', 'paper_width'];

    protected function casts(): array
    {
        return [
            'paper_width' => 'string',
        ];
    }
}
