$(window).scroll(function() {
  if($(window).scrollTop() + $(window).height() > $(document).height() - 1000) {
    console.log('x');
  }
});

$("input#types").select2(
  {
    multiple:           true,
    placeholder:        "Types",
    minimumInputLength: 2,
    openOnEnter:        false,
    ajax:               {
      url:         "/search/types",
      dataType:    'json',
      quietMillis: 200,
      data:        function (term, page)
      {
        return {
          page:   page,
          search: term
        };
      },
      results:     function (data, page)
      {
        return {
          results: data.results,
          more:    (page < data.lastPage)
        };
      }
    },
    initSelection:      function (element, callback)
    {
      var ids = $(element).val();
      if (ids !== "")
      {

        var data = [];
        $.each(
          ids.split(','), function (index, type)
          {
            data.push(
              {
                id:   type,
                text: type
              }
            );
          }
        );
        callback(data);
      }
    }
  }
);

$("input#tags").select2(
  {
    multiple:           true,
    placeholder:        "Tags",
    minimumInputLength: 2,
    openOnEnter:        false,
    ajax:               {
      url:         "/search/tags",
      dataType:    'json',
      quietMillis: 200,
      data:        function (term, page)
      {
        return {
          page:   page,
          search: term
        };
      },
      results:     function (data, page)
      {
        return {
          results: data.results,
          more:    (page < data.lastPage)
        };
      }
    },
    initSelection:      function (element, callback)
    {
      var ids = $(element).val();
      if (ids !== "")
      {
        $.ajax(
          "/search/tags-init", {
            dataType: "json",
            data:     {
              ids: ids
            }
          }
        ).done(
          function (data)
          {
            callback(data);
          }
        );
      }
    }
  }
);

$("input#authors").select2(
  {
    multiple:           true,
    placeholder:        "Authors",
    minimumInputLength: 2,
    openOnEnter:        false,
    ajax:               {
      url:         "/search/authors",
      dataType:    'json',
      quietMillis: 200,
      data:        function (term, page)
      {
        return {
          page:   page,
          search: term
        };
      },
      results:     function (data, page)
      {
        return {
          results: data.results,
          more:    (page < data.lastPage)
        };
      }
    },
    initSelection:      function (element, callback)
    {
      var ids = $(element).val();
      if (ids !== "")
      {
        $.ajax(
          "/search/authors-init", {
            dataType: "json",
            data:     {
              ids: ids
            }
          }
        ).done(
          function (data)
          {
            callback(data);
          }
        );
      }
    }
  }
);

$("#results").highlight(
  'search', {
    caseSensitive: false,
    wordsOnly:     false
  }
);

(function (i, s, o, g, r, a, m)
{
  i['GoogleAnalyticsObject'] = r;
  i[r] = i[r] || function ()
  {
    (i[r].q = i[r].q || []).push(arguments)
  }, i[r].l = 1 * new Date();
  a = s.createElement(o),
    m = s.getElementsByTagName(o)[0];
  a.async = 1;
  a.src = g;
  m.parentNode.insertBefore(a, m)
})(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

ga('create', 'UA-125104-29', 'auto');
ga('send', 'pageview');
