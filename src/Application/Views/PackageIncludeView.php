<?php
namespace Jleagle\Packages\Application\Views;

class PackageIncludeView extends BaseView
{
  private $_package;

  /**
   * @param array $packages
   */
  public function __construct(array $packages)
  {
    $this->_package = $packages;
  }

  /**
   * @return array
   */
  public function getPackage()
  {
    return $this->_package;
  }
}
