<?php
namespace Jleagle\Packages\Application;

use Cubex\Kernel\ApplicationKernel;
use Jleagle\Packages\Application\Controllers\InfoController;
use Jleagle\Packages\Application\Controllers\PackageController;
use Jleagle\Packages\Application\Controllers\SearchController;
use Jleagle\Packages\Application\Controllers\StatsController;
use Packaged\Dispatch\AssetManager;

class Application extends ApplicationKernel
{
  protected function _init()
  {
    $am = AssetManager::aliasType('src');

    $am->requireCss(
      [
        '//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css',
        'css/jquery.select2',
        'css/jquery.select2-bootstrap',
        'css/main',
      ]
    );

    $am->requireJs(
      [
        '//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js',
        '//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js',
        'js/jquery.select2.min',
        'js/jquery.highlight',
        'js/main',
      ]
    );
  }

  public function getRoutes()
  {
    return [
      ''        => new SearchController(),
      'search'  => new SearchController(),
      'stats'   => new StatsController(),
      'info'    => new InfoController(),
      'package' => new PackageController(),
    ];
  }
}
