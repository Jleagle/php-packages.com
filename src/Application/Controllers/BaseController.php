<?php
namespace Jleagle\Packages\Application\Controllers;

use Cubex\View\LayoutController;
use Jleagle\Packages\Application\Views\LayoutView;

class BaseController extends LayoutController
{
  const SECOND = 1;
  const MINUTE = 60;
  const HOUR = 3600;
  const DAY = 86400;
  const WEEK = 604800;

  const PER_PAGE = 20;

  protected function _init()
  {
    $this->setLayout(new LayoutView($this));

    $path = trim($this->_getRequest()->path(), '/');
    $this->layout()->setData('path', $path);
  }
}
