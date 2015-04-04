<?php
namespace Jleagle\Packages\Application\Forms;

use Packaged\Form\Form;

class SearchForm extends Form
{
  /**
   * @type hidden
   */
  public $types;

  /**
   * @type hidden
   */
  public $tags;

  /**
   * @type hidden
   */
  public $authors;

  /**
   * @type text
   */
  public $search;

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
