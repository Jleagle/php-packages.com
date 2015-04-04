<?php
namespace Jleagle\Packages\Application\Controllers;

use Illuminate\Database\Capsule\Manager as DB;
use Jleagle\Packages\Application\Models\Author;
use Jleagle\Packages\Application\Models\Package;
use Jleagle\Packages\Application\Models\Tag;
use Jleagle\Packages\Application\Views\SearchView;

class SearchController extends BaseController
{
  public function getRoutes()
  {
    return [
      ''             => 'searchx',
      'types'        => 'ajaxSearchPackageTypes',
      'tags'         => 'ajaxSearchTags',
      'tags-init'    => 'ajaxSearchTagsInit',
      'authors'      => 'ajaxSearchAuthors',
      'authors-init' => 'ajaxSearchAuthorsInit',
    ];
  }

  /**
   * @return SearchView
   */
  public function searchx()
  {
    // Get post data
    $data = $this->_getRequest()->query->all();
    $data['types'] = idx($data, 'types', '');
    $data['tags'] = idx($data, 'tags', '');
    $data['search'] = idx($data, 'search', '');
    $data['authors'] = idx($data, 'authors', '');
    $data['order'] = idx($data, 'order', 'downloads');
    $data['page'] = idx($data, 'page', 1);
    ksort($data);

    // Cache
    $memcache = new \Memcached;
    $key = 'search-' . md5(json_encode(array_values($data)));
    $search = $memcache->get($key);
    if($search === false)
    {

      // Start search
      $packages = Package
        ::select('author', 'name', 'description', 'type', 'downloads_m')
        ->where('name', '!=', '')
        ->where('author', '!=', '');

      // Types
      if($data['types'])
      {
        $types = explode(',', $data['types']);
        $packages->whereIn('type', $types);
      }

      // Tags
      if($data['tags'])
      {
        $tags = explode(',', $data['tags']);
        $tags = array_map('trim', $tags);
        $tags = array_filter($tags, 'is_numeric');
        $tags = implode(',', $tags);
        $tags = DB::select(
          'SELECT package_id FROM package_tag WHERE tag_id IN(' . $tags . ') GROUP BY package_id'
        );

        $packageIds = ipull($tags, 'package_id');
        $packages->whereIn('id', $packageIds);
      }

      // Search
      if($data['search'])
      {
        $packages->where(
          function ($query) use ($data)
          {
            $query
              ->where('author', 'LIKE', '%' . $data['search'] . '%')
              ->orWhere('name', 'LIKE', '%' . $data['search'] . '%')
              ->orWhere('description', 'LIKE', '%' . $data['search'] . '%');
          }
        );
      }

      // Authors
      if($data['authors'])
      {
        $authors = explode(',', $data['authors']);
        $authors = array_map('trim', $authors);
        $authors = array_filter($authors, 'is_numeric');
        $authors = implode(',', $authors);
        $authors = DB::select(
          'SELECT package_id FROM author_package WHERE author_id IN(' . $authors . ') GROUP BY package_id'
        );

        $packageIds = ipull($authors, 'package_id');
        $packages->whereIn('id', $packageIds);
      }

      // Order
      if(in_array($data['order'], ['name', 'author']))
      {
        $packages->orderBy($data['order'], 'asc');
      }
      $packages->orderBy('downloads_m', 'desc');

      // Pagination
      $count = $packages->count();
      $skip = ($data['page'] - 1) * self::PER_PAGE;
      $packages->skip($skip)->take(self::PER_PAGE);
      $pages = (int)ceil($count / self::PER_PAGE);

      // Make into an array as you can not serialize PDO objects
      $packages = $packages->get()->toArray();

      // Cache
      $memcache->set($key, [$pages, $packages], self::WEEK);
    }
    else
    {
      list($pages, $packages) = $search;
    }

    return new SearchView($packages, $data, $pages);
  }

  public function ajaxSearchPackageTypes()
  {
    $data = $this->_getRequest()->query->all();
    $type = idx($data, 'search', '');

    $memcache = new \Memcached;
    $key = 'searchTypes-' . $type;
    $searchTypes = $memcache->get($key);
    if($searchTypes === false)
    {

      $paginate = Package
        ::select('type')
        ->where('type', '<>', '')
        ->where('type', 'like', '%' . $type . '%')
        ->groupBy('type')
        ->orderBy('type', 'asc')
        ->get();

      $searchTypes = [];
      foreach($paginate as $item)
      {
        $searchTypes[] = [
          'id'   => $item->type,
          'text' => $item->type,
        ];
      }

      $memcache->set($key, $searchTypes, self::WEEK);
    }

    return [
      'results'  => $searchTypes,
      'lastPage' => 1,
    ];
  }

  public function ajaxSearchTags()
  {
    $data = $this->_getRequest()->query->all();
    $name = idx($data, 'search', '');

    $memcache = new \Memcached;
    $key = 'searchTags-' . $name;
    $searchTags = $memcache->get($key);
    if($searchTags === false)
    {

      $paginate = Tag
        ::select('id', 'name')
        ->where('name', 'like', '%' . $name . '%')
        ->orderBy('name', 'asc')
        ->get();

      $searchTags = [];
      foreach($paginate as $item)
      {
        $searchTags[] = [
          'id'   => $item->id,
          'text' => $item->name,
        ];
      }

      $memcache->set($key, $searchTags, self::WEEK);
    }

    return [
      'results'  => $searchTags,
      'lastPage' => 1,
    ];
  }

  public function ajaxSearchTagsInit()
  {
    $data = $this->_getRequest()->query->all();

    $ids = idx($data, 'ids', '');
    $ids = explode(',', $ids);
    $ids = array_filter($ids);
    asort($ids);

    $memcache = new \Memcached;
    $key = 'searchTagsInit-' . implode('-', $ids);
    $searchTagsInit = $memcache->get($key);
    if($searchTagsInit === false)
    {
      if(!$ids)
      {
        return [];
      }

      $packages = Tag
        ::select('id', 'name')
        ->whereIn('id', $ids)
        ->orderBy('name', 'asc')
        ->get();

      $searchTagsInit = [];
      foreach($packages as $item)
      {
        $searchTagsInit[] = [
          'id'   => $item->id,
          'text' => $item->name,
        ];
      }

      $memcache->set($key, $searchTagsInit, self::WEEK);
    }

    return $searchTagsInit;
  }

  public function ajaxSearchAuthors()
  {
    $data = $this->_getRequest()->query->all();
    $name = idx($data, 'search', '');

    $memcache = new \Memcached;
    $key = 'searchAuthors-' . $name;
    $searchAuthors = $memcache->get($key);
    if($searchAuthors === false)
    {
      $paginate = Author
        ::select('id', 'name')
        ->where('name', 'like', '%' . $name . '%')
        ->orderBy('name', 'asc')
        ->get();

      $searchAuthors = [];
      foreach($paginate as $item)
      {
        $searchAuthors[] = [
          'id'   => $item->id,
          'text' => $item->name,
        ];
      }

      $memcache->set($key, $searchAuthors, self::WEEK);
    }

    return [
      'results'  => $searchAuthors,
      'lastPage' => 1,
    ];
  }

  public function ajaxSearchAuthorsInit()
  {
    $data = $this->_getRequest()->query->all();
    $ids = idx($data, 'ids', '');
    $ids = explode(',', $ids);
    $ids = array_filter($ids);
    asort($ids);

    $memcache = new \Memcached;
    $key = 'searchAuthorsInit-' . implode('-', $ids);
    $searchAuthorsInit = $memcache->get($key);
    if($searchAuthorsInit === false)
    {
      if(!$ids)
      {
        return [];
      }

      $authors = Author
        ::select('id', 'name')
        ->whereIn('id', $ids)
        ->orderBy('name', 'asc')
        ->get();

      $searchAuthorsInit = [];
      foreach($authors as $item)
      {
        $searchAuthorsInit[] = [
          'id'   => $item->id,
          'text' => $item->name,
        ];
      }

      $memcache->set($key, $searchAuthorsInit, self::WEEK);
    }

    return $searchAuthorsInit;
  }
}
