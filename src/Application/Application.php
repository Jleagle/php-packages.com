<?php
namespace Jleagle\Packages\Application;

use Cubex\Kernel\ApplicationKernel;
use Jleagle\Packages\Application\Controllers\InfoController;
use Jleagle\Packages\Application\Controllers\PackageController;
use Jleagle\Packages\Application\Controllers\SearchController;
use Jleagle\Packages\Application\Controllers\StatsController;

class Application extends ApplicationKernel
{
  public function getRoutes()
  {
    return [
      ''        => new SearchController(),
      'search'  => new SearchController(),
      'stats'   => new StatsController(),
      'info'    => new InfoController(),
      'package' => new PackageController(),
      ':xx'     => '/' // todo
    ];
  }
}
