<?php
namespace Jleagle\Packages\Application\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
  protected $table = 'tags';
  protected $hidden = [];
  protected $fillable = ['name'];

  public function packages()
  {
    return $this->belongsToMany('Jleagle\Packages\Application\Models\Package');
  }
}
