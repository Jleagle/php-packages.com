<?php
namespace Jleagle\Packages\Application\Controllers;

use Jleagle\Packages\Application\Views\InfoView;

class InfoController extends BaseController
{
  public function defaultAction()
  {
    $this->_setTitle('Info');

    return new InfoView();
  }
}
