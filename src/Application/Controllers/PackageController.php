<?php
namespace Jleagle\Packages\Application\Controllers;

use Behat\Transliterator\Transliterator;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Jleagle\Packages\Application\Models\Author;
use Jleagle\Packages\Application\Models\Maintainer;
use Jleagle\Packages\Application\Models\Package;
use Jleagle\Packages\Application\Models\Stat;
use Jleagle\Packages\Application\Models\Tag;
use Jleagle\Packages\Application\Views\PackageView;
use Jleagle\Packagist;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PackageController extends BaseController
{
  private $_currentPackages = [];

  public function getRoutes()
  {
    return [
      'package' => [
        'cron-old'                => 'cronOld',
        'cron-new'                => 'cronNew',
        'update/:author/:package' => 'update',
        ':author/:package'        => 'package',
        ':author'                 => 'author',
      ]
    ];
  }

  public function package($author, $name)
  {
    try
    {
      $package = Package
        ::where('author', '=', $author)
        ->where('name', '=', $name)
        ->with('packages', 'dependencies')
        ->firstOrFail();
    }
    catch(ModelNotFoundException $e)
    {
      return new RedirectResponse('/');
    }

    $package->tags->sortBy('name');
    $package->authors->sortBy('name');
    $package->dependencies->sortByDesc('downloads_m');
    $package->packages->sortByDesc('downloads_m');

    return new PackageView($package);
  }

  public function update($author, $name)
  {
    $package = Package
      ::where('author', '=', $author)
      ->where('name', '=', $name)
      ->get()
      ->first();

    $minRefresh = Carbon::now()->subMinutes(30)->timestamp;

    if(is_null($package))
    {
      return RedirectResponse::create('/?no_such_package');
    }
    elseif(strtotime($package->updated_at) > $minRefresh)
    {
      return RedirectResponse
        ::create('/package/' . $author . '/' . $name . '?too_quick');
    }
    else
    {
      $this->_refreshPackage($package);
      return RedirectResponse
        ::create('/package/' . $author . '/' . $name);
    }
  }

  public function author()
  {
    // todo
    return new RedirectResponse('/');
  }

  public function cronOld()
  {
    $packages = Package
      ::orderBy('updated_at', 'asc')
      ->limit(3)
      ->get();

    $package = [];
    foreach($packages as $package)
    {
      $package = $this->_refreshPackage($package);
    }

    return $package;
  }

  public function cronNew()
  {
    // Get latest from Packagist
    $packgist = new Packagist('http://packagist.org');
    $packgist = $packgist->all();

    // Get latest from our database
    DB::table('packages')
      ->select(['author', 'name'])
      ->orderBy('name', 'asc')
      ->chunk(
        5000,
        function ($results)
        {
          foreach($results as $result)
          {
            $this->_currentPackages[] =
              $result['author'] . '/' . $result['name'];
          }
        }
      );

    // Diff
    $needToDelete = array_diff($this->_currentPackages, $packgist);
    $needToAdd = array_diff($packgist, $this->_currentPackages);

    // Delete
    foreach($needToDelete as $v)
    {
      list($author, $name) = explode('/', $v, 2);

      DB::table('packages')
        ->where('author', '=', $author)
        ->where('name', '=', $name)
        ->delete();
    }

    // Add
    foreach($needToAdd as $v)
    {
      list($author, $name) = explode('/', $v, 2);

      DB::table('packages')->insert(
        [
          'author'     => $author,
          'name'       => $name,
          'created_at' => date('Y-m-d H:i:s'),
          'updated_at' => date('Y-m-d H:i:s', 0)
        ]
      );
    }

    // Save to stats table
    Stat::create(
      [
        'packages' => count($packgist),
        'added'    => count($needToAdd),
        'removed'  => count($needToDelete),
      ]
    );

    return [
      'added'   => count($needToAdd),
      'deleted' => count($needToDelete),
    ];
  }

  private function _refreshPackage(Package $package)
  {
    // Get latest info
    try
    {
      $packgist = new Packagist();
      $data = $packgist->package($package->author, $package->name);
    }
    catch(\Exception $e)
    {
      return null;
    }

    //print_r($data);exit;

    // Save package data
    $package->description = $data['description'];
    $package->downloads = $data['downloads']['total'];
    $package->downloads_m = $data['downloads']['monthly'];
    $package->downloads_d = $data['downloads']['daily'];
    $package->stars = $data['favers'];
    $package->type = $data['type'];
    $package->repo = $data['repository'];
    $package->domain = parse_url($data['repository'], PHP_URL_HOST);
    $package->save();

    // Maintainers
    $ids = [];
    foreach($data['maintainers'] as $maintainer)
    {
      $model = Maintainer::firstOrCreate(['name' => $maintainer['name']]);
      $ids[] = $model->id;
    }
    $package->maintainers()->sync($ids);

    // Get the latets version
    usort(
      $data['versions'],
      function ($a, $b)
      {
        $a = $a['time'];
        $b = $b['time'];

        if($a == $b)
        {
          return 0;
        }
        return ($a > $b) ? -1 : 1;
      }
    );

    if(isset($data['versions'][0]))
    {
      $data = $data['versions'][0];

      // Authors
      $ids = [];
      if(isset($data['authors']))
      {
        foreach($data['authors'] as $author)
        {
          if(isset($author['email']) && $author['email'])
          {
            $model = Author::firstOrNew(['email' => $author['email']]);
            foreach($author as $field => $value)
            {
              if($value)
              {
                $value = Transliterator::unaccent($value);
                $model->{$field} = $value;
              }
            }
            $model->save();
            $ids[] = $model->id;
          }
        }
      }
      $package->authors()->sync($ids);

      // Tags
      $ids = [];
      if(isset($data['keywords']))
      {
        foreach($data['keywords'] as $tag)
        {
          $model = Tag::firstOrCreate(['name' => $tag]);
          $ids[] = $model->id;
        }
      }
      $package->tags()->sync($ids);

      // Dependencies
      $ids = [];
      if(isset($data['require']))
      {
        foreach($data['require'] as $fullNname => $version)
        {
          $explode = explode('/', $fullNname, 2);
          list($author, $name) = array_pad($explode, 2, '');

          $model = Package
            ::where('author', '=', $author)
            ->where('name', '=', $name)
            ->get()->first();

          if($model)
          {
            $ids[$model->id] = ['version' => $version];
          }
        }
      }
      $package->dependencies()->sync($ids);
    }

    // Mark as updated even if nothing changed.
    $package->touch();

    return $package;
  }
}
