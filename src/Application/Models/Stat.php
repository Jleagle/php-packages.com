<?php
namespace Jleagle\Packages\Application\Models;

use Illuminate\Database\Eloquent\Model;

class Stat extends Model
{
  protected $table = 'stats';
  protected $hidden = [];
  protected $fillable = ['packages', 'added', 'removed'];

  // Accessors
  public function getCreatedDateAttribute()
  {
    return date('Y-m-d', strtotime($this->created_at));
  }
}
