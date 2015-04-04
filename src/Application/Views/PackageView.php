<?php
namespace Jleagle\Packages\Application\Views;

use Jleagle\Packages\Application\Models\Package;

class PackageView extends BaseView
{
  private $_package;

  public function __construct(Package $package)
  {
    $this->_package = $package;
  }

  /**
   * @return Package
   */
  public function getPackage()
  {
    return $this->_package;
  }

  public function getRepoUrl()
  {
    $repo = $this->getPackage()->repo;
    $repo = str_replace(
      ['git://github.com/', 'git@github.com:'],
      ['https://github.com/', 'https://github.com/'],
      $repo
    );
    return $repo;
  }
}
