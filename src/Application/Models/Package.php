<?php
namespace Jleagle\Packages\Application\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
  protected $table = 'packages';
  protected $hidden = [];
  protected $fillable = ['author', 'name'];

  public function dependencies()
  {
    return $this
      ->belongsToMany(
        'Jleagle\Packages\Application\Models\Package',
        null,
        'package_id',
        'dependency_id'
      )
      ->withPivot('version')
      ->withTimestamps();
  }

  public function packages()
  {
    return $this
      ->belongsToMany(
        'Jleagle\Packages\Application\Models\Package',
        null,
        'dependency_id',
        'package_id'
      )
      ->withPivot('version')
      ->withTimestamps();
  }

  public function authors()
  {
    return $this->belongsToMany('Jleagle\Packages\Application\Models\Author');
  }

  public function maintainers()
  {
    return $this->belongsToMany(
      'Jleagle\Packages\Application\Models\Maintainer'
    );
  }

  public function tags()
  {
    return $this->belongsToMany('Jleagle\Packages\Application\Models\Tag');
  }

  // Accessors
  public function getFullNameAttribute()
  {
    return $this->author . '/' . $this->name;
  }

  public function getFullNameSpacesAttribute()
  {
    return $this->author . ' / ' . $this->name;
  }

  public function getLastUpdatedAttribute()
  {
    // http://stackoverflow.com/questions/1416697/converting-timestamp-to-time-ago-in-php-e-g-1-day-ago-2-days-ago

    $full = false;

    $now = new \DateTime();
    $ago = new \DateTime($this->updated_at);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
      'y' => 'year',
      'm' => 'month',
      'w' => 'week',
      'd' => 'day',
      'h' => 'hour',
      'i' => 'minute',
      's' => 'second',
    ];
    foreach($string as $k => &$v)
    {
      if($diff->$k)
      {
        $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
      }
      else
      {
        unset($string[$k]);
      }
    }

    if(!$full)
    {
      $string = array_slice($string, 0, 1);
    }
    return $string ? implode(', ', $string) . ' ago' : 'just now';
  }
}
