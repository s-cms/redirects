<?php

namespace SmartCms\Redirects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

/**
 * @property string $old_url
 * @property string $new_url
 * @property int $status_code
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Redirect extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function getTable()
    {
        return config('redirects.table_name', 'redirects');
    }
}
