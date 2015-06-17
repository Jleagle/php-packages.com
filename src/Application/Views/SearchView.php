<?php
namespace Jleagle\Packages\Application\Views;

use Jleagle\HtmlBuilder\Bootstrap\Pagination;
use Jleagle\Packages\Application\Forms\SearchForm;
use Packaged\Form\Render\FormElementRenderer;

class SearchView extends BaseView
{
  private $_packages;
  private $_data;
  private $_pages;

  /**
   * @param array    $packages
   * @param string[] $data
   * @param int      $pages
   */
  public function __construct(array $packages, array $data, $pages)
  {
    $this->_packages = $packages;
    $this->_data = $data;
    $this->_pages = $pages;
  }

  public function getForm()
  {
    $renderer1 = new FormElementRenderer(
      '<div class="col-xs-12 col-sm-6 col-md-4"><div class="form-group">{{label}}{{input}}</div></div>'
    );
    $renderer2 = new FormElementRenderer(
      '<div class="col-xs-12 col-sm-6 col-md-4">{{label}}<div class="form-group">{{input}}</div></div>'
    );

    $orders = [
      'downloads' => 'Downloads',
      'name'      => 'Name',
      'author'    => 'Author',
    ];

    $form = new SearchForm('/', 'get');
    $form->hydrate($this->_data);
    $form->showAutoSubmitButton(false);

    foreach($form->getElements() as $element)
    {
      $element->setRenderer($renderer1);
    }

    $form->getElement('types')
      ->setAttributes(['class' => 'form-control', 'id' => 'types']);

    $form->getElement('tags')
      ->setAttributes(['class' => 'form-control', 'id' => 'tags']);

    $form->getElement('search')
      ->setAttributes(['class' => 'form-control', 'placeholder' => 'Search']);

    $form->getElement('authors')
      ->setAttributes(['class' => 'form-control', 'id' => 'authors']);

    $form->getElement('maintainers')
      ->setAttributes(['class' => 'form-control', 'id' => 'maintainers']);

    $form->getElement('order')
      ->setAttributes(['class' => 'form-control'])
      ->setOption('values', $orders);

    $form->getElement('submit')
      ->setAttributes(['class' => 'btn btn-success', 'value' => 'Search'])
      ->setRenderer($renderer2)
      ->setAttribute('name', null);

    return $form;
  }

  public function getPagination()
  {
    $link = http_build_query(
      array_filter(
        [
          'types'   => $this->_data['types'],
          'tags'    => $this->_data['tags'],
          'search'  => $this->_data['search'],
          'authors' => $this->_data['authors'],
          'order'   => $this->_data['order'],
          'page'    => urldecode('{{page}}'),
        ]
      )
    );
    $link = '/?' . str_replace(['%7B', '%7D'], ['{', '}'], $link);
    return new Pagination($this->_data['page'], $this->_pages, $link);
  }

  public function getPackages()
  {
    return $this->_packages;
  }
}
