<?php
namespace Jleagle\Packages\Application\Controllers;

use Illuminate\Database\Capsule\Manager as DB;
use Jleagle\Packages\Application\Models\Author;
use Jleagle\Packages\Application\Models\Maintainer;
use Jleagle\Packages\Application\Models\Package;
use Jleagle\Packages\Application\Models\Tag;
use Jleagle\Packages\Application\Views\SearchView;
use Packaged\Helpers\Arrays;

class SearchController extends BaseController
{
  public function getRoutes()
  {
    return [
      ''                 => 'searchx',
      'types'            => 'ajaxSearchPackageTypes',
      'tags'             => 'ajaxSearchTags',
      'tags-init'        => 'ajaxSearchTagsInit',
      'authors'          => 'ajaxSearchAuthors',
      'authors-init'     => 'ajaxSearchAuthorsInit',
      'maintainers'      => 'ajaxSearchMaintainers',
      'maintainers-init' => 'ajaxSearchMaintainersInit',
    ];
  }

  /**
   * @return SearchView
   */
  public function searchx()
  {
    // Get post data
    $data = $this->_getRequest()->query->all();
    $data['types'] = Arrays::value($data, 'types', '');
    $data['tags'] = Arrays::value($data, 'tags', '');
    $data['search'] = Arrays::value($data, 'search', '');
    $data['authors'] = Arrays::value($data, 'authors', '');
    $data['order'] = Arrays::value($data, 'order', 'downloads_m');
    $data['page'] = Arrays::value($data, 'page', 1);
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

      // Maintainers
      if(isset($data['maintainers']) && $data['maintainers'])
      {
        $maintainers = explode(',', $data['maintainers']);
        $maintainers = array_map('trim', $maintainers);
        $maintainers = array_filter($maintainers, 'is_numeric');
        $maintainers = implode(',', $maintainers);
        $maintainers = DB::select(
          'SELECT package_id FROM maintainer_package WHERE maintainer_id IN(' . $maintainers . ') GROUP BY package_id'
        );

        $packageIds = ipull($maintainers, 'package_id');
        $packages->whereIn('id', $packageIds);
      }

      // Order
      switch($data['order'])
      {
        case 'downloads_t':
          $packages->orderBy('downloads', 'desc');
          break;
        case 'downloads_m':
          $packages->orderBy('downloads_m', 'desc');
          break;
        case 'downloads_d':
          $packages->orderBy('downloads_d', 'desc');
          break;
        case 'stars':
          $packages->orderBy('stars', 'desc');
          break;
        case 'author':
          $packages->orderBy('author', 'asc');
          break;
        case 'name':
          $packages->orderBy('name', 'asc');
          break;
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

    $this->_setTitle('Search');

    return new SearchView($packages, $data, $pages);
  }

  public function ajaxSearchPackageTypes()
  {
    $data = $this->_getRequest()->query->all();
    $type = Arrays::value($data, 'search', '');

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
    $name = Arrays::value($data, 'search', '');

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

    $ids = Arrays::value($data, 'ids', '');
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
    $name = Arrays::value($data, 'search', '');

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
    $ids = Arrays::value($data, 'ids', '');
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

  public function ajaxSearchMaintainers()
  {
    $data = $this->_getRequest()->query->all();
    $name = Arrays::value($data, 'search', '');

    $memcache = new \Memcached;
    $key = 'searchMaintainers-' . $name;
    $searchMaintainers = $memcache->get($key);
    if($searchMaintainers === false)
    {
      $paginate = Maintainer
        ::select('id', 'name')
        ->where('name', 'like', '%' . $name . '%')
        ->orderBy('name', 'asc')
        ->get();

      $searchMaintainers = [];
      foreach($paginate as $item)
      {
        $searchMaintainers[] = [
          'id'   => $item->id,
          'text' => $item->name,
        ];
      }

      $memcache->set($key, $searchMaintainers, self::WEEK);
    }

    return [
      'results'  => $searchMaintainers,
      'lastPage' => 1,
    ];
  }

  public function ajaxSearchMaintainersInit()
  {
    $data = $this->_getRequest()->query->all();
    $ids = Arrays::value($data, 'ids', '');
    $ids = explode(',', $ids);
    $ids = array_filter($ids);
    asort($ids);

    $memcache = new \Memcached;
    $key = 'searchMaintainersInit-' . implode('-', $ids);
    $searchMaintainersInit = $memcache->get($key);
    if($searchMaintainersInit === false)
    {
      if(!$ids)
      {
        return [];
      }

      $authors = Maintainer
        ::select('id', 'name')
        ->whereIn('id', $ids)
        ->orderBy('name', 'asc')
        ->get();

      $searchMaintainersInit = [];
      foreach($authors as $item)
      {
        $searchMaintainersInit[] = [
          'id'   => $item->id,
          'text' => $item->name,
        ];
      }

      $memcache->set($key, $searchMaintainersInit, self::WEEK);
    }

    return $searchMaintainersInit;
  }
}
