<?php
namespace Jleagle\Packages\Application\Forms;

use Packaged\Form\Form;

class SearchForm extends Form
{
  /**
   * @type text
   */
  public $search;
  /**
   * @type hidden
   */
  public $authors;
  /**
   * @type hidden
   */
  public $maintainers;
  /**
   * @type hidden
   */
  public $types;
  /**
   * @type hidden
   */
  public $tags;

  /**
   * @type select
   */
  public $order;

  /**
   * @type submit
   * @label &nbsp;
   */
  public $submit;
}
