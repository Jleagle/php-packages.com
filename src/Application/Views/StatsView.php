<?php
namespace Jleagle\Packages\Application\Views;

class StatsView extends BaseView
{
  private $_lineStats;
  private $_domains;
  private $_types;

  public function __construct(array $lineStats, array $domains, array $types)
  {
    $this->_lineStats = $lineStats;
    $this->_domains = $domains;
    $this->_types = $types;
  }

  /**
   * @return string
   */
  public function getLineStats()
  {
    return json_encode($this->_lineStats);
  }

  /**
   * @return array
   */
  public function getDomains()
  {
    return $this->_domains;
  }

  /**
   * @return string[][]
   */
  public function getTypes()
  {
    return $this->_types;
  }
}
