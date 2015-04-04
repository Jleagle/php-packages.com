<?php
namespace Jleagle\Packages;

use Cubex\Kernel\CubexKernel;
use Illuminate\Database\Capsule\Manager;
use Jleagle\Packages\Application\Application;

class Project extends CubexKernel
{
  protected function _init()
  {
    $this->_database();
  }

  public function defaultAction()
  {
    return new Application();
  }

  private function _database()
  {
    $capsule = new Manager();
    $capsule->addConnection(
      [
        'driver'      => 'mysql',
        'unix_socket' => $this->getConfigItem('mysql', 'unix_socket'),
        'host'        => $this->getConfigItem('mysql', 'host'),
        'database'    => $this->getConfigItem('mysql', 'database'),
        'username'    => $this->getConfigItem('mysql', 'username'),
        'password'    => $this->getConfigItem('mysql', 'password'),
        'charset'     => 'utf8',
        'collation'   => 'utf8_general_ci',
        'prefix'      => '',
      ]
    );

    // Make this Capsule instance available globally via static methods... (optional)
    $capsule->setAsGlobal();

    // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
    $capsule->bootEloquent();
  }
}
