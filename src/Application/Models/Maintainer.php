<?php
namespace Jleagle\Packages\Application\Models;

use Illuminate\Database\Eloquent\Model;

class Maintainer extends Model
{
  protected $table = 'maintainers';
  protected $hidden = [];
  protected $fillable = ['name'];

  public function packages()
  {
    return $this->belongsToMany('Jleagle\Packages\Application\Models\Package');
  }
}
