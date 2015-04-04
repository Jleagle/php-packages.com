<?php
namespace Jleagle\Packages\Application\Views;

use Cubex\View\TemplatedViewModel;

class BaseView extends TemplatedViewModel
{
  public function getPackageInclude($data)
  {
    return new PackageIncludeView($data);
  }
}
