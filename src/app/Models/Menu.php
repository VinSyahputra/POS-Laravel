<?php

namespace App\Models;

use App\Enums\Template;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price', 'category_id', 'template'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'category_id' => 'integer',
            'template' => Template::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
