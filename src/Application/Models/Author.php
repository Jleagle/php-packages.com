<?php
namespace Jleagle\Packages\Application\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
  protected $table = 'authors';
  protected $hidden = [];
  protected $fillable = ['name', 'email', 'homepage', 'role'];

  public function packages()
  {
    return $this->belongsToMany('Jleagle\Packages\Application\Models\Package');
  }

  public function getNameEmailAttribute()
  {
    return $this->name ? $this->name : $this->email;
  }
}
