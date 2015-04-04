<?php
namespace Jleagle\Packages\Application\Controllers;

use Illuminate\Database\Capsule\Manager as DB;
use Jleagle\Packages\Application\Models\Package;
use Jleagle\Packages\Application\Models\Stat;
use Jleagle\Packages\Application\Views\StatsView;

class StatsController extends BaseController
{
  public function defaultAction()
  {
    $memcache = new \Memcached;
    $stats1 = $memcache->get('stats1');
    if($stats1 === false)
    {
      // Line
      $stats = Stat
        ::select(
          DB::raw('max(`packages`) as packages'),
          DB::raw('sum(`added`) as added'),
          //DB::raw('sum(`removed`) as removed'),
          DB::raw('date(`created_at`) as date')
        )
        ->orderBy('date', 'asc')
        ->groupBy('date')
        ->get();

      $stats1 = [['Date', 'Total', 'New']];
      foreach($stats as $stat)
      {
        $stats1[] = [
          $stat->date,
          (int)$stat->packages,
          (int)$stat->added,
          //(int)$stat->removed
        ];
      }

      $memcache->set('stats1', $stats1, self::DAY);
    }

    $memcache = new \Memcached;
    $stats2 = $memcache->get('stats2');
    if($stats2 === false)
    {
      $stats2 = Package
        ::select(['domain', DB::raw('count(domain) as count')])
        ->groupBy('domain')
        ->where('domain', '<>', '')
        ->orderBy('count', 'desc')
        ->get()
        ->toArray();

      $memcache->set('stats2', $stats2, self::DAY);
    }

    $memcache = new \Memcached;
    $stats3 = $memcache->get('stats3');
    if($stats3 === false || true)
    {
      $stats3 = Package
        ::select(['type', DB::raw('count(type) as count')])
        ->groupBy('type')
        ->where('type', '<>', '')
        ->having('count', '>', 9)
        ->orderBy('count', 'desc')
        ->get()
        ->toArray();

      $memcache->set('stats3', $stats3, self::DAY);
    }

    return new StatsView($stats1, $stats2, $stats3);
  }
}
